<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

class ShiftScheduleService
{
    /**
     * بررسی اینکه آیا ساعت فعلی ایران در بازه شیفت استراحت قرار دارد یا خیر
     */
    public static function isRestShift(): bool
    {
        $enabled = Setting::where('key', 'shift_rest_enabled')->value('value');
        if ($enabled !== null && !filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $startStr = Setting::where('key', 'shift_rest_start')->value('value') ?: '05:00';
        $endStr = Setting::where('key', 'shift_rest_end')->value('value') ?: '14:00';

        $now = Carbon::now('Asia/Tehran');
        $currentMinutes = ((int) $now->format('H')) * 60 + ((int) $now->format('i'));

        [$startH, $startM] = array_pad(explode(':', $startStr), 2, 0);
        [$endH, $endM] = array_pad(explode(':', $endStr), 2, 0);

        $startMinutes = ((int) $startH) * 60 + ((int) $startM);
        $endMinutes = ((int) $endH) * 60 + ((int) $endM);

        if ($startMinutes <= $endMinutes) {
            return $currentMinutes >= $startMinutes && $currentMinutes < $endMinutes;
        }

        // اگر بازه از نیمه‌شب عبور کند (مثلاً ۲۳:۰۰ تا ۰۷:۰۰)
        return $currentMinutes >= $startMinutes || $currentMinutes < $endMinutes;
    }

    /**
     * دریافت پیام وضعیت ثبت فیش بر اساس شیفت (فرمت HTML)
     */
    public static function getReceiptNotice(): string
    {
        $startStr = Setting::where('key', 'shift_rest_start')->value('value') ?: '05:00';
        $endStr = Setting::where('key', 'shift_rest_end')->value('value') ?: '14:00';

        if (self::isRestShift()) {
            return "🌙 <b>توجه به شیفت کاری:</b>\n" .
                "در حال حاضر (از ساعت {$startStr} تا {$endStr}) در شیفت استراحت هستیم و ممکنه تایید فیش کمی طول بکشه. اصلاً نگران نباشید، سفارش شما کاملاً محفوظ است؛ لطفاً منتظر بمانید و در صورت تمایل می‌توانید به پشتیبانی هم پیام دهید.";
        }

        return "⏱ <b>زمان تایید فیش:</b> معمولاً کمتر از ۱۰ دقیقه است.\n" .
            "💡 چنانچه تایید فیش بیش از ۱ ساعت زمان برد، می‌توانید با ارسال شناسه سفارش به پشتیبانی پیام دهید.";
    }

    /**
     * یادداشت کوتاه برای قرارگیری در پیام اطلاعات کارت
     */
    public static function getCardNotice(): string
    {
        $startStr = Setting::where('key', 'shift_rest_start')->value('value') ?: '05:00';
        $endStr = Setting::where('key', 'shift_rest_end')->value('value') ?: '14:00';

        return "تایید فیش‌ها معمولاً کمتر از ۱۰ دقیقه انجام می‌شود (در شیفت استراحت {$startStr} تا {$endStr} ممکن است با کمی تاخیر بررسی شود).";
    }
}
