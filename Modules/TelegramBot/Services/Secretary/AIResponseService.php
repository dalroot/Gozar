<?php

namespace Modules\TelegramBot\Services\Secretary;

use App\Models\Plan;
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
        $this->openCodeApiKey = env('OPENCODE_API_KEY', 'sk-Gfhnig2gO7okIj4eEu0GDgFY7o6knyWKu6BxqmaP5cZRPQ5PPU2Dc5ZcGE3Ehk8o');
        $this->openCodePrimaryModel = env('OPENCODE_MODEL', 'mimo-v2.5-free');
        $this->openCodeFallbackModel = env('OPENCODE_FALLBACK_MODEL', 'laguna-s-2.1-free');

        $this->openRouterBaseUrl = env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1');
        $this->openRouterApiKey = env('OPENROUTER_API_KEY', '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');
        $this->openRouterModel = env('OPENROUTER_MODEL', 'minimax/minimax-m2.7:free');
    }

    /**
     * تمیزکاری و ساختاردهی خروجی برای تلگرام
     */
    public function formatForTelegram(string $text): string
    {
        $text = trim($text);
        // تبدیل مارک‌داون‌های رایج به فرمت خوانای تلگرام
        $text = preg_replace('/\*\*(.*?)\*\*/u', '<b>$1</b>', $text);
        $text = preg_replace('/`(.*?)`/u', '<code>$1</code>', $text);
        return $text;
    }

    /**
     * سیستم‌پرامپت طبیعی و فاقد تبلیغات ناخواسته
     */
    protected function buildSystemPrompt(array $ctx): string
    {
        $name         = $ctx['fullName'];
        $card         = $ctx['cardNumber'] ?: 'در حال به‌روزرسانی';
        $holder       = $ctx['cardHolder'];
        $brand        = $ctx['brandName'] ?: 'روزنه';
        $trialH       = $ctx['trialHours'];
        $trialMb      = $ctx['trialMb'];
        $plans        = $ctx['plans'];
        $sub          = $ctx['subscription'];
        $isIntroduced = $ctx['isIntroduced'] ?? false;

        $plansText = '';
        foreach ($plans as $p) {
            $vol   = $p->volume_gb ? "{$p->volume_gb} گیگ" : "نامحدود";
            $days  = $p->duration_days ? "{$p->duration_days} روزه" : "";
            $price = number_format((int)($p->price ?? 0));
            $plansText .= "  • {$p->name} ({$vol} - {$days}): {$price} تومان\n";
        }

        if ($sub) {
            $subText = "اطلاعات اشتراک فعال کاربر در سیستم:\n" .
                       "  - نام پلن: {$sub['plan']}\n" .
                       "  - تاریخ انقضا: {$sub['expires']}\n" .
                       "  - لینک سابسکریپشن: {$sub['link']}\n" .
                       "  - وضعیت: {$sub['status']}";
        } else {
            $subText = "کاربر در حال حاضر اشتراک فعالی در دیتابیس ندارد.";
        }

        $introRule = $isIntroduced
            ? "تو قبلاً خودت را به این کاربر معرفی کرده‌ای. به هیچ عنوان مجدداً نگو «من فرایدی هستم» و مجدداً سلام نکن."
            : "این اولین پیام این مکالمه است. خیلی کوتاه در ابتدای پیام بگو: «من فرایدی، دستیار هوشمند {$brand} هستم.»";

        return <<<PROMPT
تو «فرایدی» هستی؛ دستیار هوشمند و پشتیبان واقعی در تلگرام «{$brand}».
شخصیت و لحن: بسیار مودب، آرام، انسانی، مسلط و صمیمی (دقیقاً مانند یک پشتیبان انسانی کاربلد، نه یک ربات خشک و اسپمر).

━━━━━━━━━━━━━━━━
قانون معرفی:
{$introRule}

━━━━━━━━━━━━━━━━
اطلاعات مخاطب:
نام مخاطب: {$name}
{$subText}

━━━━━━━━━━━━━━━━
اطلاعات تعرفه‌ها (فقط در صورتی که کاربر صریحاً درباره قیمت/خرید/پلن سوال کرد ذکر کن):
{$plansText}
اکانت تست: {$trialH} ساعته با حجم {$trialMb} مگابایت

━━━━━━━━━━━━━━━━
اطلاعات پرداخت (فقط در صورت درخواست شماره کارت):
شماره کارت: {$card}
به نام: {$holder}

━━━━━━━━━━━━━━━━
قوانین حیاتی رفتار و نگارش (مهم‌ترین بخش):
۱. ممنوعیت تبلیغ و گزینه‌سازی اجباری: در انتهای پیام‌ها به هیچ عنوان لیست گزینه‌های خرید، پلن، تست یا منوی خدمات را ردیف نکن! فقط به چیزی که کاربر پرسیده پاسخ بده.
۲. احوال‌پرسی خالص: اگر کاربر گفت «سلام»، «حالت چطوره»، «کجایی» و چت دوستانه کرد، فقط با محبت و صمیمیت جواب احوال‌پرسی را بده. اصلاً حرفی از وی‌پی‌ان، پلن و فروش نزن.
۳. بررسی وضعیت اشتراک: اگر کاربر از وضعیت اشتراکش پرسید، فقط اطلاعات دقیق اشتراک او را بگو و اگر لینک خواست لینک اتصال را بده.
۴. رفع مشکل فنی/قطعی: اگر کاربر گفت خطاست یا قطعه، فقط راهنمای کوتاه بده (۱. آپدیت سابسکریپشن ۲. بررسی اتصال اینترنت).
۵. پاسخ به پیام‌های چندبخشی: اگر کاربر همزمان ۲ یا ۳ موضوع مختلف گفت، به تمام بخش‌ها در یک پیام واحد، مرتب و با پاراگراف‌بندی تمیز پاسخ بده.
۶. همیشه جملات را کامل به پایان برسان.
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

        $ctx = array_merge($paymentData, [
            'fullName'     => $userData['fullName'],
            'plans'        => Plan::where('is_active', true)->orderBy('duration_days')->get(),
            'subscription' => $subscription,
            'isIntroduced' => $isIntroduced,
        ]);

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
            ])->timeout(15)->post("{$baseUrl}/chat/completions", [
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
                return !empty($content) ? $content : null;
            }
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

    public function responseMentionsPlans(string $aiReply): bool
    {
        return (bool) preg_match('/(پلن|تعرفه|قیمت|تومان|گیگابایت|گیگ|خرید|اشتراک|بسته)/u', $aiReply);
    }

    public function responseMentionsTrial(string $aiReply): bool
    {
        return (bool) preg_match('/(تست رایگان|اکانت تست|ساعته)/u', $aiReply);
    }
}
