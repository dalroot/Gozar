<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\User;

class UsersChart extends ChartWidget
{
    protected static ?string $heading = 'کاربران ثبت‌نام شده جدید (۳۰ روز گذشته)';
    protected static ?int $sort = 0;
    protected static string $color = 'info';
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

        // Query users registered in the last 30 days
        $users = User::where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->get();

        foreach ($users as $user) {
            $date = $user->created_at->format('Y-m-d');
            if (isset($days[$date])) {
                $days[$date]++;
            }
        }

        $labels = [];
        $data = [];
        foreach ($days as $date => $count) {
            $parts = explode('-', $date);
            $labels[] = $parts[1] . '/' . $parts[2];
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'کاربر جدید',
                    'data' => $data,
                    'backgroundColor' => '#38BDF8',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
