<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Artisan::command('vpnmarket:send-daily-report {--dry-run : Print to console instead of sending to Telegram}', function () {
    try {
        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        $logChannelId = $settings->get('telegram_log_channel_id');
        $botToken = $settings->get('telegram_bot_token');

        $isDryRun = (bool) $this->option('dry-run');

        if (!$isDryRun && (!$logChannelId || !$botToken)) {
            $this->error('Telegram log channel ID or bot token is not configured.');
            return;
        }

        $nowTehran = now('Asia/Tehran');
        $startOfDay = $nowTehran->copy()->startOfDay()->setTimezone('UTC');
        $endOfDay = $nowTehran->copy()->endOfDay()->setTimezone('UTC');
        $hour = (int) $nowTehran->format('H');
        $reportTypeTitle = ($hour >= 11 && $hour <= 16) ? 'گزارش آماری نیم‌روز' : 'گزارش آماری پایانی روز';

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

        $formatBytes = function ($bytes, $precision = 2) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max(0, (float) $bytes);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min((int)$pow, count($units) - 1);
            $bytes /= pow(1024, $pow);
            return round($bytes, $precision) . ' ' . $units[$pow];
        };

        // --- استعلام داده‌ها از پنل پاسارگاد ---
        $pasargadService = new \App\Services\PasargadService(
            $settings->get('pasargad_host'),
            $settings->get('pasargad_sudo_username'),
            $settings->get('pasargad_sudo_password'),
            $settings->get('pasargad_node_hostname')
        );

        $systemStats = $pasargadService->getSystemStats();
        $nodes = $pasargadService->getNodes();

        // بازیابی اسنپ‌شات قبلی جهت محاسبه دلتای مصرف
        $prevSnapshotRaw = \App\Models\Setting::where('key', 'pasargad_traffic_snapshot')->value('value');
        $prevSnapshot = $prevSnapshotRaw ? json_decode($prevSnapshotRaw, true) : null;
        $currentSnapshot = [
            'timestamp' => now()->timestamp,
            'nodes' => [],
        ];

        $nodesMsg = "";
        $totalPeriodTraffic = 0;

        if (!empty($nodes)) {
            foreach ($nodes as $node) {
                $nodeId = $node['id'] ?? 0;
                $nodeName = $node['name'] ?? "نود {$nodeId}";
                $status = ($node['status'] ?? '') === 'connected' ? '🟢 متصل' : '🔴 قطع';

                $currentUp = (float) ($node['uplink'] ?? 0);
                $currentDown = (float) ($node['downlink'] ?? 0);
                $currentTotal = $currentUp + $currentDown;

                $currentSnapshot['nodes'][$nodeId] = [
                    'uplink' => $currentUp,
                    'downlink' => $currentDown,
                ];

                // تشخیص پرچم کشور
                $flag = '🌐';
                if (stripos($nodeName, 'UK') !== false || stripos($nodeName, 'انگلستان') !== false) {
                    $flag = '🇬🇧';
                } elseif (stripos($nodeName, 'NL') !== false || stripos($nodeName, 'هلند') !== false) {
                    $flag = '🇳🇱';
                } elseif (stripos($nodeName, 'DE') !== false || stripos($nodeName, 'آلمان') !== false) {
                    $flag = '🇩🇪';
                }

                if ($prevSnapshot && isset($prevSnapshot['nodes'][$nodeId])) {
                    $prevUp = (float) ($prevSnapshot['nodes'][$nodeId]['uplink'] ?? 0);
                    $prevDown = (float) ($prevSnapshot['nodes'][$nodeId]['downlink'] ?? 0);

                    // اگر نود ریست شده باشد، اختلاف منفی نشود
                    $diffUp = $currentUp >= $prevUp ? ($currentUp - $prevUp) : $currentUp;
                    $diffDown = $currentDown >= $prevDown ? ($currentDown - $prevDown) : $currentDown;
                    $diffTotal = $diffUp + $diffDown;
                    $totalPeriodTraffic += $diffTotal;

                    $nodesMsg .= "{$flag} <b>{$nodeName}:</b> {$status} | <code>" . $formatBytes($diffTotal) . "</code> مصرف (⬇️ " . $formatBytes($diffDown, 1) . " | ⬆️ " . $formatBytes($diffUp, 1) . ")\n";
                } else {
                    $totalPeriodTraffic += $currentTotal;
                    $nodesMsg .= "{$flag} <b>{$nodeName}:</b> {$status} | کل ترافیک: <code>" . $formatBytes($currentTotal) . "</code>\n";
                }
            }

            // ذخیره اسنپ‌شات جدید برای دوره بعد
            \App\Models\Setting::updateOrCreate(
                ['key' => 'pasargad_traffic_snapshot'],
                ['value' => json_encode($currentSnapshot)]
            );
        }

        $dateStr = $nowTehran->format('Y/m/d');
        $timeStr = $nowTehran->format('H:i');

        $msg = "📊 <b>{$reportTypeTitle} ربات و شبکه</b>\n";
        $msg .= "🗓 <b>تاریخ:</b> <code>{$dateStr}</code> | ⏰ <b>ساعت:</b> <code>{$timeStr}</code>\n\n";

        if (!empty($nodesMsg)) {
            $msg .= "🖥 <b>وضعیت نودها و ترافیک (PasarGuard):</b>\n";
            $msg .= $nodesMsg;
            if ($prevSnapshot) {
                $msg .= "⚡️ <b>مجموع مصرف شبکه در این بازه:</b> <code>" . $formatBytes($totalPeriodTraffic) . "</code>\n\n";
            } else {
                $msg .= "💡 <i>(اسنپ‌شات مبنای ترافیک ذخیره شد؛ از گزارش بعدی میزان مصرف دقیق محاسبه می‌شود)</i>\n\n";
            }
        }

        if ($systemStats) {
            $onlineUsers = (int) ($systemStats['online_users'] ?? 0);
            $activeUsers = (int) ($systemStats['active_users'] ?? 0);
            $limitedUsers = (int) ($systemStats['limited_users'] ?? 0);
            $expiredUsers = (int) ($systemStats['expired_users'] ?? 0);
            $memUsed = (float) ($systemStats['mem_used'] ?? 0);
            $memTotal = max(1, (float) ($systemStats['mem_total'] ?? 1));
            $memPercent = round(($memUsed / $memTotal) * 100);
            $diskUsed = (float) ($systemStats['disk_used'] ?? 0);
            $diskTotal = max(1, (float) ($systemStats['disk_total'] ?? 1));
            $diskPercent = round(($diskUsed / $diskTotal) * 100);
            $cpuUsage = round((float) ($systemStats['cpu_usage'] ?? 0), 1);

            $msg .= "👥 <b>وضعیت کاربران و سلامت سرور:</b>\n";
            $msg .= "🟢 <b>کاربران آنلاین هم‌اکنون:</b> <code>{$onlineUsers}</code> نفر\n";
            $msg .= "📦 <b>اکانت‌های فعال پنل:</b> <code>{$activeUsers}</code> عدد\n";
            if ($limitedUsers > 0) {
                $msg .= "⚠️ <b>حجم تمام‌شده (فرصت تمدید):</b> <code>{$limitedUsers}</code> عدد\n";
            }
            if ($expiredUsers > 0) {
                $msg .= "⏳ <b>منقضی‌شده زمانی:</b> <code>{$expiredUsers}</code> عدد\n";
            }
            $msg .= "💾 <b>منابع سرور:</b> رم <code>{$memPercent}%</code> | دیسک <code>{$diskPercent}%</code> | پردازنده <code>{$cpuUsage}%</code>\n\n";
        }

        $msg .= "───────────────\n";
        $msg .= "📈 <b>آمار فروش و پشتیبانی ربات:</b>\n";
        $msg .= "👤 <b>کاربران جدید امروز:</b> <code>{$newUsersCount}</code> نفر\n";
        $msg .= "🧪 <b>اکانت‌های تست رایگان:</b> <code>{$trialOrdersCount}</code> عدد\n";
        $msg .= "🛒 <b>کل سفارشات نقدی موفق:</b> <code>{$paidOrdersCount}</code> عدد\n";
        $msg .= "💰 <b>مجموع فروش نقدی:</b> <code>" . number_format($totalSales) . "</code> تومان\n";
        $msg .= "💳 <b>شارژ کیف پول:</b> <code>{$walletChargesCount}</code> عدد (<code>" . number_format($walletChargesSum) . "</code> تومان)\n";
        $msg .= "📦 <b>خرید/تمدید سرویس:</b> <code>{$planPurchasesCount}</code> عدد (<code>" . number_format($planPurchasesSum) . "</code> تومان)\n";
        $msg .= "👨🏻‍💻 <b>تیکت‌های جدید پشتیبانی:</b> <code>{$newTicketsCount}</code> عدد\n";

        if ($isDryRun) {
            $this->line($msg);
            $this->info('Dry-run completed successfully!');
            return;
        }

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

Schedule::command('vpnmarket:send-daily-report')->timezone('Asia/Tehran')->dailyAt('14:00');
Schedule::command('vpnmarket:send-daily-report')->timezone('Asia/Tehran')->dailyAt('23:59');
Schedule::command('vpnmarket:send-backup')->hourly();
Schedule::command('vpnmarket:monitor-trials')->everyFifteenMinutes();
