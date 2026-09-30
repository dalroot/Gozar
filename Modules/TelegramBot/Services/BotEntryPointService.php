<?php

namespace Modules\TelegramBot\Services;

use App\Models\TelegramBotSetting;

final class BotEntryPointService
{
    private const FALLBACK_USERNAME = 'gozartest2026bot';

    public function username(): string
    {
        $configured = trim((string) TelegramBotSetting::where('key', 'bot_username')->value('value'));
        $username = ltrim($configured, '@');

        return preg_match('/^[A-Za-z0-9_]{5,32}$/', $username)
            ? $username
            : self::FALLBACK_USERNAME;
    }

    public function url(?string $startPayload = null): string
    {
        $url = 'https://t.me/' . $this->username();
        $payload = trim((string) $startPayload);

        if ($payload === '') return $url;
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $payload)) {
            throw new \InvalidArgumentException('Invalid Telegram start payload.');
        }

        return $url . '?start=' . rawurlencode($payload);
    }
}
