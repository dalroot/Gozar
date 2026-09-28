<?php

namespace Modules\TelegramBot\Filament\Resources\TelegramBotSettingResource\Pages;

use Filament\Forms\Components\Repeater;
use Modules\TelegramBot\Filament\Resources\TelegramBotSettingResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use App\Models\TelegramBotSetting;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;

class ManageTelegramBotSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TelegramBotSettingResource::class;
    protected static string $view = 'filament.pages.manage-settings';


    public ?string $activeTab = 'settings';
    public ?array $data = [];


    public function mount(): void
    {
        $settings = TelegramBotSetting::all()->pluck('value', 'key')->toArray();


        if (isset($settings['deposit_amounts'])) {

            $decodedAmounts = json_decode($settings['deposit_amounts'], true);
            // اطمینان از اینکه خروجی یک آرایه است
            $settings['deposit_amounts'] = is_array($decodedAmounts) ? $decodedAmounts : [];
        } else {
            $settings['deposit_amounts'] = [];
        }


        $this->currentAmounts = $settings['deposit_amounts'];

        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('آموزش‌های اتصال')->schema([
                    Textarea::make('tutorial_android')->label('راهنمای اندروید (V2RayNG)')->rows(8),
                    Textarea::make('tutorial_ios')->label('راهنمای آیفون (V2Box/Streisand)')->rows(8),
                    Textarea::make('tutorial_windows')->label('راهنمای ویندوز (V2RayN)')->rows(8),
                ])->columnSpan('full')->hidden(fn() => $this->activeTab !== 'tutorials'),


                Section::make('پیام‌های عمومی')->schema([
                    Textarea::make('welcome_message')->label('پیام خوش‌آمدگویی')->rows(5),
                    Textarea::make('start_message')->label('پیام دستور /start')->rows(3),
                ])->columnSpan('full')->hidden(fn() => $this->activeTab !== 'messages'),

//                Section::make('تنظیمات ربات')->schema([
//                    Textarea::make('bot_name')->label('نام ربات')->rows(1),
//                    Textarea::make('bot_token')->label('توکن ربات')->rows(1),
//                ])->columnSpan('full')->hidden(fn() => $this->activeTab !== 'settings'),






                Section::make('تنظیمات کیف پول')
                    ->schema([
                        Repeater::make('deposit_amounts')
                            ->label('مبلغ‌های پیش‌فرض شارژ')
                            ->addActionLabel('افزودن مبلغ جدید')
                            ->columns(1)
                            ->schema([
                                TextInput::make('amount')
                                    ->label('مبلغ (تومان)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('تومان')
                                    ->minValue(1000),
                            ])
                            ->helperText('این مبالغ به صورت دکمه در ربات به کاربر نمایش داده می‌شوند.'),
                    ]),

                Section::make('تنظیمات کانال تلگرام')
                    ->schema([
                        TextInput::make('telegram_channel')
                            ->label('آیدی کانال رسمی تلگرام')
                            ->placeholder('مثال: @LookaNet یا -100xxxxxxxxxx')
                            ->helperText('آیدی یا یوزرنیم کانال اصلی خود را وارد کنید تا پیام‌های همگانی و دکمه‌های مربوطه به آن ارسال شوند.'),
                    ])
                    ->columnSpan('full')
                    ->collapsible(),

                Section::make('شناسه‌های اموجی پرمیوم دکمه‌ها (Telegram Bot API 9.4+)')
                    ->schema([
                        TextInput::make('emoji_plans')->label('اموجی دکمه فروشگاه (Emoji ID)')->helperText('مثال: 5463138830174092497'),
                        TextInput::make('emoji_deposit')->label('اموجی دکمه شارژ (Emoji ID)'),
                        TextInput::make('emoji_orders')->label('اموجی دکمه سفارش‌ها (Emoji ID)'),
                        TextInput::make('emoji_profile')->label('اموجی دکمه حساب من (Emoji ID)'),
                        TextInput::make('emoji_referral')->label('اموجی دکمه دعوت (Emoji ID)'),
                        TextInput::make('emoji_support')->label('اموجی دکمه پشتیبانی (Emoji ID)'),
                        TextInput::make('emoji_about')->label('اموجی دکمه درباره (Emoji ID)'),
                        TextInput::make('emoji_trial')->label('اموجی دکمه تست رایگان (Emoji ID)'),
                    ])
                    ->description('شناسه عددی (Emoji ID) ۶۴ بیتی اموجی‌های پرمیوم متحرک را جهت نمایش در دکمه‌های منوی اصلی وارد کنید.')
                    ->columnSpan('full')
                    ->collapsible()
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $formData = $this->form->getState();


        if (isset($formData['deposit_amounts'])) {

            $formData['deposit_amounts'] = json_encode($formData['deposit_amounts']);
        }

        foreach ($formData as $key => $value) {
            TelegramBotSetting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        }

        Notification::make()->title('تنظیمات با موفقیت ذخیره شد.')->success()->send();

        // رفرش کردن صفحه برای نمایش تغییرات
        $this->redirect(static::getResource()::getUrl('index'));
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('ذخیره تغییرات')
                ->submit('submit'),
        ];
    }
}
