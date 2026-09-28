<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Modules\Ticketing\Models\Ticket;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'bot_state',
        'telegram_chat_id',
        'trial_accounts_taken',
        'balance',
        'referrer_id',
        'referral_code',
        'referral_banned',
        'referral_traffic_balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'referral_banned' => 'boolean',
            'referral_traffic_balance' => 'double',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    public function notifications()
    {

        return $this->hasMany(Notification::class);
    }
    public function unreadNotifications()
    {
        return $this->hasMany(Notification::class)->where('is_read', false)->latest();
    }

    public function referralLogs()
    {
        return $this->hasMany(ReferralLog::class, 'referrer_id');
    }


}
