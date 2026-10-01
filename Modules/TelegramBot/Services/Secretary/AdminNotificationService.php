<?php

namespace Modules\TelegramBot\Services\Secretary;

use App\Models\Setting;
use App\Models\User;
use Modules\Ticketing\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    protected string $botToken;
    protected string $salesBotToken;
    protected string|int|null $adminChatId;
    protected string|int|null $logChannelId;

    public function __construct()
    {
        $this->botToken = (string) env('SECRETARY_BOT_TOKEN', '');
        $this->salesBotToken = (string) env('TELEGRAM_BOT_TOKEN', '');
        
        // شناسه ادمین اصلی و کانال گزارش‌ها
        $settings = Setting::all()->pluck('value', 'key');
        $this->adminChatId = $settings->get('telegram_admin_chat_id') ?: $settings->get('telegram_admin_id', '8629398713');
        $this->logChannelId = $settings->get('telegram_log_channel_id');
    }

    /**
     * ارسال پیام مستقیم به پی‌وی ادمین و هم‌زمان به کانال گزارش‌ها
     */
    public function sendNotificationToAdmin(string $message, ?array $keyboard = null): bool
    {
        $sentSuccessfully = false;

        $payload = [
            'text' => $message,
            'parse_mode' => 'HTML',
        ];
        if (!empty($keyboard)) {
            $payload['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        // ۱. ارسال مستقیم به پی‌وی ادمین
        if (!empty($this->adminChatId) && !empty($this->botToken)) {
            try {
                $payload['chat_id'] = $this->adminChatId;
                $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", $payload);
                if ($response->successful()) {
                    $sentSuccessfully = true;
                } else {
                    Log::error("Failed to send notification to admin PV", [
                        'admin_chat_id' => $this->adminChatId,
                        'status' => $response->status(),
                        'response' => $response->json(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Exception sending notification to admin PV: " . $e->getMessage());
            }
        }

        // ۲. ارسال به کانال اختصاصی گزارش‌ها
        if (!empty($this->logChannelId)) {
            $channelToken = !empty($this->salesBotToken) ? $this->salesBotToken : $this->botToken;
            try {
                $payload['chat_id'] = $this->logChannelId;
                $response = Http::timeout(10)->post("https://api.telegram.org/bot{$channelToken}/sendMessage", $payload);
                if ($response->successful()) {
                    $sentSuccessfully = true;
                } else {
                    Log::error("Failed to send notification to report channel", [
                        'channel_id' => $this->logChannelId,
                        'status' => $response->status(),
                        'response' => $response->json(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Exception sending notification to report channel: " . $e->getMessage());
            }
        }

        return $sentSuccessfully;
    }

    /**
     * اعلان درخواست گفتگوی مستقیم با پشتیبان انسانی
     */
    public function notifyHumanSupportRequested(int|string $customerChatId, string $fullName, ?string $username = null, ?string $lastMessage = null, ?int $ticketId = null, array|string|null $contextSummary = null): bool
    {
        $userTag = $username ? '@' . htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : "بدون یوزرنیم";
        $safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = "🚨 <b>درخواست پشتیبانی انسانی</b>\n\n" .
                "👤 <b>{$safeName}</b> · {$userTag}\n" .
                "🆔 <code>{$customerChatId}</code> (<a href=\"tg://user?id={$customerChatId}\">مشاهده پروفایل</a>)" .
                ($ticketId ? " · 🎫 <code>#{$ticketId}</code>" : '') . "\n";

        if (is_array($contextSummary)) {
            $subject = $this->safe($contextSummary['subject'] ?? 'درخواست گفتگو با پشتیبان');
            $text .= "📌 <b>موضوع:</b> {$subject}\n";
            if (!empty($contextSummary['service_status'])) {
                $text .= "📡 <b>سرویس:</b> " . $this->safe($contextSummary['service_status']) . "\n";
            }
            if (!empty($contextSummary['order'])) {
                $text .= "🧾 <b>سفارش مرتبط:</b> " . $this->safe($contextSummary['order']) . "\n";
            }
            if (!empty($contextSummary['summary'])) {
                $text .= "\n🧭 <b>جمع‌بندی فرایدی</b>\n<blockquote>" . $this->safe($contextSummary['summary']) . "</blockquote>\n";
            }
            if (!empty($contextSummary['last_user_request'])) {
                $text .= "🗣 <b>آخرین درخواست مرتبط:</b> «" . $this->safe($contextSummary['last_user_request']) . "»\n";
            }
        } elseif (!empty($contextSummary)) {
            $text .= "\n🧭 <b>جمع‌بندی فرایدی</b>\n<blockquote>" . $this->safe($contextSummary) . "</blockquote>\n";
        } elseif (!empty($lastMessage)) {
            $text .= "💬 <b>درخواست:</b> " . $this->safe($lastMessage) . "\n";
        }

        $text .= "\n🤖 <i>فرایدی تا پایان پاسخ انسانی در این گفتگو ساکت است.</i>";

        $keyboard = [];
        if (!empty($username)) {
            $keyboard[] = [['text' => '💬 ورود به پی‌وی کاربر', 'url' => "https://t.me/" . ltrim($username, '@')]];
        }
        $keyboard[] = [
            ['text' => '🤖 بازگرداندن به فرایدی', 'callback_data' => "sec_admin_resume_{$customerChatId}"],
            ['text' => '✅ پایان پشتیبانی', 'callback_data' => "sec_admin_resolve_{$customerChatId}"]
        ];

        return $this->sendNotificationToAdmin($text, $keyboard);
    }

    /**
     * اعلان دریافت پیام متفرقه/عمومی که توسط اتوماسیون پاسخ داده نشد
     */
    public function notifyGeneralMessage(int|string $customerChatId, string $fullName, ?string $username = null, string $message = ''): bool
    {
        $userTag = $username ? '@' . htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : "بدون یوزرنیم";
        $safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeMsg = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = "📩 <b>پیام جدید کاربر در پشتیبانی روزنه</b>\n\n" .
                "👤 <b>{$safeName}</b> · {$userTag}\n" .
                "🆔 <code>{$customerChatId}</code> (<a href=\"tg://user?id={$customerChatId}\">مشاهده پروفایل</a>)\n\n" .
                "💬 <b>متن پیام:</b>\n<blockquote>{$safeMsg}</blockquote>\n\n" .
                "ℹ️ <i>به کاربر اعلام شد پیام به پشتیبان انسانی ارجاع شده است.</i>";

        $keyboard = [];
        if (!empty($username)) {
            $keyboard[] = [['text' => '💬 ورود به پی‌وی کاربر', 'url' => "https://t.me/" . ltrim($username, '@')]];
        }
        return $this->sendNotificationToAdmin($text, $keyboard);
    }

    public function isAdminChat(int|string $chatId): bool
    {
        return (string) $this->adminChatId === (string) $chatId;
    }

    public function closeLatestHumanTicket(int|string $customerChatId): ?int
    {
        $user = User::where('telegram_chat_id', (string) $customerChatId)->first();
        if (!$user || !class_exists(Ticket::class)) return null;
        $ticket = Ticket::where('user_id', $user->id)
            ->where('source', 'telegram_secretary')
            ->whereIn('status', ['open', 'pending'])
            ->latest()->first();
        if (!$ticket) return null;
        $ticket->update(['status' => 'closed']);
        return (int) $ticket->id;
    }

    public function createHumanSupportTicket(?User $user, string $message, array $details = []): ?int
    {
        if (!$user || !class_exists(Ticket::class)) return null;

        try {
            $existing = Ticket::where('user_id', $user->id)
                ->where('source', 'telegram_secretary')
                ->whereIn('status', ['open', 'pending'])
                ->where('created_at', '>=', now()->subHours(12))
                ->latest()
                ->first();
            $extra = [];
            foreach ($details as $key => $value) {
                if ($value !== null && $value !== '') $extra[] = "{$key}: {$value}";
            }
            $body = trim($message) . (empty($extra) ? '' : "\n\n" . implode("\n", $extra));
            if ($existing) {
                $existing->update([
                    'message' => rtrim((string) $existing->message) .
                        "\n\n--- به‌روزرسانی " . now()->format('Y-m-d H:i') . " ---\n" . $body,
                    'priority' => 'high',
                    'status' => 'open',
                ]);
                return (int) $existing->id;
            }

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'subject' => 'درخواست پشتیبانی انسانی از فرایدی',
                'message' => $body,
                'priority' => 'high',
                'status' => 'open',
                'source' => 'telegram_secretary',
            ]);
            return (int) $ticket->id;
        } catch (\Throwable $e) {
            Log::error('Failed to create secretary support ticket', ['user_id' => $user->id]);
            return null;
        }
    }

    private function safe(mixed $value): string
    {
        return htmlspecialchars(trim((string) $value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * اعلان ارسال فیش یا پیام مهم
     */
    public function notifyReceiptSent(int|string $customerChatId, string $fullName, ?string $username = null): bool
    {
        $userTag = $username ? "@{$username}" : "بدون یوزرنیم";
        $text = "💳 <b>اعلان ارسال فیش واریزی / ثبت پرداخت</b>\n\n" .
                "👤 <b>مشتری:</b> {$fullName} ({$userTag})\n" .
                "🆔 <code>{$customerChatId}</code> (<a href=\"tg://user?id={$customerChatId}\">مشاهده پروفایل</a>)\n" .
                "📸 لطفاً جهت بررسی فیش و صدور کانفیگ، پی‌وی مشتری را چک کنید.";

        $keyboard = [];
        if (!empty($username)) {
            $keyboard[] = [['text' => '💬 ورود به پی‌وی کاربر', 'url' => "https://t.me/" . ltrim($username, '@')]];
        }

        return $this->sendNotificationToAdmin($text, $keyboard);
    }
}
