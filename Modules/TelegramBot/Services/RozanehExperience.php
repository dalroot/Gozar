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
            ? 'سلام <b>' . htmlspecialchars($safeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' عزیز</b>،'
            : 'سلام همراه گرامی،';

        return "{$greeting} من <b>" . self::ASSISTANT . "</b> دستیار هوشمند " . self::BRAND . " هستم 🤖\n\n" .
            "من به سرورها متصلم و می‌تونم در چند ثانیه <b>حجم، روزهای باقی‌مانده و لینک اتصال</b> شما رو بررسی کنم.\n" .
            "همچنین متن پیام‌های شما برای <b>همکاران پشتیبانی</b> ارسال می‌شود و در همین گفتگو پاسخگوی شما خواهند بود 🌿";
    }

    public function assistantMenu(): array
    {
        return [
            [
                $this->button('📊 وضعیت و حجم اشتراک', 'diag_status', 'status'),
                $this->button('🔗 دریافت لینک اتصال', 'sec_get_link', 'link'),
            ],
            [
                ['text' => '🛍️ ورود به ربات فروشگاه (@RoozanehNetBot)', 'url' => 'https://t.me/RoozanehNetBot'],
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
