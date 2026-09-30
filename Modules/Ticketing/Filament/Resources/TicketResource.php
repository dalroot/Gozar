<?php

namespace Modules\Ticketing\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\Ticketing\Models\Ticket;
use Nwidart\Modules\Facades\Module;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'تیکت‌های پشتیبانی';
    protected static ?string $modelLabel = 'تیکت';
    protected static ?string $pluralModelLabel = 'صندوق پشتیبانی';
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = -8;

    public static function canViewAny(): bool
    {
        return Module::isEnabled('Ticketing');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات درخواست')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')->label('کاربر')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('subject')
                        ->label('موضوع تیکت')->required()->maxLength(255),
                    Forms\Components\Select::make('priority')
                        ->label('اولویت')->options(['low' => 'پایین', 'medium' => 'متوسط', 'high' => 'بالا'])->required(),
                    Forms\Components\Select::make('status')
                        ->label('وضعیت')->options(['open' => 'باز', 'answered' => 'پاسخ داده شده', 'closed' => 'بسته شده'])->required(),
                    Forms\Components\Textarea::make('message')
                        ->label('پیام اولیه')->rows(7)->required()->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('مشتری')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('درخواست')
                    ->limit(46)
                    ->tooltip(fn (Ticket $record): string => $record->subject),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning', 'answered' => 'success', 'closed' => 'gray', default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'باز', 'answered' => 'پاسخ داده شده', 'closed' => 'بسته شده', default => $state,
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->label('اولویت')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'info', 'medium' => 'warning', 'high' => 'danger', default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'پایین', 'medium' => 'متوسط', 'high' => 'بالا', default => $state,
                    }),
                Tables\Columns\TextColumn::make('sla_state')
                    ->label('SLA')->badge()
                    ->getStateUsing(fn (Ticket $record): string => $record->status === 'open' && $record->updated_at?->lte(now()->subHours(48)) ? 'overdue' : 'ok')
                    ->formatStateUsing(fn (string $state): string => $state === 'overdue' ? 'خارج از SLA' : 'در محدوده')
                    ->color(fn (string $state): string => $state === 'overdue' ? 'danger' : 'success'),
                Tables\Columns\IconColumn::make('source')
                    ->label('منبع')
                    ->icon(fn (?string $state): string => match ($state) {
                        'web' => 'heroicon-o-globe-alt', 'telegram' => 'heroicon-o-paper-airplane', default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'web' => 'primary', 'telegram' => 'info', default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین فعالیت')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')->options(['open' => 'باز', 'answered' => 'پاسخ داده شده', 'closed' => 'بسته شده']),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('اولویت')->options(['low' => 'پایین', 'medium' => 'متوسط', 'high' => 'بالا']),
                Tables\Filters\SelectFilter::make('source')
                    ->label('منبع')->options(['web' => 'وب‌سایت', 'telegram' => 'تلگرام']),
            ])
            ->recordUrl(fn (Ticket $record): string => static::getUrl('view', ['record' => $record]))
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => TicketResource\Pages\ListTickets::route('/'),
            'create' => TicketResource\Pages\CreateTicket::route('/create'),
            'view' => TicketResource\Pages\ViewTicket::route('/{record}'),
            'edit' => TicketResource\Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
