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

        return $this->customEmoji('brand', '✈️') . '  <b>' . self::BRAND . "</b>\n\n" .
            "{$greeting} من <b>" . self::ASSISTANT . "</b>، دستیار هوشمند پشتیبانی " . self::BRAND . " هستم.\n\n" .
            "می‌توانم وضعیت واقعی اشتراک، حجم و زمان باقی‌مانده، سفارش‌ها، پرداخت‌ها و لینک‌های اتصال شما را در سامانه بررسی کنم؛ برای خرید، تمدید یا رفع مشکل هم قدم‌به‌قدم کنارتان هستم.\n\n" .
            "درخواستتان را همین‌جا بنویسید یا از گزینه‌های زیر استفاده کنید. هر زمان هم بخواهید، درخواست ارتباط با <b>پشتیبان انسانی</b> را برایتان ثبت می‌کنم.";
    }

    public function assistantMenu(): array
    {
        return [
            [
                $this->button('🛍️ خرید یا تمدید', 'sec_view_durations', 'plans', 'primary'),
                $this->button('🔍 بررسی سرویس من', 'diag_status', 'status'),
            ],
            [
                $this->button('⚡ مشکل اتصال دارم', 'diag_start', 'secure'),
                $this->button('🔗 دریافت لینک اتصال', 'sec_get_link', 'link'),
            ],
            [
                $this->button('🎁 تست رایگان', 'sec_get_trial', 'trial'),
                $this->button('📖 راهنمای اتصال', 'sec_tutorial', 'brand'),
            ],
            [
                $this->button('👨🏻‍💻 ارتباط با پشتیبان (@RoozanehHelp)', 'sec_human', 'support', 'success'),
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
