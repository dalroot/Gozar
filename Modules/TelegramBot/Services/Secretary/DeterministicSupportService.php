<?php

namespace Modules\TelegramBot\Services\Secretary;

use App\Services\PaymentAvailabilityService;
use Carbon\Carbon;
use Modules\TelegramBot\Services\BotEntryPointService;
use Modules\TelegramBot\Services\RozanehExperience;

class DeterministicSupportService
{
    public function __construct(
        private ServiceUsageService $usage,
        private SalesFunnelService $sales,
        private RozanehExperience $experience,
        private BotEntryPointService $entryPoint,
        private PaymentAvailabilityService $payments
    )
    {
    }

    public function reply(string $intent, string $message, array $ctx): ?array
    {
        $name = $this->e($ctx['name']);
        $service = $ctx['service'];

        return match ($intent) {
            IntentClassifier::GREETING => $this->greeting($ctx),
            IntentClassifier::THANKS => $this->text("خواهش می‌کنم {$name} جان 🌸 اگر مورد دیگه‌ای هست در خدمتم."),
            IntentClassifier::PLANS => $this->plans($ctx),
            IntentClassifier::TRIAL => $this->trial($ctx),
            IntentClassifier::STATUS => $this->status($message, $ctx),
            IntentClassifier::LINK => $this->link($ctx),
            IntentClassifier::TECHNICAL => $this->technical($message, $ctx),
            IntentClassifier::PAYMENT => $this->payment($message, $ctx),
            IntentClassifier::RECEIPT => $this->receipt(),
            IntentClassifier::RENEWAL => $this->renewal($message, $ctx),
            IntentClassifier::TUTORIAL => $this->tutorial(),
            IntentClassifier::HUMAN => $this->human(),
            IntentClassifier::FAQ => $this->faq($message, $ctx),
            default => null,
        };
    }

    private function greeting(array $ctx): array
    {
        if (!empty($ctx['is_introduced'])) {
            $name = $this->e($ctx['name'] ?? '');
            $hello = $name !== '' ? "سلام {$name} عزیز 👋" : 'سلام، خوش اومدید 👋';
            return $this->text(
                "{$hello}\nچه کمکی از دست فرایدی برمیاد؟ درخواستتون رو بنویسید یا از گزینه‌های زیر استفاده کنید.",
                $this->mainMenuButtons()
            );
        }
        return $this->text(
            $this->experience->assistantIntroduction($ctx['name'] ?? null),
            $this->mainMenuButtons()
        );
    }

    private function status(string $message, array $ctx): array
    {
        $name = $this->e($ctx['name'] ?? 'کاربر گرامی');
        if (!$ctx['is_verified']) {
            return $this->verificationRequired();
        }
        $order = $ctx['service'];
        if (!$order) {
            return $this->text('اشتراک فعالی برای این حساب پیدا نکردم.', $this->salesButton('مشاهده پلن‌ها'));
        }
        $expired = $order->expires_at && now()->gte($order->expires_at);
        $expiry = $order->expires_at ? Carbon::parse($order->expires_at)->format('Y/m/d H:i') : 'نامشخص';
        $plan = $this->e($order->plan?->name ?: 'اشتراک');
        $usage = $this->usage->get($order, $ctx['settings']);
        $volumeExhausted = $usage && $usage['total_bytes'] > 0 && $usage['remaining_bytes'] <= 0;

        if ($expired || $volumeExhausted) {
            $reason = $expired && $volumeExhausted
                ? 'زمان و حجم اشتراک شما به پایان رسیده‌اند'
                : ($expired ? 'زمان اشتراک شما به پایان رسیده است' : 'حجم اشتراک شما به پایان رسیده است');
            $volumeLine = $usage && $usage['total_bytes'] > 0
                ? 'حجم باقی‌مانده: <b>' . $this->formatGb($usage['remaining_bytes']) . " گیگابایت</b>\n"
                : '';
            return $this->text(
                "🔴 <b>کاربر گرامی، وضعیت اشتراک شما در سرورهای روزنه به‌صورت زیر است:</b>\n\n" .
                "پلن: {$plan}\n" .
                "تاریخ پایان: <code>{$expiry}</code>\n" .
                $volumeLine .
                "وضعیت: <b>امکان اتصال ندارد</b>\n\n" .
                "{$reason} و به همین دلیل امکان اتصال برای شما وجود ندارد. برای اتصال مجدد لازم است پلن فعلی را تمدید کنید یا بستهٔ جدیدی فعال نمایید.",
                [
                    [['text' => '🔄 تمدید یا خرید سرویس', 'callback_data' => 'sec_view_durations']],
                    [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
                ]
            );
        }

        $lines = [
            '🟢 <b>کاربر گرامی، وضعیت اشتراک شما در سرورهای روزنه به‌صورت زیر است:</b>',
            '',
            "پلن: {$plan}",
            "تاریخ پایان: <code>{$expiry}</code>",
        ];
        if ($usage && $usage['total_bytes'] > 0) {
            $lines[] = 'حجم کل: <b>' . $this->formatGb($usage['total_bytes']) . ' گیگابایت</b>';
            $lines[] = 'مصرف‌شده: <b>' . $this->formatGb($usage['used_bytes']) . ' گیگابایت</b>';
            $lines[] = 'باقی‌مانده: <b>' . $this->formatGb($usage['remaining_bytes']) . ' گیگابایت</b>';
        } else {
            $total = (float) ($order->plan?->volume_gb ?? 0);
            if ($total > 0) $lines[] = 'حجم اسمی پلن: <b>' . $this->e($total) . ' گیگابایت</b>';
            $lines[] = '';
            $lines[] = 'آمار لحظه‌ای مصرف از پنل در دسترس نبود؛ عدد باقی‌مانده را حدس نمی‌زنم.';
        }

        $lines[] = '';
        $lines[] = 'اشتراک شما از نظر زمان و حجم فعال است. اگر در اتصال مشکل دارید، می‌تونم مرحله‌به‌مرحله بررسی کنم.';
        return $this->text(implode("\n", $lines), [
            [['text' => '🔍 بررسی مشکل اتصال', 'callback_data' => 'diag_start']],
            [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
        ]);
    }

    private function link(array $ctx): array
    {
        if (!$ctx['is_verified']) return $this->verificationRequired();
        if (!$ctx['service']) return $this->text('اشتراک فعالی برای این حساب پیدا نکردم. می‌توانید همین‌جا تست رایگان بگیرید یا بستهٔ جدید فعال کنید.', [
            [['text' => '⚡️ تست رایگان', 'callback_data' => 'sec_get_trial']],
            [['text' => '🛍 خرید سرویس', 'callback_data' => 'sec_view_durations']],
        ]);
        if ($ctx['service']->expires_at && now()->gte($ctx['service']->expires_at)) {
            return $this->text('این سرویس منقضی شده است؛ فرستادن لینک آن کمکی به اتصال نمی‌کند.', $this->salesButton('تمدید سرویس'));
        }
        if (!$ctx['service_link']) {
            return $this->text('لینک اتصال در سفارش قابل استخراج نبود. برای بررسی و بازیابی امن لینک، درخواست پشتیبان انسانی ثبت کنید.', $this->humanButton());
        }
        return $this->text(
            "🔗 <b>لینک اشتراک شما</b>\n\n<pre>" . $this->e($ctx['service_link']) . "</pre>\n" .
            "<i>برای کپی، روی کادر بالا بزنید. این لینک را برای دیگران نفرستید.</i>"
        );
    }

    private function plans(array $ctx): array
    {
        if ($ctx['plans']->isEmpty()) return $this->text('در حال حاضر پلن فعالی ثبت نشده است.');

        $lines = ['🛍 <b>بسته‌های فعال روزنه</b>', '', 'بسته‌ها را می‌توانید بر اساس مدت و حجم موردنیازتان انتخاب کنید 🌿', ''];
        foreach ($ctx['plans']->take(12) as $plan) {
            $volume = $plan->volume_gb ? $this->e($plan->volume_gb) . ' گیگ' : 'نامحدود';
            $duration = $this->duration((int) $plan->duration_days);
            $lines[] = '▫️ <b>' . $this->e(trim($plan->name)) . "</b> — {$duration}";
            $lines[] = "   {$volume} | <code>" . number_format((int) $plan->price) . ' تومان</code>';
        }
        return $this->text(implode("\n", $lines), $this->sales->getDurationKeyboard());
    }

    private function faq(string $message, array $ctx): ?array
    {
        $normalized = mb_strtolower($message, 'UTF-8');
        $normalized = str_replace(['آ', 'أ', 'إ', 'ي', 'ك', 'ة', "\u{200C}"], ['ا', 'ا', 'ا', 'ی', 'ک', 'ه', ' '], $normalized);

        if (preg_match('/(صرافی|بایننس|کوکوین|نوبیتکس|ای\s*پی\s*(ثابت|اختصاصی)|ip\s*(ثابت|اختصاصی)|حساب\s*بانکی)/u', $normalized)) {
            return $this->text(
                "🏦 <b>استفاده برای صرافی و IP ثابت</b>\n\n" .
                "تمام سرویس‌های ما از <b>IP ثابت و تمیزِ اشتراکی</b> استفاده می‌کنند؛ یعنی تعداد مشخصی کاربر روی یک IP ثابت قرار می‌گیرند و خروجی سرویس برای استفاده‌های معمول و صرافی‌ها تمیز نگه داشته می‌شود.\n\n" .
                "اگر حساب بانکی یا صرافی ارزشمندی دارید، پیشنهاد می‌کنیم پیش از خرید با پشتیبانی هماهنگ کنید تا شرایط <b>IP ثابت اختصاصی</b> را توضیح دهند. این سرویس ممکن است هزینهٔ جداگانه داشته باشد.",
                $this->salesButton('پرسش از پشتیبان فروش')
            );
        }

        if (preg_match('/(نت\s*ملی|اینترنت\s*ملی|قطع.{0,12}بین.?الملل|کیل\s*سوییچ|kill\s*switch)/u', $normalized)) {
            return $this->text(
                "🌐 <b>عملکرد هنگام اینترنت ملی</b>\n\n" .
                "ما هم مسیرهای مستقیمِ بهینه داریم و هم سرورهایی که از شبکهٔ داخلی به خارج تونل شده‌اند؛ به همین دلیل هنگام اینترنت ملی برای حفظ اتصال تمهیداتی در نظر گرفته‌ایم.\n\n" .
                "با این حال، هنگام قطع سراسری یا فعال‌شدن Kill Switch امکان تضمین صددرصدی اتصال وجود ندارد."
            );
        }

        if (preg_match('/(ضمانت|بازگشت\s*وجه|پس\s*دادن\s*پول|چه\s*حسابی|حسابی.{0,12}بر.{0,4}گرد|فورس\s*ماژور)/u', $normalized)) {
            return $this->text(
                "✅ <b>شرایط ضمانت و بازگشت وجه</b>\n\n" .
                "اگر در ۲۴ ساعت اول نتوانید به سرویس متصل شوید، بازگشت وجه بدون قیدوشرط انجام می‌شود. پس از آن نیز اگر تیم پشتیبانی ظرف ۴۸ ساعت نتواند مشکل اتصال را برطرف کند، مبلغ حجم باقی‌مانده بر اساس قیمت هر گیگ محاسبه و فقط به همان حسابی که پرداخت از آن انجام شده بازگردانده می‌شود.\n\n" .
                "شرایط فورس‌ماژور مانند جنگ، قطع سراسری یا محدودیت‌های خارج از کنترل مجموعه شامل این ضمانت نیست."
            );
        }

        if (preg_match('/(sla|پایداری)/u', $normalized)) {
            return $this->text(
                "🛡 <b>پایداری و رسیدگی به اختلال</b>\n\n" .
                "سرویس‌های روزنه با مسیرهای جایگزین و پایش فنی ارائه می‌شوند و در صورت اختلال، تیم پشتیبانی برای بررسی و برقراری دوباره اتصال اقدام می‌کند. درصد پایداری یا زمان رفع قطعی تضمین نمی‌شود؛ چون محدودیت‌های سراسری و شرایط فورس‌ماژور خارج از کنترل مجموعه‌اند."
            );
        }

        if (preg_match('/(هدیه)/u', $normalized)) {
            return $this->text('پس از اولین خرید، یک گیگابایت هدیه برای کاربرانی که تست گرفته‌اند در نظر گرفته شده است. فعال‌سازی فنی این هدیه در حال تکمیل است؛ تا قبل از نهایی‌شدن، برای اعمال آن با پشتیبانی هماهنگ کنید.', $this->humanButton());
        }

        if (preg_match('/(لوکیشن|کشور|المان|آلمان|ترکیه|هلند|فنلاند|امریکا|آمریکا)/u', $normalized)) {
            $multiLocation = filter_var($ctx['settings']->get('enable_multilocation', false), FILTER_VALIDATE_BOOLEAN);
            if (!$multiLocation) {
                return $this->text(
                    "🌍 <b>لوکیشن سرویس</b>\n\n" .
                    "در حال حاضر انتخاب دستی کشور در سیستم فروش فعال نیست و پلن‌ها با مسیر پیش‌فرض سامانه تحویل می‌شوند؛ بنابراین نمی‌توانم لوکیشن آلمان را به‌صورت انتخابی تضمین کنم.\n\n" .
                    "<blockquote>اگر کشور مشخصی برایتان ضروری است، قبل از خرید از پشتیبان موجودی همان لوکیشن را بپرسید.</blockquote>",
                    $this->salesButton('استعلام لوکیشن')
                );
            }
        }

        return null;
    }

    private function technical(string $message, array $ctx): array
    {
        $order = $ctx['service'];
        if ($order?->expires_at && now()->gte($order->expires_at)) {
            return $this->status($message, $ctx);
        }
        if (!$ctx['is_verified'] || !$order) {
            return $this->text(
                "برای این گفتگو هنوز سرویس فعالی ثبت نشده است. اگر قبلاً خرید داشته‌اید، پشتیبان انسانی می‌تواند حساب را تطبیق دهد؛ در غیر این صورت تست یا خرید جدید را همین‌جا شروع کنید.",
                [
                    [['text' => '⚡️ تست رایگان', 'callback_data' => 'sec_get_trial']],
                    [['text' => '🛍 خرید سرویس', 'callback_data' => 'sec_view_durations']],
                    [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
                ]
            );
        }

        $normalized = mb_strtolower($message, 'UTF-8');
        if (preg_match('/(اندروید|v2ray|v2rayng|هیدیفای|hiddify|nekobox)/u', $normalized)) {
            return $this->text("باشه، روی اندروید بررسی می‌کنیم. داخل برنامه اول <b>Update subscription</b> را بزنید، بعد یک کانفیگ دیگر را انتخاب کنید. اگر باز هم وصل نشد، متن خطا یا اسکرین‌شات صفحه را بفرستید.");
        }
        if (preg_match('/(ایفون|آیفون|ios|v2box|streisand|shadowrocket)/u', $normalized)) {
            return $this->text("باشه، روی آیفون بررسی می‌کنیم. یک بار سابسکریپشن را به‌روزرسانی کنید و اتصال را با اینترنت دیگری امتحان کنید. اگر حل نشد، نام برنامه و متن خطا را بفرستید.");
        }

        return $this->text(
            "حتماً، مرحله‌به‌مرحله بررسیش می‌کنیم. سرویس‌تان از نظر زمان اعتبار فعاله.\n\n" .
            "اول بفرمایید با چه دستگاه و چه برنامه‌ای وصل می‌شید؟ اگر خطایی می‌بینید، متن یا عکسش را هم بفرستید."
        );
    }

    private function payment(string $message, array $ctx): array
    {
        $labels = $this->payments->userFacingLabels($ctx['settings']);
        $methods = $this->joinPersian($labels);
        $methodText = $methods !== ''
            ? "روش‌های فعال فعلی: <b>{$this->e($methods)}</b>."
            : 'روش پرداخت فعالی در حال حاضر ثبت نشده است؛ لطفاً با پشتیبانی انسانی در ارتباط باشید.';

        return $this->text(
            "برای پرداخت ابتدا بستهٔ موردنظرتان را انتخاب کنید. مبلغ نهایی و فقط روش‌هایی که واقعاً آمادهٔ پرداخت هستند در همان سفارش نمایش داده می‌شوند.\n\n{$methodText}",
            $this->salesButton('انتخاب پلن و پرداخت')
        );
    }

    private function receipt(): array
    {
        return $this->text('برای ثبت رسید، ابتدا سفارش را همین‌جا انتخاب کنید و روش کارت‌به‌کارت را بزنید؛ سپس تصویر یا شماره پیگیری را در همین گفتگو بفرستید.', $this->salesButton('انتخاب بسته و ثبت سفارش'));
    }

    private function renewal(string $message, array $ctx): array
    {
        if (preg_match('/(ریوک|revoke|باطل.{0,12}لینک|لینک.{0,12}باطل)/ui', $message)) {
            return $this->text(
                'هر زمان بخواهید می‌توانید لینک فعلی سرویس را ریوک کنید. با این کار لینک‌ها و کانفیگ‌های قبلی باطل می‌شوند و لینک تازه دریافت می‌کنید؛ حجم و زمان سرویس تغییر نمی‌کند. برای امنیت حساب، لینک جدید را فقط روی دستگاه‌های خودتان وارد کنید.',
                $this->salesButton('مدیریت سرویس و ریوک لینک')
            );
        }

        $message = $ctx['service']
            ? 'حتماً 🌿 تمدید روی همان اشتراک فعلی انجام می‌شود. حجم باقی‌مانده به دورهٔ بعد منتقل می‌شود، اما زمان باقی‌مانده منتقل نخواهد شد. برای مشاهده بسته‌ها و ادامه پرداخت از دکمهٔ زیر استفاده کنید.'
            : 'حتماً 🌿 ابتدا بستهٔ مناسب را انتخاب کنید تا مراحل ثبت سفارش و پرداخت برایتان نمایش داده شود.';
        return $this->text($message, $this->sales->getDurationKeyboard());
    }

    private function trial(array $ctx): array
    {
        $enabled = filter_var($ctx['settings']->get('trial_enabled', false), FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) {
            return $this->text('در حال حاضر ارائهٔ تست رایگان غیرفعال است.');
        }

        $limit = (int) $ctx['settings']->get('trial_limit_per_user', 1);
        $taken = (int) ($ctx['user']?->trial_accounts_taken ?? 0);
        if ($ctx['user'] && $taken >= $limit) {
            return $this->text('سهمیهٔ تست این حساب قبلاً استفاده شده است.', $this->salesButton('مشاهده پلن‌ها'));
        }

        return $this->text(
            "بله 🌿 یک گیگابایت تست رایگان بدون محدودیت زمانی دارید تا با خیال راحت کیفیت اتصال را بررسی کنید. این تست برای هر شناسهٔ تلگرام فقط یک‌بار ارائه می‌شود. اگر بعد از تست خرید کنید، یک گیگابایت هدیهٔ دیگر هم به حسابتان اضافه می‌شود.",
            [[['text' => '⚡️ دریافت تست رایگان', 'callback_data' => 'sec_get_trial']]]
        );
    }

    private function tutorial(): array
    {
        return $this->text("نام برنامه و سیستم‌عامل‌تان را بفرستید؛ مثلاً «V2RayNG اندروید»، «Hiddify آیفون» یا «Windows».\n\nروش عمومی: لینک اشتراک را کپی کنید، در برنامه از بخش Import from clipboard اضافه کنید و سپس Update subscription را بزنید.");
    }

    private function human(): array
    {
        return $this->text('درخواست ارتباط با پشتیبان انسانی ثبت شد. سابقهٔ گفتگو و بررسی‌های انجام‌شده برای پشتیبان ارسال شده است؛ نیازی نیست توضیحات قبلی را دوباره بنویسید و گفتگو از همین‌جا ادامه پیدا می‌کند.');
    }

    private function mainMenuButtons(): array
    {
        return $this->experience->assistantMenu();
    }

    private function humanButton(): array
    {
        return [[['text' => '👨🏻‍💻 ارتباط با پشتیبان انسانی', 'callback_data' => 'sec_human']]];
    }

    private function verificationRequired(): array
    {
        return $this->text(
            "برای این گفتگو هنوز سرویس روزنه‌ای ثبت نشده است. نیازی نیست به ربات دیگری بروید؛ تست رایگان یا خرید را همین‌جا شروع کنید تا حساب به‌صورت خودکار ساخته شود. هر سؤال دیگری هم دارید می‌توانید بنویسید.",
            [
                [['text' => '⚡️ تست رایگان', 'callback_data' => 'sec_get_trial']],
                [['text' => '🛍 خرید سرویس', 'callback_data' => 'sec_view_durations']],
                [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
            ]
        );
    }

    private function salesButton(string $label): array
    {
        return [[['text' => $label, 'callback_data' => 'sec_view_durations']]];
    }

    private function text(string $text, ?array $buttons = null): array
    {
        return ['text' => $text, 'buttons' => $buttons];
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function joinPersian(array $items): string
    {
        if (count($items) < 2) return $items[0] ?? '';
        $last = array_pop($items);
        return implode('، ', $items) . ' و ' . $last;
    }

    private function formatGb(int $bytes): string
    {
        return number_format($bytes / 1073741824, 2, '.', '');
    }

    private function duration(int $days): string
    {
        return match ($days) {
            30 => '۱ ماهه',
            60 => '۲ ماهه',
            90 => '۳ ماهه',
            180 => '۶ ماهه',
            365 => '۱ ساله',
            default => $days . ' روزه',
        };
    }
}
