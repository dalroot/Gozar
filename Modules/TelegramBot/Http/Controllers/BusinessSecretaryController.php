<?php

namespace Modules\TelegramBot\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\TelegramBot\Services\TelegramSecretaryService;

class BusinessSecretaryController extends Controller
{
    protected TelegramSecretaryService $secretaryService;

    public function __construct(TelegramSecretaryService $secretaryService)
    {
        $this->secretaryService = $secretaryService;
    }

    /**
     * دریافت و پردازش وب‌هوک پیام‌های ربات منشی با تاییدیه آنی به تلگرام
     */
    public function handle(Request $request)
    {
        $data = $request->all();

        // تایید فوری به تلگرام برای بستن ارتباط و جلوگیری از ارسال مجدد (Retry)
        if (function_exists('fastcgi_finish_request')) {
            echo json_encode(['status' => 'ok']);
            fastcgi_finish_request();
        }

        // ۱. پردازش کلیک روی دکمه‌های شیشه‌ای (callback_query)
        if (isset($data['callback_query'])) {
            $cq = $data['callback_query'];
            $cqId = $cq['id'] ?? '';
            $cqData = $cq['data'] ?? '';
            $chatId = $cq['message']['chat']['id'] ?? ($cq['from']['id'] ?? null);
            $businessConnectionId = $cq['message']['business_connection_id'] ?? null;

            if ($cqId && $chatId) {
                $this->secretaryService->handleCallback($cqId, $cqData, $chatId, $businessConnectionId);
            }

            return response()->json(['status' => 'callback_handled']);
        }

        // ۲. پردازش اتصال اکانت تجاری به ربات (business_connection)
        if (isset($data['business_connection'])) {
            return response()->json(['status' => 'ok']);
        }

        // ۳. پردازش پیام‌های دریافتی در چت خصوصی (business_message)
        if (isset($data['business_message'])) {
            $message = $data['business_message'];
            $messageId = $message['message_id'] ?? null;
            $businessConnectionId = $message['business_connection_id'] ?? null;
            $chatId = $message['chat']['id'] ?? null;
            $from = $message['from'] ?? [];
            $senderId = $from['id'] ?? null;
            $isBot = $from['is_bot'] ?? false;
            $username = $from['username'] ?? null;
            $fullName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
            $text = trim($message['text'] ?? $message['caption'] ?? '');

            // نادیده گرفتن پیام ربات‌ها یا پیام‌های بدون شناسه
            if ($isBot || !$businessConnectionId || !$chatId) {
                return response()->json(['status' => 'skipped']);
            }

            // الف) سکوت خودکار در حضور ادمین: اگر پیام توسط خود شما ارسال شود
            if ($chatId != $senderId) {
                Log::info("Admin sent message in chat {$chatId}. Muting Friday for 12 hours.");
                $this->secretaryService->state->setAdminActive($chatId);
                return response()->json(['status' => 'admin_active_recorded']);
            }

            // ب) اگر ادمین در ۱۲ ساعت گذشته پیام داده، ربات کاملاً سکوت می‌کند
            if ($this->secretaryService->state->isAdminActive($chatId)) {
                Log::info("Skipping reply to {$chatId}: Admin Takeover active.");
                return response()->json(['status' => 'admin_in_control']);
            }

            // ج) اگر کاربر قبلاً درخواست پشتیبان داده باشد
            if ($this->secretaryService->state->isHumanRequested($chatId)) {
                Log::info("Skipping reply to {$chatId}: Human support active.");
                return response()->json(['status' => 'human_support_active']);
            }

            // د) نادیده گرفتن پیام‌های بدون متن
            if (empty($text)) {
                return response()->json(['status' => 'empty_or_media']);
            }

            // ه) بافر کردن پیام ورودی
            $this->secretaryService->state->pushMessageToBuffer($chatId, $text);

            // ایجاد تاخیر کوتاه برای تجمیع پیام‌های پشت سر هم کاربر
            usleep(1800000); // 1.8 seconds

            // و) دریافت قفل اختصاصی برای پردازش تک‌نوبتی
            if (!$this->secretaryService->state->acquireChatLock($chatId, 10)) {
                return response()->json(['status' => 'locked_by_active_process']);
            }

            try {
                $aggregatedText = $this->secretaryService->state->pullAggregatedBuffer($chatId);
                if (empty($aggregatedText)) {
                    return response()->json(['status' => 'buffer_already_cleared']);
                }

                Log::info("📨 پیام تجمیع‌شده از طرف {$fullName} (@{$username} / {$chatId}): {$aggregatedText}");

                // ز) پردازش متن تجمیع‌شده با هوش مصنوعی
                $replyData = $this->secretaryService->processIncomingMessage($aggregatedText, $chatId, $username, $fullName);

                if ($replyData && !empty($replyData['text'])) {
                    $sent = $this->secretaryService->sendBusinessMessage(
                        $businessConnectionId,
                        $chatId,
                        $replyData['text'],
                        $replyData['buttons'] ?? null,
                        $messageId
                    );

                    return response()->json(['status' => $sent ? 'success' : 'failed']);
                }
            } finally {
                $this->secretaryService->state->releaseChatLock($chatId);
            }

            return response()->json(['status' => 'no_reply_needed']);
        }

        return response()->json(['status' => 'ignored']);
    }

    public function setWebhook(Request $request)
    {
        $appUrl = config('app.url') ?? env('APP_URL');
        $webhookUrl = rtrim($appUrl, '/') . '/webhooks/telegram-secretary';
        $result = $this->secretaryService->setWebhook($webhookUrl);
        return response()->json(['webhook_url' => $webhookUrl, 'result' => $result]);
    }
}
