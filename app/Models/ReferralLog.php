<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralLog extends Model
{
    protected $fillable = [
        'referrer_id',
        'referee_id',
        'level',
        'traffic_reward',
        'ip_address',
        'user_agent',
        'is_suspicious',
        'suspicion_reason',
    ];

    protected $casts = [
        'is_suspicious' => 'boolean',
        'traffic_reward' => 'double',
        'level' => 'integer',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }
}
