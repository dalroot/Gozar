# سند انتقال پروژه (Handoff Document) · Gozar / Roozaneh Core

**تاریخ آخرین بروزرسانی:** ۳۰ سپتامبر ۲۰۲۶  
**موضوع:** مشخصات سرور پروداکشن، دامنه، وب‌هوک ربات تلگرام و اتصالات رکس (REX)

---

## ۱. مشخصات سرور، دامنه و مخزن (Credentials & Access)

| پارامتر | مقدار / آدرس |
| :--- | :--- |
| **مخزن گیت‌هاب** | `https://github.com/dalroot/Gozar.git` (برنچ `main`) |
| **آخرین کامیت سینک‌شده** | `f56d69d` (پشتیبانی هوشمند از آرایه در getClients و رفع خطای json_decode در ساخت کانفیگ X-UI) |
| **سرور پروداکشن** | `31.77.19.220` (سیستم‌عامل Ubuntu 26.04) |
| **دسترسی سریع REX** | پورت `7444` با توکن: `09d917757914ab724ab77412ed47550f` |
| **مسیر پروژه روی سرور** | `/var/www/gozar` |
| **پنل وب ادمین** | `https://bot.cinemapluss.ir/admin/login` (دارای SSL معتبر و لایو) |
| **وب‌هوک فعال تلگرام** | `https://bot.cinemapluss.ir/webhooks/telegram` |
| **ربات ست‌شده در ویزارد** | `@RoozanehNetBot` (توکن: `8624342536:AAGUSggpF6Hfjz2C2PSOVBvZDGPbfEFCIWw`) |
| **ربات تست قبلی (مرجع عکس)** | `@gozartest2026bot` |

---

## ۲. دستورالعمل ارتباط و مدیریت سرور با REX

به دلیل ساختار REX و بسته بودن پورت‌های مستقیم، تمام ارتباطات از طریق باینری محلی `rex` انجام می‌شود:

```bash
# اجرای دستورات سریع روی سرور
rex exec 31.77.19.220:7444 -t 09d917757914ab724ab77412ed47550f "export HOME=/root; cd /var/www/gozar && <COMMAND>"

# ترمینال زنده (Interactive Terminal)
rex connect 31.77.19.220:7444 -t 09d917757914ab724ab77412ed47550f

# وضعیت سخت‌افزار و منابع سرور
rex info 31.77.19.220:7444 -t 09d917757914ab724ab77412ed47550f
```

---

## ۳. تسک‌های جاری و فرآیند انتشار (Action Items)

### ۱. جایگزینی بنر خورشید روزنه
- **فایل سورس:** `/home/hokar/Documents/Codex/2026-08-28/jsj/assets/menu-banner-rozaneh-nano-v1.jpg`
- **مقصد:** `public/menu_banner.jpg`

### ۲. اصلاح کنترلر وب‌هوک ربات تلگرام
- **مسیر:** `Modules/TelegramBot/Http/Controllers/WebhookController.php`
- **تغییر خط ۵۹۵۱:** لود عکس از فایل محلی سرور به جای URL اینترنتی:
  ```php
  $photoSource = InputFile::create(public_path('menu_banner.jpg'), 'menu_banner.jpg');
  ```

### ۳. ریست کش تلگرام و اعمال در سرور
- پاکسازی کلید کش `telegram_menu_photo_file_id` در لاراول:
  ```bash
  php artisan cache:forget telegram_menu_photo_file_id
  ```
- پول تغییرات روی سرور با REX:
  ```bash
  rex exec 31.77.19.220:7444 -t 09d917757914ab724ab77412ed47550f "export HOME=/root; cd /var/www/gozar && git pull origin main && php artisan optimize:clear"
  ```
