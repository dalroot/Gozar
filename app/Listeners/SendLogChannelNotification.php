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
            // اکانت‌های تست نوتیفیکیشن اختصاصی دارند؛ نیاز به ثبت مجدد در این بخش نیست
            if ($order->payment_method === 'trial' || (int)$order->amount === 0) {
                return;
            }

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
            $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name ?: 'کاربر') . "</a>";

            $msg = "💰 <b>ثبت پرداخت موفق جدید</b>\n\n";
            $msg .= "🧾 <b>شماره سفارش:</b> #{$order->id}\n";
            $msg .= "📦 <b>نوع:</b> " . htmlspecialchars($orderType) . "\n";
            $msg .= "💎 <b>پلن:</b> " . htmlspecialchars($planName) . "\n";
            $msg .= "💵 <b>مبلغ:</b> <code>" . number_format($order->amount) . " تومان</code>\n";
            $msg .= "💳 <b>روش پرداخت:</b> " . htmlspecialchars($paymentMethod) . "\n";
            $msg .= "🌐 <b>منبع:</b> " . htmlspecialchars($source) . "\n\n";

            $msg .= "👤 <b>مشخصات خریدار:</b>\n";
            $msg .= "▪️ <b>نام:</b> {$userLink}\n";
            if ($user->telegram_chat_id) {
                $msg .= "▪️ <b>شناسه تلگرام:</b> <code>{$user->telegram_chat_id}</code>\n";
            }
            if (!empty($user->username)) {
                $msg .= "▪️ <b>نام کاربری:</b> @" . ltrim($user->username, '@') . "\n";
            }
            $msg .= "▪️ <b>کد کاربری سیستم:</b> <code>{$user->id}</code>\n";
            $msg .= "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s');

            Telegram::sendMessage([
                'chat_id' => $logChannelId,
                'text' => $msg,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
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
            $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name ?: 'کاربر') . "</a>";

            $msg = "📩 <b>تیکت پشتیبانی جدید</b>\n\n";
            $msg .= "🎫 <b>شماره تیکت:</b> #{$ticket->id}\n";
            $msg .= "📌 <b>موضوع:</b> " . htmlspecialchars($ticket->subject) . "\n";
            $msg .= "⚡️ <b>اولویت:</b> " . htmlspecialchars($priority) . "\n";
            $msg .= "🌐 <b>منبع:</b> " . htmlspecialchars($source) . "\n\n";

            $msg .= "👤 <b>مشخصات کاربر:</b>\n";
            $msg .= "▪️ <b>نام:</b> {$userLink}\n";
            if ($user->telegram_chat_id) {
                $msg .= "▪️ <b>شناسه تلگرام:</b> <code>{$user->telegram_chat_id}</code>\n";
            }
            if (!empty($user->username)) {
                $msg .= "▪️ <b>نام کاربری:</b> @" . ltrim($user->username, '@') . "\n";
            }
            $msg .= "▪️ <b>کد کاربری سیستم:</b> <code>{$user->id}</code>\n\n";

            $msg .= "📄 <b>متن پیام:</b>\n";
            $msg .= "<blockquote>" . htmlspecialchars(mb_substr($ticket->message, 0, 300)) . (mb_strlen($ticket->message) > 300 ? '...' : '') . "</blockquote>\n";
            $msg .= "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s');

            Telegram::sendMessage([
                'chat_id' => $logChannelId,
                'text' => $msg,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            Log::info("Ticket notification sent to log channel for ticket {$ticket->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send ticket notification to log channel: " . $e->getMessage());
        }
    }
}
