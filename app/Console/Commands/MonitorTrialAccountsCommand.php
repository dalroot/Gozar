<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Setting;
use App\Services\XUIService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class MonitorTrialAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vpnmarket:monitor-trials {--dry-run : Check status without sending messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor trial accounts lifecycle and send smart notifications for 80% usage, 100% exhaustion, and 12h idle.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $this->info("Starting trial accounts monitoring" . ($isDryRun ? " (DRY RUN)" : "") . "...");

        $settings = Setting::all()->pluck('value', 'key');
        $botToken = $settings->get('telegram_bot_token');
        $xuiHost = $settings->get('xui_host');
        $xuiUser = $settings->get('xui_user');
        $xuiPass = $settings->get('xui_pass');
        $supportUrl = $settings->get('telegram_support_url', 'https://t.me/RoozanehHelp');

        if (!$botToken || !$xuiHost || !$xuiUser || !$xuiPass) {
            $this->error('Bot token or X-UI credentials are not fully configured.');
            return 1;
        }

        Telegram::setAccessToken(trim($botToken, '"\' '));

        try {
            $xui = new XUIService($xuiHost, $xuiUser, $xuiPass);
            $inbounds = $xui->getInbounds();
        } catch (\Throwable $e) {
            $this->error('Failed to connect to X-UI panel: ' . $e->getMessage());
            Log::error('MonitorTrialAccounts: X-UI connection error: ' . $e->getMessage());
            return 1;
        }

        $clientStatsMap = [];
        foreach ($inbounds as $inbound) {
            foreach (($inbound['clientStats'] ?? []) as $cs) {
                $email = $cs['email'] ?? '';
                if ($email) {
                    $clientStatsMap[$email] = [
                        'up' => (int) ($cs['up'] ?? 0),
                        'down' => (int) ($cs['down'] ?? 0),
                        'total_used' => (int) (($cs['up'] ?? 0) + ($cs['down'] ?? 0)),
                        'enable' => (bool) ($cs['enable'] ?? true),
                        'total_limit' => (int) ($cs['total'] ?? 0),
                    ];
                }
            }
        }

        $trialOrders = Order::where(function ($q) {
            $q->where('payment_method', 'trial')->orWhere('amount', 0);
        })
        ->where('status', 'paid')
        ->whereNotNull('panel_username')
        ->with('user')
        ->get();

        $this->info("Found " . $trialOrders->count() . " trial orders to evaluate.");

        $keyboardBuy = [
            'inline_keyboard' => [
                [
                    ['text' => '🛍 مشاهده پلن‌ها و ثبت سفارش', 'callback_data' => '/plans']
                ],
                [
                    ['text' => '👨‍💻 پشتیبانی و راهنمایی', 'url' => $supportUrl]
                ]
            ]
        ];

        $keyboardIdle = [
            'inline_keyboard' => [
                [
                    ['text' => '📚 راهنمای آموزش اتصال', 'callback_data' => '/tutorials']
                ],
                [
                    ['text' => '👨‍💻 پیام به پشتیبان انسانی', 'url' => $supportUrl]
                ]
            ]
        ];

        $sentCount = 0;

        foreach ($trialOrders as $order) {
            $user = $order->user;
            if (!$user || !$user->telegram_chat_id) {
                continue;
            }

            // اگر کاربر قبلاً پلن نقدی خریده باشد، پیام ارسال نمی‌شود
            $hasPurchased = Order::where('user_id', $user->id)
                ->where('status', 'paid')
                ->where('amount', '>', 0)
                ->exists();

            if ($hasPurchased) {
                continue;
            }

            // استثنا کردن اکانت تست همکار/ادمین
            if ($user->telegram_chat_id === '748300418') {
                continue;
            }

            $panelUsername = $order->panel_username;
            $stats = $clientStatsMap[$panelUsername] ?? null;

            $usedBytes = $stats ? $stats['total_used'] : 0;
            $totalLimit = $stats && $stats['total_limit'] > 0 ? $stats['total_limit'] : (1024 * 1024 * 1024);
            $isEnabled = $stats ? $stats['enable'] : true;

            $usedMB = round($usedBytes / (1024 * 1024), 1);
            $totalMB = round($totalLimit / (1024 * 1024), 1);
            $remainingMB = max(0, round(($totalLimit - $usedBytes) / (1024 * 1024), 1));
            $userName = htmlspecialchars($user->name ?: 'کاربر گرامی');

            // سناریو ۲: اتمام کامل حجم یا قطع شدن در پنل
            if ((!$isEnabled || $usedBytes >= $totalLimit) && !$order->trial_notified_100_at) {
                $text = "⏳ <b>حجم اکانت تست هدیه شما به پایان رسید</b>\n\n" .
                        "سلام {$userName} عزیز 🌸\n" .
                        "از اینکه کیفیت و پایداری شبکه <b>روزنه</b> را امتحان کردید سپاسگزاریم!\n\n" .
                        "سرویس تست شما هم‌اکنون منقضی شده است. برای دسترسی آزاد، پایدار و بدون قطعی، می‌توانید همین حالا اولین اشتراک اختصاصی خود را با <b>تخفیف ۱۰ درصدی ویژه</b> تهیه کنید:\n\n" .
                        "🎁 <b>کد تخفیف ۱۰٪:</b> <code>rayan</code>\n\n" .
                        "👇 <i>جهت انتخاب و فعال‌سازی آنی اشتراک:</i>";

                $this->info("[100% Exhausted] Triggered for {$user->name} ({$user->telegram_chat_id})");

                if (!$isDryRun) {
                    try {
                        Telegram::sendMessage([
                            'chat_id' => $user->telegram_chat_id,
                            'text' => $text,
                            'parse_mode' => 'HTML',
                            'reply_markup' => json_encode($keyboardBuy)
                        ]);
                        $order->update([
                            'trial_notified_100_at' => now(),
                            'trial_notified_80_at' => $order->trial_notified_80_at ?: now(),
                        ]);
                        $sentCount++;
                    } catch (\Throwable $e) {
                        Log::warning("Failed to send 100% trial alert to {$user->telegram_chat_id}: " . $e->getMessage());
                    }
                    usleep(300000);
                }
                continue;
            }

            // سناریو ۱: مصرف ۸۰ درصد حجم تست
            if ($totalLimit > 0 && $usedBytes >= (0.8 * $totalLimit) && !$order->trial_notified_80_at && !$order->trial_notified_100_at) {
                $text = "⚠️ <b>همراه گرامی روزنه؛ مصرف ۸۰٪ حجم تست</b>\n\n" .
                        "سلام {$userName} عزیز وقتتون بخیر 🌸\n\n" .
                        "📊 <b>وضعیت حساب تست شما:</b>\n" .
                        "▫️ <b>حجم مصرف‌شده:</b> <code>{$usedMB} مگابایت</code>\n" .
                        "▫️ <b>حجم باقیمانده:</b> <code>{$remainingMB} مگابایت</code> (تنها ۲۰٪ باقیمانده)\n\n" .
                        "جهت قدردانی از همراهی شما، یک <b>کد تخفیف ۱۰ درصدی اختصاصی</b> فعال کردیم تا بتوانید پیش از قطعی، سرویس نامحدود و پرسرعت خود را با قیمت ویژه فعال کنید:\n\n" .
                        "🎁 <b>کد تخفیف ۱۰٪:</b> <code>rayan</code>\n\n" .
                        "👇 <i>جهت مشاهده پلن‌ها و خرید با تخفیف، از دکمه‌های زیر استفاده کنید:</i>";

                $this->info("[80% Warning] Triggered for {$user->name} ({$user->telegram_chat_id}) - Used: {$usedMB} MB");

                if (!$isDryRun) {
                    try {
                        Telegram::sendMessage([
                            'chat_id' => $user->telegram_chat_id,
                            'text' => $text,
                            'parse_mode' => 'HTML',
                            'reply_markup' => json_encode($keyboardBuy)
                        ]);
                        $order->update(['trial_notified_80_at' => now()]);
                        $sentCount++;
                    } catch (\Throwable $e) {
                        Log::warning("Failed to send 80% trial alert to {$user->telegram_chat_id}: " . $e->getMessage());
                    }
                    usleep(300000);
                }
                continue;
            }

            // سناریو ۳: عدم اتصال پس از ۱۲ ساعت (مصرف صفر بایت)
            if ($usedBytes == 0 && $order->created_at <= now()->subHours(12) && !$order->trial_notified_idle_at && !$order->trial_notified_80_at && !$order->trial_notified_100_at) {
                $text = "👋 <b>همراه گرامی روزنه، وقتتون بخیر</b>\n\n" .
                        "سلام {$userName} عزیز 🌸\n" .
                        "متوجه شدیم بیش از ۱۲ ساعت از دریافت لینک تست اختصاصی شما گذشته، اما هنوز اتصالی به شبکه برقرار نکرده‌اید!\n\n" .
                        "آیا در کپی کردن لینک، راه‌اندازی یا نصب نرم‌افزار اتصال (مانند V2rayNG یا Streisand) به راهنمایی نیاز دارید؟\n" .
                        "تیم پشتیبانی ما آماده است تا گام‌به‌گام شما را راهنمایی کند تا بدون دغدغه متصل شوید ✨";

                $this->info("[12h Idle Rescue] Triggered for {$user->name} ({$user->telegram_chat_id})");

                if (!$isDryRun) {
                    try {
                        Telegram::sendMessage([
                            'chat_id' => $user->telegram_chat_id,
                            'text' => $text,
                            'parse_mode' => 'HTML',
                            'reply_markup' => json_encode($keyboardIdle)
                        ]);
                        $order->update(['trial_notified_idle_at' => now()]);
                        $sentCount++;
                    } catch (\Throwable $e) {
                        Log::warning("Failed to send 12h idle trial alert to {$user->telegram_chat_id}: " . $e->getMessage());
                    }
                    usleep(300000);
                }
                continue;
            }
        }

        $this->info("Trial monitoring completed. Sent {$sentCount} notifications.");
        return 0;
    }
}
