<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'فروش و مالی';

    protected static ?string $navigationLabel = 'پلن‌های سرویس';
    protected static ?string $pluralModelLabel = 'پلن‌های سرویس';
    protected static ?string $modelLabel = 'پلن سرویس';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // بخش چپ: اطلاعات اصلی
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('📝 اطلاعات اصلی پلن')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('نام پلن')
                                            ->required()
                                            ->placeholder('مثلاً: اشتراک طلایی یک‌ماهه'),
                                            
                                        Forms\Components\TextInput::make('price')
                                            ->label('قیمت (تومان)')
                                            ->numeric()
                                            ->required()
                                            ->placeholder('مثلاً: ۸۰۰۰۰')
                                            ->suffix('تومان'),
                                    ]),

                                Forms\Components\Section::make('⚙️ وضعیت نمایش')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('پلن فعال است؟')
                                            ->helperText('کاربران در ربات و سایت می‌توانند این پلن را ببینند.')
                                            ->default(true),

                                        Forms\Components\Toggle::make('is_popular')
                                            ->label('پلن ویژه / محبوب')
                                            ->helperText('این پلن با برچسب 🔥 محبوب‌ترین در سایت نمایش داده می‌شود.')
                                            ->default(false),
                                    ]),
                            ])
                            ->columnSpan(2),

                        // بخش راست: منابع و متدها
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('📊 منابع و محدودیت‌ها')
                                    ->schema([
                                        Forms\Components\TextInput::make('volume_gb')
                                            ->label('حجم سرویس (گیگابایت)')
                                            ->numeric()
                                            ->required()
                                            ->default(30)
                                            ->suffix('GB')
                                            ->helperText('حجم مجاز مصرف کاربر را مشخص کنید.'),

                                        Forms\Components\Select::make('duration_days')
                                            ->label('مدت زمان اعتبار پلن')
                                            ->options([
                                                7 => '⚡️ ۷ روزه (تست کوتاه)',
                                                15 => '🗓 ۱۵ روزه (نیم ماه)',
                                                30 => '🗓 ۳۰ روزه (۱ ماهه)',
                                                60 => '🗓 ۶۰ روزه (۲ ماهه)',
                                                90 => '🔥 ۹۰ روزه (۳ ماهه)',
                                                180 => '💎 ۱۸۰ روزه (۶ ماهه)',
                                                365 => '👑 ۳۶۵ روزه (۱ ساله)',
                                            ])
                                            ->required()
                                            ->default(30)
                                            ->native(false)
                                            ->helperText('مدت زمان اعتبار اشتراک کاربر را انتخاب کنید.'),
                                    ]),

                                Forms\Components\Section::make('🔌 تنظیمات گروه پنل')
                                    ->schema([
                                        Forms\Components\Select::make('pasargad_group_id')
                                            ->label('گروه پاسارگاد')
                                            ->options(function () {
                                                try {
                                                    $settings = \App\Models\Setting::pluck('value', 'key');
                                                    $host = $settings['pasargad_host'] ?? null;
                                                    $user = $settings['pasargad_sudo_username'] ?? null;
                                                    $pass = $settings['pasargad_sudo_password'] ?? null;
                                                    
                                                    if (!$host || !$user || !$pass) {
                                                        return ['' => '⚠️ تنظیمات پاسارگاد ناقص'];
                                                    }
                                                    
                                                    $service = new \App\Services\PasargadService($host, $user, $pass);
                                                    $groups = $service->getGroups();
                                                    
                                                    if (empty($groups)) {
                                                        return ['' => '⚠️ گروهی یافت نشد'];
                                                    }
                                                    
                                                    $options = ['' => '🔄 از تنظیمات کلی'];
                                                    foreach ($groups as $group) {
                                                        $id = $group['id'] ?? null;
                                                        $name = $group['name'] ?? 'بدون نام';
                                                        if ($id !== null) {
                                                            $options[$id] = "{$name} (ID: {$id})";
                                                        }
                                                    }
                                                    return $options;
                                                } catch (\Exception $e) {
                                                    return ['' => '⚠️ خطا در دریافت گروه‌ها'];
                                                }
                                            })
                                            ->helperText('خالی = استفاده از تنظیمات کلی')
                                            ->searchable()
                                            ->native(false),
                                    ]),
                            ])
                            ->columnSpan(1),

                        // بخش تمام عرض: ویژگی‌ها
                        Forms\Components\Section::make('✨ ویژگی‌های برجسته پلن')
                            ->schema([
                                Forms\Components\Textarea::make('features')
                                    ->label('ویژگی‌های پلن')
                                    ->required()
                                    ->rows(4)
                                    ->placeholder("سرعت دانلود نامحدود\nمناسب برای گیمینگ و ترید\nپشتیبانی ۲۴ ساعته")
                                    ->helperText('هر ویژگی را در یک خط جدید بنویسید (با دکمه اینتر جدا کنید).'),
                            ])
                            ->columnSpan(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام پلن'),
                Tables\Columns\TextColumn::make('price')
                    ->label('قیمت کل')
                    ->formatStateUsing(fn ($record) =>
                        number_format($record->price) . ' تومان' .
                        ($record->duration_days > 30 ? ' (' . number_format($record->monthly_price) . ' تومان/ماه)' : '')
                    ),
                Tables\Columns\BooleanColumn::make('is_popular')->label('محبوب'),
                Tables\Columns\BooleanColumn::make('is_active')->label('فعال'),
                Tables\Columns\TextColumn::make('duration_days')
                    ->label('مدت زمان')
                    ->formatStateUsing(fn ($state, $record) => $record->duration_label)
                    ->sortable(),

                Tables\Columns\TextColumn::make('monthly_price')
                    ->label('قیمت ماهانه')
                    ->formatStateUsing(fn ($record) => number_format($record->monthly_price) . ' تومان')
                    ->sortable(),



            ])


            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
