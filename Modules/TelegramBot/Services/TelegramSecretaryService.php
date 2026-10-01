<?php

namespace Modules\TelegramBot\Services;

use App\Models\User;
use App\Models\Order;
use Modules\TelegramBot\Services\Secretary\ConversationStateService;
use Modules\TelegramBot\Services\Secretary\SalesFunnelService;
use Modules\TelegramBot\Services\Secretary\AdminNotificationService;
use Modules\TelegramBot\Services\Secretary\IntentClassifier;
use Modules\TelegramBot\Services\Secretary\AIResponseService;
use Modules\TelegramBot\Services\Secretary\CustomerContextService;
use Modules\TelegramBot\Services\Secretary\DeterministicSupportService;
use Modules\TelegramBot\Services\Secretary\ServiceUsageService;
use Modules\TelegramBot\Services\Secretary\SecretaryCommerceService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramSecretaryService
{
    public ConversationStateService $state;
    public SalesFunnelService       $funnel;
    public AdminNotificationService $notifier;
    public IntentClassifier         $classifier;
    public AIResponseService        $ai;
    public CustomerContextService   $customers;
    public DeterministicSupportService $support;
    public ServiceUsageService         $usage;
    public SecretaryCommerceService    $commerce;
    protected RozanehExperience        $experience;
    protected BotEntryPointService     $entryPoint;
    protected string                $botToken;

    public function __construct(
        ConversationStateService $state,
        SalesFunnelService       $funnel,
        AdminNotificationService $notifier,
        IntentClassifier         $classifier,
        AIResponseService        $ai,
        CustomerContextService   $customers,
        DeterministicSupportService $support,
        ServiceUsageService         $usage,
        RozanehExperience           $experience,
        BotEntryPointService        $entryPoint,
        SecretaryCommerceService    $commerce
    ) {
        $this->state      = $state;
        $this->funnel     = $funnel;
        $this->notifier   = $notifier;
        $this->classifier = $classifier;
        $this->ai         = $ai;
        $this->customers  = $customers;
        $this->support    = $support;
        $this->usage      = $usage;
        $this->experience = $experience;
        $this->entryPoint = $entryPoint;
        $this->commerce   = $commerce;
        $this->botToken   = (string) env('SECRETARY_BOT_TOKEN', '');
    }

    /**
     * دریافت اطلاعات پایه کاربر از دیتابیس
     */
    public function getUserData(int|string $chatId, ?string $username = null, ?string $fullName = null): array
    {
        $user = User::where('telegram_chat_id', (string)$chatId)->first();
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
        $context      = $this->customers->build($chatId, $username, $fullName);
        $userData     = ['user' => $context['user'], 'fullName' => $context['name'], 'username' => $username, 'chatId' => $chatId];
        $paymentData  = $this->funnel->getPaymentDetails();
        $chatHistory  = $this->state->getChatHistory($chatId);
        $isIntroduced = $this->state->isIntroduced($chatId);
        $context['is_introduced'] = $isIntroduced;

        // ۱. بررسی عوارض جانبی پیام (Side Effects)
        $sideEffect = $this->classifier->classify($userMessage);
        $lastIntent = $this->state->getLastIntent($chatId);
        if ($sideEffect === IntentClassifier::TUTORIAL && $lastIntent === IntentClassifier::TECHNICAL) {
            $sideEffect = IntentClassifier::TECHNICAL;
        }
        if ($sideEffect === IntentClassifier::UNKNOWN) {
            if ($lastIntent === IntentClassifier::TRIAL && preg_match('/^(آره|اره|بله|میخوام|می خوام|اوکی|باشه)[!.، ]*$/u', trim($userMessage))) {
                $sideEffect = IntentClassifier::TRIAL;
            } elseif ($lastIntent === IntentClassifier::TECHNICAL && preg_match('/(بازم\s*نشد|هنوز\s*وصل\s*نمیشه|انجام\s*دادم|این\s*کارو\s*کردم|همون\s*خطا|چه\s*کار\s*کنم)/u', mb_strtolower(trim($userMessage), 'UTF-8'))) {
                $sideEffect = IntentClassifier::TECHNICAL;
            }
        }

        app(\App\Services\BotEventLogger::class)->record('message_classified', 'friday', [
            'chat_id' => $chatId,
            'user_id' => $context['user']?->id,
            'intent' => $sideEffect,
            'has_service' => $context['service'] !== null,
            'is_introduced' => $isIntroduced,
        ]);

        if ($sideEffect === IntentClassifier::RENEWAL && $context['user']) {
            $this->commerce->setMode($chatId, 'renewal', $context['service']?->id);
        } elseif ($sideEffect === IntentClassifier::PLANS) {
            $this->commerce->setMode($chatId, 'purchase');
        }

        $humanTicketId = null;
        if ($sideEffect === IntentClassifier::HUMAN) {
            $diagnosisState = $this->state->getDiagnosis($chatId) ?? [];
            $interactionTrail = $this->state->getInteractionTrail($chatId);
            $recentContext = collect($chatHistory)
                ->slice(-4)
                ->map(function (array $item): string {
                    $role = ($item['role'] ?? '') === 'user' ? 'کاربر' : 'فرایدی';
                    $content = mb_substr(trim((string) ($item['content'] ?? '')), 0, 300);
                    return $content !== '' ? "{$role}: {$content}" : '';
                })
                ->filter()
                ->implode("\n");
            $handoffContext = $this->buildHandoffContext($interactionTrail, $recentContext, $diagnosisState);
            $handoffCard = $this->buildHandoffCard($context, $interactionTrail, $chatHistory, $diagnosisState);
            $hasKnownIssue = $this->hasMeaningfulHandoffContext($interactionTrail, $recentContext, $diagnosisState);
            $service = $context['service'];
            $this->state->clearDiagnosis($chatId);
            $this->state->setHumanRequested($chatId);
            $humanTicketId = $this->notifier->createHumanSupportTicket($context['user'], $userMessage, [
                'telegram_chat_id' => $chatId,
                'username' => $username,
                'service_order_id' => $service?->id,
                'service_status' => $service?->status,
                'service_expires_at' => $service?->expires_at,
                'diagnosis_step' => $diagnosisState['step'] ?? null,
                'device' => $diagnosisState['device'] ?? null,
                'recent_conversation' => $recentContext,
                'interaction_trail' => $this->formatInteractionTrail($interactionTrail),
            ]);
            $humanNotified = $this->notifier->notifyHumanSupportRequested(
                $chatId, $userData['fullName'], $username, $userMessage, $humanTicketId, $handoffCard
            );
            app(\App\Services\BotEventLogger::class)->record('human_handoff_requested', 'friday', [
                'chat_id' => $chatId,
                'user_id' => $context['user']?->id,
                'order_id' => $service?->id,
                'ticket_id' => $humanTicketId,
                'diagnosis_step' => $diagnosisState['step'] ?? null,
            ]);

            if ($humanNotified) {
                $humanText = $hasKnownIssue
                    ? 'درخواست ارتباط با پشتیبان انسانی ثبت شد و سابقهٔ گفتگو، انتخاب‌ها و بررسی‌های انجام‌شده برای همکار پشتیبانی ارسال شد. نیازی نیست مشکل را دوباره توضیح دهید؛ پاسخ از همین گفتگو ادامه پیدا می‌کند.'
                    : 'درخواست ارتباط با پشتیبان انسانی ثبت شد و به همکار پشتیبانی اطلاع داده شد. چون پیش از این درخواست، موضوع مشخصی ثبت نشده بود، پشتیبان گفتگو را از همین‌جا ادامه می‌دهد و در صورت نیاز سؤال لازم را می‌پرسد؛ نیازی نیست اطلاعاتی را دوباره ارسال کنید.';
            } else {
                $humanText = 'درخواست شما دریافت شد، اما ارسال اعلان فوری به پشتیبان با اختلال روبه‌رو شد. فرایدی همچنان فعال است؛ لطفاً چند دقیقه دیگر دوباره دکمهٔ پشتیبان انسانی را بزنید.';
                $this->state->resumeAutomation($chatId);
            }
        } elseif ($sideEffect === IntentClassifier::RECEIPT) {
            $this->notifier->notifyReceiptSent($chatId, $userData['fullName'], $username);
        }

        if (!in_array($sideEffect, [IntentClassifier::UNKNOWN, IntentClassifier::GREETING, IntentClassifier::THANKS], true)) {
            $this->state->setLastIntent($chatId, $sideEffect);
        }

        // اگر عیب‌یابی فعال باشد، متن آزاد و دکمه هر دو همان state machine را جلو می‌برند.
        $reply = $sideEffect === IntentClassifier::HUMAN
            ? ['text' => $humanText, 'buttons' => null]
            : $this->diagnosisReply($userMessage, $chatId, $context, $sideEffect);

        // پاسخ عملیاتی قطعی است؛ AI فقط fallback پرسش‌های عمومی ناشناخته است.
        $reply ??= $this->support->reply($sideEffect, $userMessage, $context);
        if ($humanTicketId && $reply) {
            $reply['text'] .= "\n\nشماره پیگیری درخواست شما: <code>#{$humanTicketId}</code>";
        }
        if ($reply === null) {
            $text = $this->ai->generateReply($userMessage, $userData, $paymentData, $chatHistory, $isIntroduced);
            $reply = ['text' => $text, 'buttons' => null];
        }
        // A first standalone thank-you should open with Friday's complete welcome,
        // not look like the welcome and a separate canned reply were glued together.
        if (!$isIntroduced && $sideEffect === IntentClassifier::THANKS) {
            $reply = [
                'text' => $this->experience->assistantIntroduction($context['name'] ?? null),
                'buttons' => $this->experience->assistantMenu(),
            ];
        } elseif (!$isIntroduced && $sideEffect !== IntentClassifier::GREETING) {
            $reply['text'] = $this->experience->assistantIntroduction($context['name'] ?? null) . "\n\n" . $reply['text'];
        }
        $text = $reply['text'];

        // ۳. ثبت معرفی در صورتی که پیام اول بود
        if (!$isIntroduced) {
            $this->state->markAsIntroduced($chatId);
        }

        // ۴. ذخیره در تاریخچه
        $this->state->addMessageToHistory($chatId, 'user', $userMessage);
        $this->state->addMessageToHistory($chatId, 'assistant', $text);

        // دکمه فقط وقتی خود سناریوی قطعی به آن نیاز دارد؛ پاسخ عمومی نباید منوی فروش بسازد.
        $buttons = $reply['buttons'] ?? null;

        app(\App\Services\BotEventLogger::class)->record('reply_ready', 'friday', [
            'chat_id' => $chatId,
            'user_id' => $context['user']?->id,
            'intent' => $sideEffect,
            'button_count' => collect($buttons ?? [])->flatten(1)->count(),
        ]);

        return ['text' => $text, 'buttons' => $buttons];
    }

    private function diagnosisReply(string $message, int|string $chatId, array $ctx, string $intent): ?array
    {
        $normalized = mb_strtolower(trim($message), 'UTF-8');
        $normalized = str_replace(['آ', 'أ', 'إ', 'ي', 'ك', 'ة', "\u{200C}"], ['ا', 'ا', 'ا', 'ی', 'ک', 'ه', ' '], $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?: $normalized;
        $state = $this->state->getDiagnosis($chatId);

        if (preg_match('/(حل شد|وصل شد|درست شد|الان کار می.?کنه)/u', $normalized)) {
            $this->state->clearDiagnosis($chatId);
            return $this->diagText('خیلی عالیه که اتصال برقرار شد 🌿 اگر دوباره مشکلی پیش اومد، همین‌جا پیام بدید.', [
                [['text' => '🔍 بررسی وضعیت اشتراک', 'callback_data' => 'diag_status']],
                [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
            ]);
        }

        if ($intent === IntentClassifier::TECHNICAL && !$state) {
            return $this->startDiagnosis($chatId, $ctx);
        }
        if (!$state) return null;

        $step = $state['step'] ?? 'internet';
        if ($step === 'internet') {
            if (preg_match('/(عادی.*(کار|وصل)|اینترنت.*(هست|دارم)|بدون.*(باز|کار)|diag_internet_ok)/u', $normalized)) {
                $state['step'] = 'device';
                $this->state->setDiagnosis($chatId, $state);
                return $this->deviceQuestion();
            }
            if (preg_match('/(عادی.*(قطع|کار نمی)|اینترنت.*(ندارم|قطع)|بسته.*(تموم|تمام)|diag_internet_bad)/u', $normalized)) {
                $state['step'] = 'package';
                $this->state->setDiagnosis($chatId, $state);
                return $this->diagText(
                    "ابتدا مطمئن بشید بستهٔ اینترنت یا اعتبار اتصال شما فعال و دارای حجمه. فیلترشکن رو خاموش کنید و یک سایت داخلی رو باز کنید؛ اگر باز نشد، مشکل فعلی از اینترنت پایه است نه سرویس روزنه.",
                    $this->diagButtons([
                        ['✅ بسته فعال است', 'diag_package_ok'],
                        ['❌ بسته تمام شده', 'diag_package_empty'],
                        ['🔄 با اینترنت دیگری تست کردم', 'diag_alt_ok'],
                    ])
                );
            }
            return $this->internetQuestion();
        }

        if ($step === 'package') {
            if (preg_match('/(تمام|تموم|ندارم|diag_package_empty)/u', $normalized)) {
                $this->state->clearDiagnosis($chatId);
                return $this->diagText('بسته یا اعتبار اینترنت پایه شما تمام شده است. بعد از فعال‌کردن بسته اینترنت، دوباره سرویس روزنه را امتحان کنید.', $this->alwaysHumanButtons());
            }
            if (preg_match('/(فعال|دارم|اینترنت دیگر|اینترنت دیگه|diag_package_ok|diag_alt_ok)/u', $normalized)) {
                $state['step'] = 'device';
                $this->state->setDiagnosis($chatId, $state);
                return $this->deviceQuestion();
            }
        }

        if ($step === 'device') {
            $device = match (true) {
                preg_match('/(اندروید|android|diag_device_android)/u', $normalized) === 1 => 'android',
                preg_match('/(ایفون|ios|ipad|diag_device_ios)/u', $normalized) === 1 => 'ios',
                preg_match('/(ویندوز|windows|diag_device_windows)/u', $normalized) === 1 => 'windows',
                preg_match('/(مک|macos|diag_device_mac)/u', $normalized) === 1 => 'mac',
                preg_match('/(لینوکس|linux|diag_device_linux)/u', $normalized) === 1 => 'linux',
                default => null,
            };
            if (!$device) return $this->deviceQuestion();
            $state['device'] = $device;
            $state['step'] = 'app_update';
            $this->state->setDiagnosis($chatId, $state);
            $apps = match ($device) {
                'android' => 'V2RayNG، Hiddify، NekoBox، NekoRay، Clash Meta یا Sing-box',
                'ios' => 'Streisand، V2Box، Shadowrocket، Hiddify یا FoXray',
                'windows' => 'V2RayN، Hiddify، NekoRay، Clash Verge یا Sing-box',
                'mac' => 'Hiddify، V2Box، FoXray، Clash Verge یا Shadowrocket',
                default => 'Hiddify، Sing-box یا برنامه سازگار شما',
            };
            return $this->diagText(
                "روی این دستگاه معمولاً می‌تونید از {$apps} استفاده کنید. اول مطمئن بشید برنامه‌تون به آخرین نسخه به‌روزرسانی شده.",
                $this->diagButtons([
                    ['✅ برنامه به‌روز است', 'diag_app_updated'],
                    ['🔄 باید به‌روزرسانی کنم', 'diag_app_needs_update'],
                    ['❓ نسخه را نمی‌دانم', 'diag_app_unknown'],
                ])
            );
        }

        if ($step === 'app_update') {
            if (preg_match('/(باید|نیاز|قدیمی|نمی.?دانم|diag_app_needs_update|diag_app_unknown)/u', $normalized)) {
                return $this->diagText('لطفاً برنامه را فقط از منبع معتبر به آخرین نسخه به‌روزرسانی کنید، سپس همین‌جا بنویسید «به‌روز شد».', $this->alwaysHumanButtons());
            }
            if (preg_match('/(به.?روز|اپدیت|update|diag_app_updated)/u', $normalized)) {
                $state['step'] = 'subscription';
                $this->state->setDiagnosis($chatId, $state);
                return $this->diagText(
                    "حالا داخل برنامه گزینهٔ <b>Update subscription</b>، <b>Update all subscriptions</b> یا <b>Refresh</b> رو اجرا کنید و بعد یک کانفیگ دیگه از همان اشتراک انتخاب کنید.",
                    $this->diagButtons([
                        ['✅ انجام دادم و وصل شد', 'diag_resolved'],
                        ['❌ انجام دادم، نشد', 'diag_subscription_failed'],
                    ])
                );
            }
        }

        if ($step === 'subscription' && preg_match('/(نشد|هنوز|diag_subscription_failed)/u', $normalized)) {
            $state['step'] = 'airplane';
            $this->state->setDiagnosis($chatId, $state);
            return $this->diagText(
                'حالت هواپیما را حدود ۱۵ ثانیه روشن کنید، بعد خاموش کنید و دوباره اتصال را امتحان کنید.',
                $this->diagButtons([
                    ['✅ مشکل حل شد', 'diag_resolved'],
                    ['❌ هنوز وصل نمی‌شود', 'diag_airplane_failed'],
                ])
            );
        }

        if ($step === 'airplane' && preg_match('/(نشد|هنوز|diag_airplane_failed)/u', $normalized)) {
            $state['step'] = 'alternate';
            $this->state->setDiagnosis($chatId, $state);
            return $this->diagText(
                'لطفاً یک‌بار با اینترنت دیگری امتحان کنید؛ مثلاً بین Wi-Fi و دیتای همراه جابه‌جا شوید. این تست مشخص می‌کند مشکل از مسیر اینترنت فعلی است یا خیر.',
                $this->diagButtons([
                    ['✅ با اینترنت دیگر وصل شد', 'diag_alt_resolved'],
                    ['❌ با اینترنت دیگر هم نشد', 'diag_alt_failed'],
                ])
            );
        }

        if ($step === 'alternate') {
            if (preg_match('/(وصل شد|diag_alt_resolved)/u', $normalized)) {
                $this->state->clearDiagnosis($chatId);
                return $this->diagText('پس سرویس سالم است و اختلال از مسیر اینترنت قبلی شماست. می‌تونید فعلاً از اینترنت جایگزین استفاده کنید یا موضوع را با اپراتورتون بررسی کنید 🌿', $this->alwaysHumanButtons());
            }
            if (preg_match('/(نشد|diag_alt_failed)/u', $normalized)) {
                $interactionTrail = $this->state->getInteractionTrail($chatId);
                $recentContext = collect($this->state->getChatHistory($chatId))
                    ->slice(-4)
                    ->map(function (array $item): string {
                        $role = ($item['role'] ?? '') === 'user' ? 'کاربر' : 'فرایدی';
                        $content = mb_substr(trim((string) ($item['content'] ?? '')), 0, 300);
                        return $content !== '' ? "{$role}: {$content}" : '';
                    })
                    ->filter()
                    ->implode("\n");
                $handoffContext = $this->buildHandoffContext($interactionTrail, $recentContext, $state);
                $this->state->setHumanRequested($chatId);
                $ticketId = $this->notifier->createHumanSupportTicket($ctx['user'], 'عیب‌یابی کامل انجام شد؛ با اینترنت جایگزین نیز اتصال برقرار نشد.', [
                    'telegram_chat_id' => $chatId,
                    'device' => $state['device'] ?? null,
                    'steps' => 'internet, device, app_update, subscription, airplane, alternate_network',
                    'interaction_trail' => $this->formatInteractionTrail($interactionTrail),
                    'recent_conversation' => $recentContext,
                ]);
                $notified = $this->notifier->notifyHumanSupportRequested($chatId, $ctx['name'], $ctx['username'], 'عیب‌یابی کامل انجام شد؛ با اینترنت جایگزین نیز اتصال برقرار نشد.', $ticketId, $handoffContext);
                $this->state->clearDiagnosis($chatId);
                if (!$notified) {
                    $this->state->resumeAutomation($chatId);
                    return $this->diagText('بررسی‌های اولیه کامل شد، اما ارسال اعلان فوری به پشتیبان با اختلال روبه‌رو شد. لطفاً چند دقیقه دیگر دکمهٔ پشتیبان انسانی را بزنید.', $this->alwaysHumanButtons());
                }
                return $this->diagText('بررسی‌های اولیه کامل شد؛ سرویس شما زمان و حجم کافی دارد اما اتصال برقرار نشد. درخواست پشتیبانی انسانی با نتیجه مراحل انجام‌شده ثبت شد و همکاران از همین‌جا ادامه می‌دن 🌿');
            }
        }

        return $this->diagText('پیامتون رو دریافت کردم. می‌تونید پاسخ مرحله فعلی رو بنویسید یا یکی از دکمه‌ها رو انتخاب کنید.', $this->alwaysHumanButtons());
    }

    private function startDiagnosis(int|string $chatId, array $ctx): array
    {
        if (!$ctx['is_verified']) {
            return $this->diagText('برای این گفتگو هنوز سرویس فعالی ثبت نشده است. می‌توانید همین‌جا تست رایگان بگیرید یا بسته بخرید؛ حساب روزنه هنگام ادامهٔ فرایند به‌صورت خودکار برایتان ساخته می‌شود.', [
                [['text' => '⚡️ دریافت تست رایگان', 'callback_data' => 'sec_get_trial']],
                [['text' => '🛍 خرید سرویس', 'callback_data' => 'sec_view_durations']],
                [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
            ]);
        }
        $order = $ctx['service'];
        if (!$order) {
            return $this->diagText('سرویس فعالی برای این حساب پیدا نکردم. می‌تونید بسته جدید انتخاب کنید یا از پشتیبان انسانی کمک بگیرید.', [
                [['text' => '🛍 مشاهده بسته‌ها', 'callback_data' => 'sec_view_durations']],
                [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
            ]);
        }
        if ($order->expires_at && now()->gte($order->expires_at)) {
            return $this->support->reply(IntentClassifier::TECHNICAL, 'وصل نمیشه', $ctx);
        }
        $usage = $this->usage->get($order, $ctx['settings']);
        if ($usage && $usage['total_bytes'] > 0 && $usage['remaining_bytes'] <= 0) {
            return $this->diagText('حجم سرویس شما به پایان رسیده و علت قطعی همین مورد است. برای اتصال دوباره لازم است بسته را تمدید یا حجم جدید تهیه کنید.', [
                [['text' => '🔄 تمدید یا خرید', 'callback_data' => 'sec_view_durations']],
                [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
            ]);
        }
        $this->state->setDiagnosis($chatId, ['step' => 'internet', 'started_at' => now()->toIso8601String()]);
        $remaining = $usage && $usage['total_bytes'] > 0 ? number_format($usage['remaining_bytes'] / 1073741824, 2) . ' گیگابایت' : 'قابل دریافت نبود';
        return $this->diagText(
            "وضعیت واقعی سرویس بررسی شد: زمان اشتراک معتبر است، حجم باقی‌مانده <b>{$remaining}</b> و حساب در سامانه فعال است. حالا اینترنت پایه دستگاه رو بررسی می‌کنیم.",
            $this->internetQuestion()['buttons']
        );
    }

    private function internetQuestion(): array
    {
        return $this->diagText('فیلترشکن را خاموش کنید و یک سایت داخلی را باز کنید. آیا اینترنت عادی بدون فیلترشکن کار می‌کند؟', $this->diagButtons([
            ['✅ اینترنت عادی کار می‌کند', 'diag_internet_ok'],
            ['❌ اینترنت عادی هم قطع است', 'diag_internet_bad'],
        ]));
    }

    private function deviceQuestion(): array
    {
        return $this->diagText('با چه دستگاهی وصل می‌شید؟ می‌تونید اسم دستگاه یا سیستم‌عامل رو هم بنویسید.', [
            [['text' => '🤖 اندروید', 'callback_data' => 'diag_device_android'], ['text' => '🍎 آیفون/iPad', 'callback_data' => 'diag_device_ios']],
            [['text' => '🪟 ویندوز', 'callback_data' => 'diag_device_windows'], ['text' => '💻 macOS', 'callback_data' => 'diag_device_mac']],
            [['text' => '🐧 لینوکس', 'callback_data' => 'diag_device_linux']],
            [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
        ]);
    }

    private function diagButtons(array $choices): array
    {
        $rows = [];
        foreach ($choices as [$label, $callback]) $rows[] = [['text' => $label, 'callback_data' => $callback]];
        $rows[] = [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']];
        return $rows;
    }

    private function alwaysHumanButtons(): array
    {
        return [[['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']]];
    }

    private function diagText(string $text, ?array $buttons = null): array
    {
        return ['text' => $text, 'buttons' => $buttons];
    }

    private function formatInteractionTrail(array $trail): string
    {
        return collect($trail)
            ->slice(-10)
            ->map(function (array $item): string {
                $label = trim((string) ($item['label'] ?? ''));
                if ($label === '') return '';
                try {
                    $time = \Carbon\Carbon::parse($item['at'] ?? now())->format('H:i');
                } catch (\Throwable) {
                    $time = '--:--';
                }
                return "{$time} — {$label}";
            })
            ->filter()
            ->implode("\n");
    }

    private function buildHandoffContext(array $trail, string $recentContext, array $diagnosisState): string
    {
        $parts = [];
        $formattedTrail = $this->formatInteractionTrail($trail);
        if ($formattedTrail !== '') $parts[] = "انتخاب‌های کاربر:\n{$formattedTrail}";
        if ($recentContext !== '') $parts[] = "گفتگوی اخیر:\n{$recentContext}";
        if (!empty($diagnosisState['step'])) {
            $line = 'مرحله عیب‌یابی: ' . $diagnosisState['step'];
            if (!empty($diagnosisState['device'])) $line .= ' | دستگاه: ' . $diagnosisState['device'];
            $parts[] = $line;
        }
        return implode("\n\n", $parts);
    }

    private function buildHandoffCard(array $context, array $trail, array $chatHistory, array $diagnosisState): array
    {
        $actions = collect($trail)->pluck('action');
        $hasConnectionIssue = !empty($diagnosisState)
            || $actions->contains(fn ($action) => str_starts_with((string) $action, 'diag_') && $action !== 'diag_status');
        $hasPurchase = $actions->contains(fn ($action) => str_starts_with((string) $action, 'sec_duration_') || str_starts_with((string) $action, 'sec_buy_plan_'));
        $lastIntent = $this->state->getLastIntent($context['chat_id'] ?? $context['user']?->telegram_chat_id ?? '');
        $hasRenewal = $lastIntent === IntentClassifier::RENEWAL;

        $subjectParts = [];
        if ($hasConnectionIssue) $subjectParts[] = 'مشکل اتصال';
        if ($hasRenewal) $subjectParts[] = 'تمدید سرویس';
        elseif ($hasPurchase) $subjectParts[] = 'خرید یا انتخاب بسته';
        if (!$subjectParts) $subjectParts[] = 'درخواست گفتگو با پشتیبان';

        $order = $context['service'] ?? null;
        $serviceStatus = 'سرویسی پیدا نشد';
        if ($order) {
            $expired = $order->expires_at && now()->gte($order->expires_at);
            $serviceStatus = $expired ? 'منقضی‌شده' : 'فعال';
            if ($order->expires_at) $serviceStatus .= ' · پایان ' . \Carbon\Carbon::parse($order->expires_at)->format('Y/m/d H:i');
        }

        $lastUserMessages = collect($chatHistory)
            ->filter(fn (array $item) => ($item['role'] ?? '') === 'user')
            ->pluck('content')
            ->map(fn ($text) => trim((string) $text))
            ->reject(fn ($text) => $text === '' || preg_match('/^(سلام|وقت بخیر|پشتیبان انسانی می.?خواهم)$/u', $text))
            ->values();
        $lastUserRequest = (string) ($lastUserMessages->last() ?? 'درخواست ارتباط با پشتیبان انسانی');

        $summary = [];
        if ($hasConnectionIssue && $order && $order->expires_at && now()->gte($order->expires_at)) {
            $summary[] = 'کاربر مشکل اتصال داشته و بررسی سامانه نشان داده زمان سرویس به پایان رسیده است.';
        } elseif ($hasConnectionIssue) {
            $summary[] = 'کاربر مشکل اتصال گزارش کرده و مراحل ثبت‌شدهٔ عیب‌یابی برای ادامه بررسی آماده است.';
        }
        if ($hasRenewal) $summary[] = 'کاربر قصد تمدید سرویس فعلی را دارد.';
        elseif ($hasPurchase) $summary[] = 'کاربر وارد مسیر انتخاب بسته شده است.';
        if (!$summary) $summary[] = 'کاربر مستقیماً درخواست گفتگوی انسانی کرده و موضوع دیگری پیش از آن ثبت نشده است.';

        $selectedPlan = collect($trail)->reverse()->first(fn (array $item) => str_starts_with((string) ($item['action'] ?? ''), 'sec_buy_plan_'));
        if ($selectedPlan) $summary[] = 'آخرین انتخاب فروش: ' . ($selectedPlan['label'] ?? 'بسته انتخابی') . '.';

        return [
            'subject' => implode(' و ', array_unique($subjectParts)),
            'service_status' => $serviceStatus,
            'order' => $order ? '#' . $order->id . ' · ' . ($order->plan?->name ?? 'اشتراک') : null,
            'summary' => implode(' ', $summary),
            'last_user_request' => mb_substr($lastUserRequest, 0, 240),
        ];
    }

    private function hasMeaningfulHandoffContext(array $trail, string $recentContext, array $diagnosisState): bool
    {
        if (!empty($diagnosisState)) return true;
        foreach ($trail as $item) {
            if (!in_array($item['action'] ?? '', ['sec_human', 'sec_dismiss'], true)) return true;
        }

        foreach (preg_split('/\R/u', $recentContext) ?: [] as $line) {
            if (!preg_match('/^کاربر:\s*(.+)$/u', trim($line), $matches)) continue;
            $message = mb_strtolower(trim($matches[1]), 'UTF-8');
            $message = str_replace(['آ', 'أ', 'إ', 'ي', 'ك', "\u{200C}"], ['ا', 'ا', 'ا', 'ی', 'ک', ' '], $message);
            $message = preg_replace('/[!؟?،,.\s]+/u', ' ', $message) ?: $message;
            if (preg_match('/^(سلام|سلام وقت بخیر|وقت بخیر|درود|پشتیبان انسانی می خواهم|با پشتیبان صحبت کنم|میخوام با پشتیبان صحبت کنم)$/u', trim($message))) {
                continue;
            }
            if (mb_strlen(trim($message)) >= 4) return true;
        }
        return false;
    }

    /**
     * ارسال پیام در پی‌وی بیزینس (با پشتیبانی اختیاری از ریپلای)
     */
    public function sendBusinessMessage(
        ?string    $businessConnectionId,
        int|string $chatId,
        string     $text,
        ?array     $buttons = null,
        ?int       $replyToMessageId = null
    ): bool {
        try {
            $url     = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $payload = [
                'chat_id'                => $chatId,
                'text'                   => $text,
                'parse_mode'             => 'HTML',
                'link_preview_options'   => json_encode(['is_disabled' => true]),
            ];
            if ($businessConnectionId) {
                $payload['business_connection_id'] = $businessConnectionId;
            }

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
                $messageId = (int) $response->json('result.message_id', 0);
                if ($messageId > 0) $this->state->rememberBotMessageId($chatId, $messageId);
                return true;
            }

            $payload['text'] = strip_tags($text);
            unset($payload['parse_mode']);
            $fallback = Http::timeout(15)->post($url, $payload);
            if ($fallback->successful()) {
                $messageId = (int) $fallback->json('result.message_id', 0);
                if ($messageId > 0) $this->state->rememberBotMessageId($chatId, $messageId);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error("sendBusinessMessage error: " . $e->getMessage());
            return false;
        }
    }

    public function sendBusinessMessageWithId(?string $businessConnectionId, int|string $chatId, string $text): ?int
    {
        try {
            $payload = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML'];
            if ($businessConnectionId) $payload['business_connection_id'] = $businessConnectionId;
            $response = Http::timeout(15)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", $payload);
            if (!$response->successful()) return null;
            $messageId = (int) $response->json('result.message_id');
            if ($messageId > 0) $this->state->rememberBotMessageId($chatId, $messageId);
            return $messageId > 0 ? $messageId : null;
        } catch (\Throwable $e) {
            Log::warning('Could not send secretary progress message');
            return null;
        }
    }

    public function automationResumedReply(): array
    {
        return [
            'text' => '🤖 فرایدی دوباره فعال شد و از این پیام به بعد پاسخ‌گویی هوشمند ادامه دارد. درخواستتان را بنویسید یا از گزینه‌های زیر استفاده کنید.',
            'buttons' => $this->experience->assistantMenu(),
        ];
    }

    /** حذف پیام‌های ثبت‌شده فرایدی در همان گفتگو. */
    public function deleteTrackedBotMessages(?string $businessConnectionId, int|string $chatId): array
    {
        $messageIds = $this->state->getBotMessageIds($chatId);
        $deleted = 0;
        $failed = 0;

        foreach (array_chunk($messageIds, 100) as $chunk) {
            try {
                if ($businessConnectionId) {
                    $response = Http::timeout(15)->post(
                        "https://api.telegram.org/bot{$this->botToken}/deleteBusinessMessages",
                        ['business_connection_id' => $businessConnectionId, 'message_ids' => $chunk]
                    );
                    if ($response->successful() && $response->json('ok') === true) {
                        $deleted += count($chunk);
                    } else {
                        $failed += count($chunk);
                    }
                    continue;
                }

                foreach ($chunk as $messageId) {
                    $response = Http::timeout(10)->post(
                        "https://api.telegram.org/bot{$this->botToken}/deleteMessage",
                        ['chat_id' => $chatId, 'message_id' => $messageId]
                    );
                    $response->successful() && $response->json('ok') === true ? $deleted++ : $failed++;
                }
            } catch (\Throwable $e) {
                $failed += count($chunk);
            }
        }

        if ($failed === 0) $this->state->clearBotMessageIds($chatId);
        return ['requested' => count($messageIds), 'deleted' => $deleted, 'failed' => $failed];
    }

    public function editBusinessMessage(
        ?string $businessConnectionId,
        int|string $chatId,
        int $messageId,
        string $text,
        ?array $buttons = null
    ): bool {
        try {
            $payload = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'link_preview_options' => json_encode(['is_disabled' => true]),
            ];
            if ($businessConnectionId) $payload['business_connection_id'] = $businessConnectionId;
            if (!empty($buttons)) $payload['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
            return Http::timeout(15)->post("https://api.telegram.org/bot{$this->botToken}/editMessageText", $payload)->successful();
        } catch (\Throwable $e) {
            Log::warning('Could not edit secretary progress message', ['message_id' => $messageId]);
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
        ?string    $businessConnectionId = null,
        ?int       $messageId = null,
        ?string    $username = null,
        ?string    $fullName = null
    ): void {
        if (preg_match('/^sec_admin_(resume|resolve)_(\d+)$/', $data, $adminAction)) {
            if (!$this->notifier->isAdminChat($chatId)) {
                $this->answerCallbackQuery($callbackId, 'دسترسی این عملیات را ندارید.');
                return;
            }
            $targetChatId = $adminAction[2];
            $this->state->resumeAutomation($targetChatId);
            $ticketId = $adminAction[1] === 'resolve'
                ? $this->notifier->closeLatestHumanTicket($targetChatId)
                : null;
            $notice = $adminAction[1] === 'resolve'
                ? '✅ پشتیبانی انسانی پایان یافت و فرایدی دوباره فعال شد.'
                : '🤖 پاسخ‌گویی فرایدی دوباره برای این گفتگو فعال شد.';
            $this->sendBusinessMessage($this->state->getBusinessConnection($targetChatId), $targetChatId, $notice);
            $this->answerCallbackQuery($callbackId, $ticketId ? "تیکت #{$ticketId} بسته و فرایدی فعال شد." : 'فرایدی برای کاربر فعال شد.');
            if ($messageId) $this->clearInteractiveKeyboard($businessConnectionId, $chatId, $messageId);
            return;
        }

        $callbackLabels = [
            'sec_dismiss' => 'بستن منو',
            'diag_status' => 'درخواست بررسی وضعیت اشتراک',
            'diag_start' => 'اعلام مشکل اتصال',
            'sec_get_link' => 'درخواست لینک اشتراک',
            'sec_tutorial' => 'درخواست راهنمای اتصال',
            'sec_human' => 'درخواست پشتیبان انسانی',
            'sec_get_trial' => 'درخواست تست رایگان',
            'sec_view_durations' => 'مشاهده فهرست بسته‌ها',
            'sec_start_purchase' => 'انتخاب خرید سرویس جدید',
            'sec_start_renewal' => 'انتخاب تمدید سرویس فعلی',
        ];
        $dynamicCallbackLabel = match (true) {
            str_starts_with($data, 'sec_renew_service_') => 'انتخاب سرویس برای تمدید',
            str_starts_with($data, 'sec_invoice_') => 'بازگشت به فاکتور سفارش',
            str_starts_with($data, 'sec_discount_') => 'درخواست ثبت کد تخفیف',
            str_starts_with($data, 'sec_remove_discount_') => 'حذف کد تخفیف',
            str_starts_with($data, 'sec_pay_wallet_') => 'انتخاب پرداخت با کیف پول',
            str_starts_with($data, 'sec_pay_card_') => 'انتخاب پرداخت کارت‌به‌کارت',
            str_starts_with($data, 'sec_cancel_order_') => 'لغو سفارش',
            str_starts_with($data, 'sec_copy_link_') => 'دریافت مجدد لینک اشتراک',
            str_starts_with($data, 'sec_direct_configs_') => 'دریافت لینک‌های اتصال مستقیم',
            default => null,
        };
        $this->recordButtonInteraction($chatId, $data, $callbackLabels[$data] ?? $dynamicCallbackLabel);

        // ۱. بستن / لغو منو
        if ($data === 'sec_dismiss') {
            $this->answerCallbackQuery($callbackId, "منو بسته شد.");
            $this->deleteInteractiveMessage($businessConnectionId, $chatId, $messageId);
            return;
        }

        // ۱-۲. بازگشت به منوی اصلی
        if ($data === 'sec_main_menu') {
            $this->answerCallbackQuery($callbackId, "منوی اصلی");
            $menuText = $this->experience->assistantIntroduction($fullName);
            $menuButtons = $this->experience->assistantMenu();
            $sent = $messageId ? $this->editBusinessMessage($businessConnectionId, $chatId, $messageId, $menuText, $menuButtons) : false;
            if (!$sent) $this->sendBusinessMessage($businessConnectionId, $chatId, $menuText, $menuButtons);
            return;
        }

        if ($data === 'sec_get_trial') {
            $this->answerCallbackQuery($callbackId, 'انجام شد');
            $trialReply = [
                'text' => "⚡️ <b>دریافت اکانت تست رایگان روزنه</b>\n\n" .
                          "سرویس تست رایگان به‌صورت خودکار در <b>ربات فروشگاه روزنه</b> صادر و تحویل داده می‌شود.\n" .
                          "جهت دریافت آنی کانفیگ، لطفاً وارد ربات فروشگاه شوید 🌿",
                'buttons' => [
                    [['text' => '🎁 دریافت تست در ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot']],
                    [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']]
                ]
            ];
            $sent = $messageId ? $this->editBusinessMessage($businessConnectionId, $chatId, $messageId, $trialReply['text'], $trialReply['buttons']) : false;
            if (!$sent) $this->sendBusinessMessage($businessConnectionId, $chatId, $trialReply['text'], $trialReply['buttons']);
            return;
        }

        $commerceReply = null;
        if (in_array($data, ['sec_view_durations', 'sec_start_purchase', 'sec_start_renewal'], true)) {
            $commerceReply = [
                'text' => "🛍️ <b>خرید و تمدید اشتراک روزنه</b>\n\n" .
                          "تمامی خدمات خرید، تمدید، انتخاب پلن و پرداخت‌ها در <b>ربات رسمی فروشگاه روزنه</b> انجام می‌شود.\n" .
                          "جهت مشاهده تعرفه‌ها و ثبت سفارش، دکمهٔ زیر را لمس فرمایید 🌿",
                'buttons' => [
                    [['text' => '🛍️ ورود به ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot']],
                    [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']]
                ]
            ];
        } elseif (str_starts_with($data, 'sec_renew_service_')) {
            $commerceReply = $this->commerce->selectRenewalService($chatId, (int) str_replace('sec_renew_service_', '', $data));
        } elseif (str_starts_with($data, 'sec_invoice_')) {
            $commerceReply = $this->commerce->showInvoice($chatId, (int) str_replace('sec_invoice_', '', $data));
        } elseif (str_starts_with($data, 'sec_discount_')) {
            $commerceReply = $this->commerce->beginDiscount($chatId, (int) str_replace('sec_discount_', '', $data));
        } elseif (str_starts_with($data, 'sec_remove_discount_')) {
            $commerceReply = $this->commerce->removeDiscount($chatId, (int) str_replace('sec_remove_discount_', '', $data));
        } elseif (str_starts_with($data, 'sec_pay_wallet_')) {
            $commerceReply = $this->commerce->payWallet($chatId, (int) str_replace('sec_pay_wallet_', '', $data));
        } elseif (str_starts_with($data, 'sec_pay_card_')) {
            $commerceReply = $this->commerce->beginCardPayment($chatId, (int) str_replace('sec_pay_card_', '', $data));
        } elseif (str_starts_with($data, 'sec_cancel_order_')) {
            $commerceReply = $this->commerce->cancelOrder($chatId, (int) str_replace('sec_cancel_order_', '', $data));
        } elseif (str_starts_with($data, 'sec_copy_link_')) {
            $commerceReply = $this->commerce->serviceLink($chatId, (int) str_replace('sec_copy_link_', '', $data));
        } elseif (str_starts_with($data, 'sec_direct_configs_')) {
            $commerceReply = $this->commerce->directConfigs($chatId, (int) str_replace('sec_direct_configs_', '', $data));
        }
        if ($commerceReply) {
            $this->answerCallbackQuery($callbackId, 'انجام شد');
            $sent = $messageId ? $this->editBusinessMessage($businessConnectionId, $chatId, $messageId, $commerceReply['text'], $commerceReply['buttons'] ?? null) : false;
            if (!$sent) $this->sendBusinessMessage($businessConnectionId, $chatId, $commerceReply['text'], $commerceReply['buttons'] ?? null);
            return;
        }

        $supportActions = [
            'diag_status' => 'وضعیت اشتراکم را بررسی کن',
            'diag_start' => 'سرویسم وصل نمی‌شود',
            'sec_get_link' => 'لینک اشتراکم را بده',
            'sec_tutorial' => 'راهنمای اتصال می‌خواهم',
            'sec_human' => 'پشتیبان انسانی می‌خواهم',
        ];
        $diagnosisActions = [
            'diag_internet_ok' => 'اینترنت عادی کار می‌کند',
            'diag_internet_bad' => 'اینترنت عادی هم قطع است',
            'diag_package_ok' => 'بسته اینترنت فعال است',
            'diag_package_empty' => 'بسته اینترنت تمام شده',
            'diag_alt_ok' => 'با اینترنت دیگری تست کردم',
            'diag_device_android' => 'اندروید دارم',
            'diag_device_ios' => 'آیفون دارم',
            'diag_device_windows' => 'ویندوز دارم',
            'diag_device_mac' => 'مک دارم',
            'diag_device_linux' => 'لینوکس دارم',
            'diag_app_updated' => 'برنامه به‌روز است',
            'diag_app_needs_update' => 'باید برنامه را به‌روزرسانی کنم',
            'diag_app_unknown' => 'نسخه برنامه را نمی‌دانم',
            'diag_subscription_failed' => 'انجام دادم ولی هنوز وصل نمی‌شود',
            'diag_airplane_failed' => 'حالت هواپیما را تست کردم ولی هنوز وصل نمی‌شود',
            'diag_alt_resolved' => 'با اینترنت دیگر وصل شد',
            'diag_alt_failed' => 'با اینترنت دیگر هم وصل نشد',
            'diag_resolved' => 'مشکل حل شد و وصل شدم',
        ];
        if (isset($diagnosisActions[$data])) {
            $this->recordButtonInteraction($chatId, $data, $diagnosisActions[$data], false);
        }
        $supportActions += $diagnosisActions;
        if (isset($supportActions[$data])) {
            $this->answerCallbackQuery($callbackId, 'در حال بررسی…');
            if (isset($diagnosisActions[$data])) {
                $this->deleteInteractiveMessage($businessConnectionId, $chatId, $messageId);
            } else {
                // منوی معرفی فرایدی باید در تاریخچه بماند؛ فقط دکمه‌هایش غیرفعال می‌شوند.
                $this->clearInteractiveKeyboard($businessConnectionId, $chatId, $messageId);
            }
            if ($chatId) {
                $reply = $this->processIncomingMessage($supportActions[$data], $chatId);
                if ($reply) {
                    $sent = false;
                    if ($data === 'sec_human' && $messageId) {
                        $sent = $this->editBusinessMessage(
                            $businessConnectionId,
                            $chatId,
                            $messageId,
                            $reply['text'],
                            $reply['buttons'] ?? null
                        );
                    }
                    if (!$sent) {
                        $this->sendBusinessMessage($businessConnectionId, $chatId, $reply['text'], $reply['buttons'] ?? null);
                    }
                }
            }
            return;
        }

        // ۲. انتخاب بازه زمانی
        if (str_starts_with($data, 'sec_duration_')) {
            $days  = (int) str_replace('sec_duration_', '', $data);
            $label = $this->funnel->generateDurationLabel($days);
            $this->recordButtonInteraction($chatId, $data, "انتخاب مدت سرویس: {$label}", false);
            $this->answerCallbackQuery($callbackId, "بسته‌های {$label}");
            $this->deleteInteractiveMessage($businessConnectionId, $chatId, $messageId);

            if ($chatId) {
                $text    = "💎 <b>بسته‌های اشتراک {$label}</b>\n\n" .
                           "حجم موردنیازتان را انتخاب کنید تا خلاصهٔ سفارش نمایش داده شود 🌿";
                $buttons = $this->funnel->getPlansByDurationKeyboard($days);
                $this->sendBusinessMessage($businessConnectionId, $chatId, $text, $buttons);
            }
            return;
        }

        // ۳. بازگشت به لیست دوره‌ها
        // ۴. انتخاب پلن مشخص → ساخت فاکتور در موتور مشترک فروش و ادامه داخل فرایدی
        if (str_starts_with($data, 'sec_buy_plan_')) {
            $this->answerCallbackQuery($callbackId, "ربات فروشگاه");
            $this->deleteInteractiveMessage($businessConnectionId, $chatId, $messageId);
            if ($chatId) {
                $this->sendBusinessMessage(
                    $businessConnectionId, $chatId,
                    "🛍️ <b>خرید و ثبت سفارش</b>\n\nبرای تکمیل سفارش و فعال‌سازی آنی، لطفاً وارد ربات فروشگاه روزنه شوید:",
                    [
                        [['text' => '🛍️ ورود به ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot']],
                        [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']]
                    ]
                );
            }
            return;
        }

        $this->answerCallbackQuery($callbackId, "دریافت شد.");
    }

    private function recordButtonInteraction(int|string $chatId, string $action, ?string $label = null, bool $skipIfUnknown = true): void
    {
        if (!$chatId || ($skipIfUnknown && $label === null)) return;
        $label ??= $action;
        $this->state->recordInteraction($chatId, $action, $label);
        app(\App\Services\BotEventLogger::class)->record('button_selected', 'friday', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }

    private function deleteInteractiveMessage(?string $businessConnectionId, int|string $chatId, ?int $messageId): bool
    {
        if (!$messageId) return false;

        try {
            if ($businessConnectionId) {
                return Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/deleteBusinessMessages", [
                    'business_connection_id' => $businessConnectionId,
                    'message_ids' => [$messageId],
                ])->successful();
            }

            return Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/deleteMessage", [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ])->successful();
        } catch (\Exception $e) {
            Log::warning('Could not remove selected secretary menu', ['message_id' => $messageId]);
            return false;
        }
    }

    private function clearInteractiveKeyboard(?string $businessConnectionId, int|string $chatId, ?int $messageId): bool
    {
        if (!$messageId) return false;
        try {
            $payload = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => json_encode(['inline_keyboard' => []]),
            ];
            if ($businessConnectionId) $payload['business_connection_id'] = $businessConnectionId;
            return Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/editMessageReplyMarkup", $payload)->successful();
        } catch (\Throwable $e) {
            return false;
        }
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
