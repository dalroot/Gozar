<?php

namespace Modules\TelegramBot\Services\Secretary;

/**
 * IntentClassifier — فقط برای side effects (notify admin, receipt)
 * و تصمیم نمایش دکمه‌ها استفاده می‌شود.
 * تولید متن پاسخ کاملاً برعهده AI است.
 */
class IntentClassifier
{
    public const SIDE_HUMAN   = 'human';
    public const SIDE_RECEIPT = 'receipt';
    public const SIDE_TRIAL   = 'trial';
    public const SIDE_PLANS   = 'plans';
    public const SIDE_STATUS  = 'status';
    public const SIDE_NONE    = 'none';

    protected function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = str_replace(
            ['آ','أ','إ','ي','ك','ة','‌',"\u{200C}"],
            ['ا','ا','ا','ی','ک','ه',' ',' '],
            $text
        );
        return preg_replace('/\s+/', ' ', $text);
    }

    /**
     * تشخیص side effect پیام کاربر
     */
    public function detectSideEffect(string $message): string
    {
        $msg = $this->normalize($message);

        // ۱. درخواست پشتیبان انسانی
        if (preg_match('/(ادمین|پشتیبان|انسان|مدیر|وصل کن|صاحبت|خودت جواب نده)/u', $msg)) {
            return self::SIDE_HUMAN;
        }

        // ۲. ارسال فیش
        if (preg_match('/(فیش|واریز کردم|پرداخت کردم|کارت به کارت|رسید)/u', $msg)) {
            return self::SIDE_RECEIPT;
        }

        // ۳. درخواست تست رایگان
        if (preg_match('/(\/trial|تست رایگان|اکانت تست|تست بده|رایگان بده)/u', $msg)) {
            return self::SIDE_TRIAL;
        }

        // ۴. استعلام وضعیت اشتراک
        if (preg_match('/(وضعیت اشتراک|اشتراکم چ|اشتراک من|چک کن|سرویسم|کانفیگم|هنوز فعاله|انقضا)/u', $msg)) {
            return self::SIDE_STATUS;
        }

        // ۵. استعلام قیمت/خرید
        if (preg_match('/(قیمت|خرید|تعرفه|پلن|بسته|وی\s*پی\s*ان|فیلتر\s*شکن|vpn|چنده|هزینه)/u', $msg)) {
            return self::SIDE_PLANS;
        }

        return self::SIDE_NONE;
    }
}
