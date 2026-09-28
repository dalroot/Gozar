<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Filament\Resources\PlanResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\DiscountCodeResource;
use App\Filament\Resources\TelegramBroadcastResource;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = -2;

    protected static string $view = 'filament.widgets.quick-actions-widget';

    protected int | string | array $columnSpan = 'full';

    public function getActions(): array
    {
        $actions = [];

        // Add Plan Action
        if (class_exists(PlanResource::class)) {
            try {
                $actions[] = [
                    'label' => 'ایجاد پلن جدید',
                    'url' => PlanResource::getUrl('create'),
                    'icon' => 'heroicon-o-plus-circle',
                    'color' => 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-400 dark:hover:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-900/60',
                ];
            } catch (\Exception $e) {}
        }

        // Add User Action
        if (class_exists(UserResource::class)) {
            try {
                $actions[] = [
                    'label' => 'افزودن کاربر جدید',
                    'url' => UserResource::getUrl('create'),
                    'icon' => 'heroicon-o-user-plus',
                    'color' => 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:hover:bg-emerald-900/40 border border-emerald-200 dark:border-emerald-900/60',
                ];
            } catch (\Exception $e) {}
        }

        // Add Discount Action
        if (class_exists(DiscountCodeResource::class)) {
            try {
                $actions[] = [
                    'label' => 'ایجاد کد تخفیف',
                    'url' => DiscountCodeResource::getUrl('create'),
                    'icon' => 'heroicon-o-ticket',
                    'color' => 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:hover:bg-amber-900/40 border border-amber-200 dark:border-amber-900/60',
                ];
            } catch (\Exception $e) {}
        }

        // Telegram Broadcast Action
        if (class_exists(TelegramBroadcastResource::class)) {
            try {
                $actions[] = [
                    'label' => 'ارسال پیام همگانی',
                    'url' => TelegramBroadcastResource::getUrl('index'),
                    'icon' => 'heroicon-o-megaphone',
                    'color' => 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-400 dark:hover:bg-rose-900/40 border border-rose-200 dark:border-rose-900/60',
                ];
            } catch (\Exception $e) {}
        }

        return $actions;
    }
}
