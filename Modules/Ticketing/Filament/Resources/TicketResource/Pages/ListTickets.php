<?php

namespace Modules\Ticketing\Filament\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\Ticketing\Filament\Resources\TicketResource;
use Modules\Ticketing\Models\Ticket;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('همه'),
            'open' => Tab::make('باز')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'open'))
                ->badge(Ticket::query()->where('status', 'open')->count())
                ->badgeColor('warning'),
            'handoffs' => Tab::make('ارجاع فرایدی')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('status', 'open')
                    ->where('subject', 'like', '%پشتیبانی انسانی%'))
                ->badge(Ticket::query()->where('status', 'open')->where('subject', 'like', '%پشتیبانی انسانی%')->count())
                ->badgeColor('info'),
            'overdue' => Tab::make('خارج از SLA')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('status', 'open')
                    ->where('updated_at', '<=', now()->subHours(48)))
                ->badge(Ticket::query()->where('status', 'open')->where('updated_at', '<=', now()->subHours(48))->count())
                ->badgeColor('danger'),
            'closed' => Tab::make('بسته‌شده')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'closed')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('ثبت تیکت دستی'),
        ];
    }
}

