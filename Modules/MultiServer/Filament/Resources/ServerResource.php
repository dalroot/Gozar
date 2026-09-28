<?php

namespace Modules\MultiServer\Filament\Resources;

use Modules\MultiServer\Filament\Resources\ServerResource\Pages;
use Modules\MultiServer\Models\Server;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\XUIService;
use Filament\Notifications\Notification;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;
    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationGroup = 'سیستم و سرورها';
    protected static ?string $label = 'سرور';
    protected static ?string $pluralLabel = 'سرورها';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('ServerTabs')
                    ->persistTab()
                    ->tabs([
                        // Tab 1: Panel Connection Info
                        Forms\Components\Tabs\Tab::make('🔌 تنظیمات اتصال پنل')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('location_id')
                                        ->relationship('location', 'name')
                                        ->label('لوکیشن (کشور)')
                                        ->preload()
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('name')->required()->label('نام کشور'),
                                            Forms\Components\TextInput::make('slug')->required()->label('شناسه'),
                                            Forms\Components\TextInput::make('flag')->label('پرچم'),
                                        ])
                                        ->required(),

                                    Forms\Components\TextInput::make('name')
                                        ->label('نام سرور')
                                        ->required()
                                        ->placeholder('مثال: Server Germany 1'),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('ip_address')
                                        ->label('آدرس IP یا دامنه')
                                        ->required()
                                        ->placeholder('مثال: sub.domain.com (بدون http/https)'),

                                    Forms\Components\TextInput::make('port')
                                        ->label('پورت پنل')
                                        ->numeric()
                                        ->required()
                                        ->default(54321),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('username')
                                        ->label('نام کاربری پنل')
                                        ->required(),

                                    Forms\Components\TextInput::make('password')
                                        ->label('رمز عبور پنل')
                                        ->password()
                                        ->revealable()
                                        ->required(),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('path')
                                        ->label('URL Path')
                                        ->default('/')
                                        ->placeholder('/')
                                        ->helperText('اگر پنل روی ساب‌فولدر است (مثلاً /panel/) وارد کنید.'),

                                    Forms\Components\Toggle::make('is_https')
                                        ->label('اتصال امن (SSL/HTTPS)')
                                        ->default(false)
                                        ->inline(false),
                                ]),

                                Forms\Components\TextInput::make('inbound_id')
                                    ->label('شناسه اینباند (Inbound ID)')
                                    ->required()
                                    ->numeric()
                                    ->helperText('برای دریافت لیست، دکمه سمت چپ را بزنید.')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('selectInbound')
                                            ->icon('heroicon-o-list-bullet')
                                            ->label('انتخاب از لیست')
                                            ->color('primary')
                                            ->modalHeading('لیست اینباندهای موجود در سرور')
                                            ->modalSubmitActionLabel('تایید و انتخاب')
                                            ->form(function (Forms\Get $get) {
                                                $rawIp = $get('ip_address');
                                                $cleanIp = str_replace(['http://', 'https://', '/'], '', $rawIp);

                                                $protocol = $get('is_https') ? 'https' : 'http';
                                                $port = $get('port');
                                                $path = $get('path');

                                                $host = "{$protocol}://{$cleanIp}:{$port}{$path}";

                                                $user = $get('username');
                                                $pass = $get('password');

                                                if (!$user || !$pass || !$cleanIp) {
                                                    return [
                                                        Forms\Components\Placeholder::make('error')
                                                            ->content('❌ لطفاً ابتدا فیلدهای آدرس، پورت، نام کاربری و رمز عبور را پر کنید.')
                                                            ->extraAttributes(['class' => 'text-danger-600'])
                                                    ];
                                                }

                                                try {
                                                    $xui = new \App\Services\XUIService($host, $user, $pass);
                                                    if (!$xui->login()) {
                                                        throw new \Exception('اتصال به پنل ناموفق بود. نام کاربری یا رمز عبور اشتباه است.');
                                                    }

                                                    $inbounds = $xui->getInbounds();
                                                    if (empty($inbounds)) {
                                                        throw new \Exception('هیچ اینباندی در این سرور یافت نشد.');
                                                    }

                                                    $options = [];
                                                    foreach ($inbounds as $inbound) {
                                                        $id = $inbound['id'];
                                                        $remark = $inbound['remark'] ?? 'بدون نام';
                                                        $protocol = strtoupper($inbound['protocol'] ?? 'UNKNOWN');
                                                        $port = $inbound['port'] ?? '?';

                                                        $options[$id] = "ID: {$id}  |  {$remark}  |  {$protocol} : {$port}";
                                                    }

                                                    return [
                                                        Forms\Components\Radio::make('selected_inbound')
                                                            ->label('یکی از اینباندها را انتخاب کنید:')
                                                            ->options($options)
                                                            ->required()
                                                            ->columns(1)
                                                    ];

                                                } catch (\Exception $e) {
                                                    return [
                                                        Forms\Components\Placeholder::make('error')
                                                            ->content('خطا در دریافت لیست: ' . $e->getMessage())
                                                            ->extraAttributes(['class' => 'text-danger-600 bg-danger-50 p-3 rounded'])
                                                    ];
                                                }
                                            })
                                            ->action(function (array $data, Forms\Set $set) {
                                                if (isset($data['selected_inbound'])) {
                                                    $set('inbound_id', $data['selected_inbound']);
                                                    Notification::make()->title('اینباند انتخاب شد')->success()->send();
                                                }
                                            })
                                    ),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('سرور فعال است')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        // Tab 2: Output Link Configuration
                        Forms\Components\Tabs\Tab::make('🔗 تنظیمات لینک خروجی')
                            ->schema([
                                Forms\Components\Radio::make('link_type')
                                    ->label('نوع لینک خروجی')
                                    ->options([
                                        'single' => '🔸 لینک تکی (Single Config)',
                                        'subscription' => '🔹 لینک سابسکریپشن (Subscription URL)',
                                        'tunnel' => '🚇 لینک تانل شده (Tunneled)',
                                    ])
                                    ->default('single')
                                    ->required()
                                    ->inline()
                                    ->live(),

                                // Subscription Options
                                Forms\Components\Grid::make(3)
                                    ->visible(fn (Forms\Get $get) => $get('link_type') === 'subscription')
                                    ->schema([
                                        Forms\Components\TextInput::make('subscription_domain')
                                            ->label('دامنه/آدرس سابسکریپشن')
                                            ->placeholder('sub.example.com')
                                            ->prefix(fn (Forms\Get $get) => $get('is_https') ? 'https://' : 'http://')
                                            ->required(),

                                        Forms\Components\TextInput::make('subscription_path')
                                            ->label('مسیر (Path) سابسکریپشن')
                                            ->placeholder('/sub/')
                                            ->default('/sub/'),

                                        Forms\Components\TextInput::make('subscription_port')
                                            ->label('پورت سابسکریپشن')
                                            ->numeric()
                                            ->default(2053),
                                    ]),

                                // Tunnel Options
                                Forms\Components\Grid::make(3)
                                    ->visible(fn (Forms\Get $get) => $get('link_type') === 'tunnel')
                                    ->schema([
                                        Forms\Components\TextInput::make('tunnel_address')
                                            ->label('آدرس IP/دامنه تانل')
                                            ->placeholder('tunnel.domain.com')
                                            ->required(),

                                        Forms\Components\TextInput::make('tunnel_port')
                                            ->label('پورت تانل')
                                            ->numeric()
                                            ->default(443),

                                        Forms\Components\Toggle::make('tunnel_is_https')
                                            ->label('اتصال امن (HTTPS) تانل')
                                            ->default(false)
                                            ->inline(false),
                                    ]),

                                Forms\Components\Placeholder::make('single_info')
                                    ->content('✅ لینک تکی مستقیماً با آدرس IP/دامنه اصلی پنل ساخته می‌شود.')
                                    ->visible(fn (Forms\Get $get) => $get('link_type') === 'single')
                                    ->columnSpanFull(),
                            ]),

                        // Tab 3: Capacity Management
                        Forms\Components\Tabs\Tab::make('📊 مدیریت ظرفیت')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('capacity')
                                        ->numeric()
                                        ->default(100)
                                        ->label('ظرفیت کل سرور (کاربر)'),

                                    Forms\Components\TextInput::make('current_users')
                                        ->numeric()
                                        ->default(0)
                                        ->label('تعداد کاربران فعلی')
                                        ->disabled(),
                                ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام سرور')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('لوکیشن')
                    ->formatStateUsing(fn ($record) => ($record->location?->flag ? $record->location->flag . ' ' : '') . $record->location?->name)
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('آدرس IP')
                    ->copyable(),

                Tables\Columns\TextColumn::make('link_type')
                    ->label('نوع لینک')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'single' => 'gray',
                        'subscription' => 'success',
                        'tunnel' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'single' => 'لینک تکی',
                        'subscription' => 'سابسکریپشن',
                        'tunnel' => 'تانل شده',
                        default => $state,
                    }),


                Tables\Columns\TextColumn::make('current_users')
                    ->label('وضعیت ظرفیت')
                    ->formatStateUsing(fn ($record) => "{$record->current_users} / {$record->capacity}")
                    ->color(fn ($record) => $record->current_users >= $record->capacity ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        try {
            $settings = \App\Models\Setting::all()->pluck('value', 'key');
            $panelType = $settings->get('panel_type');
            $isMultiEnabled = filter_var(
                $settings->get('enable_multilocation', false),
                FILTER_VALIDATE_BOOLEAN
            );

            return $panelType === 'xui' && $isMultiEnabled;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected static function generateDefaultSubUrl(Forms\Get $get): string
    {
        $ip = $get('ip_address');
        $isHttps = $get('is_https');
        $port = $get('subscription_port') ?? '';

        if (empty($ip)) {
            return 'https://example.com/sub/';
        }

        $protocol = $isHttps ? 'https://' : 'http://';
        // Assuming default path is /sub/
        return "{$protocol}{$ip}/sub/";
    }

}
