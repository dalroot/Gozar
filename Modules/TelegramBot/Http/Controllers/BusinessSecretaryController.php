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
        $expectedSecret = (string) env('SECRETARY_WEBHOOK_SECRET', '');
        if ($expectedSecret !== '' && !hash_equals($expectedSecret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            Log::warning('Rejected secretary webhook with invalid secret', ['ip' => $request->ip()]);
            return response()->json(['status' => 'unauthorized'], 403);
        }

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
            $messageId = $cq['message']['message_id'] ?? null;
            $callbackFrom = $cq['from'] ?? [];
            $callbackUsername = $callbackFrom['username'] ?? null;
            $callbackFullName = trim(($callbackFrom['first_name'] ?? '') . ' ' . ($callbackFrom['last_name'] ?? ''));
            if ($chatId && $businessConnectionId) {
                $this->secretaryService->state->rememberBusinessConnection($chatId, $businessConnectionId);
            }

            if ($cqId && $chatId) {
                if (!$this->secretaryService->state->acquireChatLock($chatId, 90)) {
                    $this->secretaryService->answerCallbackQuery($cqId, 'درخواست قبلی در حال انجام است؛ چند لحظه صبر کنید.');
                    return response()->json(['status' => 'chat_busy']);
                }
                try {
                    if (!$this->secretaryService->state->claimCallback($cqId)) {
                        $this->secretaryService->answerCallbackQuery($cqId, 'این انتخاب قبلاً انجام شده است.');
                        return response()->json(['status' => 'duplicate_callback']);
                    }
                    $this->secretaryService->handleCallback($cqId, $cqData, $chatId, $businessConnectionId, $messageId, $callbackUsername, $callbackFullName);
                } finally {
                    $this->secretaryService->state->releaseChatLock($chatId);
                }
            }

            return response()->json(['status' => 'callback_handled']);
        }

        // ۲. پردازش اتصال اکانت تجاری به ربات (business_connection)
        if (isset($data['business_connection'])) {
            return response()->json(['status' => 'ok']);
        }

        // ۳. پشتیبانی هم‌زمان از پیام مستقیم بات و Telegram Business
        if (isset($data['business_message']) || isset($data['message'])) {
            $isBusinessMessage = isset($data['business_message']);
            $message = $data['business_message'] ?? $data['message'];
            $messageId = $message['message_id'] ?? null;
            $businessConnectionId = $message['business_connection_id'] ?? null;
            $chatId = $message['chat']['id'] ?? null;
            $from = $message['from'] ?? [];
            $senderId = $from['id'] ?? null;
            $isBot = $from['is_bot'] ?? false;
            $username = $from['username'] ?? null;
            $fullName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
            $text = trim($message['text'] ?? $message['caption'] ?? '');
            $hasReceiptMedia = isset($message['photo']) || isset($message['document']);
            $sentByBusinessBot = !empty($message['sender_business_bot']['id']);
            if ($chatId && $businessConnectionId) {
                $this->secretaryService->state->rememberBusinessConnection($chatId, $businessConnectionId);
            }

            // نادیده گرفتن پیام ربات‌ها یا پیام‌های بدون شناسه
            if ($isBot || !$chatId) {
                return response()->json(['status' => 'skipped']);
            }

            // پیام‌هایی که خود بات از طرف اکانت بیزینسی می‌فرستد دوباره به شکل
            // business_message برمی‌گردند. Telegram آن‌ها را با sender_business_bot
            // مشخص می‌کند؛ این پیام‌ها نه ورودی مشتری‌اند و نه دخالت دستی ادمین.
            if ($isBusinessMessage && $sentByBusinessBot) {
                return response()->json(['status' => 'bot_authored_business_message']);
            }

            // هدایت پیام‌های مستقیم خود ربات به ربات فروشگاه و اکانت پشتیبانی (پیام‌های بیزینس @RoozanehHelp کاملاً دست‌نخورده می‌مانند)
            if (!$isBusinessMessage) {
                $redirectText = "درود بر شما 🌿\n\n" .
                                "کلیه خدمات خرید اشتراک، دریافت تست رایگان ۲۴ ساعته و پشتیبانی فنی روزنه به ربات فروشگاه و اکانت پشتیبانی منتقل شده است:\n\n" .
                                "🛍️ <b>ربات فروشگاه و تست:</b> @RoozanehNetBot\n" .
                                "💬 <b>ارتباط با پشتیبانی:</b> @RoozanehHelp\n" .
                                "🆔 <b>کانال رسمی:</b> @joinroozaneh\n\n" .
                                "لطفاً جهت دریافت آنی خدمات، به بخش‌های بالا مراجعه فرمایید.";

                $buttons = [
                    [['text' => '🛍️ ورود به ربات فروشگاه', 'url' => 'https://t.me/RoozanehNetBot']],
                    [['text' => '🎁 دریافت تست رایگان ۲۴ ساعته', 'url' => 'https://t.me/RoozanehNetBot?start=test']],
                    [['text' => '💬 ارتباط با پشتیبانی روزنه', 'url' => 'https://t.me/RoozanehHelp']]
                ];

                $this->secretaryService->sendBusinessMessage(null, $chatId, $redirectText, $buttons);
                return response()->json(['status' => 'direct_message_redirected']);
            }

            $isAdminBusinessMessage = $isBusinessMessage && $chatId != $senderId;
            $normalizedAdminCommand = preg_replace('/\s+/u', ' ', mb_strtolower($text, 'UTF-8'));
            if ($isAdminBusinessMessage && preg_match('/^سکوت(?:\s+1)?$/u', $normalizedAdminCommand)) {
                $deleteRequested = preg_match('/^سکوت\s+1$/u', $normalizedAdminCommand) === 1;
                $this->secretaryService->state->setManualSilence($chatId);
                $deleteResult = $deleteRequested
                    ? $this->secretaryService->deleteTrackedBotMessages($businessConnectionId, $chatId)
                    : ['requested' => 0, 'deleted' => 0, 'failed' => 0];

                Log::info('Friday manual silence command applied', [
                    'chat_id' => $chatId,
                    'admin_id' => $senderId,
                    'command' => $deleteRequested ? 'silence_and_delete' : 'silence',
                    'delete_result' => $deleteResult,
                ]);
                app(\App\Services\BotEventLogger::class)->record('manual_silence', 'friday', [
                    'chat_id' => $chatId,
                    'admin_id' => $senderId,
                    'delete_requested' => $deleteRequested,
                    'deleted_count' => $deleteResult['deleted'],
                    'failed_count' => $deleteResult['failed'],
                ]);

                return response()->json([
                    'status' => $deleteRequested ? 'automation_silenced_and_messages_deleted' : 'automation_silenced',
                    'deleted' => $deleteResult['deleted'],
                    'failed' => $deleteResult['failed'],
                ]);
            }

            // الف) پیام دستی ادمین فقط در ادامهٔ درخواست صریح پشتیبان انسانی takeover می‌سازد.
            // پیام آزمایشی یا پیش‌دستانه ادمین نباید کاربر جدید را از فرایدی محروم کند.
            if ($isBusinessMessage && $chatId != $senderId) {
                if ($this->secretaryService->state->isHumanRequested($chatId)) {
                    Log::info("Admin answered requested human-support chat {$chatId}. Muting Friday for 30 minutes.");
                    $this->secretaryService->state->setAdminActive($chatId);
                    return response()->json(['status' => 'admin_active_recorded']);
                }

                $this->secretaryService->state->clearAdminActive($chatId);
                Log::info("Admin sent proactive message in chat {$chatId}; Friday remains available.");
                return response()->json(['status' => 'admin_message_without_takeover']);
            }

            $resumeRequested = preg_match(
                '/^(\/friday|فرایدی\s*(پاسخ بده|برگرد|فعال شو)|ربات\s*(پاسخ بده|برگرد)|برگشت به ربات)$/ui',
                $text
            );
            if ($resumeRequested) {
                if ($this->secretaryService->state->isManuallySilenced($chatId) && !$isAdminBusinessMessage) {
                    return response()->json(['status' => 'automation_manually_silenced']);
                }
                $this->secretaryService->state->resumeAutomation($chatId);
                $reply = $this->secretaryService->automationResumedReply();
                $sent = $this->secretaryService->sendBusinessMessage(
                    $businessConnectionId,
                    $chatId,
                    $reply['text'],
                    $reply['buttons'] ?? null
                );
                return response()->json(['status' => $sent ? 'automation_resumed' : 'resume_send_failed']);
            }

            // ورودی‌های تراکنشی فرایدی (کد تخفیف و رسید) باید پیش از طبقه‌بندی عمومی پردازش شوند.
            if ($this->secretaryService->commerce->hasPendingInput($chatId)) {
                $commerceReply = $this->secretaryService->commerce->processPendingInput($chatId, $text, $message);
                if ($commerceReply) {
                    $sent = $this->secretaryService->sendBusinessMessage(
                        $businessConnectionId,
                        $chatId,
                        $commerceReply['text'],
                        $commerceReply['buttons'] ?? null
                    );
                    return response()->json(['status' => $sent ? 'commerce_input_processed' : 'commerce_input_send_failed']);
                }
            }

            if ($this->secretaryService->state->isManuallySilenced($chatId)) {
                return response()->json(['status' => 'automation_manually_silenced']);
            }

            // ب) سکوت در حالت پشتیبانی انسانی؛ takeover قدیمی و بی‌دلیل خودکار پاک می‌شود.
            if ($this->secretaryService->state->isAdminActive($chatId)
                && !$this->secretaryService->state->isHumanRequested($chatId)) {
                $this->secretaryService->state->clearAdminActive($chatId);
            }
            if ($this->secretaryService->state->isHumanRequested($chatId)) {
                Log::info("Skipping reply to {$chatId}: Admin Takeover active.");
                return response()->json(['status' => 'human_support_in_control']);
            }

            // ج) اگر کاربر قبلاً درخواست پشتیبان داده باشد
            // د) نادیده گرفتن پیام‌های بدون متن
            if (empty($text) && $hasReceiptMedia) {
                $text = 'رسید پرداخت ارسال کردم';
            }
            if (empty($text)) {
                return response()->json(['status' => 'empty_or_media']);
            }

            // ه) بافر کردن پیام ورودی
            $this->secretaryService->state->pushMessageToBuffer($chatId, $text);

            // ارسال اکشن typing برای حس پاسخ‌گویی طبیعی
            $this->secretaryService->sendChatAction($businessConnectionId, $chatId, 'typing');

            // ثبت زمان ورود برای مدیریت پیام‌های رگباری (Debounce)
            $arrivalTime = microtime(true);
            \Illuminate\Support\Facades\Cache::put("sec_msg_time_{$chatId}", $arrivalTime, 30);

            // تاخیر هوشمند برای تجمیع پیام‌های پشت سر هم کاربر (۲.۵ ثانیه)
            usleep(2500000);

            // اگر کاربر پیام جدیدتری در این فاصله فرستاده باشد، این پردازش متوقف می‌شود تا پیام بعدی کل متن را تجمیع کند
            $latestTime = (float) \Illuminate\Support\Facades\Cache::get("sec_msg_time_{$chatId}", 0);
            if ($latestTime > $arrivalTime) {
                return response()->json(['status' => 'waiting_for_more_messages']);
            }

            // و) دریافت قفل اختصاصی برای پردازش تک‌نوبتی
            $lockAcquired = false;
            for ($attempt = 0; $attempt < 20; $attempt++) {
                if ($this->secretaryService->state->acquireChatLock($chatId, 90)) {
                    $lockAcquired = true;
                    break;
                }
                usleep(500000);
            }
            if (!$lockAcquired) return response()->json(['status' => 'locked_by_active_process']);

            try {
                $aggregatedText = $this->secretaryService->state->pullAggregatedBuffer($chatId);
                if (empty($aggregatedText)) {
                    return response()->json(['status' => 'buffer_already_cleared']);
                }

                Log::info('Secretary message received', [
                    'chat_id' => $chatId,
                    'source' => $isBusinessMessage ? 'business_message' : 'direct_message',
                    'has_username' => !empty($username),
                    'length' => mb_strlen($aggregatedText),
                ]);

                $progressMessageId = null;

                // ز) پردازش متن تجمیع‌شده با هوش مصنوعی
                $replyData = $this->secretaryService->processIncomingMessage($aggregatedText, $chatId, $username, $fullName);

                if ($replyData && !empty($replyData['text'])) {
                    $sent = $progressMessageId
                        ? $this->secretaryService->editBusinessMessage(
                            $businessConnectionId,
                            $chatId,
                            $progressMessageId,
                            $replyData['text'],
                            $replyData['buttons'] ?? null
                        )
                        : false;
                    if (!$sent) {
                        $sent = $this->secretaryService->sendBusinessMessage(
                            $businessConnectionId,
                            $chatId,
                            $replyData['text'],
                            $replyData['buttons'] ?? null,
                            null
                        );
                    }

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

    public function webhookInfo()
    {
        $botToken = (string) env('SECRETARY_BOT_TOKEN', '');
        if (empty($botToken)) {
            return response()->json(['ok' => false, 'description' => 'SECRETARY_BOT_TOKEN not configured']);
        }
        $res = \Illuminate\Support\Facades\Http::get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
        return response()->json($res->json() ?? ['ok' => false]);
    }
}
