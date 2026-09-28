<?php

namespace Modules\TelegramBot\Services\Secretary;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Support\Collection;

class SalesFunnelService
{
    /**
     * دریافت لیست پلن‌های فعال
     */
    public function getActivePlans(): Collection
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('duration_days', 'asc')
            ->orderBy('volume_gb', 'asc')
            ->get();

        if ($plans->isEmpty()) {
            $plans = Plan::orderBy('duration_days', 'asc')->get();
        }

        return $plans;
    }

    /**
     * تولید عنوان خوانا برای هر بازه زمانی
     */
    public function generateDurationLabel(int $days): string
    {
        if ($days % 30 === 0) {
            $months = (int) ($days / 30);
            return match ($months) {
                1 => '🗓 ۱ ماهه (۳۰ روزه)',
                2 => '🗓 ۲ ماهه (۶۰ روزه)',
                3 => '🔥 ۳ ماهه (۹۰ روزه) — ویژه',
                6 => '💎 ۶ ماهه (۱۸۰ روزه) — اقتصادی',
                12 => '👑 ۱۲ ماهه (سالانه)',
                default => "🗓 {$months} ماهه",
            };
        }
        if ($days <= 7) return "⚡️ {$days} روزه — موقت";
        return "🗓 {$days} روزه";
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
                ['text' => $label, 'callback_data' => "sec_duration_{$d}"]
            ];
        }

        $keyboard[] = [
            ['text' => '⚡️ دریافت تست رایگان', 'callback_data' => 'sec_get_trial'],
            ['text' => '❌ بستن منو', 'callback_data' => 'sec_dismiss']
        ];

        return $keyboard;
    }

    /**
     * ساخت کیبورد شیشه‌ای برای بسته‌های یک دوره زمانی خاص
     */
    public function getPlansByDurationKeyboard(int $durationDays): array
    {
        $plans = Plan::where('is_active', true)
            ->where('duration_days', $durationDays)
            ->orderBy('volume_gb', 'asc')
            ->get();

        if ($plans->isEmpty()) {
            $plans = Plan::where('duration_days', $durationDays)->get();
        }

        $keyboard = [];
        foreach ($plans as $plan) {
            $vol = $plan->volume_gb ? "{$plan->volume_gb} گیگ" : "سفارشی";
            $price = number_format($plan->price ?? 0);
            $keyboard[] = [
                ['text' => "📦 {$vol}  |  {$price} تومان", 'callback_data' => "sec_buy_plan_{$plan->id}"]
            ];
        }

        $keyboard[] = [
            ['text' => '⬅️ بازگشت به لیست دوره‌ها', 'callback_data' => 'sec_view_durations'],
            ['text' => '❌ بستن', 'callback_data' => 'sec_dismiss']
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
            'trialHours' => $settings->get('trial_duration_hours', '24'),
            'trialMb' => $settings->get('trial_volume_mb', '500'),
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
        $payment = $this->getPaymentDetails();

        $text = "🛍 <b>سفارش شما: {$pName}</b>\n" .
                "💰 مبلغ قابل پرداخت: <b>{$price} تومان</b>\n\n" .
                "💳 <b>اطلاعات کارت جهت واریز:</b>\n" .
                "شماره کارت: <code>{$payment['cardNumber']}</code>\n" .
                "به نام: <b>{$payment['cardHolder']}</b>\n\n" .
                "📸 <i>لطفاً پس از واریز، عکس یا متن فیش پرداختی را همین‌جا بفرستید تا بررسی و فعال شود.</i>";

        $buttons = [
            [
                ['text' => '⬅️ انتخاب پلن دیگر', 'callback_data' => 'sec_view_durations'],
                ['text' => '❌ بستن', 'callback_data' => 'sec_dismiss']
            ]
        ];

        return ['text' => $text, 'buttons' => $buttons];
    }
}
