<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\User;
use Modules\Ticketing\Models\Ticket;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = -4;

    protected static ?string $pollingInterval = '300s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Calculate Total Revenue
        $totalRevenue = Order::where('status', 'paid')
            ->whereNotNull('plan_id')
            ->with('plan')
            ->get()
            ->sum(function($order) {
                return $order->plan?->price ?? 0;
            });

        // Calculate Current Month Revenue
        $currentMonthRevenue = Order::where('status', 'paid')
            ->whereNotNull('plan_id')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->with('plan')
            ->get()
            ->sum(function($order) {
                return $order->plan?->price ?? 0;
            });

        $totalPaidOrders = Order::where('status', 'paid')->count();
        $totalUsers = User::count();

        // Get Latest Order info
        $latestOrder = Order::where('status', 'paid')
            ->whereNotNull('plan_id')
            ->with(['user', 'plan'])
            ->latest()
            ->first();

        $latestOrderDescription = 'سفارش خرید پلنی ثبت نشده';
        if ($latestOrder) {
            $userName = $latestOrder->user?->name ?? 'کاربر حذف شده';
            $planName = $latestOrder->plan?->name ?? 'پلن حذف شده';
            $latestOrderDescription = 'آخرین: ' . $userName . ' | ' . $planName;
        }

        // Get Ticket Statistics
        $openTicketsCount = 0;
        $todayTicketsCount = 0;
        $totalTicketsCount = 0;

        if (class_exists('Modules\Ticketing\Models\Ticket')) {
            try {
                $openTicketsCount = Ticket::whereIn('status', ['open', 'answered'])->count();
                $todayTicketsCount = Ticket::whereDate('created_at', Carbon::today())->count();
                $totalTicketsCount = Ticket::count();
            } catch (\Exception $e) {}
        }

        $openTicketsDescription = $openTicketsCount > 0 ? "{$openTicketsCount} تیکت منتظر پاسخ شماست" : "هیچ تیکت بازی وجود ندارد";
        $todayTicketsDescription = $todayTicketsCount > 0 ? "امروز {$todayTicketsCount} تیکت جدید دریافت شد" : "امروز تیکت جدیدی ثبت نشده";

        return [
            // Revenue Cards
            Stat::make('درآمد کل', number_format($totalRevenue) . ' تومان')
                ->description('مجموع فروش پلن‌ها از ابتدا')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('درآمد ماه جاری', number_format($currentMonthRevenue) . ' تومان')
                ->description('فروش پلن‌ها در ماه جاری')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('تعداد کل کاربران', $totalUsers)
                ->description('تعداد کل کاربران ثبت‌نام شده')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('سفارشات موفق کل', $totalPaidOrders)
                ->description($latestOrderDescription)
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),

            // Ticket Cards
            Stat::make('تیکت‌های باز', $openTicketsCount)
                ->description($openTicketsDescription)
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color($openTicketsCount > 0 ? 'warning' : 'success'),

            Stat::make('تیکت‌های جدید امروز', $todayTicketsCount)
                ->description($todayTicketsDescription)
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),

            Stat::make('مجموع کل تیکت‌ها', $totalTicketsCount)
                ->description('تعداد کل تیکت‌های ثبت شده در سیستم')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('gray'),
        ];
    }
}
