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
        $greeting = $safeName !== ''
            ? 'سلام <b>' . htmlspecialchars($safeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' عزیز</b>،'
            : 'سلام،';

        return $this->customEmoji('brand', '✈️') . '  <b>پشتیبانی ' . self::BRAND . "</b>\n\n" .
            "{$greeting} من <b>" . self::ASSISTANT . "</b>، ربات دستیار هوشمند روزنه هستم 🤖\n\n" .
            "برای تسریع امور شما، خدمات زیر را به‌صورت آنی انجام می‌دهم:\n" .
            "📊 <b>استعلام حجم و زمان باقیمانده سرویس</b>\n" .
            "🔗 <b>دریافت مجدد لینک‌های اتصال</b>\n" .
            "🛠 <b>راهنمایی و رفع مشکل قطعی اتصال</b>\n\n" .
            "👨🏻‍💻 <i>توجه: تیم پشتیبانی و ادمین روزنه پیام‌های شما را مستقیماً مطالعه می‌کنند و هر لحظه پاسخگوی شما خواهند بود.</i>\n\n" .
            "🛍️ <i>برای خرید اشتراک جدید یا تمدید، لطفاً از ربات رسمی فروشگاه (@RoozanehNetBot) استفاده فرمایید.</i>";
    }

    public function assistantMenu(): array
    {
        return [
            [
                ['text' => '🛍️ خرید و تمدید اشتراک', 'url' => 'https://t.me/RoozanehNetBot'],
                $this->button('🔍 بررسی سرویس من', 'diag_status', 'status'),
            ],
            [
                $this->button('⚡ مشکل اتصال دارم', 'diag_start', 'secure'),
                $this->button('🔗 دریافت لینک اتصال', 'sec_get_link', 'link'),
            ],
            [
                ['text' => '🎁 دریافت تست رایگان', 'url' => 'https://t.me/RoozanehNetBot'],
                $this->button('📖 راهنمای اتصال', 'sec_tutorial', 'brand'),
            ],
            [
                $this->button('👨🏻‍💻 ارتباط با پشتیبان انسانی', 'sec_human', 'support', 'success'),
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
