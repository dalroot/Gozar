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
            $hello = ($name !== '' && $name !== 'همراه گرامی') ? "سلام {$name} عزیز" : 'سلام، وقت بخیر';
            return $this->text(
                "{$hello} 🌸 در خدمتم. درخواست یا پیامتون رو بفرمایید تا بررسی بشه."
            );
        }
        return $this->text(
            $this->experience->assistantIntroduction($ctx['name'] ?? null),
            $this->mainMenuButtons()
        );
    }

    private function status(string $message, array $ctx): array
    {
        $name = $this->e($ctx['name'] ?? 'همراه گرامی');
        $order = $ctx['service'];

        if (!$ctx['is_verified'] || !$order) {
            return $this->text(
                "🔍 <b>استعلام وضعیت سرویس روزنه</b>\n\n" .
                "روی این حساب تلگرام، هنوز اشتراک فعالی ثبت نشده است 🌿\n\n" .
                "اگر با شماره یا اکانت دیگری خرید کرده‌اید، لطفاً <b>نام کاربری کانفیگ یا لینک اشتراک</b> خود را ارسال فرمایید تا همکاران پشتیبانی بررسی نمایند.\n\n" .
                "همچنین می‌توانید از گزینه‌های زیر استفاده فرمایید:",
                [
                    [['text' => '🛍️ خرید اشتراک در ربات فروشگاه', 'url' => 'https://t.me/RoozanehNetBot']],
                    [['text' => '👨🏻‍💻 ارتباط با پشتیبان انسانی', 'callback_data' => 'sec_human']],
                    [['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'sec_main_menu']],
                ]
            );
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
                    [['text' => '🔄 تمدید در ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot']],
                    [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
                    [['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'sec_main_menu']],
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
            [['text' => '🔗 دریافت لینک اشتراک', 'callback_data' => 'sec_get_link']],
            [['text' => '🔄 تمدید در ربات فروشگاه', 'url' => 'https://t.me/RoozanehNetBot']],
            [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
            [['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'sec_main_menu']],
        ]);
    }

    private function link(array $ctx): array
    {
        if (!$ctx['is_verified'] || !$ctx['service']) return $this->verificationRequired();
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
        return $this->text(
            "🛍️ <b>تعرفه‌ها و خرید اشتراک روزنه</b>\n\n" .
            "تمامی پلن‌های پرسرعت روزنه با تانل اختصاصی، سفارش آنی و تست رایگان در <b>ربات فروشگاه</b> ارائه می‌شوند 🌿\n\n" .
            "جهت مشاهده آخرین قیمت‌ها و خرید، دکمهٔ زیر را لمس نمایید:",
            [
                [['text' => '🛍️ ورود به ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot']],
                [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
                [['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'sec_main_menu']],
            ]
        );
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
                "🛠️ <b>بررسی مشکل اتصال</b>\n\n" .
                "روی این حساب تلگرام، هنوز اشتراک فعالی ثبت نشده است 🌿\n\n" .
                "اگر با اکانت یا شماره دیگری خرید کرده‌اید، لطفاً <b>نام کاربری کانفیگ یا لینک اشتراک</b> خود را ارسال فرمایید تا بررسی شود.\n\n" .
                "همچنین می‌توانید از گزینه‌های زیر استفاده فرمایید:",
                [
                    [['text' => '🛍️ ورود به ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot']],
                    [['text' => '👨🏻‍💻 ارتباط با پشتیبان انسانی', 'callback_data' => 'sec_human']],
                    [['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'sec_main_menu']],
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
        return $this->text(
            "💳 تمامی روش‌های پرداخت (کارت‌به‌کارت و کیف پول) به‌صورت خودکار در <b>ربات فروشگاه روزنه</b> پردازش و فعال‌سازی می‌شوند.\n\nجهت ثبت سفارش یا پرداخت، از دکمهٔ زیر وارد ربات فروشگاه شوید:",
            [
                [['text' => '🛍️ ورود به ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot']],
                [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']]
            ]
        );
    }

    private function receipt(): array
    {
        return $this->text(
            "رسیدهای کارت‌به‌کارت که در ربات فروشگاه ثبت شده‌اند، در صف تایید واحد مالی قرار دارند و طبق زمان‌بندی بررسی و تایید می‌شوند.\n\nاگر سوالی دارید یا می‌خواهید سفارشتان توسط پشتیبان انسانی بررسی شود، شماره سفارش یا پیام خود را همین‌جا ارسال نمایید 🌿",
            [
                [['text' => '👨🏻‍💻 ارتباط با پشتیبان انسانی', 'callback_data' => 'sec_human']],
                [['text' => '🛍️ بازگشت به ربات فروشگاه', 'url' => 'https://t.me/RoozanehNetBot']]
            ]
        );
    }

    private function renewal(string $message, array $ctx): array
    {
        return $this->text(
            "🔄 <b>تمدید اشتراک روزنه</b>\n\n" .
            "برای تمدید سرویس و افزایش اعتبار اشتراک فعلی، لطفاً به بخش مدیریت سرویس در ربات فروشگاه مراجعه فرمایید:",
            [
                [['text' => '🔄 تمدید در ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot']],
                [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']]
            ]
        );
    }

    private function trial(array $ctx): array
    {
        return $this->text(
            "⚡️ <b>دریافت اکانت تست رایگان</b>\n\n" .
            "سرویس تست رایگان به‌صورت خودکار در <b>ربات فروشگاه روزنه</b> صادر و تحویل داده می‌شود.\nجهت دریافت آنی کانفیگ تست، دکمهٔ زیر را لمس نمایید 🌿",
            [
                [['text' => '🎁 دریافت تست در ربات فروشگاه', 'url' => 'https://t.me/RoozanehNetBot']]
            ]
        );
    }

    public function tutorial(): array
    {
        return $this->text(
            "📱 <b>نرم‌افزارها و راهنمای اتصال روزنه</b>\n\n" .
            "برای استفاده از اشتراک روزنه، برنامه‌های پیشنهادی متناسب با سیستم‌عامل شما:\n\n" .
            "🔹 <b>اندروید (Android):</b>\n" .
            "• برنامه <b>v2rayNG</b> یا <b>Hiddify</b> (قابل دانلود از گوگل‌پلی یا گیتهاب)\n\n" .
            "🔹 <b>آیفون (iOS):</b>\n" .
            "• برنامه <b>Streisand</b>، <b>V2Box</b> یا <b>Foxray</b> (قابل دانلود از اپ‌استور)\n\n" .
            "🔹 <b>ویندوز (Windows):</b>\n" .
            "• برنامه <b>v2rayN</b> یا <b>Hiddify Next</b>\n\n" .
            "📌 <b>نحوه اتصال:</b> لینک اشتراک را کپی کنید، وارد برنامه شوید و گزینه افزودن از کلیپ‌بورد (Import from clipboard) یا علامت <b>+</b> را بزنید و دکمه اتصال را لمس کنید 🌿",
            [
                [['text' => '🔗 دریافت لینک اشتراک', 'callback_data' => 'sec_get_link']],
                [['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']],
                [['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'sec_main_menu']],
            ]
        );
    }

    public function human(): array
    {
        return $this->text(
            "👨🏻‍💻 <b>درخواست ارتباط با پشتیبان انسانی ثبت شد 🌸</b>\n\n" .
            "پیام و سابقه این گفتگو برای همکاران پشتیبانی ارسال شد. نیازی به توضیح دوباره نیست؛ همکاران ما به زودی همین‌جا پاسخگوی شما خواهند بود 🌿",
            [
                [['text' => '🛍️ ورود به ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot']],
                [['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'sec_main_menu']],
            ]
        );
    }

    private function mainMenuButtons(): array
    {
        return $this->experience->assistantMenu();
    }

    private function humanButton(): array
    {
        return [[['text' => '👨🏻‍💻 ارتباط با پشتیبان (@RoozanehHelp)', 'url' => 'https://t.me/RoozanehHelp']]];
    }

    private function verificationRequired(): array
    {
        return $this->text(
            "روی این حساب تلگرام، اشتراک ثبت‌شده‌ای پیدا نکردم 🌿\n\n" .
            "اگر با شماره یا اکانت دیگری خرید کرده‌اید، لطفاً <b>نام کاربری کانفیگ یا شناسه سفارش</b> خود را بنویسید تا همکاران پشتیبانی دستی بررسی و متصل نمایند.",
            [
                [['text' => '👨🏻‍💻 ارتباط با پشتیبان انسانی', 'callback_data' => 'sec_human']],
                [['text' => '🛍️ ورود به ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot']],
            ]
        );
    }

    private function salesButton(string $label): array
    {
        return [[['text' => "🛍️ {$label} در ربات فروشگاه", 'url' => 'https://t.me/RoozanehNetBot']]];
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
