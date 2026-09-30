<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TelegramServiceDeliveryService
{
    public function __construct(private BrandedQrService $qr)
    {
    }

    public function send(User $user, Order $order, ?Keyboard $keyboard = null, bool $isRenewal = false): void
    {
        $order->loadMissing(['plan', 'server.location']);
        $link = trim((string) $order->config_details);
        if ($link === '') throw new \RuntimeException('لینک اشتراک برای ارسال QR آماده نیست.');

        $route = Cache::get('telegram_delivery_route_' . $order->id);
        if (!$route && $isRenewal) {
            $renewalOrder = Order::where('renews_order_id', $order->id)->latest()->first();
            if ($renewalOrder) $route = Cache::get('telegram_delivery_route_' . $renewalOrder->id);
        }
        if (is_array($route) && ($route['surface'] ?? null) === 'friday') {
            $this->sendViaSecretary($user, $order, $route, $isRenewal);
            return;
        }

        $token = trim((string) Setting::where('key', 'telegram_bot_token')->value('value'));
        if ($token !== '') Telegram::setAccessToken($token);

        $keyboard ??= $this->keyboard($order);
        $asset = $this->qr->generate($link, $order->payment_method === 'trial' ? 'test' : 'paid');
        Telegram::sendPhoto([
            'chat_id' => $user->telegram_chat_id,
            'photo' => is_file($asset) ? InputFile::create($asset) : $asset,
            'caption' => $this->caption($order, $isRenewal),
            'parse_mode' => 'MarkdownV2',
            'reply_markup' => $keyboard,
        ]);
        Cache::put('telegram_delivery_sent_' . $order->id, true, now()->addDays(7));

        app(BotEventLogger::class)->record('service_delivered', 'sales_bot', [
            'chat_id' => $user->telegram_chat_id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'plan_id' => $order->plan_id,
            'is_renewal' => $isRenewal,
            'status' => 'sent',
        ]);
    }

    private function sendViaSecretary(User $user, Order $order, array $route, bool $isRenewal): void
    {
        $token = trim((string) env('SECRETARY_BOT_TOKEN', ''));
        if ($token === '') throw new \RuntimeException('توکن فرایدی برای تحویل سرویس تنظیم نشده است.');
        $asset = $this->qr->generate((string) $order->config_details, $order->payment_method === 'trial' ? 'test' : 'paid');
        $buttons = [
            [
                ['text' => '📋 دریافت مجدد لینک', 'callback_data' => 'sec_copy_link_' . $order->id],
                ['text' => '🔗 لینک‌های اتصال مستقیم', 'callback_data' => 'sec_direct_configs_' . $order->id],
            ],
            [
                ['text' => '📚 راهنمای اتصال', 'callback_data' => 'sec_tutorial'],
                ['text' => $order->payment_method === 'trial' ? '🛍 خرید سرویس' : '🔍 بررسی وضعیت', 'callback_data' => $order->payment_method === 'trial' ? 'sec_view_durations' : 'diag_status'],
            ],
        ];
        $payload = [
            'chat_id' => $route['chat_id'] ?? $user->telegram_chat_id,
            'caption' => $this->caption($order, $isRenewal),
            'parse_mode' => 'MarkdownV2',
            'reply_markup' => json_encode(['inline_keyboard' => $buttons], JSON_UNESCAPED_UNICODE),
        ];
        if (!empty($route['business_connection_id'])) {
            $payload['business_connection_id'] = $route['business_connection_id'];
        }
        $url = "https://api.telegram.org/bot{$token}/sendPhoto";
        $response = is_file($asset)
            ? Http::timeout(25)->attach('photo', fopen($asset, 'r'), basename($asset))->post($url, $payload)
            : Http::timeout(25)->post($url, array_merge($payload, ['photo' => $asset]));
        if (!$response->successful()) {
            Log::error('Friday service delivery failed', ['order_id' => $order->id, 'status' => $response->status()]);
            throw new \RuntimeException('ارسال سرویس در فرایدی ناموفق بود.');
        }
        Cache::put('telegram_delivery_sent_' . $order->id, true, now()->addDays(7));
        app(BotEventLogger::class)->record('service_delivered', 'friday', [
            'chat_id' => $user->telegram_chat_id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'plan_id' => $order->plan_id,
            'is_renewal' => $isRenewal,
            'status' => 'sent',
        ]);
    }

    public function keyboard(Order $order): Keyboard
    {
        return Keyboard::make()->inline()
            ->row([
                Keyboard::inlineButton(['text' => '📋 دریافت مجدد لینک', 'callback_data' => "copy_link_{$order->id}"]),
                Keyboard::inlineButton(['text' => '🔗 لینک‌های اتصال مستقیم', 'callback_data' => "direct_configs_order_{$order->id}"]),
            ])
            ->row([
                Keyboard::inlineButton(['text' => '📚 راهنمای اتصال', 'callback_data' => '/tutorials']),
                Keyboard::inlineButton(['text' => '🛠 سرویس‌های من', 'callback_data' => '/my_services']),
            ])
            ->row([
                Keyboard::inlineButton(['text' => '🤖 پشتیبانی تلگرام', 'url' => 'https://t.me/RoozanehHelp']),
                Keyboard::inlineButton(['text' => '🏠 منوی اصلی', 'callback_data' => '/start']),
            ]);
    }

    public function sendNotice(User $user, Order $order, string $text, array $buttons = []): bool
    {
        $route = Cache::get('telegram_delivery_route_' . $order->id);
        if (is_array($route) && ($route['surface'] ?? null) === 'friday') {
            $token = trim((string) env('SECRETARY_BOT_TOKEN', ''));
            if ($token === '') return false;
            $payload = [
                'chat_id' => $route['chat_id'] ?? $user->telegram_chat_id,
                'text' => $text,
            ];
            if ($buttons) $payload['reply_markup'] = json_encode(['inline_keyboard' => $buttons], JSON_UNESCAPED_UNICODE);
            if (!empty($route['business_connection_id'])) $payload['business_connection_id'] = $route['business_connection_id'];
            try {
                return Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload)->successful();
            } catch (\Throwable $e) {
                Log::warning('Friday order notice failed', ['order_id' => $order->id]);
                return false;
            }
        }

        try {
            $token = trim((string) Setting::where('key', 'telegram_bot_token')->value('value'));
            if ($token === '') return false;
            Telegram::setAccessToken($token);
            // Secretary callback names are only valid on Friday. The legacy sales bot
            // keeps its own keyboard/navigation and receives the notice as plain text.
            Telegram::sendMessage(['chat_id' => $user->telegram_chat_id, 'text' => $text]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Sales bot order notice failed', ['order_id' => $order->id]);
            return false;
        }
    }

    public function caption(Order $order, bool $isRenewal = false): string
    {
        $plan = $order->plan;
        if ($order->payment_method === 'trial') {
            $caption = "🎁 *" . $this->escape('سرویس تست رایگان شما آماده شد') . "*\n\n";
            $caption .= "📦 *حجم:* " . $this->escape(($plan?->volume_gb ?? 1) . ' گیگابایت') . "\n";
            $caption .= "⏳ *اعتبار زمانی:* " . $this->escape('بدون محدودیت زمانی') . "\n";
            $caption .= "👤 *نام سرویس:* `" . $this->escapeCode((string) $order->panel_username) . "`\n\n";
            $caption .= "🔗 *لینک اشتراک:*\n`" . $this->escapeCode((string) $order->config_details) . "`\n\n";
            $caption .= $this->escape("📱 برای اتصال، بارکد QR بالا را اسکن کرده یا لینک اشتراک را در برنامه وارد کنید. این تست برای هر حساب فقط یک‌بار ارائه می‌شود.\n\n💬 در صورت بروز هرگونه سوال یا مشکل، با پشتیبانی (@RoozanehHelp) در ارتباط باشید.");
            return $caption;
        }
        $title = $isRenewal ? 'تمدید سرویس با موفقیت انجام شد' : 'اشتراک شما با موفقیت فعال شد';
        $caption = "✅ *" . $this->escape($title) . "*\n\n";
        $caption .= "📦 *بسته:* `" . $this->escapeCode(trim((string) ($plan?->name ?? 'اشتراک'))) . "`\n";
        $caption .= "💾 *حجم:* " . $this->escape(($plan?->volume_gb ?? 0) . ' گیگابایت') . "\n";
        $hasTrialGift = $order->transactions()->where('type', 'bonus')->where('metadata->trial_conversion_gift', true)->exists();
        if ($hasTrialGift) {
            $caption .= "🎁 *هدیه خرید پس از تست:* " . $this->escape('۱ گیگابایت اضافه') . "\n";
        }
        $caption .= "📅 *مدت:* " . $this->escape(($plan?->duration_days ?? 0) . ' روز') . "\n";
        $expiry = $order->expires_at ? Carbon::parse($order->expires_at)->format('Y/m/d H:i') : 'نامشخص';
        $caption .= "⏳ *تاریخ پایان:* `" . $expiry . "`\n";
        $caption .= "👤 *نام سرویس:* `" . $this->escapeCode((string) $order->panel_username) . "`\n\n";
        $caption .= "🔗 *لینک اشتراک:*\n`" . $this->escapeCode((string) $order->config_details) . "`\n\n";
        $caption .= $this->escape("📱 برای اتصال، بارکد QR بالا را اسکن کرده یا لینک اشتراک را در برنامه وارد کنید. جهت دریافت کانفیگ‌های مستقیم، دکمهٔ «لینک‌های اتصال مستقیم» را لمس فرمایید.\n\n💬 در صورت بروز هرگونه سوال یا مشکل در اتصال، به پشتیبانی (@RoozanehHelp) پیام دهید.");

        return $caption;
    }

    private function escape(string $text): string
    {
        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        return str_replace($chars, array_map(fn ($char) => '\\' . $char, $chars), $text);
    }

    private function escapeCode(string $text): string
    {
        return str_replace(['\\', '`'], ['\\\\', '\\`'], $text);
    }
}
