<?php

namespace Modules\TelegramBot\Services\Secretary;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Modules\TelegramBot\Services\BotEntryPointService;
use Modules\TelegramBot\Services\PlanCatalogService;

class SalesFunnelService
{
    public function __construct(
        private PlanCatalogService $catalog,
        private BotEntryPointService $entryPoint
    ) {
    }

    /**
     * دریافت لیست پلن‌های فعال
     */
    public function getActivePlans(): Collection
    {
        return $this->catalog->activePlans();
    }

    /**
     * تولید عنوان خوانا برای هر بازه زمانی
     */
    public function generateDurationLabel(int $days): string
    {
        return $this->catalog->durationLabel($days);
    }

    /**
     * ساخت کیبورد شیشه‌ای برای انتخاب دوره زمانی
     */
    public function getDurationKeyboard(): array
    {
        $plans = $this->getActivePlans();
        $durations = $plans->pluck('duration_days')->unique()->sort();

        $keyboard = [];
        foreach ($durations as $d) {
            $label = $this->generateDurationLabel((int)$d);
            $keyboard[] = [
                ['text' => $label, 'callback_data' => "sec_duration_{$d}", 'style' => 'primary']
            ];
        }

        $keyboard[] = [
            ['text' => '⚡️ دریافت تست رایگان', 'callback_data' => 'sec_get_trial', 'style' => 'success'],
            ['text' => '❌ بستن منو', 'callback_data' => 'sec_dismiss', 'style' => 'danger']
        ];
        $keyboard[] = [
            ['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']
        ];

        return $keyboard;
    }

    /**
     * ساخت کیبورد شیشه‌ای برای بسته‌های یک دوره زمانی خاص
     */
    public function getPlansByDurationKeyboard(int $durationDays): array
    {
        $plans = $this->catalog->plansForDuration($durationDays);

        $keyboard = [];
        foreach ($plans as $plan) {
            $keyboard[] = [
                ['text' => $this->catalog->planButtonLabel($plan), 'callback_data' => "sec_buy_plan_{$plan->id}", 'style' => 'success']
            ];
        }

        $keyboard[] = [
            ['text' => '⬅️ بازگشت به لیست دوره‌ها', 'callback_data' => 'sec_view_durations', 'style' => 'primary'],
            ['text' => '❌ بستن', 'callback_data' => 'sec_dismiss', 'style' => 'danger']
        ];
        $keyboard[] = [
            ['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']
        ];

        return $keyboard;
    }

    /**
     * دریافت اطلاعات حساب بانکی جهت واریز
     */
    public function getPaymentDetails(): array
    {
        $settings = Setting::all()->pluck('value', 'key');
        return [
            'cardNumber' => $settings->get('payment_card_number', 'در حال به‌روزرسانی'),
            'cardHolder' => $settings->get('payment_card_holder_name', 'مدیریت'),
            'brandName' => $settings->get('auth_brand_name', 'روزنه'),
            'trialHours' => null,
            'trialMb' => $settings->get('trial_volume_mb', '1024'),
        ];
    }

    /**
     * متن و اطلاعات ثبت سفارش یک پلن
     */
    public function getPlanOrderMessage(int|string $planId): array
    {
        $plan = Plan::find($planId);
        $pName = $plan ? $plan->name : 'پلن انتخابی';
        $price = $plan ? number_format($plan->price ?? 0) : '0';
        if (!$plan || !$plan->is_active) {
            return ['text' => 'این پلن دیگر فعال نیست؛ لطفاً دوباره از فهرست انتخاب کنید.', 'buttons' => $this->getDurationKeyboard()];
        }

        $duration = $this->generateDurationLabel((int) $plan->duration_days);
        $volume = $plan->volume_gb ? "{$plan->volume_gb} گیگابایت" : 'حجم سفارشی';
        $deepLink = $this->entryPoint->url("plan_{$plan->id}");
        $text = "🧾 <b>خلاصه انتخاب شما</b>\n\n" .
                "پلن: <b>" . htmlspecialchars(trim($pName), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
                "مدت: {$duration}\n" .
                "حجم: <b>{$volume}</b>\n" .
                "مبلغ: <code>{$price} تومان</code>\n\n" .
                "اگر انتخابتان نهایی است، روی دکمهٔ زیر بزنید تا همین پلن برای شما باز شود و مراحل ثبت سفارش و پرداخت را ادامه دهید.";

        $buttons = [
            [
                ['text' => '✅ ادامه خرید همین پلن', 'url' => $deepLink, 'style' => 'success']
            ],
            [
                ['text' => '⬅️ انتخاب پلن دیگر', 'callback_data' => 'sec_view_durations', 'style' => 'primary'],
                ['text' => '❌ بستن', 'callback_data' => 'sec_dismiss', 'style' => 'danger']
            ],
            [
                ['text' => '👨🏻‍💻 پشتیبان انسانی', 'callback_data' => 'sec_human']
            ]
        ];

        return ['text' => $text, 'buttons' => $buttons];
    }
}
