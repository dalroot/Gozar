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
        $paidOrders = \App\Models\Order::whereDate('updated_at', today())->where('status', 'paid')->get();
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

        $esc = function (string $text): string {
            $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
            return str_replace($chars, array_map(fn($char) => '\\' . $char, $chars), $text);
        };

        $msg = "📊 *گزارش آماری روزانه ربات* \\(تاریخ: " . $esc(now()->format('Y/m/d')) . "\\)\n\n";
        $msg .= "👤 *کاربران جدید:* `{$newUsersCount}` نفر\n";
        $msg .= "🛒 *کل سفارشات موفق امروز:* `{$paidOrdersCount}` عدد\n";
        $msg .= "💰 *مجموع فروش امروز:* `" . number_format($totalSales) . "` تومان\n\n";
        $msg .= "───────────────\n";
        $msg .= "💳 *شارژ کیف پول:* `{$walletChargesCount}` عدد \\(جمعاً `" . number_format($walletChargesSum) . "` تومان\\)\n";
        $msg .= "📦 *خرید/تمدید سرویس:* `{$planPurchasesCount}` عدد \\(جمعاً `" . number_format($planPurchasesSum) . "` تومان\\)\n";
        $msg .= "👨🏻‍💻 *تیکت‌های جدید پشتیبانی:* `{$newTicketsCount}` عدد\n";

        \Telegram\Bot\Laravel\Facades\Telegram::setAccessToken($botToken);
        \Telegram\Bot\Laravel\Facades\Telegram::sendMessage([
            'chat_id' => $logChannelId,
            'text' => $msg,
            'parse_mode' => 'MarkdownV2',
        ]);

        $this->info('Daily report sent successfully!');
    } catch (\Exception $e) {
        $this->error('Failed to send daily report: ' . $e->getMessage());
        \Log::error('Failed to send daily report: ' . $e->getMessage());
    }
})->purpose('Send daily activity report to Telegram log channel');

Schedule::command('vpnmarket:send-daily-report')->dailyAt('23:59');
Schedule::command('vpnmarket:send-backup')->hourly();
