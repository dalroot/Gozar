<?php

namespace Modules\TelegramBot\Services;

use App\Models\User;
use App\Models\Order;
use Modules\TelegramBot\Services\Secretary\ConversationStateService;
use Modules\TelegramBot\Services\Secretary\SalesFunnelService;
use Modules\TelegramBot\Services\Secretary\AdminNotificationService;
use Modules\TelegramBot\Services\Secretary\IntentClassifier;
use Modules\TelegramBot\Services\Secretary\AIResponseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramSecretaryService
{
    public ConversationStateService $state;
    public SalesFunnelService       $funnel;
    public AdminNotificationService $notifier;
    public IntentClassifier         $classifier;
    public AIResponseService        $ai;
    protected string                $botToken;

    public function __construct(
        ConversationStateService $state,
        SalesFunnelService       $funnel,
        AdminNotificationService $notifier,
        IntentClassifier         $classifier,
        AIResponseService        $ai
    ) {
        $this->state      = $state;
        $this->funnel     = $funnel;
        $this->notifier   = $notifier;
        $this->classifier = $classifier;
        $this->ai         = $ai;
        $this->botToken   = env('SECRETARY_BOT_TOKEN', '8450449696:AAGfdyIZg4FnLlpKeuDo6D8imdi1bFKo7eQ');
    }

    /**
     * دریافت اطلاعات پایه کاربر از دیتابیس
     */
    public function getUserData(int|string $chatId, ?string $username = null, ?string $fullName = null): array
    {
        $user = User::where('telegram_chat_id', (string)$chatId)->first();
        if (!$user && $username) {
            $user = User::where('name', 'like', "%{$username}%")->first();
        }

        $name = trim($fullName ?? ($user?->name ?? ''));
        if (empty($name)) {
            $name = $username ?? 'دوست گرامی';
        }

        return [
            'user'     => $user,
            'fullName' => $name,
            'username' => $username,
            'chatId'   => $chatId,
        ];
    }

    /**
     * پردازش پیام ورودی (با پشتیبانی از پیام‌های تجمیع‌شده / بافر)
     */
    public function processIncomingMessage(
        string     $userMessage,
        int|string $chatId,
        ?string    $username = null,
        ?string    $fullName = null
    ): ?array {
        $userData     = $this->getUserData($chatId, $username, $fullName);
        $paymentData  = $this->funnel->getPaymentDetails();
        $chatHistory  = $this->state->getChatHistory($chatId);
        $isIntroduced = $this->state->isIntroduced($chatId);

        // ۱. بررسی عوارض جانبی پیام (Side Effects)
        $sideEffect = $this->classifier->detectSideEffect($userMessage);

        if ($sideEffect === IntentClassifier::SIDE_HUMAN) {
            $this->state->setHumanRequested($chatId);
            $this->notifier->notifyHumanSupportRequested(
                $chatId, $userData['fullName'], $username, $userMessage
            );
        } elseif ($sideEffect === IntentClassifier::SIDE_RECEIPT) {
            $this->notifier->notifyReceiptSent($chatId, $userData['fullName'], $username);
        }

        // ۲. تولید پاسخ با هوش مصنوعی
        $text = $this->ai->generateReply($userMessage, $userData, $paymentData, $chatHistory, $isIntroduced);

        // ۳. ثبت معرفی در صورتی که پیام اول بود
        if (!$isIntroduced) {
            $this->state->markAsIntroduced($chatId);
        }

        // ۴. ذخیره در تاریخچه
        $this->state->addMessageToHistory($chatId, 'user', $userMessage);
        $this->state->addMessageToHistory($chatId, 'assistant', $text);

        // ۵. تعیین هوشمند دکمه‌ها
        $buttons = $this->resolveButtons($sideEffect, $text);

        return ['text' => $text, 'buttons' => $buttons];
    }

    /**
     * تعیین دکمه‌های مناسب (بسیار محافظه‌کارانه جهت جلوگیری از شلوغی چت)
     */
    protected function resolveButtons(string $sideEffect, string $aiReply): ?array
    {
        if ($sideEffect === IntentClassifier::SIDE_PLANS || $this->ai->responseMentionsPlans($aiReply)) {
            return $this->funnel->getDurationKeyboard();
        }

        if ($sideEffect === IntentClassifier::SIDE_TRIAL || $this->ai->responseMentionsTrial($aiReply)) {
            return [
                [
                    ['text' => '⚡️ دریافت تست رایگان ۲۴ ساعته', 'callback_data' => 'sec_get_trial'],
                    ['text' => '❌ بستن', 'callback_data' => 'sec_dismiss'],
                ]
            ];
        }

        return null;
    }

    /**
     * ارسال پیام در پی‌وی بیزینس (با پشتیبانی اختیاری از ریپلای)
     */
    public function sendBusinessMessage(
        string     $businessConnectionId,
        int|string $chatId,
        string     $text,
        ?array     $buttons = null,
        ?int       $replyToMessageId = null
    ): bool {
        try {
            $url     = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $payload = [
                'business_connection_id' => $businessConnectionId,
                'chat_id'                => $chatId,
                'text'                   => $text,
                'parse_mode'             => 'HTML',
            ];

            if ($replyToMessageId) {
                $payload['reply_parameters'] = json_encode([
                    'message_id' => $replyToMessageId
                ]);
            }

            if (!empty($buttons)) {
                $payload['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
            }

            $response = Http::timeout(15)->post($url, $payload);
            if ($response->successful()) {
                return true;
            }

            $payload['text'] = strip_tags($text);
            unset($payload['parse_mode']);
            return Http::timeout(15)->post($url, $payload)->successful();
        } catch (\Exception $e) {
            Log::error("sendBusinessMessage error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * پردازش کلیک روی دکمه‌های شیشه‌ای
     */
    public function handleCallback(
        string     $callbackId,
        string     $data,
        int|string $chatId,
        ?string    $businessConnectionId = null
    ): void {
        // ۱. بستن / لغو منو
        if ($data === 'sec_dismiss') {
            $this->answerCallbackQuery($callbackId, "منو بسته شد.");
            return;
        }

        // ۲. انتخاب بازه زمانی
        if (str_starts_with($data, 'sec_duration_')) {
            $days  = (int) str_replace('sec_duration_', '', $data);
            $label = $this->funnel->generateDurationLabel($days);
            $this->answerCallbackQuery($callbackId, "بسته‌های {$label}");

            if ($businessConnectionId) {
                $text    = "💎 <b>بسته‌های اشتراک {$label}:</b>\n\n" .
                           "👉 <i>لطفاً حجم مورد نظرتون رو انتخاب کنید:</i>";
                $buttons = $this->funnel->getPlansByDurationKeyboard($days);
                $this->sendBusinessMessage($businessConnectionId, $chatId, $text, $buttons);
            }
            return;
        }

        // ۳. بازگشت به لیست دوره‌ها
        if ($data === 'sec_view_durations') {
            $this->answerCallbackQuery($callbackId, "تعرفه‌ها");
            if ($businessConnectionId) {
                $text    = "🛍 <b>فروشگاه اشتراک:</b>\n\n" .
                           "👉 <i>لطفاً مدت زمان مورد نظرتون رو انتخاب کنید:</i>";
                $buttons = $this->funnel->getDurationKeyboard();
                $this->sendBusinessMessage($businessConnectionId, $chatId, $text, $buttons);
            }
            return;
        }

        // ۴. انتخاب پلن مشخص → اطلاعات کارت
        if (str_starts_with($data, 'sec_buy_plan_')) {
            $planId    = str_replace('sec_buy_plan_', '', $data);
            $orderInfo = $this->funnel->getPlanOrderMessage($planId);
            $this->answerCallbackQuery($callbackId, "اطلاعات سفارش");
            if ($businessConnectionId) {
                $this->sendBusinessMessage(
                    $businessConnectionId, $chatId,
                    $orderInfo['text'], $orderInfo['buttons']
                );
            }
            return;
        }

        // ۵. دریافت تست رایگان
        if ($data === 'sec_get_trial') {
            $payment = $this->funnel->getPaymentDetails();
            $this->answerCallbackQuery($callbackId, "اکانت تست ۲۴ ساعته");
            if ($businessConnectionId) {
                $text    = "🎁 <b>اکانت تست رایگان {$payment['trialHours']} ساعته ({$payment['trialMb']} مگابایت)</b>\n\n" .
                           "برای دریافت آنی، روی دکمه زیر کلیک کنید:";
                $buttons = [
                    [['text' => '⚡️ دریافت تست در ربات اصلی', 'url' => 'https://t.me/gozartest2026bot?start=trial']],
                    [['text' => '❌ بستن', 'callback_data' => 'sec_dismiss']]
                ];
                $this->sendBusinessMessage($businessConnectionId, $chatId, $text, $buttons);
            }
            return;
        }

        $this->answerCallbackQuery($callbackId, "دریافت شد.");
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text): bool
    {
        try {
            Http::post("https://api.telegram.org/bot{$this->botToken}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
                'text'              => $text,
                'show_alert'        => false,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function setWebhook(string $webhookUrl): array
    {
        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/setWebhook", [
                'url'             => $webhookUrl,
                'allowed_updates' => [
                    'message', 'business_connection', 'business_message',
                    'edited_business_message', 'callback_query'
                ],
                'drop_pending_updates' => true,
            ]);
            return $response->json() ?? ['ok' => false];
        } catch (\Exception $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }
}
