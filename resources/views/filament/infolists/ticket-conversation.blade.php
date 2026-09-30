@php
    $ticket = $getRecord();
    $replies = $ticket->replies()->with('user')->orderBy('created_at')->get();
    $rawMessage = trim((string) $ticket->message);
    $knownKeys = [
        'telegram_chat_id', 'service_order_id', 'service_status', 'service_expires_at',
        'device', 'recent_conversation', 'interaction_trail',
    ];
    $keyPattern = implode('|', array_map('preg_quote', $knownKeys));
    $parts = preg_split('/(?=^(?:' . $keyPattern . '):)/m', $rawMessage) ?: [];
    $summary = trim((string) array_shift($parts));
    $context = [];
    foreach ($parts as $part) {
        if (preg_match('/^([a-z_]+):\s*(.*)$/s', trim($part), $matches)) {
            $context[$matches[1]] = trim(strip_tags($matches[2]));
        }
    }
    $contextLabels = [
        'telegram_chat_id' => 'شناسه تلگرام',
        'service_order_id' => 'شماره سفارش',
        'service_status' => 'وضعیت سرویس',
        'service_expires_at' => 'پایان اعتبار',
        'device' => 'دستگاه یا برنامه',
    ];
@endphp

<div class="rz-conversation" dir="rtl">
    <article class="rz-conversation__message rz-conversation__message--customer">
        <header>
            <strong>خلاصه درخواست {{ $ticket->user?->name ?? 'مشتری' }}</strong>
            <span>{{ optional($ticket->created_at)->diffForHumans() }}</span>
        </header>
        <p>{{ $summary ?: 'پیام اولیه‌ای برای این تیکت ثبت نشده است.' }}</p>

        @if(collect($contextLabels)->keys()->contains(fn ($key) => filled($context[$key] ?? null)))
            <dl class="rz-conversation__facts">
                @foreach($contextLabels as $key => $label)
                    @if(filled($context[$key] ?? null))
                        <div><dt>{{ $label }}</dt><dd>{{ $context[$key] }}</dd></div>
                    @endif
                @endforeach
            </dl>
        @endif

        @if(filled($context['recent_conversation'] ?? null))
            <section class="rz-conversation__context-block">
                <h4>گفتگوی اخیر</h4>
                <div>{{ $context['recent_conversation'] }}</div>
            </section>
        @endif

        @if(filled($context['interaction_trail'] ?? null))
            <section class="rz-conversation__context-block">
                <h4>مسیر اقدامات کاربر</h4>
                <div>{{ $context['interaction_trail'] }}</div>
            </section>
        @endif
    </article>

    @foreach($replies as $reply)
        @php
            $isAdmin = (bool) ($reply->user?->is_admin);
            $attachmentPath = $reply->attachment_path;
            if (is_string($attachmentPath) && ($decoded = json_decode($attachmentPath, true)) && is_array($decoded)) {
                $attachmentPath = $decoded[0] ?? null;
            }
        @endphp
        <article @class([
            'rz-conversation__message',
            'rz-conversation__message--support' => $isAdmin,
            'rz-conversation__message--customer' => !$isAdmin,
        ])>
            <header>
                <strong>{{ $isAdmin ? 'پشتیبانی روزنه' : ($reply->user?->name ?? 'مشتری') }}</strong>
                <span>{{ optional($reply->created_at)->diffForHumans() }}</span>
            </header>
            <p>{{ $reply->message }}</p>
            @if($attachmentPath)
                <a class="rz-conversation__attachment" href="{{ Storage::disk('public')->url($attachmentPath) }}" target="_blank" rel="noopener">
                    مشاهده فایل ضمیمه
                </a>
            @endif
        </article>
    @endforeach

    @if($replies->isEmpty())
        <p class="rz-conversation__empty">هنوز پاسخی برای این درخواست ثبت نشده است.</p>
    @endif
</div>
