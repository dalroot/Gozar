<?php

namespace Modules\TelegramBot\Services;

final class RozanehExperience
{
    public const BRAND = 'روزنه';
    public const ASSISTANT = 'فرایدی';
    public const TAGLINE = 'آرام، امن، همیشه در دسترس.';

    /**
     * The approved Telegram iOS Adaptive visual system.
     * Text labels remain meaningful if custom emoji rendering is unavailable.
     */
    private const ICONS = [
        'brand' => 6028346797368283073,
        'active' => 6041919344995209164,
        'secure' => 6037249452824072506,
        'plans' => 5778672437122045013,
        'link' => 6028171274939797252,
        'support' => 6030784887093464891,
        'status' => 5936143551854285132,
        'trial' => 5920515922505765329,
        'wallet' => 5778421276024509124,
        'home' => 6042137469204303531,
        'gift' => 5773677501825945508,
        'profile' => 6032994772321309200,
    ];

    public function icon(string $name): ?int
    {
        return self::ICONS[$name] ?? null;
    }

    public function customEmoji(string $name, string $fallback): string
    {
        $id = $this->icon($name);
        return $id ? "<tg-emoji emoji-id=\"{$id}\">{$fallback}</tg-emoji>" : $fallback;
    }

    public function assistantIntroduction(?string $name = null): string
    {
        $safeName = trim((string) $name);
        $greeting = ($safeName !== '' && $safeName !== 'همراه گرامی')
            ? 'سلام <b>' . htmlspecialchars($safeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b> عزیز 🌸'
            : 'سلام همراه گرامی 🌸';

        return "{$greeting}\n" .
            "من <b>" . self::ASSISTANT . "</b> هستم، دستیار پشتیبانی " . self::BRAND . " 🤖\n\n" .
            "اینجا می‌تونم در موارد زیر کمکتون کنم:\n" .
            "🔹 <b>بررسی وضعیت اشتراک، حجم و تاریخ انقضا</b>\n" .
            "🔹 <b>عیب‌یابی اختلال اتصال و لینک ساب</b>\n" .
            "🔹 <b>دانلود نرم‌افزارها و راهنمای راه‌اندازی</b>\n\n" .
            "💬 در صورت نیاز به بررسی دقیق‌تر، پیام شما به <b>پشتیبان انسانی</b> ارجاع داده می‌شود.\n\n" .
            "درخواستتون رو بنویسید یا از گزینه‌های زیر انتخاب کنید:";
    }

    public function assistantMenu(): array
    {
        return [
            [
                ['text' => '📊 وضعیت اشتراک من', 'callback_data' => 'diag_status'],
                ['text' => '🛠️ عیب‌یابی اتصال', 'callback_data' => 'diag_start'],
            ],
            [
                ['text' => '📱 دانلود نرم‌افزار و آموزش', 'callback_data' => 'sec_apps'],
                ['text' => '🔗 دریافت لینک اتصال', 'callback_data' => 'sec_get_link'],
            ],
            [
                ['text' => '🛍️ ورود به ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot'],
            ],
            [
                ['text' => '👨🏻‍💻 ارتباط با پشتیبان انسانی', 'callback_data' => 'sec_human'],
            ],
        ];
    }

    public function button(
        string $text,
        string $callbackData,
        string $icon = '',
        ?string $style = null
    ): array {
        $button = ['text' => $text, 'callback_data' => $callbackData];
        if ($style) $button['style'] = $style;
        return $button;
    }
}
