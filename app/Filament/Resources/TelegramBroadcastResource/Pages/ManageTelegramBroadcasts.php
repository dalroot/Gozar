<?php

namespace App\Filament\Resources\TelegramBroadcastResource\Pages;

use App\Filament\Resources\TelegramBroadcastResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Jobs\SendTelegramBroadcast;

class ManageTelegramBroadcasts extends Page
{
    protected static string $resource = TelegramBroadcastResource::class;
    protected static ?string $title = 'ارسال پیام همگانی';
    protected static string $view = 'filament.resources.telegram-broadcast.manage-telegram-broadcast';

    // --- تعریف یک اکشن برای ارسال پیام ---
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_broadcast')
                ->label('ارسال پیام همگانی جدید')
                ->color('danger') // رنگ متمایز
                ->icon('heroicon-o-arrow-path')
                ->modalHeading('ارسال پیام همگانی جدید')
                ->form([
                    \Filament\Forms\Components\Select::make('target')
                        ->label('هدف ارسال')
                        ->options([
                            'users' => 'ارسال به همه کاربران ربات',
                            'channel' => 'ارسال به کانال تلگرام',
                            'all' => 'ارسال به هر دو (کاربران و کانال)',
                        ])
                        ->default('users')
                        ->required()
                        ->live(),
                    \Filament\Forms\Components\TextInput::make('channel_id')
                        ->label('آیدی عددی یا یوزرنیم کانال تلگرام')
                        ->helperText('مثال: @mychannel یا -100xxxxxxxx. اگر خالی بگذارید به صورت پیش‌فرض به کانال فیش‌ها ارسال می‌شود.')
                        ->visible(fn ($get) => in_array($get('target'), ['channel', 'all'])),
                    Textarea::make('message')
                        ->label('متن پیام همگانی (برای تلگرام)')
                        ->helperText('می‌توانید از کدهای HTML (مانند <b>خط ضخیم</b>، <i>کج</i> و <tg-emoji emoji-id="5188481279963715781">🚀</tg-emoji>) استفاده کنید.')
                        ->required()
                        ->rows(8)
                        ->maxLength(4096),
                ])
                ->action(function (array $data) {

                    // 1. Job را به صف می‌فرستد
                    SendTelegramBroadcast::dispatch($data['message'], $data['target'], $data['channel_id'] ?? null);

                    // 2. نمایش نوتیفیکیشن موفقیت
                    Notification::make()
                        ->title('در حال ارسال...')
                        ->body('عملیات ارسال پیام همگانی در پس‌زمینه (Queue) آغاز شد. ممکن است تکمیل آن زمان ببرد.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
