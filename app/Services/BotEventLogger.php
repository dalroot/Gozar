<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

final class BotEventLogger
{
    private const ALLOWED = [
        'user_id', 'order_id', 'plan_id', 'ticket_id', 'intent', 'status',
        'payment_method', 'is_renewal', 'has_service', 'is_introduced',
        'diagnosis_step', 'button_count', 'source', 'reason',
        'action',
    ];

    public function record(string $event, string $surface, array $context = []): void
    {
        $payload = [
            'event' => $event,
            'surface' => $surface,
        ];

        if (isset($context['chat_id'])) {
            $payload['actor_ref'] = substr(hash_hmac(
                'sha256',
                (string) $context['chat_id'],
                (string) config('app.key', 'rozaneh')
            ), 0, 16);
        }

        foreach (self::ALLOWED as $key) {
            $value = $context[$key] ?? null;
            if (is_scalar($value) || $value === null) $payload[$key] = $value;
        }

        Log::info('rozaneh.bot_event', $payload);
    }
}
