<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;

class WebAppController extends Controller
{
    /**
     * احراز هویت ساده بر اساس پارامترهای تلگرام
     * در محیط پروداکشن باید initData را اعتبارسنجی کنید
     */
    private function getUser(Request $request)
    {
        // در مینی‌اپ، تلگرام شناسه کاربر را ارسال می‌کند
        // فعلاً برای تست ساده از query string می‌گیریم
        // مثال: https://your-domain.com/webapp?tg_id=123456789
        $tgId = $request->query('tg_id');

        if (!$tgId) return null;

        return User::where('telegram_chat_id', $tgId)->first();
    }

    public function index(Request $request)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return view('webapp.error', ['message' => 'کاربر یافت نشد. لطفا ربات را استارت کنید.']);
        }

        $activeServices = $user->orders()
            ->where('status', 'paid')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return view('webapp.index', compact('user', 'activeServices'));
    }

    public function plans(Request $request)
    {
        $user = $this->getUser($request);
        $plans = Plan::where('is_active', true)->orderBy('price')->get();

        return view('webapp.plans', compact('user', 'plans'));
    }

    public function orderDetail($id, Request $request)
    {
        $user = $this->getUser($request);
        $order = Order::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        return view('webapp.detail', compact('user', 'order'));
    }

    public function referral(Request $request)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return view('webapp.error', ['message' => 'کاربر یافت نشد.']);
        }

        // Get bot username from settings (assuming it's stored or hardcoded)
        $botUsername = \App\Models\TelegramBotSetting::where('key', 'bot_username')->value('value') ?? 'vpnmarket_bot';
        $inviteLink = "https://t.me/{$botUsername}?start=ref_{$user->id}";

        // If you have a referral system, calculate real stats
        // Mock stats for UI:
        $totalInvited = \App\Models\User::where('referred_by', $user->id)->count();
        $totalEarned = 0; // Or calculate from ReferralLogs if they exist

        return view('webapp.referral', compact('user', 'inviteLink', 'totalInvited', 'totalEarned'));
    }

    public function wheel(Request $request)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return view('webapp.error', ['message' => 'کاربر یافت نشد.']);
        }

        // Check if user can spin (e.g., every 24 hours)
        $canSpin = true;
        $nextSpinAt = null;

        if ($user->last_spin_at && \Carbon\Carbon::parse($user->last_spin_at)->addHours(24)->isFuture()) {
            $canSpin = false;
            $nextSpinAt = \Carbon\Carbon::parse($user->last_spin_at)->addHours(24)->format('Y-m-d\TH:i:sP');
        }

        return view('webapp.wheel', compact('user', 'canSpin', 'nextSpinAt'));
    }

    public function spinWheel(Request $request)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'کاربر یافت نشد.']);
        }

        if ($user->last_spin_at && \Carbon\Carbon::parse($user->last_spin_at)->addHours(24)->isFuture()) {
            return response()->json(['success' => false, 'message' => 'شما قبلاً گردونه را چرخانده‌اید. لطفاً فردا امتحان کنید.']);
        }

        // Prizes array: [Prize Amount, Probability Weight, Title]
        // High probability for small prizes to keep engagement, 0 means no win (پوچ)
        $prizes = [
            [0, 30, 'پوچ!'], // 30% chance
            [500, 30, '۵۰۰ تومان'], // 30% chance
            [1000, 20, '۱,۰۰۰ تومان'], // 20% chance
            [2000, 15, '۲,۰۰۰ تومان'], // 15% chance
            [5000, 4, '۵,۰۰۰ تومان'], // 4% chance
            [10000, 1, '۱۰,۰۰۰ تومان! (ویژه)'], // 1% chance
        ];

        // Randomly select a prize based on weight
        $totalWeight = array_sum(array_column($prizes, 1));
        $rand = mt_rand(1, $totalWeight);

        $selectedPrize = $prizes[0];
        $prizeIndex = 0;
        
        $currentWeight = 0;
        foreach ($prizes as $index => $prize) {
            $currentWeight += $prize[1];
            if ($rand <= $currentWeight) {
                $selectedPrize = $prize;
                $prizeIndex = $index;
                break;
            }
        }

        // Update user
        $user->last_spin_at = now();
        if ($selectedPrize[0] > 0) {
            $user->balance += $selectedPrize[0];
        }
        $user->save();

        return response()->json([
            'success' => true,
            'prize_amount' => $selectedPrize[0],
            'prize_title' => $selectedPrize[2],
            'prize_index' => $prizeIndex, // To tell the frontend which slice to stop at
            'new_balance' => $user->balance
        ]);
    }
}
