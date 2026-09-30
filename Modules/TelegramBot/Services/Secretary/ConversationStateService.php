<?php

namespace Modules\TelegramBot\Services\Secretary;

use Illuminate\Support\Facades\Cache;

class ConversationStateService
{
    /**
     * فعال‌سازی حالت مداخله ادمین (سکوت کوتاه ربات برای جلوگیری از پاسخ هم‌زمان)
     */
    public function setAdminActive(int|string $chatId, int $minutes = 30): void
    {
        Cache::put("sec_admin_active_{$chatId}", true, now()->addMinutes($minutes));
    }

    /**
     * بررسی آیا ادمین اخیراً در این چت پیام داده است
     */
    public function isAdminActive(int|string $chatId): bool
    {
        return Cache::has("sec_admin_active_{$chatId}");
    }

    public function clearAdminActive(int|string $chatId): void
    {
        Cache::forget("sec_admin_active_{$chatId}");
    }

    /** سکوت دستی فرایدی تا زمانی که مدیر صریحاً آن را دوباره فعال کند. */
    public function setManualSilence(int|string $chatId): void
    {
        Cache::forever("sec_manual_silence_{$chatId}", true);
    }

    public function isManuallySilenced(int|string $chatId): bool
    {
        return Cache::has("sec_manual_silence_{$chatId}");
    }

    public function clearManualSilence(int|string $chatId): void
    {
        Cache::forget("sec_manual_silence_{$chatId}");
    }

    /** شناسه پیام‌های فرایدی برای پاک‌سازی امن با فرمان «سکوت 1». */
    public function rememberBotMessageId(int|string $chatId, int $messageId): void
    {
        if ($messageId <= 0) return;
        $key = "sec_bot_message_ids_{$chatId}";
        $ids = Cache::get($key, []);
        $ids[] = $messageId;
        $ids = array_values(array_unique(array_map('intval', $ids)));
        Cache::put($key, array_slice($ids, -100), now()->addHours(48));
    }

    public function getBotMessageIds(int|string $chatId): array
    {
        $ids = Cache::get("sec_bot_message_ids_{$chatId}", []);
        return is_array($ids) ? array_values(array_filter(array_map('intval', $ids))) : [];
    }

    public function clearBotMessageIds(int|string $chatId): void
    {
        Cache::forget("sec_bot_message_ids_{$chatId}");
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

    public function clearHumanRequested(int|string $chatId): void
    {
        Cache::forget("sec_human_req_{$chatId}");
    }

    public function resumeAutomation(int|string $chatId): void
    {
        $this->clearAdminActive($chatId);
        $this->clearHumanRequested($chatId);
        $this->clearManualSilence($chatId);
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

    /** جلوگیری از پردازش دوباره یک callback در retryهای تلگرام. */
    public function claimCallback(string $callbackId): bool
    {
        if (trim($callbackId) === '') return false;
        return (bool) Cache::add(
            'sec_callback_' . hash('sha256', $callbackId),
            true,
            now()->addMinutes(15)
        );
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

    public function getLastIntent(int|string $chatId): ?string
    {
        $intent = Cache::get("sec_last_intent_{$chatId}");
        return is_string($intent) && $intent !== '' ? $intent : null;
    }

    public function setLastIntent(int|string $chatId, string $intent): void
    {
        Cache::put("sec_last_intent_{$chatId}", $intent, now()->addMinutes(30));
    }

    public function getDiagnosis(int|string $chatId): ?array
    {
        $state = Cache::get("sec_diagnosis_{$chatId}");
        return is_array($state) ? $state : null;
    }

    public function setDiagnosis(int|string $chatId, array $state): void
    {
        Cache::put("sec_diagnosis_{$chatId}", $state, now()->addHours(2));
    }

    public function clearDiagnosis(int|string $chatId): void
    {
        Cache::forget("sec_diagnosis_{$chatId}");
    }

    /**
     * ثبت مسیر انتخاب‌های کاربر برای تحویل دقیق گفتگو به پشتیبان انسانی.
     * فقط عنوان عملیاتی و داده‌های غیرحساس نگه‌داری می‌شوند.
     */
    public function recordInteraction(int|string $chatId, string $action, string $label, array $meta = []): void
    {
        $key = "sec_interactions_{$chatId}";
        $trail = Cache::get($key, []);
        $safeMeta = [];

        foreach ($meta as $metaKey => $value) {
            if (is_scalar($value) || $value === null) {
                $safeMeta[(string) $metaKey] = $value;
            }
        }

        $trail[] = [
            'action' => mb_substr(trim($action), 0, 80),
            'label' => mb_substr(trim($label), 0, 180),
            'meta' => $safeMeta,
            'at' => now()->toIso8601String(),
        ];

        Cache::put($key, array_slice($trail, -20), now()->addHours(12));
    }

    public function getInteractionTrail(int|string $chatId): array
    {
        $trail = Cache::get("sec_interactions_{$chatId}", []);
        return is_array($trail) ? $trail : [];
    }

    public function clearInteractionTrail(int|string $chatId): void
    {
        Cache::forget("sec_interactions_{$chatId}");
    }

    public function rememberBusinessConnection(int|string $chatId, ?string $businessConnectionId): void
    {
        if (!$businessConnectionId) return;
        Cache::put("sec_business_connection_{$chatId}", $businessConnectionId, now()->addDays(30));
    }

    public function getBusinessConnection(int|string $chatId): ?string
    {
        $value = Cache::get("sec_business_connection_{$chatId}");
        return is_string($value) && $value !== '' ? $value : null;
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
