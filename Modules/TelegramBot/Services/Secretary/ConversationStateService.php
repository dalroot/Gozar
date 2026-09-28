<?php

namespace Modules\TelegramBot\Services\Secretary;

use Illuminate\Support\Facades\Cache;

class ConversationStateService
{
    /**
     * فعال‌سازی حالت مداخله ادمین (سکوت ربات به مدت ۱۲ ساعت)
     */
    public function setAdminActive(int|string $chatId, int $hours = 12): void
    {
        Cache::put("sec_admin_active_{$chatId}", true, now()->addHours($hours));
    }

    /**
     * بررسی آیا ادمین اخیراً در این چت پیام داده است
     */
    public function isAdminActive(int|string $chatId): bool
    {
        return Cache::has("sec_admin_active_{$chatId}");
    }

    /**
     * ثبت درخواست پشتیبان انسانی توسط کاربر
     */
    public function setHumanRequested(int|string $chatId, int $hours = 12): void
    {
        Cache::put("sec_human_req_{$chatId}", true, now()->addHours($hours));
    }

    /**
     * بررسی اینکه آیا کاربر درخواست پشتیبان داده است
     */
    public function isHumanRequested(int|string $chatId): bool
    {
        return Cache::has("sec_human_req_{$chatId}");
    }

    /**
     * قفل ضد تکرار پردازش پیام در حال اجرا
     */
    public function acquireChatLock(int|string $chatId, int $seconds = 10): bool
    {
        $lockKey = "sec_chat_lock_{$chatId}";
        return (bool) Cache::add($lockKey, true, now()->addSeconds($seconds));
    }

    public function releaseChatLock(int|string $chatId): void
    {
        Cache::forget("sec_chat_lock_{$chatId}");
    }

    /**
     * افزودن پیام به بافر تجمیع پیام‌های متوالی
     */
    public function pushMessageToBuffer(int|string $chatId, string $text): void
    {
        $key = "sec_msg_buffer_{$chatId}";
        $buffer = Cache::get($key, []);
        $buffer[] = trim($text);
        Cache::put($key, $buffer, now()->addSeconds(10));
    }

    public function appendToDebounceBuffer(int|string $chatId, string $text): void
    {
        $this->pushMessageToBuffer($chatId, $text);
    }

    /**
     * دریافت و خالی کردن بافر پیام‌ها
     */
    public function pullAggregatedBuffer(int|string $chatId): string
    {
        $key = "sec_msg_buffer_{$chatId}";
        $buffer = Cache::get($key, []);
        Cache::forget($key);
        if (empty($buffer)) {
            return '';
        }
        return implode("\n", array_unique($buffer));
    }

    /**
     * وضعیت معرفی اولیه در سشن
     */
    public function isIntroduced(int|string $chatId): bool
    {
        return Cache::has("sec_introduced_{$chatId}");
    }

    public function markAsIntroduced(int|string $chatId): void
    {
        Cache::put("sec_introduced_{$chatId}", true, now()->addDays(7));
    }

    /**
     * دریافت تاریخچه پیام‌های اخیر چت (حافظه مکالمه)
     */
    public function getChatHistory(int|string $chatId): array
    {
        return Cache::get("sec_history_{$chatId}", []);
    }

    /**
     * افزودن پیام جدید به تاریخچه مکالمه
     */
    public function addMessageToHistory(int|string $chatId, string $role, string $content): void
    {
        $history = $this->getChatHistory($chatId);
        $history[] = [
            'role' => $role === 'user' ? 'user' : 'assistant',
            'content' => $content,
        ];

        // نگهداری ۶ پیام اخیر جهت حفظ کانتکست تمیز
        if (count($history) > 6) {
            $history = array_slice($history, -6);
        }

        Cache::put("sec_history_{$chatId}", $history, now()->addHours(4));
    }
}
