<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\User;
use App\Models\Transaction;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class RewardReferrerListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (!$user || !$user->referrer_id) {
            return;
        }

        $referrer = $user->referrer;
        if (!$referrer) {
            return;
        }

        // فقط برای اولین سفارش پرداخت شده‌ی موفق کاربر
        if ($this->isFirstPaidOrder($user)) {
            // کمپین اختصاصی رایان موزیک (شناسه ۱۳ یا کد rayanmusic)
            if ($referrer->referral_code === 'rayanmusic' || $referrer->id === 13) {
                try {
                    $commission = 30000;
                    $referrer->increment('balance', $commission);

                    Transaction::create([
                        'user_id' => $referrer->id,
                        'order_id' => $order->id,
                        'amount' => $commission,
                        'type' => 'referral_reward',
                        'status' => 'completed',
                        'description' => "پورسانت فروش کمپین رایان موزیک (سفارش #{$order->id} - {$user->name})"
                    ]);

                    if ($referrer->telegram_chat_id) {
                        $orderPlanName = $order->plan ? $order->plan->name : 'اشتراک روزنه';
                        $inv = $order->invoice_number ? "#{$order->invoice_number}" : "#{$order->id}";
                        $msg = "🎉 <b>فروش جدید از کمپین رایان موزیک!</b>\n\n"
                            . "👤 خریدار: <b>" . htmlspecialchars($user->name) . "</b>\n"
                            . "📦 بسته: <b>{$orderPlanName}</b> (فاکتور {$inv})\n"
                            . "💰 پورسانت شما: <b>۳۰,۰۰۰ تومان</b>\n\n"
                            . "✅ این مبلغ با موفقیت به موجودی کیف پول/حساب شما افزوده شد.";

                        Telegram::sendMessage([
                            'chat_id' => $referrer->telegram_chat_id,
                            'text' => $msg,
                            'parse_mode' => 'HTML'
                        ]);
                    }

                    Log::info("RayanMusic campaign commission rewarded: {$commission} tomans to user #{$referrer->id} for order #{$order->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to reward RayanMusic campaign commission: " . $e->getMessage());
                }
            } else {
                try {
                    $referralService = new ReferralService();
                    $referralService->recordPurchase($user);
                } catch (\Exception $e) {
                    Log::error("Failed to reward MLM referrers on purchase: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Checks if this is the user's first ever paid order.
     */
    private function isFirstPaidOrder(User $user): bool
    {
        $paidOrdersCount = $user->orders()
            ->where('status', 'paid')
            ->whereNotNull('plan_id')
            ->count();

        return $paidOrdersCount === 1;
    }
}
