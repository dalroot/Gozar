<?php

namespace App\Services;

use App\Models\User;
use App\Models\ReferralLog;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class ReferralService
{
    /**
     * بررسی مشکوک بودن رفرال بر اساس قوانین آنتی‌چیت
     */
    public function checkSuspicion(User $referrer, User $referee, ?string $ipAddress, ?string $userAgent, ?string &$reason): bool
    {
        $reason = null;

        // ۱. بررسی قانون آی‌پی تکراری (بیش از ۲ ثبت‌نام با یک آی‌پی برای این معرف)
        if (!empty($ipAddress)) {
            $sameIpCount = ReferralLog::where('referrer_id', $referrer->id)
                ->where('ip_address', $ipAddress)
                ->count();
                
            if ($sameIpCount >= 2) {
                $reason = "ثبت‌نام مکرر ({$sameIpCount} بار) با یک آی‌پی آدرس ({$ipAddress})";
                return true;
            }
        }

        // ۲. قانون سوسکی: بررسی خرید زیرمجموعه‌ها در صورتی که کاربر بیش از ۵ گیگابایت حجم دریافت کرده باشد
        $totalEarnedMB = ReferralLog::where('referrer_id', $referrer->id)
            ->where('is_suspicious', false)
            ->sum('traffic_reward');

        if ($totalEarnedMB >= 5120.0) { // معادل ۵ گیگابایت
            $activeReferralsCount = User::where('referrer_id', $referrer->id)
                ->whereHas('orders', function ($query) {
                    $query->where('status', 'paid');
                })
                ->count();

            if ($activeReferralsCount === 0) {
                $reason = "دریافت حجم رایگان بالا (۵ گیگابایت+) بدون داشتن حتی یک خرید موفق در زیرمجموعه‌ها";
                return true;
            }
        }

        // ۳. بررسی آیدی تلگرام خیلی جدید (بالای ۸ میلیارد) بدون داشتن یوزرنیم
        if ($referee->telegram_chat_id && (int)$referee->telegram_chat_id > 8000000000) {
            if (empty($referee->name) || $referee->name === 'کاربر') {
                $reason = "اکانت تلگرام بسیار جدید (شناسه بالای ۸ میلیارد) بدون نام کاربری مناسب";
                return true;
            }
        }

        return false;
    }

    /**
     * ثبت رفرال مستقیم سطح ۱ هنگام عضویت کاربر جدید
     */
    public function recordSignup(User $referrer, User $referee, ?string $ipAddress = null, ?string $userAgent = null): ReferralLog
    {
        $reason = null;
        $isSuspicious = $this->checkSuspicion($referrer, $referee, $ipAddress, $userAgent, $reason);

        $rewardMB = 1536.0; // ۱.۵ گیگابایت به مگابایت (1.5 * 1024)

        $log = ReferralLog::create([
            'referrer_id' => $referrer->id,
            'referee_id' => $referee->id,
            'level' => 1,
            'traffic_reward' => $rewardMB,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'is_suspicious' => $isSuspicious,
            'suspicion_reason' => $reason,
        ]);

        // اگر مشکوک نبود و معرف مسدود نبود، حجم هدیه را به کیف ترافیکی او اضافه کن
        if (!$isSuspicious && !$referrer->referral_banned) {
            $referrer->increment('referral_traffic_balance', $rewardMB);
        }

        return $log;
    }

    /**
     * ثبت پاداش‌های سطح ۲ و ۳ به صورت مشروط پس از اولین خرید موفق کاربر دعوت شده
     */
    public function recordPurchase(User $referee, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        // بررسی اینکه آیا این کاربر قبلاً خریدی انجام داده یا خیر (فقط اولین خرید شامل پاداش می‌شود)
        $paidOrdersCount = $referee->orders()->where('status', 'paid')->count();
        if ($paidOrdersCount > 1) {
            return; // اولین خرید نیست
        }

        $referrerL1 = $referee->referrer;
        if (!$referrerL1) {
            return;
        }

        // پاداش سطح ۲ برای معرفِ معرف (L2)
        $referrerL2 = $referrerL1->referrer;
        if ($referrerL2 && !$referrerL2->referral_banned) {
            $reason = null;
            $isSuspicious = $this->checkSuspicion($referrerL2, $referee, $ipAddress, $userAgent, $reason);
            $rewardMB = 1024.0; // ۱ گیگابایت

            ReferralLog::create([
                'referrer_id' => $referrerL2->id,
                'referee_id' => $referee->id,
                'level' => 2,
                'traffic_reward' => $rewardMB,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'is_suspicious' => $isSuspicious,
                'suspicion_reason' => $reason,
            ]);

            if (!$isSuspicious) {
                $referrerL2->increment('referral_traffic_balance', $rewardMB);
                
                // ارسال نوتیفیکیشن
                if ($referrerL2->telegram_chat_id) {
                    try {
                        $msg = "👤 <b>پاداش سطح ۲ صندوقچه ترافیک!</b>\n\nکاربر جدیدی به عنوان زیرمجموعه سطح ۲ شما خرید انجام داد و <code>۱.۰ گیگابایت</code> ترافیک هدیه به صندوقچه شما اضافه شد! 🎉";
                        Telegram::sendMessage([
                            'chat_id' => $referrerL2->telegram_chat_id,
                            'text' => $msg,
                            'parse_mode' => 'HTML'
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Failed to notify L2 referrer: " . $e->getMessage());
                    }
                }
            }
        }

        // پاداش سطح ۳ برای معرفِ سطح ۳ (L3)
        if ($referrerL2) {
            $referrerL3 = $referrerL2->referrer;
            if ($referrerL3 && !$referrerL3->referral_banned) {
                $reason = null;
                $isSuspicious = $this->checkSuspicion($referrerL3, $referee, $ipAddress, $userAgent, $reason);
                $rewardMB = 512.0; // ۵۰۰ مگابایت

                ReferralLog::create([
                    'referrer_id' => $referrerL3->id,
                    'referee_id' => $referee->id,
                    'level' => 3,
                    'traffic_reward' => $rewardMB,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'is_suspicious' => $isSuspicious,
                    'suspicion_reason' => $reason,
                ]);

                if (!$isSuspicious) {
                    $referrerL3->increment('referral_traffic_balance', $rewardMB);
                    
                    // ارسال نوتیفیکیشن
                    if ($referrerL3->telegram_chat_id) {
                        try {
                            $msg = "👤 <b>پاداش سطح ۳ صندوقچه ترافیک!</b>\n\nکاربر جدیدی به عنوان زیرمجموعه سطح ۳ شما خرید انجام داد و <code>۵۰۰ مگابایت</code> ترافیک هدیه به صندوقچه شما اضافه شد! 🎉";
                            Telegram::sendMessage([
                                'chat_id' => $referrerL3->telegram_chat_id,
                                'text' => $msg,
                                'parse_mode' => 'HTML'
                            ]);
                        } catch (\Exception $e) {
                            Log::error("Failed to notify L3 referrer: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
}
