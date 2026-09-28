<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralLogResource\Pages;
use App\Models\ReferralLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ReferralLogResource extends Resource
{
    protected static ?string $model = ReferralLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'مدیریت کاربران';
    protected static ?string $navigationLabel = 'گزارش‌های رفرال و ضد تقلب';
    protected static ?string $pluralModelLabel = 'گزارش‌های رفرال و ضد تقلب';
    protected static ?string $modelLabel = 'لاگ رفرال';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                TextColumn::make('referrer.name')
                    ->label('کاربر دعوت‌کننده (معرف)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('referee.name')
                    ->label('کاربر جدید (دعوت‌شده)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('level')
                    ->label('سطح پاداش')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success',
                        2 => 'warning',
                        3 => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('traffic_reward')
                    ->label('حجم هدیه')
                    ->formatStateUsing(fn ($state) => round($state / 1024, 2) . ' GB')
                    ->sortable(),

                TextColumn::make('ip_address')
                    ->label('آی‌پی ثبت‌نام')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_suspicious')
                    ->label('مشکوک؟')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('suspicion_reason')
                    ->label('دلیل شک')
                    ->limit(30)
                    ->tooltip(fn (ReferralLog $record): string => $record->suspicion_reason ?? ''),

                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('is_suspicious')
                    ->label('فقط موارد مشکوک/تقلب')
                    ->query(fn (Builder $query) => $query->where('is_suspicious', true)),

                SelectFilter::make('level')
                    ->label('سطح رفرال')
                    ->options([
                        1 => 'سطح ۱',
                        2 => 'سطح ۲',
                        3 => 'سطح ۳',
                    ]),
            ])
            ->actions([
                // اکشن تایید و رفع اتهام
                Action::make('dismiss_suspicion')
                    ->label('رفع سوءظن')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ReferralLog $record): bool => $record->is_suspicious)
                    ->action(function (ReferralLog $record) {
                        $record->update([
                            'is_suspicious' => false,
                            'suspicion_reason' => null
                        ]);
                        
                        // اضافه کردن حجم به معرف
                        if ($record->referrer && !$record->referrer->referral_banned) {
                            $record->referrer->increment('referral_traffic_balance', $record->traffic_reward);
                        }

                        Notification::make()
                            ->title('رفع سوءظن انجام شد')
                            ->body('این رفرال تایید شد و ترافیک به حساب معرف واریز گردید.')
                            ->success()
                            ->send();
                    }),

                // اکشن مسدودسازی معرف
                Action::make('ban_referrer')
                    ->label('مسدودسازی رفرال معرف')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ReferralLog $record): bool => $record->referrer && !$record->referrer->referral_banned)
                    ->action(function (ReferralLog $record) {
                        if ($record->referrer) {
                            $record->referrer->update(['referral_banned' => true]);
                        }

                        Notification::make()
                            ->title('معرف مسدود شد')
                            ->body('دسترسی کاربر معرف به سیستم رفرال مسدود گردید.')
                            ->danger()
                            ->send();
                    }),

                // اکشن رفع مسدودسازی معرف
                Action::make('unban_referrer')
                    ->label('رفع مسدودسازی رفرال')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (ReferralLog $record): bool => $record->referrer && $record->referrer->referral_banned)
                    ->action(function (ReferralLog $record) {
                        if ($record->referrer) {
                            $record->referrer->update(['referral_banned' => false]);
                        }

                        Notification::make()
                            ->title('مسدودیت رفع شد')
                            ->body('کاربر معرف مجددا می‌تواند از سیستم رفرال استفاده کند.')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListReferralLogs::route('/'),
        ];
    }
}
