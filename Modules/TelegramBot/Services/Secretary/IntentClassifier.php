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
            self::HUMAN => '/(ادمین|پشتیبان\s*انسانی|اپراتور|مدیر|انسان|خودت\s*جواب\s*نده|شکایت|با\s*پشتیبان.{0,10}(وصل|صحبت)|وصل.{0,10}(ادمین|اپراتور|پشتیبان))/u',
            self::RECEIPT => '/(فیش|رسید|واریز کردم|پرداخت کردم|کارت به کارت کردم)/u',
            self::TRIAL => '/(\/trial|تست(?!\s*(سرعت|پینگ|اتصال))|اکانت\s*(تست|رایگان)|رایگان\s*(بده|میخوام|می خوام))/u',
            self::TECHNICAL => '/(مشکل\s*(اتصال|وصل|نت|اینترنت)|قطعی|وصل\s*(نمیشه|نمی.?شه|نشد)|نمی.?تونم\s*وصل|قطعه|قط\s*(هستم|شدم|بودم|هست|شده)|قطع\s*(هستم|شدم|بودم|هست|شده)|از\s*(دیروز|دیشب|امروز).{0,12}(قط|قطع|وصل)|کار\s*(نمی.?کنه|نمیکنه|نمی.?کنن|نکرد)|بازم\s*نشد|ارور|خطا|کند|پینگ|اینترنت\s*ندارم|آپدیت\s*(نمیشه|نشد)|کانفیگ.{0,10}قط|لینک.{0,10}قط|update|timeout)/u',
            self::FAQ => '/(صرافی|بایننس|کوکوین|نوبیتکس|ای\s*پی\s*(ثابت|اختصاصی)|ip\s*(ثابت|اختصاصی)|حساب\s*بانکی|ثابت\s*بودن\s*ای\s*پی|نت\s*ملی|اینترنت\s*ملی|قطع.{0,12}بین.?الملل|کیل\s*سوییچ|kill\s*switch|لوکیشن|کشور|سرور\s*(المان|ترکیه|هلند|فنلاند|امریکا)|ضمانت|بازگشت\s*وجه|پس\s*دادن\s*پول|چه\s*حسابی.{0,10}بر.{0,4}گرد|فورس\s*ماژور|sla|پایداری|هدیه)/u',
            self::RENEWAL => '/(تمدید|اضافه کردن زمان|ترافیک اضافه|حجم\s*اضافه|خرید حجم|انتقال.{0,20}(حجم|زمان)|(حجم|زمان).{0,20}منتقل|ریوک|revoke|باطل.{0,12}لینک|لینک.{0,12}باطل)/u',
            self::LINK => '/(لینک|کانفیگ|subscription|سابسکریپشن|کپی لینک|لینک اتصال)/u',
            self::STATUS => '/(وضعیت.{0,8}(اشتراک|سرویس)|(اشتراک|سرویس).{0,12}(من|بررسی|وضعیت)|اشتراکم|سرویسم|هنوز فعاله|انقضا|چقدر\s*مونده|(چند|چه قدر).{0,8}گیگ|گیگ.{0,8}مونده|حجمم|مصرف.{0,8}(من|چقدر)|حجم.{0,8}(باقی|مونده|چقدر))/u',
            self::PAYMENT => '/(شماره کارت|کارت بانکی|پرداخت|درگاه|کیف پول|واریز|هزینه رو کجا|شبا|ارز\s*دیجیتال|رمز.?ارز)/u',
            self::PLANS => '/(قیمت|قسمت\s*(بسته|پلن|تعرفه)|خرید|تعرفه|پلن|بسته|وی\s*پی\s*ان|فیلتر\s*شکن|vpn|چنده|هزینه|(?:\d+|سی|پنجاه|صد)\s*گیگ|(?:یک|دو|سه|\d+)\s*ماهه?)/u',
            self::TUTORIAL => '/(اموزش|راهنما|چطور وصل|نصب|چه\s*(برنامه|نرم\s*افزار)|برنامه|نرم\s*افزار|اندروید|ایفون|ویندوز|مک|v2ray|hiddify|streisand|nekobox|nekoray|clash|sing.?box|shadowrocket|v2box)/u',
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
