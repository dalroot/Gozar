<?php

namespace Modules\TelegramBot\Services\Secretary;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    protected string $botToken;
    protected string|int|null $adminChatId;

    public function __construct()
    {
        $this->botToken = env('SECRETARY_BOT_TOKEN', '8450449696:AAGfdyIZg4FnLlpKeuDo6D8imdi1bFKo7eQ');
        
        // شناسه ادمین اصلی
        $settings = Setting::all()->pluck('value', 'key');
        $this->adminChatId = $settings->get('telegram_admin_id', '8629398713');
    }

    /**
     * ارسال پیام مستقیم به ادمین در ربات
     */
    public function sendNotificationToAdmin(string $message, ?array $keyboard = null): bool
    {
        if (empty($this->adminChatId) || empty($this->botToken)) {
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $payload = [
                'chat_id' => $this->adminChatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ];

            if (!empty($keyboard)) {
                $payload['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
            }

            $response = Http::timeout(10)->post($url, $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to send admin notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * اعلان درخواست گفتگوی مستقیم با پشتیبان انسانی
     */
    public function notifyHumanSupportRequested(int|string $customerChatId, string $fullName, ?string $username = null, ?string $lastMessage = null): bool
    {
        $userTag = $username ? "@{$username}" : "بدون یوزرنیم";
        $text = "🚨 <b>درخواست پشتیبانی انسانی در پی‌وی</b>\n\n" .
                "👤 <b>مشتری:</b> {$fullName} ({$userTag})\n" .
                "🆔 <b>شناسه تلگرام:</b> <code>{$customerChatId}</code>\n";

        if (!empty($lastMessage)) {
            $text .= "💬 <b>آخرین پیام:</b> <i>" . htmlspecialchars($lastMessage) . "</i>\n";
        }

        $text .= "\n⏱ <i>ربات در این چت به حالت سکوت رفت تا پاسخ دهید.</i>";

        $keyboard = [
            [
                ['text' => '💬 ورود به چت مشتری در پی‌وی', 'url' => "tg://user?id={$customerChatId}"]
            ]
        ];

        return $this->sendNotificationToAdmin($text, $keyboard);
    }

    /**
     * اعلان ارسال فیش یا پیام مهم
     */
    public function notifyReceiptSent(int|string $customerChatId, string $fullName, ?string $username = null): bool
    {
        $userTag = $username ? "@{$username}" : "بدون یوزرنیم";
        $text = "💳 <b>اعلان ارسال فیش واریزی / ثبت پرداخت</b>\n\n" .
                "👤 <b>مشتری:</b> {$fullName} ({$userTag})\n" .
                "🆔 <b>شناسه تلگرام:</b> <code>{$customerChatId}</code>\n" .
                "📸 لطفاً جهت بررسی فیش و صدور کانفیگ، پی‌وی مشتری را چک کنید.";

        $keyboard = [
            [
                ['text' => '💬 باز کردن پی‌وی مشتری', 'url' => "tg://user?id={$customerChatId}"]
            ]
        ];

        return $this->sendNotificationToAdmin($text, $keyboard);
    }
}
