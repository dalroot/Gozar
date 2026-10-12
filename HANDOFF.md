# سند انتقال جامع پروژه (Comprehensive Handoff Document) · Gozar / Roozaneh Core

**تاریخ آخرین بروزرسانی:** ۱۳ اکتبر ۲۰۲۶ (ساعت ۰۰:۳۷ بامداد به وقت ایران)  
**نسخه:** نسخه پایدار با زیرساخت پاسارگاد و کانفیگ‌های ملی‌نت (Production Active)  
**مخزن گیت‌هاب:** `https://github.com/dalroot/Gozar.git` (برنچ `main`)  

---

## ۱. مشخصات سرور، دامنه و دسترسی‌ها (Infrastructure & Credentials)

| پارامتر | مقدار / آدرس | توضیحات |
| :--- | :--- | :--- |
| **سرور اصلی (Production)** | `31.77.19.220` | سیستم‌عامل Ubuntu 24.04 (germany-play) |
| **دسترسی سریع با REX** | پورت `7444` | توکن دسترسی: `09d917757914ab724ab77412ed47550f` |
| **مسیر پروژه روی سرور** | `/var/www/gozar` | کاربر وب: `www-data` |
| **پنل وب ادمین (فیلامنت)** | `https://bot.cinemapluss.ir/admin/login` | دارای گواهینامه SSL معتبر |
| **وب‌هوک ربات فروش** | `https://bot.cinemapluss.ir:88/webhooks/telegram` | پورت ۸۸ با گواهینامه SSL رسمی و بدون پندینگ |
| **ربات فروش فعال** | `@RoozanehNetBot` | توکن: `8624342536:AAGUSggpF6Hfjz2C2PSOVBvZDGPbfEFCIWw` |
| **ربات منشی هوشمند فرایدی** | `@RozanehaiSupportBot` | توکن: `8450449696:AAGfdyIZg4FnLlpKeuDo6D8imdi1bFKo7eQ` |
| **پشتیبانی رسمی تلگرام** | `@RoozanehHelp` | ارتباط مستقیم و رسیدگی به امور کاربران |
| **کانال تایید فیش‌ها** | `-1004458721823` | اعلان رسیدهای کارت‌به‌کارت و دکمه‌های تایید/رد مدیریت و پی‌وی |
| **کانال گزارش روزنه (لاگ‌ها)** | `-1004382000216` | ثبت گزارش عضویت‌ها، سفارشات، تست‌ها و ورود به پی‌وی |
| **کانال بکاپ خودکار** | `-1004246017791` | ارسال دوره‌ای فایل فشرده دیتابیس توسط کران‌جاب |
| **شناسه تلگرام ادمین ارشد** | `8629398713` (`dalroot`) | دسترسی کامل مدیریتی در ربات و پنل |

---

## ۲. دستورالعمل سریع مدیریت سرور با REX

به دلیل محدودیت‌های پورت‌های مستقیم SSH، تمام ارتباطات با سرور پروداکشن از طریق ابزار امن `rex` انجام می‌شود:

```bash
# اجرای دستور روی سرور بدون PTY (روش استاندارد و سریع)
rex exec -r -t 09d917757914ab724ab77412ed47550f 31.77.19.220:7444 "export HOME=/root && cd /var/www/gozar && <COMMAND>"

# دیپلوی و اعمال سریع تغییرات کد
rex exec -r -t 09d917757914ab724ab77412ed47550f 31.77.19.220:7444 "export HOME=/root && cd /var/www/gozar && git pull origin main && php artisan optimize:clear"

# اتصال به ترمینال زنده تعاملی
rex connect 31.77.19.220:7444 -t 09d917757914ab724ab77412ed47550f
```

---

## ۳. پنل مدیریت وی‌پی‌ان: پاسارگاد (PasarGuard)

سیستم به صورت کامل به پنل پاسارگاد متصل است:

* **آدرس پنل:** `https://panel.cinemapluss.ir:2053`
* **دامنه سابسکریپشن:** `https://sub.cinemapluss.ir`
* **نوع احراز هویت:** کلید اختصاصی `X-API-Key` به مقدار `pg_key_0349b929-0f74-41b2-a21c-b842e16d6f19`
* **نام کاربری و رمز پنل:** کاربر `admin`
* **شناسه گروه کاربری تست/پیش‌فرض:** گروه شماره `1` (`pasargad_trial_group_id: 1`)
* **سرویس لاراول:** `App\Services\PasargadService`

---

## ۴. مشخصات سرورها و کانفیگ‌های فعال شبکه (Active Configurations)

**تاریخ و ساعت استخراج:** ۱۳ اکتبر ۲۰۲۶ - ۰۰:۳۷ بامداد  
تمام کانفیگ‌ها بر روی پروتکل‌های مدرن VLESS Reality، VMess HiWeb MeliNet و VLESS Direct تنظیم شده‌اند:

### ۱. کانفیگ‌های سرور انگلستان (UK):
* **01 \| 🇬🇧 UK MeliNet (ملی‌نت - عبور از فیلترینگ شدید):**
  ```text
  vmess://ewogICJ2IjogIjIiLAogICJwcyI6ICIwMSB8IFx1RDgzQ1x1RERFQ1x1RDgzQ1x1RERFNyBVSyBNZWxpTmV0IiwKICAiYWRkIjogInBsYXkuY2luZW1hcGx1c3MuaXIiLAogICJwb3J0IjogIjIwODMiLAogICJpZCI6ICJlNmQ4ZGRkZC02YzA1LTQ5NjEtOTU0OS1jNzdmNmZlNzI3NjAiLAogICJhaWQiOiAiMCIsCiAgInNjeSI6ICJhdXRvIiwKICAibmV0IjogInRjcCIsCiAgInR5cGUiOiAiaHR0cCIsCiAgImhvc3QiOiAiaGl3ZWIuaXIiLAogICJwYXRoIjogIi8iLAogICJ0bHMiOiAiIiwKICAic25pIjogIiIsCiAgImFscG4iOiAiIiwKICAiZnAiOiAiIiwKICAiaW5zZWN1cmUiOiAiMCIsCiAgInZjbiI6ICIiLAogICJwY3MiOiAiIgp9
  ```
* **02 \| 🇬🇧 UK Direct (مستقیم پرسرعت پورت 8082):**
  ```text
  vless://439f1dac-bf54-4fb9-8c2f-69c27ab6fe47@play.cinemapluss.ir:8082?encryption=none&security=none&type=tcp&headerType=http&host=hiweb.ir&path=%2F#02%20%7C%20%F0%9F%87%AC%F0%9F%87%A7%20UK%20Direct
  ```
* **03 \| 🇬🇧 UK VIP (پروتکل Reality پورت 3045):**
  ```text
  vless://439f1dac-bf54-4fb9-8c2f-69c27ab6fe47@play.cinemapluss.ir:3045?encryption=none&flow=xtls-rprx-vision&security=reality&sni=gateway.icloud.com&fp=chrome&pbk=Kg77e_SIZX0TSJ2p6xs-xNrcjbsjJMXquQx0TvLklSU&sid=bd83b5da689e506e&type=tcp&headerType=none#03%20%7C%20%F0%9F%87%AC%F0%9F%87%A7%20UK%20VIP
  ```

### ۲. کانفیگ‌های سرور هلند (NL):
* **04 \| 🇳🇱 NL MeliNet (ملی‌نت - پایداری روی تمام اپراتورها):**
  ```text
  vmess://ewogICJ2IjogIjIiLAogICJwcyI6ICIwNCB8IFx1RDgzQ1x1RERGM1x1RDgzQ1x1RERGMSBOTCBNZWxpTmV0IiwKICAiYWRkIjogIm5sLmNpbmVtYXBsdXNzLmlyIiwKICAicG9ydCI6ICIyMDgzIiwKICAiaWQiOiAiZTZkOGRkZGQtNmMwNS00OTYxLTk1NDktYzc3ZjZmZTcyNzYwIiwKICAiYWlkIjogIjAiLAogICJzY3kiOiAiYXV0byIsCiAgIm5ldCI6ICJ0Y3AiLAogICJ0eXBlIjogImh0dHAiLAogICJob3N0IjogImhpd2ViLmlyIiwKICAicGF0aCI6ICIvIiwKICAidGxzIjogIiIsCiAgInNuaSI6ICIiLAogICJhbHBuIjogIiIsCiAgImZwIjogIiIsCiAgImluc2VjdXJlIjogIjAiLAogICJ2Y24iOiAiIiwKICAicGNzIjogIiIKfQ==
  ```
* **05 \| 🇳🇱 NL Direct (مستقیم پورت 8082):**
  ```text
  vless://439f1dac-bf54-4fb9-8c2f-69c27ab6fe47@nl.cinemapluss.ir:8082?encryption=none&security=none&type=tcp&headerType=http&host=hiweb.ir&path=%2F#05%20%7C%20%F0%9F%87%B3%F0%9F%87%B1%20NL%20Direct
  ```
* **06 \| 🇳🇱 NL VIP (پروتکل Reality پورت 3045):**
  ```text
  vless://439f1dac-bf54-4fb9-8c2f-69c27ab6fe47@nl.cinemapluss.ir:3045?encryption=none&flow=xtls-rprx-vision&security=reality&sni=gateway.icloud.com&fp=chrome&pbk=Kg77e_SIZX0TSJ2p6xs-xNrcjbsjJMXquQx0TvLklSU&sid=bd83b5da689e506e&type=tcp&headerType=none#06%20%7C%20%F0%9F%87%B3%F0%9F%87%B1%20NL%20VIP
  ```

---

## ۵. مکانیزم اکانت‌های تست رایگان (Trial Engine)

* **حجم تست:** ۳۰۷۲ مگابایت (۳ گیگابایت کامل)
* **مدت زمان اعتبار:** ۲۴ ساعت
* **ساختار نام‌گذاری:** ترتیبی تمیز (`test1`، `test2`، `test3`، ...)
* **محافظت از تداخل نام:** ربات در لحظه ساخت اکانت، همزمان دیتابیس و پنل پاسارگاد را استعلام گرفته و اولین نام کاربری کاملاً آزاد را انتخاب می‌کند تا هرگز خطای تکراری رخ ندهد.

---

## ۶. سامانه هوشمند عیب‌یابی (Troubleshooting Wizard)

* **کنترلر:** `Modules/TelegramBot/Traits/TroubleshootingWizardTrait.php`
* **فایل نصبی رسمی اندروید:** نسخه v2rayNG v2.2.6 بر روی سرور نگهداری شده و با فایل‌آیدی ابری تلگرام بدون تاخیر ارسال می‌شود:
  * `file_id`: `BQACAgEAAxkDAAIDi2rMG9tQvRS9QRGlAxjeMiqhGgbMAAJfBwACbRtpRnKG0QABhEKb7z0E`
* **ارسال اسکرین‌شات و تیکت:** کاربران در مرحله نهایی می‌توانند اسکرین‌شات اتصال خود را ارسال کنند که مستقیماً به پی‌وی ادمین ارسال شده و تیکت پشتیبانی باز می‌شود.

---

## ۷. پلن‌های فروش رسمی در دیتابیس (`plans`)

| شناسه | نوع بسته | حجم | مدت زمان | قیمت (تومان) | وضعیت در پنل |
| :---: | :--- | :---: | :---: | :---: | :--- |
| **2** | 📦 پلن اقتصادی | ۲۵ گیگابایت | ۳۰ روز | **۲۱۵,۰۰۰** | فعال (`is_active: 1`) |
| **3** | ⚡️ پلن استاندارد | ۴۵ گیگابایت | ۳۰ روز | **۲۹۵,۰۰۰** | فعال (`is_popular: 1` • پیشنهاد ویژه) |
| **4** | 🚀 پلن حرفه‌ای VIP | ۷۵ گیگابایت | ۳۰ روز | **۳۹۵,۰۰۰** | فعال (`is_active: 1`) |

---

## ۸. اطلاعات حساب بانکی جهت تایید فیش‌ها

* **شماره کارت:** `5859-8311-1983-7713`
* **نام صاحب حساب:** توانا محمدی
* **شیفت تایید فیش‌ها:** ساعت ۰۵:۰۰ صبح تا ۱۴:۰۰ ظهر بازه استراحت و مابقی ساعات تایید زیر ۱۰ دقیقه.

---

## ۹. سامانه گزارشات، دکمه‌های پی‌وی و تایید فیش‌ها (Logs & Receipts Engine)

* **تفکیک کامل کانال‌ها:**
  * کانال لاگ‌ها (`-1004382000216`): گزارش عضویت جدید، اکانت تست، ایجاد سفارش، شارژ کیف پول، تمدید، بازخورد تست و تیکت‌ها.
  * کانال فیش‌ها (`-1004458721823`): صرفاً اعلان رسیدهای واریزی کارت‌به‌کارت با تصویر/متن و دکمه‌های تایید/رد مدیریت.
* **دکمه هوشمند ورود به پی‌وی (`buildUserChatButton` / `getUserChatInlineButton`):**
  * برای کاربران دارای آیدی: `https://t.me/username` با متن `👤 ورود به پی‌وی (@username)`
  * برای کاربران بدون آیدی: دیپ‌لینک پایدار `tg://openmessage?user_id=...` با متن `👤 ورود به پی‌وی کاربر (ارسال پیام)` بدون بروز خطای حریم خصوصی تلگرام (`BUTTON_USER_PRIVACY_RESTRICTED`).
* **طراحی بصری تایید/رد فیش:**
  * پاسخ به کلیک ادمین با پاپ‌آپ مودال برجسته (`show_alert: true`).
  * تغییر پیام با بنر چشمگیر (`✅✅✅ 【 فیش تایید و سفارش فعال شد 】 ✅✅✅` یا `❌❌❌ 【 فیش و سفارش رد شد 】 ❌❌❌`).
  * باقی ماندن دکمه شیشه‌ای وضعیت رنگی (`🟢 تایید شد توسط ...` / `🔴 رد شد: ...`) و دکمه مستقیم ورود به پی‌وی کاربر زیر پیام.
