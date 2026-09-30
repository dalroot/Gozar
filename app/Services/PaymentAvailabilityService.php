<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;

final class PaymentAvailabilityService
{
    public function isEnabled(string $method, ?Collection $settings = null): bool
    {
        $settings ??= Setting::all()->pluck('value', 'key');
        $map = [
            'wallet' => 'pay_enable_wallet',
            'card' => 'pay_enable_card',
            'bank_gateway' => 'pay_enable_bank_gateway',
            'crypto' => 'pay_enable_crypto',
            'paypal' => 'pay_enable_paypal',
            'intl' => 'pay_enable_intl',
        ];
        $key = $map[$method] ?? null;
        if (!$key) return false;

        $default = in_array($method, ['wallet', 'card'], true) ? '1' : '0';
        $value = $settings->get($key, $default);
        $enabled = filter_var($value, FILTER_VALIDATE_BOOLEAN)
            || $value === '1' || $value === 1 || $value === true;
        if (!$enabled) return false;

        return match ($method) {
            'bank_gateway' => false,
            'crypto' => collect([
                $settings->get('crypto_usdt_trc20'),
                $settings->get('crypto_usdt_bep20'),
                $settings->get('crypto_btc'),
            ])->contains(fn ($item) => trim((string) $item) !== ''),
            'paypal' => trim((string) $settings->get('paypal_link', '')) !== ''
                || trim((string) $settings->get('paypal_email', '')) !== '',
            'intl' => str_starts_with(trim((string) $settings->get('intl_payment_link', '')), 'http'),
            default => true,
        };
    }

    public function userFacingLabels(?Collection $settings = null): array
    {
        $labels = [
            'wallet' => 'کیف پول',
            'card' => 'کارت‌به‌کارت',
            'crypto' => 'ارز دیجیتال',
            'paypal' => 'PayPal',
            'intl' => 'کارت بین‌المللی',
        ];

        return collect($labels)
            ->filter(fn ($label, $method) => $this->isEnabled($method, $settings))
            ->values()
            ->all();
    }
}
