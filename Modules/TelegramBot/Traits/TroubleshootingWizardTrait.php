<?php

namespace Modules\TelegramBot\Traits;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

trait TroubleshootingWizardTrait
{
    /**
     * بررسی دسترسی ادمین جهت اجرای ایزوله عیب‌یابی
     */
    protected function isAdminChat($chatId, $user = null): bool
    {
        $adminChatId = $this->settings->get("telegram_admin_chat_id", "8629398713");
        if ((string)$chatId === (string)$adminChatId || (string)$chatId === "8629398713") {
            return true;
        }
        if ($user && $user->is_admin) {
            return true;
        }
        return false;
    }

    /**
     * ذخیره‌سازی داده‌های پاسخ‌های عیب‌یابی کاربر در کش
     */
    protected function setTsData($chatId, string $key, $value): void
    {
        $data = Cache::get("ts_data_{$chatId}", []);
        $data[$key] = $value;
        Cache::put("ts_data_{$chatId}", $data, now()->addHours(2));
    }

    protected function getTsData($chatId): array
    {
        return Cache::get("ts_data_{$chatId}", []);
    }

    protected function clearTsData($chatId): void
    {
        Cache::forget("ts_data_{$chatId}");
    }

    /**
     * گام ۱: منوی شروع عیب‌یابی هوشمند (انتخاب سیستم‌عامل)
     */
    protected function showTroubleshootWelcome($chatId, $messageId = null, $user = null)
    {
        if ($user) {
            $user->update(["bot_state" => null]);
        }
        $this->clearTsData($chatId);

        $userName = htmlspecialchars($user?->name ?: "کاربر گرامی");
        $text = "🔧 <b>سامانه هوشمند عیب‌یابی و رفع قطعی روزنه</b>\n\n" .
                "سلام <b>{$userName}</b> عزیز وقتتون بخیر 🌸\n\n" .
                "⚠️ <b>کاربر گرامی:</b> جهت بررسی دقیق و برای اینکه راحت‌تر بتونیم مشکل اتصالتان را عیب‌یابی کنیم و سریعاً مشکل را حل کنیم، لطفاً به این چند پرسش کوتاه و گام‌به‌گام پاسخ دهید:\n\n" .
                "📱 <b>پرسش اول:</b> در حال حاضر از کدام سیستم‌عامل استفاده می‌کنید؟";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "📱 اندروید (Android)", "callback_data" => "ts_os_android", "style" => "primary"]),
                $this->makeInlineButton(["text" => "🍏 آیفون / آیپد (iOS)", "callback_data" => "ts_os_ios", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "💻 ویندوز (Windows)", "callback_data" => "ts_os_windows", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🏠 بازگشت به منوی اصلی", "callback_data" => "/start", "style" => "danger"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * توزیع‌کننده کلیک‌های مراحل عیب‌یابی (Router)
     */
    protected function handleTroubleshootingCallback($chatId, $data, $messageId, $user)
    {
        switch ($data) {
            case "ts_start":
                $this->showTroubleshootWelcome($chatId, $messageId, $user);
                break;
            case "ts_os_android":
                $this->setTsData($chatId, 'os', 'اندروید (Android)');
                $this->showTroubleshootAndroidApps($chatId, $messageId);
                break;
            case "ts_os_ios":
                $this->setTsData($chatId, 'os', 'iOS (آیفون / آیپد)');
                $this->showTroubleshootIosApps($chatId, $messageId);
                break;
            case "ts_os_windows":
                $this->setTsData($chatId, 'os', 'ویندوز (Windows)');
                $this->showTroubleshootWindowsApps($chatId, $messageId);
                break;

            // انتخاب نرم‌افزار اندروید
            case "ts_app_v2rayng":
                $this->setTsData($chatId, 'app', 'v2rayNG');
                $this->showTroubleshootVersionCheck($chatId, $messageId, 'v2rayng');
                break;
            case "ts_app_hiddify":
                $this->setTsData($chatId, 'app', 'Hiddify');
                $this->showTroubleshootVersionCheck($chatId, $messageId, 'hiddify');
                break;
            case "ts_app_other_valid":
                $this->setTsData($chatId, 'app', 'سایر کلاینت‌های استاندارد');
                $this->showTroubleshootVersionCheck($chatId, $messageId, 'v2rayng');
                break;
            case "ts_app_fake_android":
                $this->setTsData($chatId, 'app', 'نرم‌افزار متفرقه / نامشخص');
                $this->showTroubleshootFakeAppWarning($chatId, $messageId, "android");
                break;

            // انتخاب نرم‌افزار iOS
            case "ts_app_streisand":
                $this->setTsData($chatId, 'app', 'Streisand');
                $this->showTroubleshootVersionCheck($chatId, $messageId, 'streisand');
                break;
            case "ts_app_ios_valid":
                $this->setTsData($chatId, 'app', 'V2Box / FoXray / Hiddify');
                $this->showTroubleshootVersionCheck($chatId, $messageId, 'streisand');
                break;
            case "ts_app_fake_ios":
                $this->setTsData($chatId, 'app', 'نرم‌افزار متفرقه در iOS');
                $this->showTroubleshootFakeAppWarning($chatId, $messageId, "ios");
                break;

            // انتخاب نرم‌افزار ویندوز
            case "ts_app_v2rayn":
                $this->setTsData($chatId, 'app', 'v2rayN');
                $this->showTroubleshootVersionCheck($chatId, $messageId, 'v2rayn');
                break;
            case "ts_app_hiddify_win":
                $this->setTsData($chatId, 'app', 'Hiddify Windows');
                $this->showTroubleshootVersionCheck($chatId, $messageId, 'hiddify_win');
                break;
            case "ts_app_fake_win":
                $this->setTsData($chatId, 'app', 'نرم‌افزار متفرقه در ویندوز');
                $this->showTroubleshootFakeAppWarning($chatId, $messageId, "windows");
                break;

            // بررسی نسخه
            case "ts_version_yes":
                $this->setTsData($chatId, 'has_latest_version', 'بله (آخرین نسخه)');
                $this->showTroubleshootUpdateSub($chatId, $messageId);
                break;
            case "ts_version_dl_v2rayng":
                $this->setTsData($chatId, 'has_latest_version', 'خیر (نیاز به دانلود نسخه جدید داشت)');
                $this->showTroubleshootDownloadLinks($chatId, $messageId, 'v2rayng');
                break;
            case "ts_send_apk_v2rayng":
                $this->sendTroubleshootApk($chatId, 'v2rayng');
                break;
            case "ts_version_dl_hiddify":
                $this->setTsData($chatId, 'has_latest_version', 'خیر (نیاز به دانلود نسخه جدید داشت)');
                $this->showTroubleshootDownloadLinks($chatId, $messageId, 'hiddify');
                break;
            case "ts_version_dl_streisand":
                $this->setTsData($chatId, 'has_latest_version', 'خیر (نیاز به دانلود نسخه جدید داشت)');
                $this->showTroubleshootDownloadLinks($chatId, $messageId, 'streisand');
                break;
            case "ts_version_dl_v2rayn":
                $this->setTsData($chatId, 'has_latest_version', 'خیر (نیاز به دانلود نسخه جدید داشت)');
                $this->showTroubleshootDownloadLinks($chatId, $messageId, 'v2rayn');
                break;
            case "ts_version_dl_hiddify_win":
                $this->setTsData($chatId, 'has_latest_version', 'خیر (نیاز به دانلود نسخه جدید داشت)');
                $this->showTroubleshootDownloadLinks($chatId, $messageId, 'hiddify_win');
                break;

            // مرحله بروزرسانی اشتراک
            case "ts_help_import":
                $this->showTroubleshootHelpImport($chatId, $messageId);
                break;
            case "ts_step_isp":
                $this->setTsData($chatId, 'sub_updated', 'بله (بروزرسانی شد)');
                $this->showTroubleshootIspSelection($chatId, $messageId);
                break;

            // اپراتورها
            case "ts_isp_mci":
                $this->setTsData($chatId, 'isp', 'همراه اول (MCI)');
                $this->showTroubleshootIspGuide($chatId, $messageId, "همراه اول (MCI)");
                break;
            case "ts_isp_mtn":
                $this->setTsData($chatId, 'isp', 'ایرانسل (Irancell)');
                $this->showTroubleshootIspGuide($chatId, $messageId, "ایرانسل (Irancell)");
                break;
            case "ts_isp_rightel":
                $this->setTsData($chatId, 'isp', 'رایتل (RighTel)');
                $this->showTroubleshootIspGuide($chatId, $messageId, "رایتل (RighTel)");
                break;
            case "ts_isp_wifi":
                $this->setTsData($chatId, 'isp', 'وای‌فای و اینترنت ثابت (Wi-Fi)');
                $this->showTroubleshootIspGuide($chatId, $messageId, "وای‌فای و اینترنت ثابت (Wi-Fi)");
                break;

            // نتیجه نهایی
            case "ts_resolved":
                $this->showTroubleshootResolved($chatId, $messageId);
                break;
            case "ts_require_screenshot":
                $this->showTroubleshootRequireScreenshot($chatId, $messageId, $user);
                break;
        }
    }

    /**
     * گام ۲ اندروید: انتخاب اپلیکیشن
     */
    protected function showTroubleshootAndroidApps($chatId, $messageId)
    {
        $text = "📱 <b>عیب‌یابی اندروید — انتخاب نرم‌افزار اتصال</b>\n\n" .
                "در حال حاضر از کدام اپلیکیشن برای اتصال استفاده می‌کنید؟";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "🟢 v2rayNG (پیشنهادی روزنه)", "callback_data" => "ts_app_v2rayng", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔵 Hiddify (پیشنهادی روزنه)", "callback_data" => "ts_app_hiddify", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🟡 V2Box / سایر برنامه‌ها", "callback_data" => "ts_app_other_valid", "style" => "default"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "⚠️ برنامه متفرقه یا نمی‌دانم", "callback_data" => "ts_app_fake_android", "style" => "danger"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_start", "style" => "default"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * گام ۲ آیفون: انتخاب اپلیکیشن
     */
    protected function showTroubleshootIosApps($chatId, $messageId)
    {
        $text = "🍏 <b>عیب‌یابی iOS (آیفون و آیپد) — انتخاب نرم‌افزار</b>\n\n" .
                "از کدام اپلیکیشن برای اتصال استفاده می‌کنید؟";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "🟢 Streisand (پیشنهادی روزنه)", "callback_data" => "ts_app_streisand", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔵 Hiddify / V2Box / FoXray", "callback_data" => "ts_app_ios_valid", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "⚠️ برنامه متفرقه یا نمی‌دانم", "callback_data" => "ts_app_fake_ios", "style" => "danger"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_start", "style" => "default"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * گام ۲ ویندوز: انتخاب اپلیکیشن
     */
    protected function showTroubleshootWindowsApps($chatId, $messageId)
    {
        $text = "💻 <b>عیب‌یابی ویندوز — انتخاب نرم‌افزار</b>\n\n" .
                "از کدام نرم‌افزار در سیستم خود استفاده می‌کنید؟";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "🟢 v2rayN (پیشنهادی روزنه)", "callback_data" => "ts_app_v2rayn", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔵 Hiddify Next (پیشنهادی روزنه)", "callback_data" => "ts_app_hiddify_win", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "⚠️ برنامه متفرقه یا نمی‌دانم", "callback_data" => "ts_app_fake_win", "style" => "danger"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_start", "style" => "default"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * بررسی داشتن آخرین نسخه نرم‌افزار
     */
    protected function showTroubleshootVersionCheck($chatId, $messageId, string $appKey)
    {
        $appNames = [
            'v2rayng' => 'v2rayNG',
            'hiddify' => 'Hiddify',
            'streisand' => 'Streisand',
            'v2rayn' => 'v2rayN',
            'hiddify_win' => 'Hiddify Windows',
        ];
        $appName = $appNames[$appKey] ?? 'نرم‌افزار انتخابی';

        $text = "📲 <b>پرسش مهم: بررسی نسخه {$appName}</b>\n\n" .
                "آیا مطمئن هستید که <b>آخرین نسخه</b> این برنامه را نصب دارید؟\n\n" .
                "💡 به دلیل ارتقای مداوم فیلترینگ و پروتکل‌های ضد فیلتر روزنه (مانند Reality و XHTTP)، در صورتی که نسخه برنامه‌تان قدیمی باشد، اتصال شما برقرار نخواهد شد.";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "✅ بله، آخرین نسخه رو دارم", "callback_data" => "ts_version_yes", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "⬇️ خیر / دریافت لینک دانلود آخرین نسخه", "callback_data" => "ts_version_dl_{$appKey}", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 انتخاب برنامه دیگر", "callback_data" => "ts_start", "style" => "default"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * ارائه لینک‌های دقیق و تست‌شده دانلود آخرین نسخه
     */
    protected function showTroubleshootDownloadLinks($chatId, $messageId, string $appKey)
    {
        $text = "📥 <b>دانلود آخرین نسخه رسمی نرم‌افزار</b>\n\n";

        if ($appKey === 'v2rayng') {
            $text .= "📱 <b>برنامه v2rayNG (مخصوص اندروید):</b>\n\n" .
                     "⚠️ <i>توجه: به دلیل محدودیت‌ها، این برنامه موقتاً از گوگل‌پلی در دسترس نیست.</i>\n\n" .
                     "🚀 <b>روش‌های دریافت نسخه رسمی (v2.2.6):</b>\n" .
                     "۱. <b>ارسال مستقیم در تلگرام:</b> روی دکمه آبی زیر بزنید تا فایل APK فوراً همینجا در چت برایتان فرستاده شود.\n" .
                     "۲. <b>دانلود مستقیم از سرور روزنه:</b> <a href=\"https://bot.cinemapluss.ir:88/downloads/v2rayNG.apk\">کلیک جهت دانلود سریع APK</a>\n" .
                     "۳. <b>دانلود مستقیم از گیت‌هاب رسمی:</b> <a href=\"https://github.com/2dust/v2rayNG/releases/download/2.2.6/v2rayNG_2.2.6_arm64-v8a.apk\">دانلود فایل نسخه ۶۴بیتی</a>\n\n";
        } elseif ($appKey === 'hiddify') {
            $text .= "📱 <b>برنامه Hiddify (مخصوص اندروید):</b>\n\n" .
                     "▫️ <a href=\"https://play.google.com/store/apps/details?id=app.hiddify.com\">📲 دریافت مستقیم از Google Play</a>\n" .
                     "▫️ <a href=\"https://github.com/hiddify/hiddify-app/releases/latest/download/Hiddify-Android-universal.apk\">📦 دانلود مستقیم فایل Universal APK از GitHub</a>\n\n";
        } elseif ($appKey === 'streisand') {
            $text .= "🍏 <b>برنامه‌های پیشنهادی iOS (آیفون / آیپد):</b>\n\n" .
                     "▫️ <a href=\"https://apps.apple.com/app/streisand/id6450534064\">📥 دانلود Streisand از App Store</a>\n" .
                     "▫️ <a href=\"https://apps.apple.com/app/v2box-v2ray-client/id6446814690\">📥 دانلود V2Box از App Store</a>\n" .
                     "▫️ <a href=\"https://apps.apple.com/app/hiddify-proxy-vpn/id6596777532\">📥 دانلود Hiddify از App Store</a>\n\n";
        } elseif ($appKey === 'v2rayn') {
            $text .= "💻 <b>برنامه v2rayN (مخصوص ویندوز):</b>\n\n" .
                     "▫️ <a href=\"https://github.com/2dust/v2rayN/releases/latest/download/v2rayN-With-Core.zip\">📦 دانلود مستقیم فایل زیپ کامل با Core (نسخه 64bit)</a>\n" .
                     "▫️ <a href=\"https://github.com/2dust/v2rayN/releases/latest\">🌐 صفحه تمام نسخه‌ها در GitHub</a>\n\n";
        } elseif ($appKey === 'hiddify_win') {
            $text .= "💻 <b>برنامه Hiddify (مخصوص ویندوز):</b>\n\n" .
                     "▫️ <a href=\"https://github.com/hiddify/hiddify-app/releases/latest/download/Hiddify-Windows-Setup-x64.exe\">📦 دانلود مستقیم فایل Setup ویندوز (x64)</a>\n\n";
        }

        $text .= "<i>(پس از دانلود و نصب، روی دکمه «نصب کردم» بزنید تا عیب‌یابی را ادامه دهیم).</i>";

        $keyboard = Keyboard::make()->inline();
        if ($appKey === 'v2rayng') {
            $keyboard->row([
                $this->makeInlineButton(["text" => "📥 ارسال مستقیم فایل نصبی v2rayNG در تلگرام", "callback_data" => "ts_send_apk_v2rayng", "style" => "primary"]),
            ]);
        }
        $keyboard->row([
            $this->makeInlineButton(["text" => "✅ نصب / بروزرسانی کردم؛ رفتن به گام بعد", "callback_data" => "ts_version_yes", "style" => "success"]),
        ])
        ->row([
            $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_start", "style" => "default"]),
        ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * ارسال مستقیم فایل APK داخل تلگرام بدون نیاز به خروج از ربات
     */
    protected function sendTroubleshootApk($chatId, $appKey = 'v2rayng')
    {
        $fileId = 'BQACAgEAAxkDAAIDi2rMG9tQvRS9QRGlAxjeMiqhGgbMAAJfBwACbRtpRnKG0QABhEKb7z0E';

        $caption = "📦 <b>فایل نصبی آخرین نسخه رسمی v2rayNG (نسخه ۲.۲.۶)</b>\n\n" .
                   "▫️ سازگار با تمام گوشی‌های اندروید (arm64)\n" .
                   "▫️ بدون فیلتر و بدون نیاز به ورود به سایت یا فروشگاه\n\n" .
                   "کافیست روی فایل بالا لمس کنید تا نصب شود ✨";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "✅ نصب کردم؛ ادامه عیب‌یابی", "callback_data" => "ts_version_yes", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 بازگشت به مراحل", "callback_data" => "ts_os_android", "style" => "default"]),
            ]);

        try {
            Telegram::sendDocument([
                'chat_id' => $chatId,
                'document' => $fileId,
                'caption' => $caption,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send APK file via file_id: " . $e->getMessage());
        }
    }

    /**
     * هشدار قطعی ناشی از برنامه متفرقه + لینک نصب رسمی
     */
    protected function showTroubleshootFakeAppWarning($chatId, $messageId, $os = "android")
    {
        $text = "🛑 <b>علت اصلی قطعی: استفاده از نرم‌افزار نامعتبر یا متفرقه</b>\n\n" .
                "کانفیگ‌های نسل جدید روزنه با پروتکل‌های پیشرفته ضد فیلتر (مانند Reality و TLS و XHTTP) پیکربندی شده‌اند. نرم‌افزارهای متفرقه یا کلاینت‌های غیررسمی توانایی هندل این پروتکل‌ها را ندارند و فوراً قطع می‌شوند!\n\n" .
                "🚀 <b>راهکار فوری:</b> لطفاً نرم‌افزار پیشنهادی را از روش‌های مستقیم زیر دریافت و نصب فرمایید:\n\n";

        $keyboard = Keyboard::make()->inline();

        if ($os === "android") {
            $text .= "▫️ <b>v2rayNG:</b> نسخه رسمی ۲.۲.۶ (پیشنهاد اول ما)\n" .
                     "▫️ <b>Hiddify:</b> <a href=\"https://play.google.com/store/apps/details?id=app.hiddify.com\">دریافت از گوگل‌پلی</a>\n";
            $backCallback = "ts_os_android";
            $keyboard->row([
                $this->makeInlineButton(["text" => "📥 دریافت مستقیم فایل v2rayNG در تلگرام", "callback_data" => "ts_send_apk_v2rayng", "style" => "primary"]),
            ]);
        } elseif ($os === "ios") {
            $text .= "▫️ <a href=\"https://apps.apple.com/app/streisand/id6450534064\">🍏 دانلود Streisand از App Store</a>\n" .
                     "▫️ <a href=\"https://apps.apple.com/app/v2box-v2ray-client/id6446814690\">🍏 دانلود V2Box از App Store</a>\n";
            $backCallback = "ts_os_ios";
        } else {
            $text .= "▫️ <a href=\"https://github.com/2dust/v2rayN/releases/latest/download/v2rayN-With-Core.zip\">💻 دانلود مستقیم v2rayN با هسته کامل</a>\n" .
                     "▫️ <a href=\"https://github.com/hiddify/hiddify-app/releases/latest/download/Hiddify-Windows-Setup-x64.exe\">💻 دانلود مستقیم برنامه Hiddify ویندوز</a>\n";
            $backCallback = "ts_os_windows";
        }

        $keyboard->row([
            $this->makeInlineButton(["text" => "✅ نصب کردم؛ رفتن به مرحله بعد", "callback_data" => "ts_version_yes", "style" => "success"]),
        ])
        ->row([
            $this->makeInlineButton(["text" => "🔙 بازگشت", "callback_data" => $backCallback, "style" => "default"]),
        ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * گام ۳: آموزش الزام‌آور بروزرسانی سابسکریپشن + معرفی لوکیشن‌ها و اینباندها
     */
    protected function showTroubleshootUpdateSub($chatId, $messageId)
    {
        $text = "🔄 <b>گام دوم: بروزرسانی لینک اشتراک (Update Subscription)</b>\n\n" .
                "در بیش از ۸۰٪ مواقع، علت قطعی این است که سرورهای جدید را در برنامه‌تان بروزرسانی نکرده‌اید!\n\n" .
                "📋 <b>روش انجام در ۲ ثانیه:</b>\n" .
                "▫️ <b>v2rayNG:</b> وارد برنامه شوید > منوی سه‌نقطه (⋮) بالای صفحه > گزینه <b>Update subscription</b> را لمس کنید.\n" .
                "▫️ <b>Streisand / Hiddify:</b> آیکون فلش چرخان 🔄 (Update) را در تب سرورها لمس کنید.\n\n" .
                "🌐 <b>کانفیگ‌های فعال و پیشنهادی روزنه پس از آپدیت:</b>\n" .
                "🇬🇧 <code>01 | 🇬🇧 UK MeliNet</code> (سرور انگلیس - پینگ پایین و پایدار)\n" .
                "🇳🇱 <code>04 | 🇳🇱 NL MeliNet</code> (سرور هلند - تانل ملی و پرسرعت)\n\n" .
                "💡 <b>پیشنهاد اتصال سریع:</b>\n" .
                "▫️ <b>همراه اول:</b> کانفیگ <code>01 | 🇬🇧 UK MeliNet</code>\n" .
                "▫️ <b>ایرانسل و رایتل:</b> کانفیگ <code>04 | 🇳🇱 NL MeliNet</code>\n" .
                "▫️ <b>وای‌فای / مودم خانگی:</b> هر دو سرور بالا با پینگ عالی متصل می‌شوند.\n\n" .
                "آیا بروزرسانی اشتراک را در برنامه انجام دادید؟";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "✅ بله، بروزرسانی کردم", "callback_data" => "ts_step_isp", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "❓ بلد نیستم لینک رو وارد کنم", "callback_data" => "ts_help_import", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_start", "style" => "default"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * راهنمای وارد کردن لینک اشتراک
     */
    protected function showTroubleshootHelpImport($chatId, $messageId)
    {
        $text = "📥 <b>راهنمای سریع افزودن لینک اشتراک به برنامه:</b>\n\n" .
                "۱. در همین ربات، از بخش «سرویس‌های من» لینک اختصاصی اشتراک خود را کپی کنید.\n" .
                "۲. وارد برنامه v2rayNG شوید.\n" .
                "۳. روی <b>علامت مثبت (+) بالای صفحه</b> بزنید.\n" .
                "۴. گزینه <b>«Import config from clipboard»</b> را انتخاب کنید.\n" .
                "۵. پس از اضافه شدن، از منوی سه‌نقطه (⋮) گزینه <b>Update subscription</b> را لمس نمایید.";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "✅ انجام دادم؛ گام بعدی", "callback_data" => "ts_step_isp", "style" => "success"]),
                $this->makeInlineButton(["text" => "🔙 بازگشت", "callback_data" => "ts_version_yes", "style" => "default"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * گام ۴: انتخاب اپراتور اینترنت کاربر
     */
    protected function showTroubleshootIspSelection($chatId, $messageId)
    {
        $text = "📶 <b>گام سوم: انتخاب اینترنت فعال دستگاه</b>\n\n" .
                "در این لحظه، اینترنت گوشی یا دستگاه شما متعلق به کدام اپراتور است؟";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "📱 همراه اول (MCI)", "callback_data" => "ts_isp_mci", "style" => "primary"]),
                $this->makeInlineButton(["text" => "🟡 ایرانسل (Irancell)", "callback_data" => "ts_isp_mtn", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🟢 رایتل (RighTel)", "callback_data" => "ts_isp_rightel", "style" => "primary"]),
                $this->makeInlineButton(["text" => "🏠 وای‌فای / مخابرات / شاتل", "callback_data" => "ts_isp_wifi", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_version_yes", "style" => "default"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * راهنمای اتصال متناسب با اپراتور
     */
    protected function showTroubleshootIspGuide($chatId, $messageId, $ispName)
    {
        $recommended = match ($ispName) {
            'همراه اول (MCI)' => "<code>01 | 🇬🇧 UK MeliNet</code>",
            'ایرانسل (Irancell)' => "<code>04 | 🇳🇱 NL MeliNet</code>",
            'رایتل (RighTel)' => "<code>04 | 🇳🇱 NL MeliNet</code> یا <code>01 | 🇬🇧 UK MeliNet</code>",
            default => "<code>01 | 🇬🇧 UK MeliNet</code> یا <code>04 | 🇳🇱 NL MeliNet</code>",
        };

        $text = "📊 <b>تنظیم اتصال برای اینترنت {$ispName}:</b>\n\n" .
                "۱. وارد برنامه شوید و روی گزینه <b>«تست پینگ واقعی (Real delay / Ping)»</b> بزنید.\n" .
                "۲. برای اینترنت شما، سرور پیشنهادی روزنه: {$recommended}\n" .
                "۳. کانفیگی که عدد پینگ سبز یا زرد نشان می‌دهد را لمس کرده و دکمه اتصال (دایره V) را بزنید.\n\n" .
                "💡 <i>نکته: حتماً سایر فیلترشکن‌ها یا پروکسی تلگرام را خاموش کنید تا تداخل نداشته باشند.</i>\n\n" .
                "آیا اکنون متصل شدید؟";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "🚀 بله متصل شدم! مشکل حل شد", "callback_data" => "ts_resolved", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "❌ هنوز متصل نمی‌شود (ارسال اسکرین‌شات)", "callback_data" => "ts_require_screenshot", "style" => "danger"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 انتخاب اپراتور دیگر", "callback_data" => "ts_step_isp", "style" => "default"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * پیام رفع موفقیت‌آمیز مشکل
     */
    protected function showTroubleshootResolved($chatId, $messageId)
    {
        $this->clearTsData($chatId);

        $text = "🎉 <b>بسیار عالی! خوشحالیم که مشکل اتصال شما برطرف شد.</b>\n\n" .
                "هر زمان احساس کندی یا قطعی داشتید، کافیست یک‌بار طبق همین راهنما گزینه <b>Update subscription</b> را بزنید تا تازه‌ترین سرورهای روز بارگذاری شوند ✨\n\n" .
                "از همراهی صمیمانه شما با روزنه سپاسگزاریم 🌸";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "🏠 بازگشت به منوی اصلی", "callback_data" => "/start", "style" => "primary"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * گیت الزامی اسکرین‌شات قبل از ارجاع به کارشناس
     */
    protected function showTroubleshootRequireScreenshot($chatId, $messageId, $user = null)
    {
        if ($user) {
            $user->update(["bot_state" => "awaiting_troubleshoot_screenshot"]);
        }

        $text = "📸 <b>گام نهایی: ارسال اسکرین‌شات از صفحه برنامه (الزامی)</b>\n\n" .
                "کاربر گرامی، جهت بررسی تخصصی توسط کارشناسان فنی روزنه:\n\n" .
                "⚠️ <b>لطفاً همین حالا یک اسکرین‌شات از صفحه اصلی برنامه‌تان (جایی که لیست کانفیگ‌ها و نتیجه تست پینگ مشخص است) در همین چت ارسال نمایید.</b>\n\n" .
                "<i>(پس از ارسال تصویر، گزارش کامل فنی همراه با اسکرین‌شات مستقیماً برای کارشناس ارشد ارسال و تیکت پیگیری برای شما صادر خواهد شد).</i>";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "❌ انصراف و بازگشت", "callback_data" => "/start", "style" => "danger"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * پردازش اسکرین‌شات ارسالی توسط کاربر، ایجاد تیکت رسمی و ارسال مستقیم به پی‌وی ادمین
     */
    protected function handleTroubleshootPhoto($chatId, $message, $user, $update = null)
    {
        $user->update(["bot_state" => null]);

        $photoList = $message->getPhoto();
        $largestPhoto = is_array($photoList) ? end($photoList) : null;
        $fileId = $largestPhoto ? ($largestPhoto['file_id'] ?? $largestPhoto->getFileId()) : null;

        $tsData = $this->getTsData($chatId);
        $os = $tsData['os'] ?? 'مشخص نشده';
        $app = $tsData['app'] ?? 'مشخص نشده';
        $version = $tsData['has_latest_version'] ?? 'بررسی نشده';
        $sub = $tsData['sub_updated'] ?? 'انجام نشده';
        $isp = $tsData['isp'] ?? 'مشخص نشده';

        // اطلاعات سرویس کاربر
        $activeOrder = $user->orders()->whereIn('status', ['completed', 'active'])->latest()->first()
            ?: $user->orders()->latest()->first();

        $planTitle = $activeOrder?->plan?->title ?: 'بدون پلن فعال';
        $orderSub = $activeOrder?->config_details ?: 'ندارد';
        if (mb_strlen($orderSub) > 60) {
            $orderSub = mb_substr($orderSub, 0, 57) . '...';
        }

        $userName = htmlspecialchars($user->name ?: "کاربر گرامی");
        $userUsername = !empty($user->username) ? "@" . ltrim($user->username, '@') : 'ندارد';
        $userLink = "<a href=\"tg://user?id={$chatId}\">{$userName}</a> ({$userUsername})";

        // ۱. ایجاد تیکت رسمی در سیستم
        $ticketMessage = "گزارش عیب‌یابی خودکار:\n" .
                         "- سیستم‌عامل: {$os}\n" .
                         "- نرم‌افزار: {$app}\n" .
                         "- وضعیت نسخه: {$version}\n" .
                         "- وضعیت آپدیت ساب: {$sub}\n" .
                         "- اپراتور اینترنت: {$isp}\n" .
                         "- پلن کاربری: {$planTitle}\n" .
                         "- لینک اشتراک: {$orderSub}";

        $ticket = null;
        try {
            $ticket = $user->tickets()->create([
                'subject' => "🚨 گزارش عیب‌یابی و قطعی ({$isp} | {$app})",
                'message' => $ticketMessage,
                'priority' => 'high',
                'status' => 'open',
                'source' => 'telegram',
                'user_id' => $user->id,
            ]);

            $replyData = ['user_id' => $user->id, 'message' => '[📎 اسکرین‌شات پیوست عیب‌یابی]'];
            if ($update) {
                try {
                    $replyData['attachment_path'] = $this->savePhotoAttachment($update, 'ticket_attachments');
                } catch (\Throwable $e) {}
            }
            $ticket->replies()->create($replyData);
        } catch (\Throwable $e) {
            Log::error("Failed to create troubleshoot ticket: " . $e->getMessage());
        }

        $ticketIdStr = $ticket ? "#{$ticket->id}" : "نامشخص";

        // ۲. متن گزارش کامل برای ادمین
        $adminReport = "🛡️ <b>[گزارش فنی تیکت — مخصوص ادمین]</b>\n\n" .
                       "🚨 <b>گزارش قطعی و عیب‌یابی هوشمند روزنه</b>\n\n" .
                       "👤 <b>کاربر:</b> {$userLink}\n" .
                       "🆔 <b>چت‌آیدی:</b> <code>{$chatId}</code>\n" .
                       "🎫 <b>شماره تیکت:</b> <b>{$ticketIdStr}</b>\n\n" .
                       "📋 <b>نتایج عیب‌یابی کاربر:</b>\n" .
                       "▫️ <b>سیستم‌عامل:</b> {$os}\n" .
                       "▫️ <b>نرم‌افزار:</b> {$app}\n" .
                       "▫️ <b>وضعیت نسخه:</b> {$version}\n" .
                       "▫️ <b>بروزرسانی اشتراک:</b> {$sub}\n" .
                       "▫️ <b>اپراتور اینترنت:</b> <b>{$isp}</b>\n\n" .
                       "📦 <b>وضعیت اشتراک:</b>\n" .
                       "▫️ <b>پلن:</b> {$planTitle}\n" .
                       "▫️ <b>لینک ساب:</b> <code>{$orderSub}</code>\n\n" .
                       "⏰ <b>زمان ثبت:</b> " . now()->format("Y-m-d H:i:s");

        $adminChatId = $this->settings->get("telegram_admin_chat_id", "8629398713");

        $adminKeyboard = Keyboard::make()->inline();
        if ($ticket) {
            $adminKeyboard->row([
                $this->makeInlineButton(["text" => "✏️ پاسخ به تیکت {$ticketIdStr}", "callback_data" => "reply_ticket_{$ticket->id}", "style" => "primary"]),
            ]);
        }
        if (!empty($user->username)) {
            $adminKeyboard->row([
                $this->makeInlineButton(["text" => "💬 ارسال پیام مستقیم در تلگرام", "url" => "https://t.me/" . ltrim($user->username, '@'), "style" => "default"]),
            ]);
        }

        // ۳. ارسال مستقیم اسکرین‌شات و گزارش به پی‌وی ادمین
        if ($adminChatId) {
            try {
                if ($fileId) {
                    Telegram::sendPhoto([
                        'chat_id' => $adminChatId,
                        'photo' => $fileId,
                        'caption' => $adminReport,
                        'parse_mode' => 'HTML',
                        'reply_markup' => $adminKeyboard,
                    ]);
                } else {
                    Telegram::sendMessage([
                        'chat_id' => $adminChatId,
                        'text' => $adminReport,
                        'parse_mode' => 'HTML',
                        'reply_markup' => $adminKeyboard,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send troubleshoot report to admin PV {$adminChatId}: " . $e->getMessage());
            }
        }

        // ۴. ارسال به کانال لاگ (در صورت تنظیم بودن)
        $logChannelId = $this->settings->get('telegram_log_channel_id');
        if ($logChannelId && (string)$logChannelId !== (string)$adminChatId) {
            try {
                if ($fileId) {
                    Telegram::sendPhoto([
                        'chat_id' => $logChannelId,
                        'photo' => $fileId,
                        'caption' => $adminReport,
                        'parse_mode' => 'HTML',
                    ]);
                } else {
                    $this->sendToLogChannel($adminReport);
                }
            } catch (\Throwable $e) {}
        }

        // پاکسازی کش عیب‌یابی این سشن
        $this->clearTsData($chatId);

        // ۵. پیام تایید به کاربر
        $isTesterAdmin = ((string)$chatId === (string)$adminChatId);
        $userTag = $isTesterAdmin ? "👤 <b>[پیام نمایش‌داده‌شده به کاربر]</b>\n\n" : "";
        $userReply = "{$userTag}✅ <b>اسکرین‌شات و گزارش عیب‌یابی شما با موفقیت ثبت شد (تیکت {$ticketIdStr})</b>\n\n" .
                     "اطلاعات دستگاه، اپراتور و تصویر ارسالی شما مستقیماً برای کارشناس ارشد فنی ارسال شد.\n" .
                     "به زودی پس از بررسی صفحه برنامه شما، راهنمایی اختصاصی همینجا برای شما ارسال خواهد شد 🌸\n\n" .
                     "از صبر و همراهی شما سپاسگزاریم ✨";

        $userKeyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "🏠 بازگشت به منوی اصلی", "callback_data" => "/start", "style" => "primary"]),
            ]);

        $this->sendOrEditMessage($chatId, $userReply, $userKeyboard);
    }
}
