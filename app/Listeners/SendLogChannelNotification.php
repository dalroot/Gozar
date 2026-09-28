<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use Modules\Ticketing\Events\TicketCreated;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendLogChannelNotification
{
    /**
     * Escape characters for MarkdownV2 parse mode.
     */
    protected function escape(string $text): string
    {
        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        return str_replace($chars, array_map(fn($char) => '\\' . $char, $chars), $text);
    }

    /**
     * Handle OrderPaid event.
     */
    public function handleOrderPaid(OrderPaid $event): void
    {
        try {
            $order = $event->order;
            $user = $order->user;
            $plan = $order->plan;

            $settings = Setting::all()->pluck('value', 'key');
            $logChannelId = $settings->get('telegram_log_channel_id');
            $botToken = $settings->get('telegram_bot_token');

            if (!$logChannelId || !$botToken) {
                return;
            }

            Telegram::setAccessToken($botToken);

            $orderType = $order->renews_order_id ? 'تمدید سرویس' : ($plan ? "خرید سرویس" : "شارژ کیف پول");
            $planName = $plan ? $plan->name : 'شارژ کیف پول';
            $paymentMethod = $order->payment_method ?? 'نامشخص';
            $source = $order->source === 'telegram' ? '🤖 ربات تلگرام' : '🌐 وب‌سایت';

            $msg = "💰 *خرید/شارژ جدید ثبت شد*\n\n";
            $msg .= "🔸 *سفارش:* \\#{$order->id}\n";
            $msg .= "🔸 *نوع:* " . $this->escape($orderType) . "\n";
            $msg .= "🔸 *پلن:* " . $this->escape($planName) . "\n";
            $msg .= "🔸 *مبلغ:* " . $this->escape(number_format($order->amount) . ' تومان') . "\n";
            $msg .= "🔸 *روش پرداخت:* " . $this->escape($paymentMethod) . "\n";
            $msg .= "🔸 *منبع:* " . $this->escape($source) . "\n";
            
            $msg .= "\n👤 *مشخصات خریدار:*\n";
            $msg .= "🔸 *نام:* " . $this->escape($user->name) . "\n";
            if ($user->telegram_chat_id) {
                $msg .= "🔸 *آیدی تلگرام:* [{$user->telegram_chat_id}](tg://user?id={$user->telegram_chat_id})\n";
            }
            $msg .= "🔸 *شناسه کاربر در سیستم:* `{$user->id}`\n";

            Telegram::sendMessage([
                'chat_id' => $logChannelId,
                'text' => $msg,
                'parse_mode' => 'MarkdownV2',
            ]);

            Log::info("Purchase notification sent to log channel for order {$order->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send purchase notification to log channel: " . $e->getMessage());
        }
    }

    /**
     * Handle TicketCreated event.
     */
    public function handleTicketCreated(TicketCreated $event): void
    {
        try {
            $ticket = $event->ticket;
            $user = $ticket->user;

            $settings = Setting::all()->pluck('value', 'key');
            $logChannelId = $settings->get('telegram_log_channel_id');
            $botToken = $settings->get('telegram_bot_token');

            if (!$logChannelId || !$botToken) {
                return;
            }

            Telegram::setAccessToken($botToken);

            $priorityMap = [
                'low' => '🔵 کم',
                'medium' => '🟡 متوسط',
                'high' => '🔴 زیاد',
            ];
            $priority = $priorityMap[$ticket->priority] ?? $ticket->priority;
            $source = $ticket->source === 'telegram' ? '🤖 ربات تلگرام' : '🌐 وب‌سایت';

            $msg = "📩 *تیکت پشتیبانی جدید*\n\n";
            $msg .= "🔸 *تیکت:* \\#{$ticket->id}\n";
            $msg .= "🔸 *موضوع:* " . $this->escape($ticket->subject) . "\n";
            $msg .= "🔸 *اولویت:* " . $this->escape($priority) . "\n";
            $msg .= "🔸 *منبع:* " . $this->escape($source) . "\n";
            
            $msg .= "\n👤 *مشخصات کاربر:*\n";
            $msg .= "🔸 *نام:* " . $this->escape($user->name) . "\n";
            if ($user->telegram_chat_id) {
                $msg .= "🔸 *آیدی تلگرام:* [{$user->telegram_chat_id}](tg://user?id={$user->telegram_chat_id})\n";
            }
            $msg .= "🔸 *شناسه کاربر در سیستم:* `{$user->id}`\n";

            $msg .= "\n📄 *متن پیام:*\n";
            $msg .= "_" . $this->escape(mb_substr($ticket->message, 0, 300)) . (mb_strlen($ticket->message) > 300 ? '...' : '') . "_";

            Telegram::sendMessage([
                'chat_id' => $logChannelId,
                'text' => $msg,
                'parse_mode' => 'MarkdownV2',
            ]);

            Log::info("Ticket notification sent to log channel for ticket {$ticket->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send ticket notification to log channel: " . $e->getMessage());
        }
    }
}
