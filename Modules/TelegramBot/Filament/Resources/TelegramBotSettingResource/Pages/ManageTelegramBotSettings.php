<?php

namespace Modules\TelegramBot\Filament\Resources\TelegramBotSettingResource\Pages;

use App\Models\TelegramBotSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Modules\TelegramBot\Filament\Resources\TelegramBotSettingResource;

class ManageTelegramBotSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TelegramBotSettingResource::class;
    protected static string $view = 'filament.pages.manage-settings';
    protected static ?string $title = 'محتوا و تنظیمات ربات فروش';

    // Kept for compatibility with the existing shared Blade wrapper.
    public ?string $activeTab = 'settings';
    public ?array $data = [];

    public function mount(): void
    {
        $settings = TelegramBotSetting::all()->pluck('value', 'key')->toArray();
        $decodedAmounts = json_decode($settings['deposit_amounts'] ?? '[]', true);
        $settings['deposit_amounts'] = is_array($decodedAmounts) ? $decodedAmounts : [];
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('تنظیمات ربات فروش')
                    ->persistTab()
                    ->persistTabInQueryString('section')
                    ->tabs([
                        Tabs\Tab::make('پیام‌های عمومی')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                Section::make('شروع گفتگو')
                                    ->description('متن‌هایی که کاربر هنگام ورود یا اجرای دستور شروع در ربات فروش می‌بیند.')
                                    ->schema([
                                        Textarea::make('welcome_message')
                                            ->label('پیام خوش‌آمدگویی')
                                            ->rows(6),
                                        Textarea::make('start_message')
                                            ->label('پیام دستور /start')
                                            ->rows(5),
                                    ]),
                            ]),

                        Tabs\Tab::make('آموزش اتصال')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                Section::make('راهنمای سیستم‌عامل‌ها')
                                    ->description('متن راهنما را کوتاه، مرحله‌ای و متناسب با برنامهٔ پیشنهادی هر سیستم‌عامل نگه دارید.')
                                    ->schema([
                                        Textarea::make('tutorial_android')->label('اندروید — V2RayNG')->rows(9),
                                        Textarea::make('tutorial_ios')->label('آیفون — V2Box / Streisand')->rows(9),
                                        Textarea::make('tutorial_windows')->label('ویندوز — V2RayN')->rows(9),
                                    ]),
                            ]),

                        Tabs\Tab::make('فروش و کیف پول')
                            ->icon('heroicon-o-wallet')
                            ->schema([
                                Section::make('مبلغ‌های آمادهٔ شارژ')
                                    ->description('این مبلغ‌ها به‌صورت گزینه‌های سریع در ربات فروش نمایش داده می‌شوند.')
                                    ->schema([
                                        Repeater::make('deposit_amounts')
                                            ->label('مبلغ‌های پیش‌فرض')
                                            ->addActionLabel('افزودن مبلغ')
                                            ->reorderable()
                                            ->collapsible()
                                            ->schema([
                                                TextInput::make('amount')
                                                    ->label('مبلغ')
                                                    ->required()
                                                    ->numeric()
                                                    ->suffix('تومان')
                                                    ->minValue(1000),
                                            ]),
                                    ]),
                                Section::make('کانال رسمی')
                                    ->schema([
                                        TextInput::make('telegram_channel')
                                            ->label('شناسه کانال تلگرام')
                                            ->placeholder('@Rozaneh یا -100xxxxxxxxxx')
                                            ->helperText('برای کانال عمومی از @username و برای کانال خصوصی از شناسه عددی استفاده کنید.'),
                                    ]),
                            ]),

                        Tabs\Tab::make('ظاهر دکمه‌ها')
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                Section::make('شناسه اموجی‌های اختصاصی')
                                    ->description('هر فیلد یک Emoji ID عددی تلگرام می‌پذیرد. خالی‌بودن فیلد باعث استفاده از ظاهر پیش‌فرض می‌شود.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('emoji_plans')->label('فروشگاه')->numeric(),
                                        TextInput::make('emoji_deposit')->label('شارژ کیف پول')->numeric(),
                                        TextInput::make('emoji_orders')->label('سفارش‌ها')->numeric(),
                                        TextInput::make('emoji_profile')->label('حساب من')->numeric(),
                                        TextInput::make('emoji_referral')->label('دعوت از دوستان')->numeric(),
                                        TextInput::make('emoji_support')->label('پشتیبانی')->numeric(),
                                        TextInput::make('emoji_about')->label('درباره روزنه')->numeric(),
                                        TextInput::make('emoji_trial')->label('تست رایگان')->numeric(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
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

        Notification::make()
            ->title('تنظیمات ربات فروش ذخیره شد')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('ذخیره تغییرات')
                ->submit('submit'),
        ];
    }
}
