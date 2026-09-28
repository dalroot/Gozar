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
                $settings = \App\Models\Setting::all()->pluck('value', 'key');
                $logChannelId = $settings->get('telegram_log_channel_id');
                $botToken = $settings->get('telegram_bot_token');

                if ($logChannelId && $botToken) {
                    \Telegram\Bot\Laravel\Facades\Telegram::setAccessToken($botToken);
                    
                    $userType = $user->telegram_chat_id ? "🤖 ربات تلگرام" : "🌐 وب‌سایت";
                    $esc = function (string $text): string {
                        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
                        return str_replace($chars, array_map(fn($char) => '\\' . $char, $chars), $text);
                    };

                    $msg = "👤 *کاربر جدید ثبت‌نام کرد*\n\n";
                    $msg .= "🔸 *نام:* " . $esc($user->name) . "\n";
                    $msg .= "🔸 *منبع:* {$userType}\n";
                    if ($user->telegram_chat_id) {
                        $msg .= "🔸 *آیدی تلگرام:* [{$user->telegram_chat_id}](tg://user?id={$user->telegram_chat_id})\n";
                    }
                    $msg .= "🔸 *شناسه کاربر در سیستم:* `{$user->id}`\n";
                    if ($user->referrer_id) {
                        $referrer = User::find($user->referrer_id);
                        if ($referrer) {
                            $msg .= "🔸 *معرف:* " . $esc($referrer->name) . " \\(ID: `{$referrer->id}`\\)\n";
                        }
                    }

                    \Telegram\Bot\Laravel\Facades\Telegram::sendMessage([
                        'chat_id' => $logChannelId,
                        'text' => $msg,
                        'parse_mode' => 'MarkdownV2',
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
