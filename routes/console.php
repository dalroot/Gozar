<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Artisan::command('vpnmarket:send-daily-report', function () {
    try {
        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        $logChannelId = $settings->get('telegram_log_channel_id');
        $botToken = $settings->get('telegram_bot_token');

        if (!$logChannelId || !$botToken) {
            $this->error('Telegram log channel ID or bot token is not configured.');
            return;
        }

        // Stats queries
        $newUsersCount = \App\Models\User::whereDate('created_at', today())->count();

        // اکانت‌های تست رایگان امروز
        $trialOrdersCount = \App\Models\Order::whereDate('created_at', today())
            ->where(function($q) {
                $q->where('payment_method', 'trial')
                  ->orWhere('amount', 0);
            })->count();

        // سفارش‌های نقدی موفق (جدا از تست‌های رایگان)
        $paidOrders = \App\Models\Order::whereDate('updated_at', today())
            ->where('status', 'paid')
            ->where('payment_method', '!=', 'trial')
            ->where('amount', '>', 0)
            ->get();

        $paidOrdersCount = $paidOrders->count();
        $totalSales = $paidOrders->sum('amount');

        $walletCharges = $paidOrders->whereNull('plan_id');
        $walletChargesCount = $walletCharges->count();
        $walletChargesSum = $walletCharges->sum('amount');

        $planPurchases = $paidOrders->whereNotNull('plan_id');
        $planPurchasesCount = $planPurchases->count();
        $planPurchasesSum = $planPurchases->sum('amount');

        $newTicketsCount = 0;
        if (class_exists(\Modules\Ticketing\Models\Ticket::class)) {
            $newTicketsCount = \Modules\Ticketing\Models\Ticket::whereDate('created_at', today())->count();
        }

        $dateStr = now()->format('Y/m/d');

        $msg = "📊 <b>گزارش آماری روزانه ربات</b> (تاریخ: {$dateStr})\n\n";
        $msg .= "👤 <b>کاربران جدید امروز:</b> <code>{$newUsersCount}</code> نفر\n";
        $msg .= "🧪 <b>اکانت‌های تست رایگان:</b> <code>{$trialOrdersCount}</code> عدد\n";
        $msg .= "🛒 <b>کل سفارشات نقدی موفق:</b> <code>{$paidOrdersCount}</code> عدد\n";
        $msg .= "💰 <b>مجموع فروش نقدی:</b> <code>" . number_format($totalSales) . "</code> تومان\n\n";
        $msg .= "───────────────\n";
        $msg .= "💳 <b>شارژ کیف پول:</b> <code>{$walletChargesCount}</code> عدد (جمعاً <code>" . number_format($walletChargesSum) . "</code> تومان)\n";
        $msg .= "📦 <b>خرید/تمدید سرویس:</b> <code>{$planPurchasesCount}</code> عدد (جمعاً <code>" . number_format($planPurchasesSum) . "</code> تومان)\n";
        $msg .= "👨🏻‍💻 <b>تیکت‌های جدید پشتیبانی:</b> <code>{$newTicketsCount}</code> عدد\n";

        \Telegram\Bot\Laravel\Facades\Telegram::setAccessToken($botToken);
        \Telegram\Bot\Laravel\Facades\Telegram::sendMessage([
            'chat_id' => $logChannelId,
            'text' => $msg,
            'parse_mode' => 'HTML',
        ]);

        $this->info('Daily report sent successfully!');
    } catch (\Exception $e) {
        $this->error('Failed to send daily report: ' . $e->getMessage());
        \Log::error('Failed to send daily report: ' . $e->getMessage());
    }
})->purpose('Send daily activity report to Telegram log channel');

Schedule::command('vpnmarket:send-daily-report')->dailyAt('23:59');
Schedule::command('vpnmarket:send-backup')->hourly();
