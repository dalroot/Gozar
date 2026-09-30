<?php

namespace App\Filament\Resources;

use App\Events\OrderPaid;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Inbound;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\MarzbanService;
use App\Services\XUIService;
use App\Services\RemnawaveService;
use App\Services\PasargadService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Keyboard\Keyboard;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Str;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'سفارشات';
    protected static ?string $modelLabel = 'سفارش';
    protected static ?string $pluralModelLabel = 'سفارشات';
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = -9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    // Left column: Order items & pricing (2 spans)
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make('📦 اطلاعات سفارش و اقلام')
                                ->schema([
                                    Forms\Components\Select::make('user_id')
                                        ->relationship('user', 'name')
                                        ->label('کاربر خریدار')
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Forms\Components\Select::make('plan_id')
                                        ->relationship('plan', 'name')
                                        ->label('پلن خریداری شده')
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('انتخاب کنید (خالی = شارژ کیف پول)'),

                                    Forms\Components\Select::make('server_id')
                                        ->relationship('server', 'name')
                                        ->label('سرور / لوکیشن مقصد')
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('انتخاب سرور مقصد...'),

                                    Forms\Components\TextInput::make('amount')
                                        ->label('مبلغ نهایی تراکنش')
                                        ->numeric()
                                        ->required()
                                        ->suffix('تومان')
                                        ->placeholder('مثلاً: 50000'),
                                ])->columns(2),
                        ])
                        ->columnSpan(2),

                    // Right column: Status and Metadata (1 span)
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make('⚙️ وضعیت و تنظیمات پرداخت')
                                ->schema([
                                    Forms\Components\Select::make('status')
                                        ->label('وضعیت نهایی سفارش')
                                        ->options([
                                            'pending' => '⏳ در انتظار پرداخت',
                                            'paid' => '✅ پرداخت شده',
                                            'expired' => '❌ منقضی/ناموفق شده',
                                        ])
                                        ->required()
                                        ->native(false),

                                    Forms\Components\Select::make('payment_method')
                                        ->label('روش پرداخت')
                                        ->options([
                                            'wallet' => '👛 کیف پول داخلی',
                                            'card' => '💳 کارت به کارت',
                                            'bank' => '🏦 درگاه آنلاین',
                                            'crypto' => '🪙 رمز ارز (Crypto)',
                                            'manual_admin' => '🛠️ تایید دستی ادمین',
                                        ])
                                        ->native(false),

                                    Forms\Components\Select::make('source')
                                        ->label('منبع سفارش')
                                        ->options([
                                            'web' => '🌐 وب‌سایت',
                                            'telegram' => '💬 ربات تلگرام',
                                        ])
                                        ->default('web')
                                        ->native(false),

                                    Forms\Components\TextInput::make('panel_username')
                                        ->label('نام کاربری پنل (Username)')
                                        ->placeholder('user-x-123')
                                        ->helperText('نام کاربری ساخته شده در پنل VPN'),
                                ]),
                        ])
                        ->columnSpan(1),

                    // Full width section: Config connection details
                    Forms\Components\Section::make('🔗 جزئیات فنی و لینک‌های اتصال')
                        ->schema([
                            Forms\Components\Textarea::make('config_details')
                                ->label('لینک‌های کانفیگ سرویس')
                                        ->rows(6)
                                        ->placeholder("vless://...\nvmess://...")
                                        ->helperText('لینک‌های کانفیگ تحویل داده شده به کاربر را وارد کنید.'),
                        ])
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('card_payment_receipt')->label('رسید')->disk('public')->toggleable()->size(60)->circular()->url(fn (Order $record): ?string => $record->card_payment_receipt ? Storage::disk('public')->url($record->card_payment_receipt) : null)->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('receipt_text')
                    ->label('متن رسید / هش')
                    ->getStateUsing(fn (Order $record): ?string => 
                        $record->card_payment_receipt && str_starts_with($record->card_payment_receipt, 'text_receipt:') 
                            ? str_replace('text_receipt:', '', $record->card_payment_receipt) 
                            : ($record->card_payment_receipt && str_starts_with($record->card_payment_receipt, 'crypto_hash:') 
                                ? str_replace('crypto_hash:', '', $record->card_payment_receipt) 
                                : null)
                    )
                    ->limit(25)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')->label('کاربر')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('plan.name')->label('پلن / آیتم')->default(fn (Order $record): string => $record->plan_id ? $record->plan->name : "شارژ کیف پول")->description(function (Order $record): string {
                    if ($record->renews_order_id) return " (تمدید سفارش #" . $record->renews_order_id . ")";
                    return '';
                })->color(fn(Order $record) => $record->renews_order_id ? 'primary' : 'gray'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ نهایی')
                    ->formatStateUsing(fn ($state) => number_format($state) . ' تومان')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('درگاه/روش')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'wallet' => 'success',
                        'card' => 'warning',
                        'bank' => 'info',
                        'crypto' => 'primary',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'wallet' => 'کیف پول',
                        'card' => 'کارت به کارت',
                        'bank' => 'درگاه آنلاین',
                        'crypto' => 'کریپتو',
                        'manual_admin' => 'تایید دستی',
                        default => $state ?? 'نامشخص'
                    })
                    ->default('—'),
                IconColumn::make('source')->label('منبع')->icon(fn (?string $state): string => match ($state) { 'web' => 'heroicon-o-globe-alt', 'telegram' => 'heroicon-o-paper-airplane', default => 'heroicon-o-question-mark-circle' })->color(fn (?string $state): string => match ($state) { 'web' => 'primary', 'telegram' => 'info', default => 'gray' }),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')->badge()->color(fn (string $state): string => match ($state) { 'pending' => 'warning', 'paid' => 'success', 'expired' => 'danger', default => 'gray' })->formatStateUsing(fn (string $state): string => match ($state) { 'pending' => 'در انتظار پرداخت', 'paid' => 'پرداخت شده', 'expired' => 'منقضی شده', default => $state }),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ سفارش')->dateTime('Y-m-d')->sortable(),
                Tables\Columns\TextColumn::make('expires_at')->label('تاریخ انقضا')->dateTime('Y-m-d')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(['pending' => 'در انتظار پرداخت', 'paid' => 'پرداخت شده', 'expired' => 'منقضی شده']),
                Tables\Filters\SelectFilter::make('source')->label('منبع')->options(['web' => 'وب‌سایت', 'telegram' => 'تلگرام']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('approve')->label('تایید و اجرا')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()->modalHeading('تایید پرداخت سفارش')->modalDescription('آیا از تایید این پرداخت اطمینان دارید؟')->visible(fn (Order $order): bool => $order->status === 'pending')
                    ->action(function (Order $order) {
                        try {
                            if (\App\Services\PaymentService::approveOrder($order)) {
                                Notification::make()->title('عملیات موفقیت‌آمیز بود.')->success()->send();
                            } else {
                                Notification::make()->title('خطا')->body('پرداخت تایید نشد.')->danger()->send();
                            }
                        } catch (\Exception $e) {
                            Log::error("Manual approve error for order {$order->id}: " . $e->getMessage());
                            Notification::make()->title('خطا')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListOrders::route('/'), 'create' => Pages\CreateOrder::route('/create'), 'edit' => Pages\EditOrder::route('/{record}/edit')]; }
}
