<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendDiscountToExhaustedCommand extends Command
{
    protected $signature = "vpnmarket:send-exhausted-discount";
    protected $description = "Send 15% discount offer with rayan code and direct support ID to users whose trials are exhausted and have not purchased yet.";

    public function handle()
    {
        $settings = Setting::all()->pluck("value", "key");
        $botToken = trim($settings->get("telegram_bot_token"), "\"' ");
        $supportUrl = $settings->get("telegram_support_url", "https://t.me/RoozanehHelp");
        $supportId = $settings->get("telegram_support_id", "@RoozanehHelp");

        Telegram::setAccessToken($botToken);

        $targets = [
            ["id" => 14, "name" => "Mehdi", "chat_id" => "5950809504"],
            ["id" => 31, "name" => "Ruhan", "chat_id" => "1671979274"],
            ["id" => 38, "name" => "M.R.Mohamadi", "chat_id" => "218845445"],
            ["id" => 44, "name" => "Rayan", "chat_id" => "5609644784"],
        ];

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🛍 مشاهده پلن‌ها و خرید با تخفیف", "callback_data" => "/plans"]
                ],
                [
                    ["text" => "👨‍💻 ارتباط مستقیم با پشتیبانی", "url" => $supportUrl]
                ]
            ]
        ];

        foreach ($targets as $t) {
            $hasPurchased = Order::where("user_id", $t["id"])
                ->where("status", "paid")
                ->where("amount", ">", 0)
                ->exists();

            if ($hasPurchased) {
                $this->info("Skipping {$t['name']} (already purchased)");
                continue;
            }

            $userName = htmlspecialchars($t["name"] ?: "کاربر گرامی");
            $text = "🎁 <b>فرصت ویژه همراهی با روزنه — تخفیف ۱۵٪ اختصاصی</b>\n\n" .
                    "سلام <b>{$userName}</b> عزیز وقتتون بخیر 🌸\n" .
                    "امیدواریم از کیفیت، پایداری و سرعت سرویس تست <b>روزنه</b> رضایت داشته باشید ✨\n\n" .
                    "با توجه به اینکه حجم اکانت تست هدیه شما به پایان رسیده است، برای تداوم دسترسی آزاد، پرسرعت و بدون قطعی، یک <b>کد تخفیف ویژه ۱۵ درصدی</b> برای شما فعال کردیم تا بتوانید اولین اشتراک خود را با قیمت فوق‌العاده تهیه کنید:\n\n" .
                    "🎁 <b>کد تخفیف ۱۵٪:</b> <code>rayan</code>\n\n" .
                    "👨‍💻 <b>آیدی پشتیبانی مستقیم:</b> {$supportId}\n\n" .
                    "👇 <i>جهت مشاهده تعرفه‌ها و فعال‌سازی آنی اشتراک، از دکمه‌های زیر استفاده کنید:</i>";

            try {
                $res = Telegram::sendMessage([
                    "chat_id" => $t["chat_id"],
                    "text" => $text,
                    "parse_mode" => "HTML",
                    "reply_markup" => json_encode($keyboard)
                ]);
                $this->info("SUCCESS: Sent to {$t['name']} ({$t['chat_id']}) - MsgID: " . $res->getMessageId());
            } catch (\Throwable $e) {
                $this->error("FAILED: Could not send to {$t['name']} ({$t['chat_id']}): " . $e->getMessage());
            }
            usleep(400000);
        }

        return 0;
    }
}
