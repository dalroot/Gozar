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

        $nowTehran = now('Asia/Tehran');
        $startOfDay = $nowTehran->copy()->startOfDay()->setTimezone('UTC');
        $endOfDay = $nowTehran->copy()->endOfDay()->setTimezone('UTC');

        // Stats queries
        $newUsersCount = \App\Models\User::whereBetween('created_at', [$startOfDay, $endOfDay])->count();

        // اکانت‌های تست رایگان امروز
        $trialOrdersCount = \App\Models\Order::whereBetween('created_at', [$startOfDay, $endOfDay])
            ->where(function($q) {
                $q->where('payment_method', 'trial')
                  ->orWhere('amount', 0);
            })->count();

        // سفارش‌های نقدی موفق (جدا از تست‌های رایگان)
        $paidOrders = \App\Models\Order::whereBetween('updated_at', [$startOfDay, $endOfDay])
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
            $newTicketsCount = \Modules\Ticketing\Models\Ticket::whereBetween('created_at', [$startOfDay, $endOfDay])->count();
        }

        $dateStr = $nowTehran->format('Y/m/d');

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

Artisan::command('vpnmarket:send-campaign-report', function () {
    try {
        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        $logChannelId = $settings->get('telegram_log_channel_id');
        $botToken = $settings->get('telegram_bot_token');

        if (!$logChannelId || !$botToken) {
            $this->error('Telegram log channel ID or bot token is not configured.');
            return;
        }

        $campaignStart = \Carbon\Carbon::parse('2026-10-02 00:00:00', 'Asia/Tehran')->setTimezone('UTC');
        $totalUsers = \App\Models\User::where('created_at', '>=', $campaignStart)->count();
        $totalTrials = \App\Models\Order::where('created_at', '>=', $campaignStart)
            ->where(function($q) {
                $q->where('payment_method', 'trial')->orWhere('amount', 0);
            })->count();

        // سفارش‌های واقعی نقدی تایید شده
        $paidOrders = \App\Models\Order::where('status', 'paid')
            ->where('created_at', '>=', $campaignStart)
            ->where(function($q) {
                $q->where('payment_method', '!=', 'trial')->orWhereNull('payment_method');
            })
            ->where('amount', '>', 0)
            ->with('user', 'plan')
            ->get();

        // گروه‌بندی دریافتی‌های نقدی (جلوگیری از دوبار شمردن کیف پول)
        $cashOrders = $paidOrders->filter(function($o) {
            return $o->payment_method !== 'wallet';
        });

        $totalCash = $cashOrders->sum('amount');
        $uniqueCustomersCount = $paidOrders->pluck('user_id')->unique()->count();

        $msg = "📈 <b>گزارش جامع فروش و بازخورد کمپین (تبلیغ احسن کریمی)</b>\n\n";
        $msg .= "🗓 <b>بازه زمانی:</b> از ۱۱ مهر ۱۴۰۵ (شروع کمپین) تا هم‌اکنون\n\n";
        $msg .= "👥 <b>کاربران جدید ربات:</b> <code>{$totalUsers}</code> نفر\n";
        $msg .= "🧪 <b>اکانت‌های تست رایگان:</b> <code>{$totalTrials}</code> عدد\n";
        $msg .= "🛍 <b>تعداد خریداران نهایی:</b> <code>{$uniqueCustomersCount}</code> نفر\n";
        $msg .= "💰 <b>مجموع کل فروش نقدی:</b> <code>" . number_format($totalCash) . "</code> تومان\n\n";
        $msg .= "───────────────\n";
        $msg .= "🧾 <b>ریز واریزی‌های نقدی تایید شده:</b>\n";

        $counter = 1;
        foreach ($cashOrders as $co) {
            $u = $co->user;
            $nameStr = htmlspecialchars($u ? $u->name : 'کاربر');
            $userLink = ($u && $u->telegram_chat_id) 
                ? "<a href=\"tg://user?id={$u->telegram_chat_id}\">{$nameStr}</a>" 
                : $nameStr;
            $typeStr = $co->plan_id ? ($co->plan ? $co->plan->name : 'خرید پلن') : 'شارژ کیف پول';
            $msg .= "{$counter}️⃣ {$userLink}: <code>" . number_format($co->amount) . "</code> تومان (سفارش #{$co->id} - {$typeStr})\n";
            $counter++;
        }

        $msg .= "\n💡 <i>نکته: خریدهای انجام‌شده با موجودی کیف پول جهت جلوگیری از دوبار شمردن، در سطر شارژ کیف پول همان کاربر لحاظ شده‌اند.</i>\n";
        $msg .= "⏰ <b>زمان گزارش:</b> " . now('Asia/Tehran')->format('Y/m/d — H:i:s');

        \Telegram\Bot\Laravel\Facades\Telegram::setAccessToken($botToken);
        \Telegram\Bot\Laravel\Facades\Telegram::sendMessage([
            'chat_id' => $logChannelId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        $this->info('Campaign report sent successfully!');
    } catch (\Exception $e) {
        $this->error('Failed to send campaign report: ' . $e->getMessage());
    }
})->purpose('Send campaign activity report to Telegram log channel');

Schedule::command('vpnmarket:send-daily-report')->dailyAt('23:59');
Schedule::command('vpnmarket:send-backup')->hourly();
