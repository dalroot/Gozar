<?php

namespace Modules\TelegramBot\Traits;

use App\Models\User;
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
     * گام ۱: منوی شروع عیب‌یابی هوشمند (انتخاب سیستم‌عامل)
     */
    protected function showTroubleshootWelcome($chatId, $messageId = null, $user = null)
    {
        if ($user) {
            $user->update(["bot_state" => null]);
        }

        $userName = htmlspecialchars($user?->name ?: "کاربر گرامی");
        $text = "🔧 <b>سامانه هوشمند عیب‌یابی و رفع قطعی روزنه</b>\n\n" .
                "سلام <b>{$userName}</b> عزیز وقتتون بخیر 🌸\n" .
                "برای اینکه در کمتر از ۲ دقیقه مشکل اتصالتان را شناسایی و برطرف کنیم، لطفاً نوع سیستم‌عامل دستگاهتان را انتخاب نمایید:";

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
                $this->showTroubleshootAndroidApps($chatId, $messageId);
                break;
            case "ts_os_ios":
                $this->showTroubleshootIosApps($chatId, $messageId);
                break;
            case "ts_os_windows":
                $this->showTroubleshootWindowsApps($chatId, $messageId);
                break;
            case "ts_app_fake":
                $this->showTroubleshootFakeAppWarning($chatId, $messageId, "android");
                break;
            case "ts_app_fake_ios":
                $this->showTroubleshootFakeAppWarning($chatId, $messageId, "ios");
                break;
            case "ts_app_fake_win":
                $this->showTroubleshootFakeAppWarning($chatId, $messageId, "windows");
                break;
            case "ts_app_v2rayng":
            case "ts_app_other_valid":
                $this->showTroubleshootUpdateSub($chatId, $messageId);
                break;
            case "ts_help_import":
                $this->showTroubleshootHelpImport($chatId, $messageId);
                break;
            case "ts_step_isp":
                $this->showTroubleshootIspSelection($chatId, $messageId);
                break;
            case "ts_isp_mci":
                $this->showTroubleshootIspGuide($chatId, $messageId, "همراه اول (MCI)");
                break;
            case "ts_isp_mtn":
                $this->showTroubleshootIspGuide($chatId, $messageId, "ایرانسل (Irancell)");
                break;
            case "ts_isp_rightel":
                $this->showTroubleshootIspGuide($chatId, $messageId, "رایتل (RighTel)");
                break;
            case "ts_isp_wifi":
                $this->showTroubleshootIspGuide($chatId, $messageId, "وای‌فای و اینترنت ثابت (Wi-Fi)");
                break;
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
                $this->makeInlineButton(["text" => "🟢 v2rayNG (نرم‌افزار رسمی روزنه)", "callback_data" => "ts_app_v2rayng", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔵 Hiddify / Streisand / V2Box", "callback_data" => "ts_app_other_valid", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "⚠️ برنامه متفرقه یا نمی‌دانم", "callback_data" => "ts_app_fake", "style" => "danger"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_start", "style" => "secondary"]),
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
                $this->makeInlineButton(["text" => "🟢 Streisand (رسمی و پیشنهادی)", "callback_data" => "ts_app_v2rayng", "style" => "success"]),
                $this->makeInlineButton(["text" => "🔵 V2Box / FoXray / Sing-box", "callback_data" => "ts_app_other_valid", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "⚠️ برنامه دیگر / نمی‌دانم", "callback_data" => "ts_app_fake_ios", "style" => "danger"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_start", "style" => "secondary"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * گام ۲ ویندوز: انتخاب اپلیکیشن
     */
    protected function showTroubleshootWindowsApps($chatId, $messageId)
    {
        $text = "💻 <b>عیب‌یابی ویندوز — انتخاب نرم‌افزار</b>\n\n" .
                "از کدام نرم‌افزار در ویندوز استفاده می‌کنید؟";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "🟢 v2rayN (رسمی)", "callback_data" => "ts_app_v2rayng", "style" => "success"]),
                $this->makeInlineButton(["text" => "🔵 Hiddify Next / Nekoray", "callback_data" => "ts_app_other_valid", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "⚠️ برنامه دیگر", "callback_data" => "ts_app_fake_win", "style" => "danger"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_start", "style" => "secondary"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * هشدار قطعی ناشی از برنامه متفرقه + لینک نصب رسمی
     */
    protected function showTroubleshootFakeAppWarning($chatId, $messageId, $os = "android")
    {
        $text = "🛑 <b>علت اصلی قطعی: استفاده از نرم‌افزار نامعتبر</b>\n\n" .
                "کانفیگ‌های نسل جدید روزنه (پروتکل‌های ضد فیلتر Reality و TLS و XHTTP) فقط روی نرم‌افزارهای رسمی هسته Xray کار می‌کنند و برنامه‌های متفرقه توانایی اتصال آن‌ها را ندارند!\n\n" .
                "🚀 <b>راهکار فوری (حل مشکل در ۱ دقیقه):</b>\n";

        if ($os === "android") {
            $text .= "۱. آخرین نسخه رسمی <b>v2rayNG</b> را از لینک مستقیم زیر نصب کنید:\n" .
                     "📥 <a href=\"https://github.com/2dust/v2rayNG/releases\">دانلود مستقیم نسخه رسمی از GitHub</a>\n" .
                     "یا از گوگل‌پلی:\n" .
                     "📥 <a href=\"https://play.google.com/store/apps/details?id=com.v2ray.ang\">دریافت از Google Play</a>\n\n" .
                     "۲. پس از نصب، لینک اشتراک خود را در برنامه اضافه کنید.";
            $backCallback = "ts_os_android";
        } elseif ($os === "ios") {
            $text .= "۱. نرم‌افزار رسمی و رایگان <b>Streisand</b> یا <b>V2Box</b> را از اپ‌استور نصب کنید:\n" .
                     "📥 <a href=\"https://apps.apple.com/us/app/streisand/id6450534064\">دانلود Streisand از App Store</a>\n" .
                     "📥 <a href=\"https://apps.apple.com/us/app/v2box-v2ray-client/id6446814690\">دانلود V2Box از App Store</a>\n\n" .
                     "۲. پس از نصب، لینک اشتراک خود را اضافه کنید.";
            $backCallback = "ts_os_ios";
        } else {
            $text .= "۱. آخرین نسخه رسمی <b>v2rayN</b> را از گیت‌هاب دانلود و اجرا کنید:\n" .
                     "📥 <a href=\"https://github.com/2dust/v2rayN/releases\">دانلود v2rayN برای ویندوز</a>\n\n" .
                     "۲. لینک اشتراک را در برنامه وارد کنید.";
            $backCallback = "ts_os_windows";
        }

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "✅ نصب کردم؛ رفتن به مرحله بعد", "callback_data" => "ts_app_v2rayng", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 بازگشت", "callback_data" => $backCallback, "style" => "secondary"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * گام ۳: آموزش الزام‌آور بروزرسانی سابسکریپشن
     */
    protected function showTroubleshootUpdateSub($chatId, $messageId)
    {
        $text = "🔄 <b>گام دوم: بروزرسانی لینک اشتراک (Update Subscription)</b>\n\n" .
                "در بیش از ۸۰٪ مواقع، علت قطعی این است که سرورهای جدید را در برنامه‌تان بروزرسانی نکرده‌اید!\n\n" .
                "📋 <b>روش انجام در ۲ ثانیه (برنامه v2rayNG):</b>\n" .
                "۱. وارد برنامه شوید.\n" .
                "۲. روی <b>منوی سه‌نقطه (⋮) در گوشه بالای صفحه</b> بزنید.\n" .
                "۳. گزینه <b>«Update subscription»</b> (بروزرسانی اشتراک) را انتخاب کنید تا لیست سرورها بروز و تازه شود.\n\n" .
                "<i>(در آیفون / Streisand: وارد تب Subscription شوید و دکمه فلش چرخان Update را لمس کنید).</i>\n\n" .
                "آیا بروزرسانی را انجام دادید؟";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "✅ بله، بروزرسانی کردم", "callback_data" => "ts_step_isp", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "❓ بلد نیستم لینک رو وارد کنم", "callback_data" => "ts_help_import", "style" => "primary"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_os_android", "style" => "secondary"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * راهنمای وارد کردن لینک اشتراک
     */
    protected function showTroubleshootHelpImport($chatId, $messageId)
    {
        $text = "📥 <b>راهنمای سریع کپی و افزودن لینک اشتراک:</b>\n\n" .
                "۱. در همین ربات، از بخش «سرویس‌های من» لینک اختصاصی اشتراک خود را کپی کنید.\n" .
                "۲. وارد برنامه v2rayNG شوید.\n" .
                "۳. روی <b>علامت مثبت (+) بالای صفحه</b> بزنید.\n" .
                "۴. گزینه <b>«Import config from clipboard»</b> (وارد کردن از کلیپ‌بورد) را انتخاب کنید.\n" .
                "۵. پس از اضافه شدن، از منوی سه‌نقطه (⋮) گزینه <b>Update subscription</b> را لمس کنید.";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "✅ انجام دادم؛ گام بعدی", "callback_data" => "ts_step_isp", "style" => "success"]),
                $this->makeInlineButton(["text" => "🔙 بازگشت", "callback_data" => "ts_app_v2rayng", "style" => "secondary"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * گام ۴: انتخاب اپراتور اینترنت کاربر
     */
    protected function showTroubleshootIspSelection($chatId, $messageId)
    {
        $text = "📶 <b>گام سوم: انتخاب اپراتور و تست پینگ (Real Delay)</b>\n\n" .
                "اینترنت دستگاه شما در این لحظه متعلق به کدام اپراتور است؟";

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
                $this->makeInlineButton(["text" => "🔙 مرحله قبل", "callback_data" => "ts_app_v2rayng", "style" => "secondary"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * راهنمای اتصال متناسب با اپراتور
     */
    protected function showTroubleshootIspGuide($chatId, $messageId, $ispName)
    {
        $text = "📊 <b>تنظیم اتصال برای اینترنت {$ispName}:</b>\n\n" .
                "۱. وارد برنامه شوید و روی گزینه <b>«تست پینگ واقعی (Real delay / Ping)»</b> بزنید.\n" .
                "۲. برای اپراتور شما، سرورهایی که با عنوان <b>TLS</b> یا <b>XHTTP</b> یا <b>Reality</b> مشخص شده‌اند بهترین عملکرد را دارند.\n" .
                "۳. کانفیگی که عدد پینگ سبز یا زرد (مثلاً <code>140ms</code>) نشان می‌دهد را انتخاب و دکمه اتصال (دایره V) را بزنید.\n\n" .
                "💡 <i>نکته مهم: حتماً فیلترشکن قبلی یا پروکسی داخلی تلگرام را خاموش کنید تا تداخل نداشته باشند.</i>\n\n" .
                "آیا اکنون متصل شدید؟";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "🚀 بله متصل شدم! مشکل حل شد", "callback_data" => "ts_resolved", "style" => "success"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "❌ هنوز متصل نمی‌شود (بررسی کارشناس)", "callback_data" => "ts_require_screenshot", "style" => "danger"]),
            ])
            ->row([
                $this->makeInlineButton(["text" => "🔙 انتخاب اپراتور دیگر", "callback_data" => "ts_step_isp", "style" => "secondary"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * پیام رفع موفقیت‌آمیز مشکل
     */
    protected function showTroubleshootResolved($chatId, $messageId)
    {
        $text = "🎉 <b>بسیار عالی! خوشحالیم که مشکل اتصال شما برطرف شد.</b>\n\n" .
                "هر زمان در آینده احساس افت سرعت یا قطعی داشتید، کافیست طبق همین آموزش یک‌بار منوی سه‌نقطه برنامه را زده و <b>Update subscription</b> را لمس کنید تا به تازه‌ترین سرورهای روز وصل شوید ✨\n\n" .
                "از اینکه با روزنه همراه هستید سپاسگزاریم 🌸";

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
                "کاربر گرامی، برای اینکه کارشناس فنی بتواند در سریع‌ترین زمان علت دقیق اختلال را بررسی کند:\n\n" .
                "⚠️ <b>لطفاً یک اسکرین‌شات از صفحه اصلی برنامه‌تان (جایی که نام کانفیگ‌ها و نتیجه تست پینگ مشخص است) همینجا در ربات ارسال فرمایید.</b>\n\n" .
                "<i>(به محض ارسال اسکرین‌شات، تیکت پشتیبانی شما همراه با تصویر مستقیماً برای کارشناس ارشد ارسال خواهد شد).</i>";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "❌ انصراف و بازگشت", "callback_data" => "/start", "style" => "danger"]),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * پردازش اسکرین‌شات ارسالی توسط کاربر در گام نهایی عیب‌یابی
     */
    protected function handleTroubleshootPhoto($chatId, $message, $user)
    {
        $user->update(["bot_state" => null]);

        $userName = htmlspecialchars($user->name ?: "کاربر گرامی");
        $userLink = "<a href=\"tg://user?id={$chatId}\">{$userName}</a> (<code>{$chatId}</code>)";

        // ارسال هشدار به کانال لاگ / ادمین
        $logAlert = "🚨 <b>گزارش قطعی جدید (سامانه عیب‌یابی)</b>\n\n" .
                    "👤 <b>کاربر:</b> {$userLink}\n" .
                    "📸 کاربر تمام مراحل عیب‌یابی را طی کرده و اسکرین‌شات پیوست را ارسال نموده است.\n" .
                    "⏰ <b>زمان:</b> " . now()->format("Y-m-d H:i:s");

        $this->sendToLogChannel($logAlert);

        // پیام تایید به کاربر
        $replyText = "✅ <b>اسکرین‌شات شما با موفقیت دریافت و تحویل کارشناس شد.</b>\n\n" .
                     "هم‌اکنون اطلاعات دستگاه و تصویر ارسالی شما در صف بررسی کارشناسان فنی قرار گرفت و در سریع‌ترین زمان پاسخ را همینجا دریافت خواهید کرد 🌸\n\n" .
                     "از صبر و شکیبایی شما سپاسگزاریم ✨";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(["text" => "🏠 بازگشت به منوی اصلی", "callback_data" => "/start", "style" => "primary"]),
            ]);

        $this->sendOrEditMessage($chatId, $replyText, $keyboard);
    }
}
