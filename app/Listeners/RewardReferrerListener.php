<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Log;

class RewardReferrerListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        $user = $order->user;

        // فقط برای اولین سفارش پرداخت شده‌ی موفق کاربر
        if ($user->referrer_id && $this->isFirstPaidOrder($user)) {
            try {
                $referralService = new ReferralService();
                $referralService->recordPurchase($user);
            } catch (\Exception $e) {
                Log::error("Failed to reward MLM referrers on purchase: " . $e->getMessage());
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
