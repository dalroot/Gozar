<?php

namespace Modules\TelegramBot\Services\Secretary;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIResponseService
{
    protected string $openCodeBaseUrl;
    protected string $openCodeApiKey;
    protected string $openCodePrimaryModel;
    protected string $openCodeFallbackModel;

    protected ?string $openRouterBaseUrl;
    protected ?string $openRouterApiKey;
    protected ?string $openRouterModel;

    public function __construct()
    {
        $this->openCodeBaseUrl = env('OPENCODE_BASE_URL', 'https://opencode.ai/zen/v1');
        $this->openCodeApiKey = env('OPENCODE_API_KEY', '');
        $this->openCodePrimaryModel = env('OPENCODE_MODEL', 'mimo-v2.5-free');
        $this->openCodeFallbackModel = env('OPENCODE_FALLBACK_MODEL', 'laguna-s-2.1-free');

        $this->openRouterBaseUrl = env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1');
        $this->openRouterApiKey = env('OPENROUTER_API_KEY', '');
        $this->openRouterModel = env('OPENROUTER_MODEL', 'minimax/minimax-m2.7:free');
    }

    /**
     * تمیزکاری و ساختاردهی خروجی برای تلگرام
     */
    public function formatForTelegram(string $text): string
    {
        // مدل اجازه تولید HTML خام ندارد؛ ابتدا خروجی را امن و سپس دو قالب ساده را تبدیل می‌کنیم.
        $text = htmlspecialchars(strip_tags(trim($text)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = preg_replace('/\*\*(.*?)\*\*/u', '<b>$1</b>', $text);
        $text = preg_replace('/`(.*?)`/u', '<code>$1</code>', $text);
        return $text;
    }

    /**
     * سیستم‌پرامپت طبیعی و فاقد تبلیغات ناخواسته
     */
    protected function buildSystemPrompt(array $ctx): string
    {
        $name = $ctx['fullName'];

        return <<<PROMPT
تو همکار پشتیبانی روزنه در یک گفت‌وگوی تلگرامی هستی. مخاطب «{$name}» است.

طبیعی، کوتاه و دقیق جواب بده؛ مثل یک پشتیبان باتجربه. نام خودت یا برند را تکرار نکن، بی‌دلیل سلام نکن و جمله‌های قالبی مثل «در خدمتم» را پشت سر هم نیاور.
اگر مسئله مبهم است فقط یک سؤال روشن‌کننده بپرس. از تاریخچه برای ادامهٔ همان موضوع استفاده کن و حرف قبلی را تکرار نکن.

قوانین قطعی:
- هرگز URL، لینک تست، لینک اشتراک، کد فعال‌سازی، کانفیگ، شماره کارت، قیمت، حجم باقی‌مانده یا تاریخ انقضا تولید نکن؛ این موارد فقط توسط سرویس‌های قطعی سامانه پاسخ داده می‌شوند.
- هیچ واقعیت مربوط به حساب مشتری را حدس نزن.
- وعدهٔ انجام کاری که ابزارش را نداری نده.
- اگر سؤال عملیاتی را دقیق متوجه نشدی، موضوع را با یک سؤال کوتاه مشخص کن.
- پاسخ حداکثر سه پاراگراف کوتاه باشد.
PROMPT;
    }

    /**
     * تولید پاسخ هوشمند
     */
    public function generateReply(
        string $userMessage,
        array  $userData,
        array  $paymentData,
        array  $chatHistory = [],
        bool   $isIntroduced = false
    ): string {
        $subscription = null;
        $user = $userData['user'] ?? null;
        if ($user) {
            $order = Order::where('user_id', $user->id)
                ->where('status', 'paid')
                ->with('plan')
                ->latest()
                ->first();

            if ($order) {
                $isExpired = $order->expires_at && \Carbon\Carbon::parse($order->expires_at)->isPast();
                $subLink = $order->subscription_url ?? ($order->panel_username ? "https://sub.rozaneh.live/sub/{$order->panel_username}" : "در دسترس در ربات اصلی");
                $subscription = [
                    'plan'    => $order->plan?->name ?? 'اشتراک',
                    'expires' => $order->expires_at ? \Carbon\Carbon::parse($order->expires_at)->format('Y/m/d') : 'نامشخص',
                    'link'    => $subLink,
                    'status'  => $isExpired ? 'منقضی شده' : 'فعال',
                ];
            }
        }

        // اطلاعات حساس/عملیاتی عمداً وارد مدل آزاد نمی‌شوند.
        $ctx = [
            'fullName' => $userData['fullName'],
            'isIntroduced' => $isIntroduced,
        ];

        $systemPrompt = $this->buildSystemPrompt($ctx);

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($chatHistory as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        // 1. اولویت اول: مدل سریع و زنده OpenCode
        $reply = $this->callModel($this->openCodeBaseUrl, $this->openCodeApiKey, $this->openCodePrimaryModel, $messages);

        // 2. اولویت دوم: مدل پشتیبان OpenCode
        if (empty($reply) && !empty($this->openCodeFallbackModel)) {
            $reply = $this->callModel($this->openCodeBaseUrl, $this->openCodeApiKey, $this->openCodeFallbackModel, $messages);
        }

        // 3. اولویت سوم: کلید کاربر در OpenRouter
        if (empty($reply) && !empty($this->openRouterApiKey)) {
            $reply = $this->callModel($this->openRouterBaseUrl, $this->openRouterApiKey, $this->openRouterModel, $messages);
        }

        // 4. فال‌بک محلی در صورت بروز هرگونه مشکل شبکه
        if (empty($reply)) {
            $reply = $this->localFallback($userMessage, $userData['fullName'], $subscription);
        }

        return $this->formatForTelegram($reply);
    }

    protected function callModel(string $baseUrl, string $apiKey, string $model, array $messages): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->timeout(12)->post("{$baseUrl}/chat/completions", [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.4,
                'max_tokens'  => 550,
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'] ?? null;
                if ($content) {
                    // حذف ردپای تامل / reasoning در صورت وجود
                    $content = preg_replace('/<think>.*?<\/think>/s', '', $content);
                    $content = trim($content);
                }
                if (!empty($content) && !$this->isGroundedModelReply($content)) {
                    Log::warning('Rejected ungrounded secretary AI reply', [
                        'model' => $model,
                        'reason' => 'contained protected operational data',
                    ]);
                    return null;
                }
                return !empty($content) ? $content : null;
            }
            Log::warning("AI provider rejected request", [
                'model' => $model,
                'base_url' => $baseUrl,
                'status' => $response->status(),
                'error' => $response->json('error.message'),
            ]);
        } catch (\Exception $e) {
            Log::warning("AI [{$model}] on [{$baseUrl}] error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * فال‌بک تمیز، طبیعی و کاملاً بدون تبلیغات
     */
    protected function localFallback(string $message, string $name, ?array $sub = null): string
    {
        $msg = mb_strtolower($message, 'UTF-8');

        if (preg_match('/(چطوری|حالت چطوره|خوبی|چه خبر|کجایی)/u', $msg)) {
            return "ممنون {$name} عزیز، من خوبم. امیدوارم حال شما هم عالی باشه 🌸";
        }

        if (preg_match('/(سلام|درود|hi|hello)/u', $msg)) {
            return "سلام {$name} گرامی 🌸 در خدمتم، امری باشه بفرمایید.";
        }

        if (preg_match('/(اشتراک|وضعیت|کانفیگ|سرویس)/u', $msg)) {
            if ($sub) {
                return "{$name} گرامی، وضعیت اشتراک شما در سیستم:\n" .
                       "🔹 پلن: {$sub['plan']}\n" .
                       "🔹 وضعیت: {$sub['status']}\n" .
                       "🔹 تاریخ انقضا: {$sub['expires']}\n" .
                       (!empty($sub['link']) ? "🔗 لینک اتصال: {$sub['link']}" : "");
            }
            return "{$name} عزیز، در حال حاضر اشتراک فعالی به نام شما در سیستم ثبت نشده است. اگر مایل به تهیه اشتراک یا دریافت تست هستید، در خدمتم 🌸";
        }

        if (preg_match('/(قیمت|تعرفه|پلن|خرید|بسته)/u', $msg)) {
            return "برای مشاهده لیست پلن‌ها و تعرفه‌ها می‌توانید از دکمه‌های زیر استفاده کنید، یا بفرمایید چه حجمی مد نظرتونه؟ 🌸";
        }

        return "در خدمتم {$name} جان، پیامتون رو دریافت کردم 🌿";
    }

    private function isGroundedModelReply(string $reply): bool
    {
        $forbidden = [
            '~(?:https?://|tg://|t\.me/)~iu',
            '/(کد\s*فعال.?سازی|لینک\s*(اتصال|اشتراک|تست)|شماره\s*کارت)/u',
            '/(?:\d[\s-]*){16}/u',
        ];
        foreach ($forbidden as $pattern) {
            if (preg_match($pattern, $reply)) return false;
        }
        return true;
    }
}
