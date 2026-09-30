<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Filament\Pages\Dashboard;
use Modules\Ticketing\Models\Ticket;

class OperationsDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'مرکز عملیات';

    protected static ?string $title = 'مرکز عملیات روزنه';

    protected static ?int $navigationSort = -10;

    protected static string $view = 'filament.pages.operations-dashboard-v2';

    public array $stats = [];

    public array $recentOrders = [];

    public function mount(): void
    {
        $this->refreshDashboard();
    }

    public function refreshDashboard(): void
    {
        $today = now()->startOfDay();
        $supportDeadline = now()->subHours(48);

        $paidToday = Order::query()
            ->where('status', 'paid')
            ->where('created_at', '>=', $today);

        $failedProvisioning = Order::query()
            ->where('status', 'paid')
            ->whereNotNull('plan_id')
            ->where(function ($query): void {
                $query->whereNull('config_details')
                    ->orWhere('config_details', '');
            })
            ->count();

        $this->stats = [
            'revenue_today' => (int) (clone $paidToday)->sum('amount'),
            'paid_today' => (clone $paidToday)->count(),
            'pending_orders' => Order::query()->where('status', 'pending')->count(),
            'pending_receipts' => Order::query()
                ->where('status', 'pending')
                ->whereNotNull('card_payment_receipt')
                ->count(),
            'failed_provisioning' => $failedProvisioning,
            'open_tickets' => Ticket::query()->whereIn('status', ['open', 'answered'])->count(),
            'overdue_tickets' => Ticket::query()
                ->where('status', 'open')
                ->where('updated_at', '<=', $supportDeadline)
                ->count(),
            'human_handoffs' => Ticket::query()
                ->where('status', 'open')
                ->where('subject', 'like', '%پشتیبانی انسانی%')
                ->count(),
            'new_users_today' => User::query()->where('created_at', '>=', $today)->count(),
            'refunds_today' => (int) Transaction::query()
                ->where('type', 'refund')
                ->where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->sum('amount'),
        ];

        $this->recentOrders = Order::query()
            ->with(['user:id,name', 'plan:id,name'])
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'user' => $order->user?->name ?? '—',
                'plan' => $order->plan?->name ?? 'شارژ کیف پول',
                'amount' => number_format((int) $order->amount) . ' تومان',
                'status' => $order->status,
                'created_at' => optional($order->created_at)->format('Y/m/d H:i'),
            ])
            ->all();
    }
}

