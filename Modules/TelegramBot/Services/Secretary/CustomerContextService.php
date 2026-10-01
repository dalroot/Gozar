<?php

namespace Modules\TelegramBot\Services\Secretary;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Modules\TelegramBot\Services\PlanCatalogService;

class CustomerContextService
{
    public function __construct(private PlanCatalogService $catalog)
    {
    }

    public function build(int|string $chatId, ?string $username, ?string $fullName): array
    {
        // Never guess a customer from a display name or username.
        $user = User::where('telegram_chat_id', (string) $chatId)->first();
        $orders = $user
            ? Order::with(['plan', 'server'])->where('user_id', $user->id)->latest()->get()
            : collect();

        $service = $orders->first(function (Order $order) {
            return in_array($order->status, ['paid', 'active', 'completed'], true)
                && (!$order->expires_at || now()->lt($order->expires_at));
        }) ?? $orders->first(fn (Order $order) => in_array($order->status, ['paid', 'active', 'completed'], true));

        $settings = Setting::query()->pluck('value', 'key');

        $rawName = $fullName ?: ($user?->name ?: $username);

        return [
            'chat_id' => (string) $chatId,
            'username' => $username,
            'name' => $this->sanitizeName($rawName),
            'user' => $user,
            'is_verified' => $user !== null,
            'service' => $service,
            'service_link' => $service ? $this->extractServiceLink($service) : null,
            'plans' => $this->catalog->activePlans(),
            'settings' => $settings,
        ];
    }

    public function sanitizeName(?string $name): string
    {
        $raw = trim((string) $name);
        $clean = preg_replace('/[.\-_,،:;!؟?~`@#$%^&*()+=\[\]{}|\/\\\\<>]/u', '', $raw);
        $clean = trim(preg_replace('/\s+/u', ' ', $clean));
        if (mb_strlen($clean, 'UTF-8') >= 2) {
            return $clean;
        }
        return 'همراه گرامی';
    }

    private function extractServiceLink(Order $order): ?string
    {
        $candidates = $this->flattenCandidates($order->config_details);
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if (filter_var($candidate, FILTER_VALIDATE_URL) && str_starts_with($candidate, 'http')) {
                return $candidate;
            }
        }

        $domain = $order->server?->subscription_domain;
        if ($domain && $order->panel_username) {
            return rtrim($domain, '/') . '/sub/' . rawurlencode($order->panel_username);
        }

        return null;
    }

    private function flattenCandidates(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->flattenCandidates($decoded);
            }
            preg_match_all('~https?://[^\s"<>]+~u', $value, $matches);
            return $matches[0] ?: [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $priority = ['subscription_url', 'subscriptionUrl', 'sub_url', 'link', 'config', 'url'];
        $result = [];
        foreach ($priority as $key) {
            if (array_key_exists($key, $value)) {
                $result = array_merge($result, $this->flattenCandidates($value[$key]));
            }
        }
        foreach ($value as $key => $item) {
            if (!in_array($key, $priority, true)) {
                $result = array_merge($result, $this->flattenCandidates($item));
            }
        }
        return $result;
    }
}
