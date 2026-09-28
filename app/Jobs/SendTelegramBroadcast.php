<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\TelegramBot\Http\Controllers\WebhookController; // <-- از کنترلر ربات استفاده می‌کنیم

class SendTelegramBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;
    protected $target;
    protected $channelId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $message, string $target = 'users', ?string $channelId = null)
    {
        $this->message = $message;
        $this->target = $target;
        $this->channelId = $channelId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $controller = new WebhookController();
        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        $botSettings = \App\Models\TelegramBotSetting::all()->pluck('value', 'key');

        // 1. ارسال به کانال تلگرام
        if (in_array($this->target, ['channel', 'all'])) {
            $channelTarget = $this->channelId 
                ?: $botSettings->get('telegram_channel')
                ?: $settings->get('telegram_channel') 
                ?: $settings->get('telegram_receipt_channel_id')
                ?: $settings->get('telegram_required_channel_id')
                ?: $settings->get('telegram_channel_url');

            if ($channelTarget) {
                // اگر لینک کامل کانال بود، یوزرنیم آن را استخراج کن
                if (filter_var($channelTarget, FILTER_VALIDATE_URL)) {
                    $path = parse_url($channelTarget, PHP_URL_PATH);
                    $username = trim($path, '/');
                    if ($username) {
                        $channelTarget = '@' . $username;
                    }
                }
                
                $controller->sendBroadcastToChannel($channelTarget, $this->message);
            }
        }

        // 2. ارسال به همه کاربران ربات
        if (in_array($this->target, ['users', 'all'])) {
            User::whereNotNull('telegram_chat_id')
                ->select('telegram_chat_id')
                ->chunk(100, function ($users) use ($controller) {
                    foreach ($users as $user) {
                        $controller->sendBroadcastMessage($user->telegram_chat_id, $this->message);
                        usleep(50000);
                    }
                });
        }
    }
}
