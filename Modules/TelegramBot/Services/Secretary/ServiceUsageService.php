<?php

namespace Modules\TelegramBot\Services\Secretary;

use App\Models\Order;
use App\Services\PasargadService;
use App\Services\RemnawaveService;
use App\Services\XUIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServiceUsageService
{
    /**
     * آمار مصرف را فقط از پنل واقعی می‌خواند؛ در صورت خطا مقدار ساختگی برنمی‌گرداند.
     */
    public function get(Order $order, $settings): ?array
    {
        if (!$order->panel_username) {
            return null;
        }

        return Cache::remember("sec_usage_{$order->id}", now()->addSeconds(60), function () use ($order, $settings) {
            try {
                $panelType = $order->server ? 'xui' : (string) ($settings->get('panel_type') ?: 'xui');
                $raw = match ($panelType) {
                    'xui' => $this->fromXui($order, $settings),
                    'marzban' => $this->fromMarzban($order, $settings),
                    'pasargad' => $this->fromPasargad($order, $settings),
                    'remnawave' => $this->fromRemnawave($order, $settings),
                    default => null,
                };

                if (!$raw || !isset($raw['total_bytes'], $raw['used_bytes'])) {
                    return null;
                }

                $total = max(0, (int) $raw['total_bytes']);
                $used = max(0, (int) $raw['used_bytes']);

                return [
                    'total_bytes' => $total,
                    'used_bytes' => $used,
                    'remaining_bytes' => max(0, $total - $used),
                    'source' => $panelType,
                ];
            } catch (\Throwable $e) {
                Log::warning('Secretary usage lookup failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    private function fromXui(Order $order, $settings): ?array
    {
        $server = $order->server;
        $host = $server?->full_host ?: $settings->get('xui_host');
        $username = $server?->username ?: $settings->get('xui_user');
        $password = $server?->password ?: $settings->get('xui_pass');
        // سفارش‌های قدیمی/تست ممکن است روی اینباندی غیر از پیش‌فرض ساخته شده باشند.
        $inboundId = $server ? (int) $server->inbound_id : 0;

        if (!$host || !$username || !$password) {
            return null;
        }

        $inbounds = (new XUIService($host, $username, $password))->getInbounds();
        foreach ($inbounds as $inbound) {
            if ($inboundId && (int) ($inbound['id'] ?? 0) !== $inboundId) {
                continue;
            }
            foreach (($inbound['clientStats'] ?? []) as $stats) {
                if ((string) ($stats['email'] ?? '') !== (string) $order->panel_username) {
                    continue;
                }
                return [
                    'total_bytes' => (int) ($stats['total'] ?? 0),
                    'used_bytes' => (int) ($stats['up'] ?? 0) + (int) ($stats['down'] ?? 0),
                ];
            }
        }

        return null;
    }

    private function fromMarzban(Order $order, $settings): ?array
    {
        $baseUrl = rtrim(trim((string) $settings->get('marzban_host'), " \t\n\r\0\x0B\"'"), '/');
        if (!$baseUrl) return null;

        $login = Http::timeout(12)->asForm()->post($baseUrl . '/api/admin/token', [
            'username' => $settings->get('marzban_sudo_username'),
            'password' => $settings->get('marzban_sudo_password'),
        ]);
        $token = $login->json('access_token');
        if (!$login->successful() || !$token) return null;

        $response = Http::timeout(12)->withToken($token)->get(
            $baseUrl . '/api/user/' . rawurlencode($order->panel_username)
        );
        if (!$response->successful()) return null;

        return [
            'total_bytes' => (int) $response->json('data_limit', 0),
            'used_bytes' => (int) $response->json('used_traffic', 0),
        ];
    }

    private function fromPasargad(Order $order, $settings): ?array
    {
        $user = (new PasargadService(
            $settings->get('pasargad_host'),
            $settings->get('pasargad_sudo_username'),
            $settings->get('pasargad_sudo_password'),
            $settings->get('pasargad_node_hostname')
        ))->getUser($order->panel_username);

        if (!$user) return null;
        return [
            'total_bytes' => (int) ($user['data_limit'] ?? 0),
            'used_bytes' => (int) ($user['used_traffic'] ?? 0),
        ];
    }

    private function fromRemnawave(Order $order, $settings): ?array
    {
        $user = (new RemnawaveService(
            $settings->get('remnawave_host'),
            $settings->get('remnawave_api_token'),
            $settings->get('remnawave_node_hostname')
        ))->getUser($order->panel_username);

        if (!$user) return null;
        return [
            'total_bytes' => (int) ($user['trafficLimitBytes'] ?? 0),
            'used_bytes' => (int) ($user['usedTrafficBytes'] ?? $user['usedTraffic'] ?? 0),
        ];
    }
}
