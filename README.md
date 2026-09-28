<p align="center">
  <h1 align="center">⚡️ GozarNet (گذر نت)</h1>
  <p align="center">
    <strong>پلتفرم جامع، هوشمند و ماژولار مدیریت، فروش و خودکارسازی سرویس‌های اینترنت آزاد و VPN</strong>
  </p>
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11.x-FF2D20.svg?style=for-the-badge&logo=laravel" alt="Laravel 11"></a>
  <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-3.x-F59E0B.svg?style=for-the-badge&logo=filament" alt="Filament 3"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777BB4.svg?style=for-the-badge&logo=php" alt="PHP 8.3"></a>
  <a href="https://core.telegram.org/bots/api"><img src="https://img.shields.io/badge/Telegram%20Bot-API%208.x-24A1DE.svg?style=for-the-badge&logo=telegram" alt="Telegram Bot"></a>
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge" alt="MIT License"></a>
</p>

---

## 📖 درباره گذر نت (GozarNet)

**GozarNet (گذر نت)** یک اکوسیستم کامل و متن‌باز برای مدیریت، توزیع و فروش اشتراک‌های VPN بر پایه فریم‌ورک قدرتمند **Laravel 11** و پنل مدیریت فوق‌سریع **Filament 3** است. 
این پلتفرم با هدف ساده‌سازی، خودکارسازی ۱۰۰٪ فرآیندها، ارائه تجربه کاربری لوکس و بدون قطعی در تلگرام و وب توسعه یافته است و به صورت پیش‌فرض از معماری **White-label (برندینگ سفارشی)** پشتیبانی می‌کند.

---

## 🌟 ویژگی‌ها و قابلیت‌های کلیدی

### 🌐 ۱. پشتیبانی یکپارچه از چندین پنل (Multi-Panel Core)
- 🌊 **Remnawave:** مدیریت پیشرفته کاربران، سابسکریپشن‌های مدرن و Squads.
- ⚡️ **Marzban:** پشتیبانی کامل از پروتکل‌های V2Ray/Xray، ترافیک و بازنشانی زمان.
- 🛠 **X-UI (3x-ui / Sanaei / Alireza):** اتصال مستقیم به اینباندها و کلاینت‌ها.
- 🦅 **PasarGuard (پاسارگاد):** ساخت کاربر، ریست ترافیک و همگام‌سازی لینک سابسکریپشن.

### 🤖 ۲. ربات تلگرام فوق‌پیشرفته (Telegram Bot 8.x + Mini App)
- **طراحی مدرن و مینیمال:** استفاده از دکمه‌های شیشه‌ای پریمیوم، استایل‌های رنگی اختصاصی تلگرام و رندرینگ هوشمند.
- **داشبورد وب (Mini App):** پنل کاربری درون تلگرام برای مشاهده وضعیت اشتراک، QR Code و ترافیک لحظه‌ای.
- **سیستم اکانت تست ۲۴ ساعته:** تحویل آنی لینک کانفیگ و QR Code اختصاصی برای کاربران جدید با کنترل سهمیه هوشمند.
- **سیستم مالی و کیف پول:** شارژ موجودی، پرداخت آنی با کیف پول، واریز کارت به کارت، درگاه‌های رمزارز (USDT TRC20/BEP20, BTC) و بین‌المللی.
- **سیستم کد تخفیف و رفرال:** زیرمجموعه‌گیری با پاداش نقدی و اشتراک هدیه، امکان انتقال ترافیک به اشتراک‌ها.
- **احراز هویت کپچای اموجی و زبان:** جلوگیری از ورود ربات‌های اسپمر با سیستم تایید کپچای هوشمند دو زبانه (فارسی / انگلیسی).

### 📡 ۳. ساختار کانال‌های سه‌گانه هوشمند (3-Channel Hub)
1. **کانال گزارش فعالیت‌ها (Log Channel):** ثبت زنده تمامی ورودها، ثبت‌نام‌ها، ساخت اکانت تست، ایجاد سفارش، پرداخت‌های کیف پول، تمدیدها و تیکت‌های پشتیبانی.
2. **کانال تایید فیش‌ها (Receipt Channel):** ارسال آنی تصویر یا کد پیگیری فیش‌های کارت به کارت به همراه دکمه‌های شیشه‌ای **«✅ تایید»** و **«❌ رد فیش با دلیل»** برای بررسی سریع ادمین‌ها.
3. **کانال بکاپ خودکار دیتابیس (Backup Channel):** ارسال زمان‌بندی‌شده و فشرده کل دیتابیس به صورت مستقیم به یک کانال خصوصی امن تلگرام.

### 💾 ۴. سیستم بکاپ‌گیری خودکار و بازیابی آنی (Automated Backup & Restore)
- ایجاد خودکار فایل‌های فشرده استاندارد دیتابیس (`.sql.gz`).
- ارسال دوره‌ای به کانال خصوصی بر اساس بازه دلخواه (مثلاً هر ۱، ۳، ۶، ۱۲ یا ۲۴ ساعت).
- دکمه **«ارسال فوری بکاپ»** از داخل پنل مدیریت Filament.
- بازیابی ساده کل دیتابیس در چند ثانیه با یک دستور تک‌خطی.

### 🗺 ۵. مدیریت چند سروره و لود بالانسر (Multi-Server & Locations)
- تعریف موقعیت‌های جغرافیایی مختلف (آلمان، هلند، فنلاند، ترکیه و...) به همراه پرچم کشورها.
- توزیع خودکار و هوشمند کاربران روی خلوت‌ترین سرورهای فعال بر اساس ظرفیت.

---

## 🛠 پیش‌نیازهای سرور

* **سیستم‌عامل:** Ubuntu 22.04 LTS یا Ubuntu 24.04 LTS
* **موتور پردازش:** PHP 8.2 یا PHP 8.3 با اکستنشن‌های: `php-fpm`, `php-mysql`, `php-mbstring`, `php-xml`, `php-curl`, `php-zip`, `php-redis`
* **دیتابیس:** MySQL 8.0+ یا MariaDB 10.11+
* **وب‌سرور:** Nginx
* **مدیریت پکیج‌ها:** Composer 2.x
* **دامنه با گواهی SSL:** فعال روی Nginx با Certbot/Let's Encrypt

---

## 🚀 راهنمای نصب و راه‌اندازی

### روش ۱: نصب سریع با یک دستور (پیشنهادی)
وارد سرور ابونتو خود شوید و دستور زیر را اجرا کنید:
```bash
wget -O install.sh https://raw.githubusercontent.com/dalroot/GozarNet/main/install.sh && sudo bash install.sh
```

---

### روش ۲: نصب دستی گام‌به‌گام (Manual Installation)

#### ۱. کلون کردن مخزن و نصب وابستگی‌ها
```bash
cd /var/www
git clone https://github.com/dalroot/GozarNet.git gozarnet
cd gozarnet
composer install --no-dev --optimize-autoloader
```

#### ۲. پیکربندی فایل محیطی (.env)
```bash
cp .env.example .env
php artisan key:generate
nano .env
```
اطلاعات اتصال دیتابیس (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) و توکن ربات تلگرام (`TELEGRAM_BOT_TOKEN`) را وارد کنید.

### ۳. اجرای مایگریشن‌ها و مقداردهی اولیه
```bash
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
```

### ۴. تنظیم وب‌سرور Nginx
نمونه کانفیگ پیشنهادی Nginx:
```nginx
server {
    listen 80;
    server_name panel.yourdomain.com;
    root /var/www/gozarnet/public;

    index index.php;
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```
دریافت رایگان گواهی SSL:
```bash
sudo certbot --nginx -d panel.yourdomain.com
```

### ۵. ثبت وب‌هوک ربات تلگرام
```bash
php artisan telegrambot:set-webhook
```

### ۶. تنظیم کرون‌جاب زمان‌بندی لاراول (ضروری برای بکاپ و گزارشات)
دستور زیر را در Crontab سرور قرار دهید:
```bash
crontab -e
```
خط زیر را در انتهای فایل اضافه کنید:
```cron
* * * * * cd /var/www/gozarnet && php artisan schedule:run >> /dev/null 2>&1
```

---

## ⚙️ پیکربندی کانال‌های تلگرام و بکاپ در پنل ادمین

پس از ورود به پنل مدیریت (`https://panel.yourdomain.com/admin`):
1. به مسیر **تنظیمات سایت > تنظیمات ربات تلگرام** بروید.
2. شناسه‌های کانال‌های تلگرام خود را وارد کنید:
   * **آی‌دی کانال گزارش فعالیت‌ها (`telegram_log_channel_id`):** شناسه کانال گزارش (مانند `-100123456789`)
   * **آی‌دی کانال تایید فیش‌ها (`telegram_receipt_channel_id`):** شناسه کانال بررسی فیش‌های واریزی
   * **آی‌دی کانال بکاپ خودکار (`telegram_backup_channel_id`):** شناسه کانال خصوصی ارسال فایل دیتابیس
   * **بازه زمانی بکاپ (`backup_interval_hours`):** مثلاً عدد `6` (هر ۶ ساعت)
3. ذخیره کنید.

---

## 🔄 دستورات کاربردی ترمینال (Artisan CLI)

| دستور | کاربرد |
| :--- | :--- |
| `php artisan vpnmarket:send-backup --force` | ارسال دستی و فوری فایل بکاپ دیتابیس به کانال تلگرام |
| `php artisan vpnmarket:send-daily-report` | تولید و ارسال آمار روزانه فروش و کاربران به کانال لاگ |
| `php artisan telegrambot:set-webhook` | ثبت یا تجدید وب‌هوک ربات تلگرام روی دامنه سرور |
| `php artisan cache:clear && php artisan optimize:clear` | پاکسازی تمامی کش‌های لاراول و فیلامنت |

---

## 🗄 راهنمای بازیابی (Restore) بکاپ دیتابیس

برای بازگرداندن فایل بکاپ `.sql.gz` روی دیتابیس سرور:
```bash
zcat /مسیر/backup_vpnmarket_YYYY-MM-DD_HH-mm-ss.sql.gz | mysql -u[یوزر_دیتابیس] -p'[رمز_دیتابیس]' [نام_دیتابیس]
cd /var/www/gozarnet && php artisan cache:clear && php artisan optimize:clear
```

---

## 🤝 تشکر و قدردانی (Acknowledgments)
پلتفرم **گذر نت (GozarNet)** با الهام و ارتقای ایده‌های پروژه‌های اولیه مدیریت VPN و جامعه متن‌باز توسعه یافته است. از تمامی توسعه‌دهندگان و بنیان‌گذاران اولیه‌ای که در شکل‌گیری نسخه اولیه این سیستم نقش داشته‌اند، صمیمانه سپاسگزاری و قدردانی می‌کنیم. معماری فعلی با بازنویسی کامل هسته، اضافه شدن ماژول منشی هوشمند کسب‌وکار، سیستم توزیع کانال‌های سه‌گانه و اسکریپت‌های مدرن به عنوان یک پروژه مستقل و پایدار به مسیر خود ادامه می‌دهد.

---

## 🤝 مشارکت و توسعه (Contributing)
ما از تمامی پیشنهادات، گزارش باگ‌ها و Pull Request‌های جامعه متن‌باز به گرمی استقبال می‌کنیم.

## 📄 لایسنس
این پروژه تحت **[لایسنس MIT](LICENSE)** منتشر شده است و استفاده شخصی و تجاری از آن کاملاً آزاد و رایگان است.

