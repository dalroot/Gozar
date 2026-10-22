<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'server_id',
        'status',
        'expires_at',
        'payment_method',
        'card_payment_receipt',
        'nowpayments_payment_id',
        'config_details',
        'amount',
        'source',
        'panel_username',
        'reserved_slot',
        'trial_notified_80_at',
        'trial_notified_100_at',
        'trial_notified_idle_at',
        'invoice_number',
    ];

    public static function nextInvoiceNumber(): int
    {
        return ((int) static::whereNotNull('invoice_number')->max('invoice_number')) + 1;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }


    public function server()
    {

        if (class_exists('Modules\MultiServer\Models\Server')) {
            return $this->belongsTo(\Modules\MultiServer\Models\Server::class, 'server_id');
        }

        return $this->belongsTo(Plan::class, 'plan_id')->whereNull('id');
    }

    public function store(Plan $plan)
    {
        return view('payment.choose', ['plan' => $plan]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            Log::info('Order is being created', [
                'panel_username' => $order->panel_username,
                'user_id' => $order->user_id
            ]);

            // اختصاص خودکار شماره فاکتور رسمی فقط برای خریدهای نقدی موفق (نه اکانت‌های تست)
            if ($order->status === 'paid' && (float)$order->amount > 0 && empty($order->invoice_number)) {
                if ($order->payment_method !== 'trial' && $order->plan_id != 1) {
                    $order->invoice_number = static::nextInvoiceNumber();
                }
            }
        });

        static::updating(function ($order) {
            // اختصاص شماره فاکتور در لحظه تایید پرداخت توسط ادمین یا درگاه
            if ($order->status === 'paid' && (float)$order->amount > 0 && empty($order->invoice_number)) {
                if ($order->payment_method !== 'trial' && $order->plan_id != 1) {
                    $order->invoice_number = static::nextInvoiceNumber();
                }
            }
        });
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
