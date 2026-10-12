<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;


use Modules\Ticketing\Providers\EventServiceProvider as TicketingEventServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // رجیستر EventServiceProvider ماژول Ticketing
        $this->app->register(TicketingEventServiceProvider::class);
    }

    public function boot(): void
    {
        User::creating(function ($user) {
            do {
                $code = 'REF-' . strtoupper(\Illuminate\Support\Str::random(6));
            } while (User::where('referral_code', $code)->exists());

            $user->referral_code = $code;
        });

        User::created(function ($user) {
            try {
                // کاربران ربات تلگرام در کنترلر اختصاصی ربات با دکمه شیشه‌ای و جزئیات کامل لاگ می‌شوند
                if ($user->telegram_chat_id) {
                    return;
                }

                $settings = \App\Models\Setting::all()->pluck('value', 'key');
                $logChannelId = $settings->get('telegram_log_channel_id');
                $botToken = $settings->get('telegram_bot_token');

                if ($logChannelId && $botToken) {
                    \Telegram\Bot\Laravel\Facades\Telegram::setAccessToken($botToken);

                    $msg = "👤 <b>ثبت‌نام کاربر جدید در وب‌سایت</b>\n\n";
                    $msg .= "🔸 <b>نام:</b> " . htmlspecialchars($user->name ?: 'کاربر') . "\n";
                    $msg .= "🔸 <b>منبع:</b> 🌐 وب‌سایت\n";
                    $msg .= "🔸 <b>شناسه کاربر در سیستم:</b> <code>#{$user->id}</code>\n";
                    if ($user->referrer_id) {
                        $referrer = User::find($user->referrer_id);
                        if ($referrer) {
                            $msg .= "🔸 <b>معرف:</b> " . htmlspecialchars($referrer->name) . " (ID: <code>{$referrer->id}</code>)\n";
                        }
                    }
                    $msg .= "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s');

                    \Telegram\Bot\Laravel\Facades\Telegram::sendMessage([
                        'chat_id' => $logChannelId,
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error("Failed to send new user log: " . $e->getMessage());
            }
        });

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\OrderPaid::class,
            [\App\Listeners\SendLogChannelNotification::class, 'handleOrderPaid']
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Ticketing\Events\TicketCreated::class,
            [\App\Listeners\SendLogChannelNotification::class, 'handleTicketCreated']
        );
    }
}
