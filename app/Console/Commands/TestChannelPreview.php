<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Setting;
use Telegram\Bot\Laravel\Facades\Telegram;
use Modules\TelegramBot\Http\Controllers\WebhookController;

class TestChannelPreview extends Command
{
    protected $signature = 'gozar:test-channel-preview';
    protected $description = 'Send preview messages to log and receipt channels using real bot templates';

    public function handle()
    {
        $user = User::where('telegram_chat_id', '8629398713')->first() ?? User::first();
        if (!$user) {
            $this->error('User not found');
            return 1;
        }

        $controller = app(WebhookController::class);

        // 1. پیام به کانال لاگ‌ها (قالب اکانت تست رایگان)
        $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name ?: 'کاربر') . "</a>";
        $usernameLine = !empty($user->username) ? "💬 <b>نام کاربری:</b> @" . ltrim($user->username, '@') . "\n" : "";
        $durationText = "24 ساعت (1 روز)";
        $volumeMB = 3072;
        $uniqueUsername = "test8";
        $locationFlag = "🇬🇧🇩🇪🇳🇱";
        $locationName = "🇬🇧 انگلیس، 🇩🇪 آلمان و 🇳🇱 هلند";

        $logMessage = "🧪 <b>اکانت تست رایگان فعال شد</b>\n\n" .
            "👤 <b>نام کاربر:</b> {$userLink}\n" .
            "🆔 <b>شناسه تلگرام:</b> <code>{$user->telegram_chat_id}</code>\n" .
            $usernameLine .
            "🔢 <b>کد کاربری در سیستم:</b> <code>{$user->id}</code>\n" .
            "👤 <b>نام اکانت در پنل:</b> <code>{$uniqueUsername}</code>\n" .
            "📊 <b>حجم:</b> {$volumeMB} مگابایت ({$durationText})\n" .
            "🌐 <b>سرور:</b> {$locationFlag} {$locationName}\n" .
            "⏰ <b>زمان فعال‌سازی:</b> " . now()->format('Y-m-d H:i:s');

        $logOk = $controller->sendToLogChannel($logMessage, 'HTML', $controller->buildUserChatButton($user));
        $this->info("Log Channel Result: " . ($logOk ? 'SUCCESS' : 'FAILED'));

        // 2. پیام به کانال فیش‌ها (قالب فیش جدید در انتظار تایید)
        $settings = Setting::all()->pluck('value', 'key');
        $receiptChannelId = $settings->get('telegram_receipt_channel_id');
        $botToken = $settings->get('telegram_bot_token');

        if ($receiptChannelId && $botToken) {
            Telegram::setAccessToken($botToken);

            $orderType = "خرید سرویس (⚡️ پلن استاندارد ۴۵ گیگ)";
            $orderId = 15;
            $adminCaption = "🧾 <b>رسید پرداخت جدید (سفارش #{$orderId})</b>\n\n" .
                "👤 <b>نام کاربر:</b> {$userLink}\n" .
                "🆔 <b>شناسه تلگرام:</b> <code>{$user->telegram_chat_id}</code>\n" .
                (!empty($user->username) ? "💬 <b>نام کاربری:</b> @" . ltrim($user->username, '@') . "\n" : "") .
                "🔢 <b>کد کاربری سیستم:</b> <code>{$user->id}</code>\n" .
                "💵 <b>مبلغ فیش:</b> <code>۲۹۵,۰۰۰ تومان</code>\n" .
                "📦 <b>نوع سفارش:</b> {$orderType}\n" .
                "⏰ <b>زمان ثبت:</b> " . now()->format('Y-m-d H:i:s') . "\n\n" .
                "👇 <i>جهت بررسی و تایید فیش از دکمه‌های زیر استفاده کنید:</i>";

            $keyboardRows = [
                [
                    ['text' => '✅ تایید پرداخت', 'callback_data' => "admin_approve_order_{$orderId}"],
                    ['text' => '❌ رد پرداخت', 'callback_data' => "admin_reject_order_{$orderId}"]
                ]
            ];
            $pvBtn = $controller->getUserChatInlineButton($user);
            if ($pvBtn) {
                $keyboardRows[] = [$pvBtn];
            }

            try {
                Telegram::sendMessage([
                    'chat_id' => $receiptChannelId,
                    'text' => $adminCaption,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode(['inline_keyboard' => $keyboardRows]),
                    'disable_web_page_preview' => true,
                ]);
                $this->info("Receipt Channel Result: SUCCESS");
            } catch (\Exception $e) {
                $this->error("Receipt Channel Error: " . $e->getMessage());
            }
        }

        return 0;
    }
}
