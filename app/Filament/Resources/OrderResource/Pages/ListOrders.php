<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('همه سفارش‌ها'),
            'pending' => Tab::make('منتظر پرداخت')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending'))
                ->badge(Order::query()->where('status', 'pending')->count())
                ->badgeColor('warning'),
            'receipts' => Tab::make('رسیدهای منتظر')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('status', 'pending')
                    ->whereNotNull('card_payment_receipt'))
                ->badge(Order::query()->where('status', 'pending')->whereNotNull('card_payment_receipt')->count())
                ->badgeColor('warning'),
            'paid' => Tab::make('پرداخت‌شده')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'paid'))
                ->badge(Order::query()->where('status', 'paid')->count())
                ->badgeColor('success'),
            'delivery' => Tab::make('نیازمند تحویل')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('status', 'paid')
                    ->whereNotNull('plan_id')
                    ->where(fn (Builder $query): Builder => $query
                        ->whereNull('config_details')
                        ->orWhere('config_details', '')))
                ->badge(Order::query()
                    ->where('status', 'paid')
                    ->whereNotNull('plan_id')
                    ->where(fn (Builder $query): Builder => $query
                        ->whereNull('config_details')
                        ->orWhere('config_details', ''))
                    ->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('ثبت سفارش دستی'),
        ];
    }
}

