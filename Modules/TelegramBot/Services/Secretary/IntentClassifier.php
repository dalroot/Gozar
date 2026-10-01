<?php

namespace Modules\TelegramBot\Services\Secretary;

class IntentClassifier
{
    public const HUMAN = 'human';
    public const RECEIPT = 'receipt';
    public const TRIAL = 'trial';
    public const PLANS = 'plans';
    public const STATUS = 'status';
    public const LINK = 'link';
    public const TECHNICAL = 'technical';
    public const PAYMENT = 'payment';
    public const RENEWAL = 'renewal';
    public const TUTORIAL = 'tutorial';
    public const FAQ = 'faq';
    public const GREETING = 'greeting';
    public const THANKS = 'thanks';
    public const UNKNOWN = 'unknown';

    public const SIDE_HUMAN = self::HUMAN;
    public const SIDE_RECEIPT = self::RECEIPT;
    public const SIDE_TRIAL = self::TRIAL;
    public const SIDE_PLANS = self::PLANS;
    public const SIDE_STATUS = self::STATUS;
    public const SIDE_NONE = self::UNKNOWN;

    public function classify(string $message): string
    {
        $msg = $this->normalize($message);
        // Quick commands are intentionally language-neutral so users can type
        // STATUS (or /status) without navigating any buttons.
        if (in_array($msg, ['status', '/status', 'وضعیت', '/وضعیت'], true)) {
            return self::STATUS;
        }
        $rules = [
            self::HUMAN => '/(ادمین|پشتیبان|پشتبان|پشتیبانی|پشتبانی|اپراتور|مدیر|انسان|آدم|خودت\s*جواب\s*نده|شکایت|کسی\s*هست|وصل\s*کن|با\s*پشت.{0,6}(وصل|صحبت|چت)|وصل.{0,10}(ادمین|اپراتور|پشت)|صحبت.{0,10}(ادمین|پشت)|(میخوام|می\s*خوام|می‌خوام|باید).{0,10}(صحبت|چت|حرف).{0,10}(بزنم|کنم)|ربات\s*(کار\s*نمی|خرابه|قاطی|خنگ|متوجه|نمیفهمه|بدرد))/u',
            self::RECEIPT => '/(فیش|رسید|واریز کردم|پرداخت کردم|کارت به کارت کردم|واریزی)/u',
            self::TRIAL => '/(\/trial|تست(?!\s*(سرعت|پینگ|اتصال))|اکانت\s*(تست|رایگان)|رایگان\s*(بده|میخوام|می\s*خوام|می‌خوام))/u',
            self::TECHNICAL => '/(مشکل\s*(اتصال|وصل|نت|اینترنت)|قطعی|وصل\s*(نمیشه|نمی.?شه|نشد)|نمی.?تونم\s*وصل|نمیتونم\s*وصل|قطعه|قطع\s*(هستم|شدم|بودم|هست|شده)|از\s*(دیروز|دیشب|امروز).{0,12}(قط|قطع|وصل)|کار\s*(نمی.?کنه|نمیکنه|نمی.?کنن|نکرد)|بازم\s*نشد|ارور|خطا|کند|پینگ|اینترنت\s*ندارم|آپدیت\s*(نمیشه|نشد)|اپدیت\s*(نمیشه|نشد)|کانفیگ.{0,10}قط|لینک.{0,10}قط|لینک\s*ساب|سابم|سابسکریپشن|update|timeout)/u',
            self::FAQ => '/(صرافی|بایننس|کوکوین|نوبیتکس|ای\s*پی\s*(ثابت|اختصاصی)|ip\s*(ثابت|اختصاصی)|حساب\s*بانکی|ثابت\s*بودن\s*ای\s*پی|نت\s*ملی|اینترنت\s*ملی|قطع.{0,12}بین.?الملل|کیل\s*سوییچ|kill\s*switch|لوکیشن|کشور|سرور\s*(المان|ترکیه|هلند|فنلاند|امریکا)|ضمانت|بازگشت\s*وجه|پس\s*دادن\s*پول|چه\s*حسابی.{0,10}بر.{0,4}گرد|فورس\s*ماژور|sla|پایداری|هدیه)/u',
            self::RENEWAL => '/(تمدید|اضافه کردن زمان|ترافیک اضافه|حجم\s*اضافه|خرید حجم|انتقال.{0,20}(حجم|زمان)|(حجم|زمان).{0,20}منتقل|ریوک|revoke|باطل.{0,12}لینک|لینک.{0,12}باطل)/u',
            self::LINK => '/(لینک|کانفیگ|subscription|سابسکریپشن|کپی لینک|لینک اتصال)/u',
            self::STATUS => '/(وضعیت.{0,8}(اشتراک|سرویس)|(اشتراک|سرویس).{0,12}(من|بررسی|وضعیت)|اشتراکم|سرویسم|هنوز فعاله|انقضا|چقدر\s*مونده|(چند|چه قدر).{0,8}گیگ|گیگ.{0,8}مونده|حجمم|مصرف.{0,8}(من|چقدر)|حجم.{0,8}(باقی|مونده|چقدر)|باقی\s*مانده)/u',
            self::PAYMENT => '/(شماره کارت|کارت بانکی|پرداخت|درگاه|کیف پول|واریز|هزینه رو کجا|شبا|ارز\s*دیجیتال|رمز.?ارز)/u',
            self::PLANS => '/(قیمت|قسمت\s*(بسته|پلن|تعرفه)|خرید|بخرم|میخوام\s*بخرم|می\s*خوام\s*بخرم|می‌خوام\s*بخرم|میخوام\s*(سرویس|اشتراک|کانفیگ|اکانت|فیلتر)|تعرفه|پلن|بسته|وی\s*پی\s*ان|فیلتر\s*شکن|فیلتر|vpn|چنده|چند\s*(تومنه|هست)|هزینه|اکانت|سرویس\s*جدید|ترافیک|(?:\d+|سی|پنجاه|صد)\s*گیگ|(?:یک|دو|سه|\d+)\s*ماهه?)/u',
            self::TUTORIAL => '/(اموزش|آموزش|راهنما|چطور وصل|نصب|دانلود|چه\s*(برنامه|نرم\s*افزار)|برنامه|نرم\s*افزار|اپلیکیشن|اندروید|ایفون|آیفون|ویندوز|مک|ios|v2ray|hiddify|streisand|nekobox|nekoray|clash|sing.?box|shadowrocket|v2box)/u',
            self::THANKS => '/^(ممنون|مرسی|سپاس|تشکر|دمت گرم|دستت درد نکنه|اوکی|باشه|حل شد)[!؟?.,، ]*$/u',
            self::GREETING => '/^(سلام|درود|وقت بخیر|صبح بخیر|شب بخیر|خوبی|حالت چطوره|چطوری|سلامتی|احوال|hi|hello)([!\؟?.,، ]|$)/u',
        ];
        foreach ($rules as $intent => $pattern) {
            if (preg_match($pattern, $msg)) return $intent;
        }
        return self::UNKNOWN;
    }

    public function detectSideEffect(string $message): string
    {
        return $this->classify($message);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = str_replace(['آ', 'أ', 'إ', 'ي', 'ك', 'ة', "\u{200C}"], ['ا', 'ا', 'ا', 'ی', 'ک', 'ه', ' '], $text);
        return preg_replace('/\s+/u', ' ', $text) ?: $text;
    }
}
