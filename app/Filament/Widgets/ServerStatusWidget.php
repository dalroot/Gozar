<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ServerStatusWidget extends Widget
{
    protected static ?int $sort = 1;

    protected static string $view = 'filament.widgets.server-status-widget';

    protected int | string | array $columnSpan = 'full';

    public function getServers(): array
    {
        if (!class_exists('Modules\MultiServer\Models\Server')) {
            return [];
        }

        // Cache the server status list for 30 seconds to prevent page load delays
        return cache()->remember('admin_server_status_list_v2', 30, function () {
            $servers = \Modules\MultiServer\Models\Server::with('location')->get();
            $list = [];

            foreach ($servers as $server) {
                $isOnline = false;
                $ip = $server->ip_address;
                $port = $server->port ?: 80;

                // 0.5s timeout for fast response
                $connection = @fsockopen($ip, $port, $errno, $errstr, 0.5);
                if (is_resource($connection)) {
                    $isOnline = true;
                    fclose($connection);
                }

                $current = $server->current_users ?: 0;
                $capacity = $server->capacity ?: 100;
                $loadPercent = min(100, round(($current / $capacity) * 100));

                $loadColor = 'success'; // green
                if ($loadPercent >= 85) {
                    $loadColor = 'danger'; // red
                } elseif ($loadPercent >= 60) {
                    $loadColor = 'warning'; // orange
                }

                $list[] = [
                    'name' => $server->name,
                    'ip' => $server->ip_address,
                    'location' => $server->location?->name ?: 'نامشخص',
                    'flag' => $server->location?->flag ?: '🌐',
                    'is_active' => $server->is_active,
                    'is_online' => $isOnline,
                    'current' => $current,
                    'capacity' => $capacity,
                    'load_percent' => $loadPercent,
                    'load_color' => $loadColor,
                ];
            }

            return $list;
        });
    }
}
