<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;

class OrdersChart extends ChartWidget
{
    protected static ?string $heading = 'نمودار فروش ۳۰ روز گذشته (تومان)';
    protected static ?int $sort = -1;
    protected static string $color = 'success';
    protected static ?string $maxHeight = '250px';
    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    protected function getData(): array
    {
        $days = [];

        // Generate last 30 days dates
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $days[$date] = 0;
        }

        // Query orders in the last 30 days
        $orders = Order::where('status', 'paid')
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->with('plan')
            ->get();

        foreach ($orders as $order) {
            $date = $order->created_at->format('Y-m-d');
            if (isset($days[$date])) {
                $days[$date] += $order->amount ?: ($order->plan?->price ?? 0);
            }
        }

        $labels = [];
        $data = [];
        foreach ($days as $date => $total) {
            // Format labels as MM/DD
            $parts = explode('-', $date);
            $labels[] = $parts[1] . '/' . $parts[2];
            $data[] = $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'فروش (تومان)',
                    'data' => $data,
                    'fill' => 'start',
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
