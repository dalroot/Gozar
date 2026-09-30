<?php

namespace App\Filament\Pages;

use App\Models\Inbound;
use App\Models\Setting;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ThemeSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.theme-settings';
    protected static ?string $navigationLabel = 'تنظیمات سایت';
    protected static ?string $title = 'تنظیمات و محتوای سایت';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();


        foreach ($settings as $key => $value) {
            if ($value === '') {
                $settings[$key] = null;
            }
            if ($key === 'xui_default_inbound_id' && $value !== null) {
                $settings[$key] = (string) $value;
            }
            if ($key === 'xui_target_inbounds' && !empty($value)) {
                $decoded = is_string($value) ? json_decode($value, true) : $value;
                $settings[$key] = is_array($decoded) ? array_map('strval', $decoded) : [];
            }
        }

        $this->form->fill(array_merge([
            'panel_type' => 'marzban',
            'xui_host' => null,
            'xui_user' => null,
            'xui_pass' => null,
            'xui_default_inbound_id' => null,
            'xui_target_inbounds' => ['3', '7', '11', '14'],
            'xui_link_type' => 'single',
            'marzban_host' => null,
            'marzban_sudo_username' => null,
            'marzban_sudo_password' => null,
            'remnawave_host' => null,
            'remnawave_api_token' => null,
            'remnawave_squad_uuid' => null,
            'remnawave_node_hostname' => null,
        ], $settings));
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Tabs')
                ->id('main-tabs')
                ->persistTab()
                ->tabs([
                    Tabs\Tab::make('تنظیمات قالب')
                        ->icon('heroicon-o-swatch')
                        ->schema([
                            Select::make('active_theme')->label('قالب اصلی سایت')->options([
                                'welcome' => 'قالب خوش‌آمدگویی',
                                'rocket' => 'قالب RoketVPN (موشکی)',
                                'lookanet' => 'قالب پیشرفته لوکانت (Glassmorphism)',
                            ])->default('welcome')->live(),
                            Select::make('active_auth_theme')->label('قالب صفحات ورود/ثبت‌نام')->options([
                                'default' => 'قالب پیش‌فرض (Breeze)',
                                'cyberpunk' => 'قالب سایبرپانک',
                                'rocket' => 'قالب RoketVPN (موشکی)',
                            ])->default('cyberpunk')->live(),
                        ]),

                    Tabs\Tab::make('محتوای قالب RoketVPN (موشکی)')
                        ->icon('heroicon-o-rocket-launch')
                        ->visible(fn(Get $get) => $get('active_theme') === 'rocket')
                        ->schema([
                            Section::make('عمومی')->schema([
                                TextInput::make('rocket_navbar_brand')->label('نام برند در Navbar'),
                                TextInput::make('rocket_footer_text')->label('متن فوتر'),
                            ])->columns(2),
                            Section::make('بخش اصلی (Hero Section)')->schema([
                                TextInput::make('rocket_hero_title')->label('تیتر اصلی'),
                                Textarea::make('rocket_hero_subtitle')->label('زیرتیتر')->rows(2),
                                TextInput::make('rocket_hero_button_text')->label('متن دکمه اصلی'),
                            ]),
                            Section::make('بخش قیمت‌گذاری (Pricing)')->schema([
                                TextInput::make('rocket_pricing_title')->label('عنوان بخش'),
                            ]),
                            Section::make('بخش سوالات متداول (FAQ)')->schema([
                                TextInput::make('rocket_faq_title')->label('عنوان بخش'),
                                TextInput::make('rocket_faq1_q')->label('سوال اول'),
                                Textarea::make('rocket_faq1_a')->label('پاسخ اول')->rows(2),
                                TextInput::make('rocket_faq2_q')->label('سوال دوم'),
                                Textarea::make('rocket_faq2_a')->label('پاسخ دوم')->rows(2),
                            ]),
                            Section::make('لینک‌های اجتماعی')->schema([
                                TextInput::make('telegram_link')->label('لینک تلگرام (کامل)'),
                                TextInput::make('instagram_link')->label('لینک اینستاگرام (کامل)'),
                            ])->columns(2),
                        ]),

                    Tabs\Tab::make('محتوای قالب سایبرپانک')->icon('heroicon-o-bolt')->visible(fn(Get $get) => $get('active_theme') === 'cyberpunk')->schema([
                        Section::make('عمومی')->schema([
                            TextInput::make('cyberpunk_navbar_brand')->label('نام برند در Navbar')->placeholder('VPN Market'),
                            TextInput::make('cyberpunk_footer_text')->label('متن فوتر')->placeholder('© 2025 Quantum Network. اتصال برقرار شد.'),
                        ])->columns(2),
                        Section::make('بخش اصلی (Hero Section)')->schema([
                            TextInput::make('cyberpunk_hero_title')->label('تیتر اصلی')->placeholder('واقعیت را هک کن'),
                            Textarea::make('cyberpunk_hero_subtitle')->label('زیرتیتر')->rows(3),
                            TextInput::make('cyberpunk_hero_button_text')->label('متن دکمه اصلی')->placeholder('دریافت دسترسی'),
                        ]),
                        Section::make('بخش ویژگی‌ها (Features)')->schema([
                            TextInput::make('cyberpunk_features_title')->label('عنوان بخش')->placeholder('سیستم‌عامل آزادی دیجیتال شما'),
                            TextInput::make('cyberpunk_feature1_title')->label('عنوان ویژگی ۱')->placeholder('پروتکل Warp'),
                            Textarea::make('cyberpunk_feature1_desc')->label('توضیح ویژگی ۱')->rows(2),
                            TextInput::make('cyberpunk_feature2_title')->label('عنوان ویژگی ۲')->placeholder('حالت Ghost'),
                            Textarea::make('cyberpunk_feature2_desc')->label('توضیح ویژگی ۲')->rows(2),
                            TextInput::make('cyberpunk_feature3_title')->label('عنوان ویژگی ۳')->placeholder('اتصال پایدار'),
                            Textarea::make('cyberpunk_feature3_desc')->label('توضیح ویژگی ۳')->rows(2),
                            TextInput::make('cyberpunk_feature4_title')->label('عنوان ویژگی ۴')->placeholder('پشتیبانی Elite'),
                            Textarea::make('cyberpunk_feature4_desc')->label('توضیح ویژگی ۴')->rows(2),
                        ])->columns(2),
                        Section::make('بخش قیمت‌گذاری (Pricing)')->schema([
                            TextInput::make('cyberpunk_pricing_title')->label('عنوان بخش')->placeholder('انتخاب پلن اتصال'),
                        ]),
                        Section::make('بخش سوالات متداول (FAQ)')->schema([
                            TextInput::make('cyberpunk_faq_title')->label('عنوان بخش')->placeholder('اطلاعات طبقه‌بندی شده'),
                            TextInput::make('cyberpunk_faq1_q')->label('سوال اول')->placeholder('آیا اطلاعات کاربران ذخیره می‌شود؟'),
                            Textarea::make('cyberpunk_faq1_a')->label('پاسخ اول')->rows(2),
                            TextInput::make('cyberpunk_faq2_q')->label('سوال دوم')->placeholder('چگونه می‌توانم سرویس را روی چند دستگاه استفاده کنم؟'),
                            Textarea::make('cyberpunk_faq2_a')->label('پاسخ دوم')->rows(2),
                        ]),
                    ]),

                    Tabs\Tab::make('محتوای صفحات ورود')->icon('heroicon-o-key')->schema([
                        Section::make('متن‌های عمومی')->schema([TextInput::make('auth_brand_name')->label('نام برند')->placeholder('VPNMarket'),]),
                        Section::make('صفحه ورود (Login)')->schema([
                            TextInput::make('auth_login_title')->label('عنوان فرم ورود'),
                            TextInput::make('auth_login_email_placeholder')->label('متن داخل فیلد ایمیل'),
                            TextInput::make('auth_login_password_placeholder')->label('متن داخل فیلد رمز عبور'),
                            TextInput::make('auth_login_remember_me_label')->label('متن "مرا به خاطر بسپار"'),
                            TextInput::make('auth_login_forgot_password_link')->label('متن لینک "فراموشی رمز"'),
                            TextInput::make('auth_login_submit_button')->label('متن دکمه ورود'),
                            TextInput::make('auth_login_register_link')->label('متن لینک ثبت‌نام'),
                        ])->columns(2),
                        Section::make('صفحه ثبت‌نام (Register)')->schema([
                            TextInput::make('auth_register_title')->label('عنوان فرم ثبت‌نام'),
                            TextInput::make('auth_register_name_placeholder')->label('متن داخل فیلد نام'),
                            TextInput::make('auth_register_password_confirm_placeholder')->label('متن داخل فیلد تکرار رمز'),
                            TextInput::make('auth_register_submit_button')->label('متن دکمه ثبت‌نام'),
                            TextInput::make('auth_register_login_link')->label('متن لینک ورود'),
                        ])->columns(2),
                    ]),

                    Tabs\Tab::make('تنظیمات پنل V2Ray')
                        ->icon('heroicon-o-server-stack')
                        ->schema([
                            Radio::make('panel_type')
                                ->label('نوع پنل')
                                ->options([
                                    'marzban' => 'مرزبان',
                                    'xui' => 'تنظیمات پنل سنایی / X-UI / TX-UI',
                                    'pasargad' => '🦅 پاسارگاد (PasarGuard)',
                                    'remnawave' => 'پنل Remnawave',
                                ])
                                ->live()
                                ->required(),

                            Section::make('⚙️ حالت اتصال پنل')
                                ->description('نوع اتصال به پنل X-UI را انتخاب کنید')
                                ->visible(fn (Get $get) => $get('panel_type') === 'xui')
                                ->schema([
                                    Toggle::make('enable_multilocation')
                                        ->label('استفاده از سیستم مولتی لوکیشن (چند سروره)')
                                        ->helperText('در صورت فعال‌سازی، باید سرورها را از منوی «مولتی سرور» تنظیم کنید و کاربر هنگام خرید لوکیشن انتخاب می‌کند.')
                                        ->default(false)
                                        ->live(),
                                ]),

                            // پیام راهنما وقتی مولتی لوکیشن فعال است
                            Section::make('🌍 سیستم مولتی لوکیشن فعال است')
                                ->description('شما در حال استفاده از سیستم چند سروره هستید. تنظیمات سرورها از طریق منوی «مولتی سرور» در sidebar انجام می‌شود.')
                                ->visible(fn(Get $get) => $get('panel_type') === 'xui' && $get('enable_multilocation') === true)
                                ->schema([
                                    // می‌توانید اینجا یک placeholder یا اطلاعات تکمیلی بگذارید
                                ]),

                            Section::make('تنظیمات پنل مرزبان')
                                ->visible(fn (Get $get) => $get('panel_type') === 'marzban')
                                ->schema([
                                    TextInput::make('marzban_host')->label('آدرس پنل مرزبان'),
                                    TextInput::make('marzban_sudo_username')->label('نام کاربری ادمین'),
                                    TextInput::make('marzban_sudo_password')->label('رمز عبور ادمین')->password(),
                                    TextInput::make('marzban_node_hostname')->label('آدرس دامنه/سرور برای کانفیگ')
                                ]),

                            Section::make('🦅 تنظیمات پنل پاسارگاد')
                                ->description('اطلاعات اتصال به پنل PasarGuard')
                                ->icon('heroicon-o-server')
                                ->columns(2)
                                ->visible(fn (Get $get) => $get('panel_type') === 'pasargad')
                                ->schema([
                                    TextInput::make('pasargad_host')
                                        ->label('آدرس پنل پاسارگاد')
                                        ->placeholder('https://panel.example.com')
                                        ->columnSpan(2),
                                    TextInput::make('pasargad_sudo_username')
                                        ->label('نام کاربری ادمین'),
                                    TextInput::make('pasargad_sudo_password')
                                        ->label('رمز عبور ادمین')
                                        ->password(),
                                    TextInput::make('pasargad_node_hostname')
                                        ->label('آدرس نود (اختیاری)')
                                        ->placeholder('node.example.com')
                                        ->helperText('اگر آدرس سابسکریپشن متفاوت است اینجا وارد کنید')
                                        ->columnSpan(2),
                                    Select::make('pasargad_trial_group_id')
                                        ->label('گروه اکانت تست')
                                        ->options(function () {
                                            try {
                                                $host = Setting::where('key', 'pasargad_host')->first()?->value;
                                                $user = Setting::where('key', 'pasargad_sudo_username')->first()?->value;
                                                $pass = Setting::where('key', 'pasargad_sudo_password')->first()?->value;
                                                
                                                if (!$host || !$user || !$pass) {
                                                    return ['' => '⚠️ ابتدا تنظیمات پاسارگاد را ذخیره کنید'];
                                                }
                                                
                                                $service = new \App\Services\PasargadService($host, $user, $pass);
                                                $groups = $service->getGroups();
                                                
                                                if (empty($groups)) {
                                                    return ['' => '⚠️ گروهی یافت نشد'];
                                                }
                                                
                                                $options = [];
                                                foreach ($groups as $group) {
                                                    $id = $group['id'] ?? null;
                                                    $name = $group['name'] ?? 'بدون نام';
                                                    if ($id !== null) {
                                                        $options[$id] = "{$name} (ID: {$id})";
                                                    }
                                                }
                                                return $options;
                                            } catch (\Exception $e) {
                                                Log::error('Failed to fetch Pasargad groups: ' . $e->getMessage());
                                                return ['' => '⚠️ خطا در دریافت گروه‌ها'];
                                            }
                                        })
                                        ->helperText('اکانت‌های تست در این گروه ساخته می‌شوند')
                                        ->columnSpan(2)
                                        ->searchable()
                                        ->native(false),
                                    Select::make('pasargad_paid_group_id')
                                        ->label('گروه سرویس‌های پولی')
                                        ->options(function () {
                                            try {
                                                $host = Setting::where('key', 'pasargad_host')->first()?->value;
                                                $user = Setting::where('key', 'pasargad_sudo_username')->first()?->value;
                                                $pass = Setting::where('key', 'pasargad_sudo_password')->first()?->value;
                                                
                                                if (!$host || !$user || !$pass) {
                                                    return ['' => '⚠️ ابتدا تنظیمات پاسارگاد را ذخیره کنید'];
                                                }
                                                
                                                $service = new \App\Services\PasargadService($host, $user, $pass);
                                                $groups = $service->getGroups();
                                                
                                                if (empty($groups)) {
                                                    return ['' => '⚠️ گروهی یافت نشد'];
                                                }
                                                
                                                $options = [];
                                                foreach ($groups as $group) {
                                                    $id = $group['id'] ?? null;
                                                    $name = $group['name'] ?? 'بدون نام';
                                                    if ($id !== null) {
                                                        $options[$id] = "{$name} (ID: {$id})";
                                                    }
                                                }
                                                return $options;
                                            } catch (\Exception $e) {
                                                Log::error('Failed to fetch Pasargad groups: ' . $e->getMessage());
                                                return ['' => '⚠️ خطا در دریافت گروه‌ها'];
                                            }
                                        })
                                        ->helperText('سرویس‌های خریداری شده در این گروه ساخته می‌شوند')
                                        ->columnSpan(2)
                                        ->searchable()
                                        ->native(false),
                                ]), // <-- Added closing bracket for the pasargad section schema!

                            Section::make('تنظیمات پنل Remnawave')
                                ->visible(fn (Get $get) => $get('panel_type') === 'remnawave')
                                ->schema([
                                    TextInput::make('remnawave_host')->label('آدرس پنل Remnawave (مثال: https://panel.example.com)'),
                                    TextInput::make('remnawave_api_token')->label('API Token (از داشبورد Remnawave بسازید)')->password(),
                                    Select::make('remnawave_squad_uuid')
                                        ->label('Squad پیش‌فرض (خرید سرویس)')
                                        ->helperText('اکانت‌های خریداری شده به این Squad وصل می‌شوند.')
                                        ->options(fn () => \App\Filament\Pages\ManageTrialSettings::getRemnawaveSquads())
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('انتخاب Squad...'),
                                    TextInput::make('remnawave_node_hostname')->label('آدرس سابسکریپشن (مثال: https://sub.example.com)'),

                                ]),

                            Section::make('تنظیمات پنل سنایی / X-UI / TX-UI')
                                ->visible(fn(Get $get) => $get('panel_type') === 'xui')
                                ->headerActions([
                                    Action::make('testXuiConnection')
                                        ->label('⚡ تست و بررسی اتصال به پنل X-UI')
                                        ->color('success')
                                        ->icon('heroicon-o-signal')
                                        ->action(function (Get $get) {
                                            $clean = function ($val) {
                                                if (is_null($val)) return '';
                                                $str = (string) $val;
                                                $decoded = json_decode($str, true);
                                                if (is_string($decoded)) {
                                                    $str = $decoded;
                                                }
                                                return trim($str, "\"'\t\n\r ");
                                            };

                                            $dbSettings = Setting::all()->pluck('value', 'key');

                                            $host = $clean($get('xui_host') ?: $dbSettings->get('xui_host'));
                                            $user = $clean($get('xui_user') ?: $dbSettings->get('xui_user'));
                                            $pass = $clean($get('xui_pass') ?: $dbSettings->get('xui_pass'));

                                            if (empty($host) || empty($user) || empty($pass)) {
                                                Notification::make()
                                                    ->title('اطلاعات ناقص است')
                                                    ->body('لطفاً آدرس کامل پنل، نام کاربری و رمز عبور را وارد کنید.')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            try {
                                                $service = new \App\Services\XUIService($host, $user, $pass);
                                                if ($service->login()) {
                                                    $inbounds = $service->getInbounds();
                                                    $count = count($inbounds);
                                                    Notification::make()
                                                        ->title('اتصال به پنل X-UI برقرار شد ✅')
                                                        ->body("ارتباط با پنل با موفقیت برقرار شد. تعداد {$count} اینباند فعال در پنل دریافت شد.")
                                                        ->success()
                                                        ->send();
                                                } else {
                                                    Notification::make()
                                                        ->title('خطا در برقراری اتصال با X-UI')
                                                        ->body("امکان لاگین وجود ندارد.\nآدرس: {$host}\nکاربر: {$user}")
                                                        ->danger()
                                                        ->send();
                                                }
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('خطا در تست اتصال')
                                                    ->body($e->getMessage())
                                                    ->danger()
                                                    ->send();
                                            }
                                        }),
                                ])
                                ->schema([
                                    TextInput::make('xui_host')->label('آدرس کامل پنل سنایی')
                                        ->required(),
                                    TextInput::make('xui_user')->label('نام کاربری')
                                        ->required(),
                                    TextInput::make('xui_pass')->label('رمز عبور')->password()
                                        ->required(),

                                    Select::make('xui_default_inbound_id')
                                        ->label('اینباند پیش‌فرض')
                                        ->options(function () {
                                            $options = [];
                                            $inbounds = \App\Models\Inbound::all();

                                            foreach ($inbounds as $inbound) {
                                                $data = $inbound->inbound_data;
                                                if (!is_array($data) || !isset($data['id']) || ($data['enable'] ?? false) !== true) {
                                                    continue;
                                                }

                                                $panelId = (string) $data['id'];
                                                $options[$panelId] = sprintf(
                                                    '%s (ID: %s) - %s:%s',
                                                    $data['remark'] ?? 'بدون عنوان',
                                                    $panelId,
                                                    strtoupper($data['protocol'] ?? 'unknown'),
                                                    $data['port'] ?? '-'
                                                );
                                            }

                                            return $options;
                                        })
                                        ->getOptionLabelUsing(function ($value) {
                                            if (blank($value)) return 'انتخاب نشده';

                                            $inbound = \App\Models\Inbound::firstWhere(function($item) use ($value) {
                                                return isset($item->inbound_data['id']) && (string)$item->inbound_data['id'] === (string)$value;
                                            });

                                            return $inbound?->dropdown_label ?? "⚠️ نامعتبر (ID: $value)";
                                        })
                                        ->native(false)
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('ابتدا Sync از X-UI را بزنید و صفحه را رفرش کنید')
                                        ->helperText('این اینباند به عنوان اینباند پایه در لینک‌های تک‌کانفیگ استفاده می‌شود'),

                                    Select::make('xui_target_inbounds')
                                        ->label('اینباندهای اشتراک (تحویل همزمان کانفیگ‌ها)')
                                        ->multiple()
                                        ->options(function () {
                                            $options = [];
                                            $inbounds = \App\Models\Inbound::all();

                                            foreach ($inbounds as $inbound) {
                                                $data = $inbound->inbound_data;
                                                if (!is_array($data) || !isset($data['id'])) {
                                                    continue;
                                                }

                                                $panelId = (string) $data['id'];
                                                $options[$panelId] = sprintf(
                                                    '%s (ID: %s) - %s:%s',
                                                    $data['remark'] ?? 'بدون عنوان',
                                                    $panelId,
                                                    strtoupper($data['protocol'] ?? 'unknown'),
                                                    $data['port'] ?? '-'
                                                );
                                            }

                                            return $options;
                                        })
                                        ->native(false)
                                        ->searchable()
                                        ->preload()
                                        ->helperText('اینباندهایی که هنگام خرید، تمدید یا تایید سرویس، به صورت خودکار به سابسکریپشن کاربر متصل می‌شوند (پیش‌فرض: ۳، ۷، ۱۱، ۱۴)'),

                                    Radio::make('xui_link_type')->label('نوع لینک تحویلی')->options(['single' => 'لینک تکی', 'subscription' => 'لینک سابسکریپشن'])->default('single')
                                        ->required(),
                                    TextInput::make('xui_subscription_url_base')->label('آدرس پایه لینک سابسکریپشن'),
                                ]),
                   ]),

                    Tabs\Tab::make('تنظیمات پرداخت')->icon('heroicon-o-credit-card')->schema([
                        Section::make('فعال‌سازی درگاه‌ها')->description('هر درگاه را جداگانه روشن/خاموش کنید. در ربات فقط موارد فعال نمایش داده می‌شود.')->schema([
                            Toggle::make('pay_enable_wallet')->label('👛 کیف پول داخلی')->default(true),
                            Toggle::make('pay_enable_card')->label('💳 کارت‌به‌کارت ریالی')->default(true),
                            Toggle::make('pay_enable_bank_gateway')->label('🏦 درگاه بانکی آنلاین')->default(false),
                            Toggle::make('pay_enable_crypto')->label('🪙 رمزارز (USDT/BTC)')->default(false),
                            Toggle::make('pay_enable_paypal')->label('🅿️ PayPal')->default(false),
                            Toggle::make('pay_enable_intl')->label('💎 Visa / MasterCard (لینک درگاه)')->default(false),
                        ])->columns(2),
                        Section::make('درگاه بانکی آنلاین')->description('پس از اتصال به درگاه، اطلاعات را اینجا وارد کنید.')->schema([
                            TextInput::make('bank_gateway_name')->label('نام درگاه')->placeholder('مثلاً: زرین‌پال یا نکست‌پی'),
                            TextInput::make('bank_gateway_merchant_id')->label('شناسه پذیرنده (Merchant ID)')->password(),
                        ])->columns(2),
                        Section::make('پرداخت کارت به کارت')->schema([
                            TextInput::make('payment_card_number')
                                ->label('شماره کارت')
                                ->mask('9999-9999-9999-9999')
                                ->placeholder('XXXX-XXXX-XXXX-XXXX')
                                ->helperText('شماره کارت ۱۶ رقمی خود را وارد کنید.')
                                ->numeric(false)
                                ->validationAttribute('شماره کارت'),
                            TextInput::make('payment_card_holder_name')->label('نام صاحب حساب'),
                            Textarea::make('payment_card_instructions')->label('توضیحات اضافی')->rows(3),
                        ]),
                        Section::make('رمزارز')->schema([
                            TextInput::make('crypto_usdt_trc20')->label('USDT TRC20')->placeholder('T...'),
                            TextInput::make('crypto_usdt_bep20')->label('USDT BEP20')->placeholder('0x...'),
                            TextInput::make('crypto_btc')->label('BTC')->placeholder('bc1...'),
                            Textarea::make('crypto_instructions')->label('راهنمای پرداخت کریپتو')->rows(3)
                                ->default('پس از واریز، اسکرین‌شات رسید را در ربات ارسال کنید.'),
                        ])->columns(1),
                        Section::make('PayPal')->schema([
                            TextInput::make('paypal_link')->label('لینک PayPal')->url()->placeholder('https://paypal.me/...'),
                            TextInput::make('paypal_email')->label('ایمیل PayPal')->email(),
                            Textarea::make('paypal_instructions')->label('توضیحات')->rows(2),
                        ]),
                        Section::make('Visa / MasterCard')->schema([
                            TextInput::make('intl_payment_link')->label('لینک درگاه بین‌المللی')->url()->placeholder('https://...'),
                            Textarea::make('intl_payment_instructions')->label('توضیحات')->rows(2),
                        ]),
                    ]),

                    Tabs\Tab::make('تنظیمات ربات تلگرام')->icon('heroicon-o-paper-airplane')->schema([
                        Section::make('اطلاعات اتصال ربات')->schema([
                            TextInput::make('telegram_bot_token')->label('توکن ربات تلگرام')->password(),
                            TextInput::make('telegram_admin_chat_id')->label('چت آی‌دی ادمین')->numeric(),
                            TextInput::make('telegram_log_channel_id')
                                ->label('آی‌دی کانال گزارش فعالیت‌ها')
                                ->placeholder('-100123456789')
                                ->hint('گزارش خریدها، ثبت‌نام‌ها و فعالیت‌های ربات به این کانال ارسال می‌شود.'),
                            TextInput::make('telegram_receipt_channel_id')
                                ->label('آی‌دی کانال تایید فیش‌ها')
                                ->placeholder('-100987654321')
                                ->hint('فیش‌های کارت‌به‌کارت با دکمه‌های تایید/رد به این کانال ارسال می‌شوند.'),
                            TextInput::make('telegram_backup_channel_id')
                                ->label('آی‌دی کانال ارسال بکاپ خودکار')
                                ->placeholder('-1004246017791')
                                ->hint('فایل‌های فشرده بکاپ دیتابیس به صورت دوره‌ای به این کانال خصوصی ارسال می‌شوند.'),
                            Toggle::make('backup_enabled')
                                ->label('فعالسازی بکاپ خودکار دیتابیس')
                                ->default(true),
                            TextInput::make('backup_interval_hours')
                                ->label('بازه زمانی ارسال بکاپ (به ساعت)')
                                ->numeric()
                                ->default(6)
                                ->placeholder('6')
                                ->hint('هر چند ساعت یک‌بار فایل بکاپ جدید ارسال شود (مثلاً: 1، 3، 6، 12، 24).'),
                        ]),
                        Section::make('اجبار به عضویت در کانال')
                            ->description('کاربران باید قبل از استفاده از ربات، در کانال عضو شوند.')
                            ->schema([
                                Toggle::make('force_join_enabled')
                                    ->label('فعالسازی اجبار به عضویت')
                                    ->reactive()
                                    ->default(false),
                                TextInput::make('telegram_required_channel_id')
                                    ->label('آی‌دی کانال (Username یا Chat ID)')
                                    ->placeholder('@mychannel یا -100123456789')
                                    ->hint('اگر کانال عمومی است @username و اگر خصوصی است Chat ID (مثل -100123456789) را وارد کنید.')
                                    ->required(fn (Get $get): bool => $get('force_join_enabled') === true)
                                    ->maxLength(100),
                            ]),
                    ]),

                    Tabs\Tab::make('سیستم دعوت از دوستان')
                        ->icon('heroicon-o-gift')
                        ->schema([
                            Section::make('تنظیمات پاداش دعوت')
                                ->description('مبالغ پاداش را به تومان وارد کنید.')
                                ->schema([
                                    TextInput::make('referral_welcome_gift')
                                        ->label('هدیه خوش‌آمدگویی')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('مبلغی که بلافاصله پس از ثبت‌نام با کد معرف، به کیف پول کاربر جدید اضافه می‌شود.'),
                                    TextInput::make('referral_referrer_reward')
                                        ->label('پاداش معرف')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('مبلغی که پس از اولین خرید موفق کاربر جدید، به کیف پول معرف او اضافه می‌شود.'),
                                ]),
                        ]),

                ])->columnSpanFull(),
        ])->statePath('data');
    }

    public function submit(): void
    {
        $this->form->validate();
        $formData = $this->form->getState();

        foreach ($formData as $key => $value) {
            // حذف تنظیمات خالی
            if ($value === '' || $value === null) {
                \App\Models\Setting::where('key', $key)->delete();
                Cache::forget("setting.{$key}");
                continue;
            }

            // 🔥 مهم: تبدیل xui_default_inbound_id به string ساده
            if ($key === 'xui_default_inbound_id') {
                $value = (string) $value;
            }

            // ذخیره مستقیم
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) || is_object($value) ? json_encode($value) : $value]
            );

            Cache::forget("setting.{$key}");
        }

        // پاک کردن کش‌های مرتبط
        Cache::forget('inbounds_dropdown');
        Cache::forget('settings');

        Notification::make()
            ->title('تنظیمات با موفقیت ذخیره شد.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('send_backup_now')
                ->label('📤 ارسال فوری بکاپ به تلگرام')
                ->color('success')
                ->icon('heroicon-o-arrow-up-tray')
                ->requiresConfirmation()
                ->modalHeading('ارسال فوری بکاپ دیتابیس')
                ->modalDescription('آیا مایلید هم‌اکنون یک فایل فشرده از دیتابیس تهیه شده و به کانال تلگرام ارسال گردد؟')
                ->action(function () {
                    $exitCode = \Illuminate\Support\Facades\Artisan::call('vpnmarket:send-backup', ['--force' => true]);
                    if ($exitCode === 0) {
                        Notification::make()
                            ->title('بکاپ با موفقیت به کانال تلگرام ارسال شد')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('خطا در ارسال بکاپ به تلگرام')
                            ->body('لطفاً تنظیمات توکن ربات و کانال بکاپ را بررسی فرمایید.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
