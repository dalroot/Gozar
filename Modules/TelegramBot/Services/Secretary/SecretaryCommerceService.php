<?php

namespace Modules\TelegramBot\Services\Secretary;

use App\Models\DiscountCode;
use App\Models\DiscountCodeUsage;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymentAvailabilityService;
use App\Services\PaymentService;
use App\Services\ShiftScheduleService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SecretaryCommerceService
{
    public function __construct(private PaymentAvailabilityService $payments)
    {
    }

    public function setMode(int|string $chatId, string $mode, ?int $originalOrderId = null): void
    {
        Cache::put($this->stateKey($chatId), [
            'mode' => $mode === 'renewal' ? 'renewal' : 'purchase',
            'original_order_id' => $originalOrderId,
        ], now()->addHours(2));
    }

    public function chooseMode(int|string $chatId, ?string $fullName = null, ?string $username = null): array
    {
        $user = $this->ensureUser($chatId, $fullName, $username);
        $service = $this->renewableOrders($user)->first();
        if (!$service) {
            $this->setMode($chatId, 'purchase');
            return [
                'text' => "🛍 <b>خرید سرویس جدید</b>\n\nابتدا مدت سرویس موردنظرتان را انتخاب کنید.",
                'buttons' => app(SalesFunnelService::class)->getDurationKeyboard(),
            ];
        }

        return [
            'text' => "🛍 <b>خرید یا تمدید</b>\n\nمی‌خواهید سرویس قبلی‌تان تمدید شود یا یک سرویس جداگانه بسازیم؟",
            'buttons' => [
                [['text' => '🔄 تمدید سرویس فعلی', 'callback_data' => 'sec_start_renewal', 'style' => 'success']],
                [['text' => '➕ خرید سرویس جدید', 'callback_data' => 'sec_start_purchase', 'style' => 'primary']],
                [['text' => '❌ بستن', 'callback_data' => 'sec_dismiss', 'style' => 'danger']],
            ],
        ];
    }

    public function startRenewal(int|string $chatId, ?string $fullName = null, ?string $username = null): array
    {
        $user = $this->ensureUser($chatId, $fullName, $username);
        $orders = $this->renewableOrders($user);
        if ($orders->isEmpty()) {
            $this->setMode($chatId, 'purchase');
            return [
                'text' => 'سرویس قابل تمدیدی پیدا نکردم؛ می‌توانید یک سرویس جدید انتخاب کنید.',
                'buttons' => app(SalesFunnelService::class)->getDurationKeyboard(),
            ];
        }
        if ($orders->count() === 1) {
            $order = $orders->first();
            $this->setMode($chatId, 'renewal', $order->id);
            return [
                'text' => "🔄 <b>تمدید سرویس {$this->e($order->panel_username ?: '#'.$order->id)}</b>\n\nبسته‌ای را که می‌خواهید روی همین سرویس فعال شود انتخاب کنید. حجم باقی‌مانده منتقل می‌شود و زمان دورهٔ منقضی‌شده منتقل نخواهد شد.",
                'buttons' => app(SalesFunnelService::class)->getDurationKeyboard(),
            ];
        }

        $buttons = $orders->take(8)->map(fn (Order $order) => [[
            'text' => ($order->panel_username ?: 'سرویس #' . $order->id) . ' · ' . ($order->expires_at ? \Carbon\Carbon::parse($order->expires_at)->format('Y/m/d') : 'بدون تاریخ'),
            'callback_data' => 'sec_renew_service_' . $order->id,
        ]])->values()->all();
        $buttons[] = [['text' => '❌ بستن', 'callback_data' => 'sec_dismiss']];
        return ['text' => 'برای تمدید، سرویس موردنظر را انتخاب کنید:', 'buttons' => $buttons];
    }

    public function createTrial(int|string $chatId, ?string $businessConnectionId = null, ?string $fullName = null, ?string $username = null): array
    {
        $user = $this->ensureUser($chatId, $fullName, $username);
        $lock = Cache::lock('sec_trial_user_' . $user->id, 120);
        if (!$lock->get()) return $this->error('درخواست تست شما در حال پردازش است؛ چند لحظه صبر کنید.');
        try {
            $settings = Setting::query()->pluck('value', 'key');
            if (!filter_var($settings->get('trial_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
                return $this->error('ارائهٔ تست رایگان در حال حاضر غیرفعال است.');
            }
            $alreadyUsed = (int) $user->trial_accounts_taken >= 1
                || $user->orders()->where('payment_method', 'trial')->whereIn('status', ['paid', 'active', 'completed'])->exists();
            if ($alreadyUsed) {
                return [
                    'text' => 'سهمیهٔ تست رایگان این حساب قبلاً استفاده شده است. تست برای هر حساب تلگرام فقط یک‌بار ارائه می‌شود.',
                    'buttons' => [[['text' => '🛍 مشاهده بسته‌ها', 'callback_data' => 'sec_view_durations']]],
                ];
            }

            $volumeMb = max(1, (int) $settings->get('trial_volume_mb', 1024));
            $trialPlan = Plan::firstOrCreate(
                ['name' => 'سرویس هدیه / تست رایگان'],
                ['price' => 0, 'volume_gb' => (int) ceil($volumeMb / 1024), 'duration_days' => 0, 'features' => ['تست رایگان روزنه'], 'is_active' => false]
            );
            $base = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($username ?: $chatId));
            $order = Order::create([
                'user_id' => $user->id,
                'plan_id' => $trialPlan->id,
                'status' => 'pending',
                'payment_method' => 'trial',
                'amount' => 0,
                'source' => 'telegram_secretary',
                'panel_username' => 'trial_' . ($base ?: $user->id) . '_' . Str::lower(Str::random(4)),
            ]);
            $this->rememberDeliveryRoute($order, $chatId, $businessConnectionId);
            if (!PaymentService::approveOrder($order)) throw new \RuntimeException('ساخت سرویس تست کامل نشد.');
            $order->refresh();
            if (!Cache::has('telegram_delivery_sent_' . $order->id)) {
                app(\App\Services\TelegramServiceDeliveryService::class)->send($user, $order);
            }
            $user->increment('trial_accounts_taken');
            app(\App\Services\BotEventLogger::class)->record('trial_created', 'friday', [
                'chat_id' => $chatId, 'user_id' => $user->id, 'order_id' => $order->id,
                'plan_id' => $trialPlan->id, 'status' => 'paid',
            ]);
            return [
                'text' => '✅ اکانت تست ساخته شد و QR و لینک اتصال برای شما ارسال شد.',
                'buttons' => null,
                'delete_progress' => true,
            ];
        } catch (\Throwable $e) {
            Log::error('Friday trial creation failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return $this->error('ساخت اکانت تست با خطا روبه‌رو شد و سهمیه‌ای از شما کم نشد. لطفاً دوباره تلاش کنید.');
        } finally {
            optional($lock)->release();
        }
    }

    public function selectRenewalService(int|string $chatId, int $orderId): array
    {
        $user = $this->user($chatId);
        $order = $user?->orders()->whereKey($orderId)->whereIn('status', ['paid', 'active', 'completed'])->first();
        if (!$order) return $this->error('این سرویس برای تمدید در دسترس نیست.');
        $this->setMode($chatId, 'renewal', $order->id);
        return [
            'text' => "🔄 سرویس <b>{$this->e($order->panel_username ?: '#'.$order->id)}</b> انتخاب شد. حالا بستهٔ تمدید را انتخاب کنید.",
            'buttons' => app(SalesFunnelService::class)->getDurationKeyboard(),
        ];
    }

    public function selectPlan(int|string $chatId, int $planId, ?string $businessConnectionId = null, ?string $fullName = null, ?string $username = null): array
    {
        $user = $this->ensureUser($chatId, $fullName, $username);
        $plan = Plan::whereKey($planId)->where('is_active', true)->first();
        if (!$plan) return $this->error('این بسته دیگر فعال نیست؛ لطفاً دوباره از فهرست انتخاب کنید.');

        $state = Cache::get($this->stateKey($chatId), ['mode' => 'purchase']);
        $mode = ($state['mode'] ?? 'purchase') === 'renewal' ? 'renewal' : 'purchase';
        $original = null;
        if ($mode === 'renewal') {
            $original = $user->orders()->whereKey($state['original_order_id'] ?? 0)
                ->whereIn('status', ['paid', 'active', 'completed'])->first();
            if (!$original) return $this->startRenewal($chatId, $fullName, $username);
        }

        $order = Order::where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('status', 'pending')
            ->where('source', 'telegram_secretary')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->when($original, fn ($q) => $q->where('renews_order_id', $original->id), fn ($q) => $q->whereNull('renews_order_id'))
            ->latest()->first();

        if (!$order) {
            $order = Order::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'server_id' => $original?->server_id,
                'status' => 'pending',
                'source' => 'telegram_secretary',
                'amount' => $plan->price,
                'discount_amount' => 0,
                'discount_code_id' => null,
                'renews_order_id' => $original?->id,
                'panel_username' => $original?->panel_username ?: 'u' . $user->id . 'o' . Str::random(5),
            ]);
        }

        Order::where('user_id', $user->id)
            ->where('source', 'telegram_secretary')
            ->where('status', 'pending')
            ->where('id', '!=', $order->id)
            ->whereNull('card_payment_receipt')
            ->update(['status' => 'expired']);

        $this->rememberDeliveryRoute($order, $chatId, $businessConnectionId);
        Cache::put($this->stateKey($chatId), array_merge($state, ['order_id' => $order->id]), now()->addHours(2));
        app(\App\Services\BotEventLogger::class)->record('order_created', 'friday', [
            'chat_id' => $chatId, 'user_id' => $user->id, 'order_id' => $order->id,
            'plan_id' => $plan->id, 'status' => 'pending', 'source' => 'telegram_secretary',
            'is_renewal' => (bool) $original,
        ]);
        return $this->invoice($user, $order->fresh('plan'));
    }

    public function invoice(User $user, Order $order): array
    {
        if ($order->user_id !== $user->id || $order->status !== 'pending') return $this->error('این سفارش دیگر قابل پرداخت نیست.');
        $order->loadMissing('plan');
        $plan = $order->plan;
        $kind = $order->renews_order_id ? 'تمدید سرویس' : 'خرید سرویس جدید';
        if ($order->card_payment_receipt) {
            return $this->receiptPendingReply($order, $kind);
        }
        $lines = [
            '🧾 <b>خلاصه سفارش #' . $order->id . '</b>', '',
            'نوع: <b>' . $kind . '</b>',
            'بسته: <b>' . $this->e($plan?->name ?? 'اشتراک') . '</b>',
            'مدت: <b>' . (int) ($plan?->duration_days ?? 0) . ' روز</b>',
            'حجم: <b>' . $this->e((string) ($plan?->volume_gb ?? 0)) . ' گیگابایت</b>',
        ];
        if ((float) $order->discount_amount > 0) {
            $lines[] = 'قیمت اصلی: <s>' . number_format((float) $plan->price) . ' تومان</s>';
            $lines[] = 'تخفیف: <b>' . number_format((float) $order->discount_amount) . ' تومان</b>';
        }
        $lines[] = 'مبلغ نهایی: <code>' . number_format((float) $order->amount) . ' تومان</code>';
        $lines[] = 'موجودی کیف پول: <code>' . number_format((float) $user->fresh()->balance) . ' تومان</code>';
        $lines[] = '';
        $lines[] = 'مشخصات را بررسی کنید و روش پرداخت را انتخاب کنید.';

        $settings = Setting::query()->pluck('value', 'key');
        $buttons = [];
        if (!$order->discount_code_id) $buttons[] = [['text' => '🎫 ثبت کد تخفیف', 'callback_data' => 'sec_discount_' . $order->id]];
        else $buttons[] = [['text' => '❌ حذف کد تخفیف', 'callback_data' => 'sec_remove_discount_' . $order->id]];
        if ($this->payments->isEnabled('wallet', $settings)) {
            $walletLabel = (float) $user->balance >= (float) $order->amount
                ? '👛 پرداخت با کیف پول'
                : '👛 موجودی کیف پول کافی نیست';
            $buttons[] = [['text' => $walletLabel, 'callback_data' => 'sec_pay_wallet_' . $order->id, 'style' => (float) $user->balance >= (float) $order->amount ? 'success' : 'primary']];
        }
        if ($this->payments->isEnabled('card', $settings)) {
            $buttons[] = [['text' => '💳 پرداخت کارت‌به‌کارت', 'callback_data' => 'sec_pay_card_' . $order->id, 'style' => 'primary']];
        }
        $buttons[] = [
            ['text' => '⬅️ تغییر بسته', 'callback_data' => 'sec_view_durations'],
            ['text' => '❌ لغو سفارش', 'callback_data' => 'sec_cancel_order_' . $order->id, 'style' => 'danger'],
        ];
        return ['text' => implode("\n", $lines), 'buttons' => $buttons];
    }

    public function beginDiscount(int|string $chatId, int $orderId): array
    {
        $order = $this->ownedPendingOrder($chatId, $orderId);
        if (!$order) return $this->error('سفارش معتبر نیست یا قبلاً پردازش شده است.');
        Cache::put($this->inputKey($chatId), ['type' => 'discount', 'order_id' => $order->id], now()->addMinutes(15));
        return ['text' => "🎫 کد تخفیف را در یک پیام بفرستید.\nبرای بازگشت، دکمهٔ انصراف را بزنید.", 'buttons' => [[['text' => 'انصراف', 'callback_data' => 'sec_invoice_' . $order->id]]]];
    }

    public function applyDiscount(int|string $chatId, string $codeText): array
    {
        $input = Cache::get($this->inputKey($chatId));
        $order = $this->ownedPendingOrder($chatId, (int) ($input['order_id'] ?? 0));
        if (!$order) return $this->error('زمان ثبت کد گذشته یا سفارش معتبر نیست.');
        $user = $order->user;
        $code = DiscountCode::whereRaw('LOWER(code) = ?', [mb_strtolower(trim($codeText), 'UTF-8')])->first();
        $error = null;
        if (!$code || !$code->is_active) $error = 'کد تخفیف معتبر نیست.';
        elseif ($code->starts_at && $code->starts_at->isFuture()) $error = 'زمان استفاده از این کد هنوز شروع نشده است.';
        elseif ($code->expires_at && $code->expires_at->isPast()) $error = 'این کد تخفیف منقضی شده است.';
        elseif ($code->usage_limit && $code->used_count >= $code->usage_limit) $error = 'سهمیهٔ این کد تخفیف تمام شده است.';
        elseif ($code->usage_limit_per_user && DiscountCodeUsage::where('discount_code_id', $code->id)->where('user_id', $user->id)->count() >= $code->usage_limit_per_user) $error = 'سهمیهٔ استفاده شما از این کد تمام شده است.';
        elseif ($code->min_order_amount && $order->plan->price < $code->min_order_amount) $error = 'مبلغ سفارش کمتر از حداقل این کد است.';
        elseif ($order->renews_order_id && !$code->applies_to_renewal) $error = 'این کد برای تمدید قابل استفاده نیست.';
        elseif (!empty($code->plan_ids) && !in_array($order->plan_id, $code->plan_ids)) $error = 'این کد برای بستهٔ انتخابی فعال نیست.';
        if ($error) return ['text' => '❌ ' . $error . "\nمی‌توانید کد دیگری بفرستید یا انصراف دهید.", 'buttons' => [[['text' => 'انصراف', 'callback_data' => 'sec_invoice_' . $order->id]]]];

        $discount = $code->calculateDiscount((float) $order->plan->price);
        $order->discount_amount = $discount;
        $order->discount_code_id = $code->id;
        $order->amount = max(0, (float) $order->plan->price - $discount);
        $order->save();
        Cache::forget($this->inputKey($chatId));
        $reply = $this->invoice($user, $order->fresh('plan'));
        $reply['text'] = "✅ کد تخفیف اعمال شد.\n\n" . $reply['text'];
        return $reply;
    }

    public function removeDiscount(int|string $chatId, int $orderId): array
    {
        $order = $this->ownedPendingOrder($chatId, $orderId);
        if (!$order) return $this->error('سفارش معتبر نیست.');
        $order->discount_amount = 0;
        $order->discount_code_id = null;
        $order->amount = $order->plan->price;
        $order->save();
        return $this->invoice($order->user, $order->fresh('plan'));
    }

    public function beginCardPayment(int|string $chatId, int $orderId): array
    {
        $order = $this->ownedPendingOrder($chatId, $orderId);
        if (!$order) return $this->error('سفارش معتبر نیست یا قبلاً پردازش شده است.');
        if ($order->card_payment_receipt) {
            return $this->receiptPendingReply($order, $order->renews_order_id ? 'تمدید سرویس' : 'خرید سرویس جدید');
        }
        $settings = Setting::query()->pluck('value', 'key');
        if (!$this->payments->isEnabled('card', $settings)) return $this->error('پرداخت کارت‌به‌کارت در حال حاضر فعال نیست.');
        $order->payment_method = 'card';
        $order->save();
        Cache::put($this->inputKey($chatId), ['type' => 'receipt', 'order_id' => $order->id], now()->addHours(6));
        $cardNotice = ShiftScheduleService::getCardNotice();
        return [
            'text' => "💳 <b>پرداخت کارت‌به‌کارت</b>\n\n" .
                "مبلغ دقیق: <code>" . number_format((float) $order->amount) . " تومان</code>\n" .
                "به نام: <b>" . $this->e((string) $settings->get('payment_card_holder_name', '')) . "</b>\n" .
                "شماره کارت: <code>" . $this->e((string) $settings->get('payment_card_number', '')) . "</code>\n\n" .
                "پس از واریز، تصویر فیش یا شماره پیگیری را همین‌جا ارسال کنید. رسید مستقیماً به سفارش #{$order->id} متصل می‌شود.\n\n" .
                "⏱ <i>" . $this->e($cardNotice) . "</i>",
            'buttons' => [[['text' => '⬅️ تغییر روش پرداخت', 'callback_data' => 'sec_invoice_' . $order->id]], [['text' => '❌ لغو سفارش', 'callback_data' => 'sec_cancel_order_' . $order->id]]],
        ];
    }

    public function payWallet(int|string $chatId, int $orderId): array
    {
        $lock = Cache::lock('sec_wallet_payment_' . $orderId, 120);
        if (!$lock->get()) return $this->error('پرداخت این سفارش در حال پردازش است.');
        $debited = false;
        try {
            DB::transaction(function () use ($chatId, $orderId, &$debited) {
                $order = Order::lockForUpdate()->with('plan')->find($orderId);
                $user = User::lockForUpdate()->where('telegram_chat_id', (string) $chatId)->first();
                if (!$order || !$user || $order->user_id !== $user->id || $order->status !== 'pending') throw new \RuntimeException('سفارش معتبر نیست یا قبلاً پرداخت شده است.');
                if ($order->card_payment_receipt) throw new \RuntimeException('رسید این سفارش ثبت شده و در انتظار تأیید است؛ پرداخت دوباره انجام نشد.');
                if ((float) $user->balance < (float) $order->amount) throw new \RuntimeException('موجودی کیف پول کافی نیست.');
                $user->decrement('balance', $order->amount);
                $order->payment_method = 'wallet';
                $order->save();
                Transaction::firstOrCreate(
                    ['user_id' => $user->id, 'order_id' => $order->id, 'type' => 'purchase', 'status' => 'completed'],
                    ['amount' => -$order->amount, 'description' => ($order->renews_order_id ? 'تمدید' : 'خرید') . ' از طریق فرایدی و کیف پول']
                );
                $debited = true;
            });

            $order = Order::findOrFail($orderId);
            if (!PaymentService::approveOrder($order)) throw new \RuntimeException('فعال‌سازی سرویس کامل نشد.');
            $order->refresh();
            $displayOrder = $order->renews_order_id ? Order::find($order->renews_order_id) : $order;
            if ($displayOrder && !Cache::has('telegram_delivery_sent_' . $displayOrder->id)) {
                app(\App\Services\TelegramServiceDeliveryService::class)->send($order->user, $displayOrder, null, (bool) $order->renews_order_id);
            }
            Cache::forget($this->inputKey($chatId));
            Cache::forget($this->stateKey($chatId));
            if ($order->fresh()->plan_id === null) {
                return ['text' => '⚠️ ارتباط با پنل ساخت سرویس برقرار نشد؛ مبلغ پرداختی بدون کسری به کیف پول شما برگشت و می‌توانید دوباره تلاش کنید.', 'buttons' => [[['text' => '🛍 انتخاب بسته', 'callback_data' => 'sec_view_durations']]]];
            }
            return ['text' => '✅ پرداخت با کیف پول انجام شد. سرویس فعال و قالب QR و لینک اتصال برایتان ارسال شد.', 'buttons' => null];
        } catch (\Throwable $e) {
            $order = Order::find($orderId);
            if ($debited && $order && $order->plan_id !== null && $order->status === 'pending') {
                DB::transaction(function () use ($order) {
                    $user = User::lockForUpdate()->find($order->user_id);
                    $alreadyRefunded = Transaction::where('order_id', $order->id)->where('type', 'refund')->where('status', 'completed')->exists();
                    if (!$alreadyRefunded) {
                        $user->increment('balance', $order->amount);
                        Transaction::create(['user_id'=>$user->id,'order_id'=>$order->id,'amount'=>$order->amount,'type'=>'refund','status'=>'completed','description'=>'بازگشت خودکار خرید ناموفق فرایدی']);
                    }
                });
            }
            if (in_array($e->getMessage(), ['موجودی کیف پول کافی نیست.', 'سفارش معتبر نیست یا قبلاً پرداخت شده است.', 'رسید این سفارش ثبت شده و در انتظار تأیید است؛ پرداخت دوباره انجام نشد.'], true)) {
                Log::info('Secretary wallet payment blocked safely', ['order_id' => $orderId, 'reason' => $e->getMessage()]);
            } else {
                Log::error('Secretary wallet payment failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            }
            return $this->error($e->getMessage());
        } finally {
            optional($lock)->release();
        }
    }

    public function cancelOrder(int|string $chatId, int $orderId): array
    {
        $order = $this->ownedPendingOrder($chatId, $orderId);
        if (!$order) return $this->error('سفارش قبلاً پردازش یا لغو شده است.');
        if ($order->card_payment_receipt) return $this->error('رسید این سفارش در انتظار بررسی است و اکنون قابل لغو نیست.');
        $order->status = 'expired';
        $order->save();
        Cache::forget($this->inputKey($chatId));
        return ['text' => "سفارش #{$order->id} لغو شد و هیچ مبلغی از حساب شما کسر نشد.", 'buttons' => [[['text' => '🛍 انتخاب بسته', 'callback_data' => 'sec_view_durations']]]];
    }

    public function showInvoice(int|string $chatId, int $orderId): array
    {
        Cache::forget($this->inputKey($chatId));
        $order = $this->ownedPendingOrder($chatId, $orderId);
        return $order ? $this->invoice($order->user, $order) : $this->error('سفارش معتبر نیست.');
    }

    public function serviceLink(int|string $chatId, int $orderId): array
    {
        $user = $this->user($chatId);
        $order = $user?->orders()->whereKey($orderId)->whereIn('status', ['paid', 'active', 'completed'])->first();
        if (!$order || trim((string) $order->config_details) === '') return $this->error('لینک این سرویس در دسترس نیست.');
        return ['text' => "🔗 <b>لینک اشتراک سرویس</b>\n\n<code>{$this->e($order->config_details)}</code>\n\nبرای کپی روی لینک بزنید و آن را در اختیار دیگران قرار ندهید.", 'buttons' => [[['text' => '📚 راهنمای اتصال', 'callback_data' => 'sec_tutorial']]]];
    }

    public function directConfigs(int|string $chatId, int $orderId): array
    {
        $user = $this->user($chatId);
        $order = $user?->orders()->whereKey($orderId)->whereIn('status', ['paid', 'active', 'completed'])->first();
        if (!$order) return $this->error('سرویس موردنظر پیدا نشد.');
        $source = trim((string) $order->config_details);
        if ($source === '') return $this->error('لینک اتصال برای این سرویس ثبت نشده است.');
        $content = $source;
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::timeout(15)->get($source);
                if ($response->successful()) $content = trim($response->body());
            } catch (\Throwable) {
            }
        }
        $decoded = base64_decode(str_replace(["\r", "\n"], '', $content), true);
        if ($decoded !== false && preg_match('/(?:vless|vmess|trojan|ss|hysteria2?):\/\//i', $decoded)) $content = $decoded;
        preg_match_all('~(?:vless|vmess|trojan|ss|hysteria2?)://[^\s<>]+~i', $content, $matches);
        $links = array_values(array_unique($matches[0] ?? []));
        if (!$links && preg_match('~^(?:vless|vmess|trojan|ss|hysteria2?)://~i', $source)) $links = [$source];
        if (!$links) return ['text' => 'لینک‌های مستقیم از اشتراک قابل استخراج نبودند؛ از لینک اشتراک اصلی استفاده کنید.', 'buttons' => [[['text' => '📋 دریافت لینک اشتراک', 'callback_data' => 'sec_copy_link_' . $order->id]]]];
        $blocks = collect($links)->take(8)->map(fn ($link, $i) => ($i + 1) . ". <code>{$this->e($link)}</code>")->implode("\n\n");
        return ['text' => "🔗 <b>لینک‌های اتصال مستقیم</b>\n\n{$blocks}\n\nبرای کپی روی هر لینک بزنید.", 'buttons' => [[['text' => '📋 لینک اشتراک اصلی', 'callback_data' => 'sec_copy_link_' . $order->id]]]];
    }

    public function hasPendingInput(int|string $chatId): bool
    {
        return is_array(Cache::get($this->inputKey($chatId)));
    }

    public function processPendingInput(int|string $chatId, string $text, array $message = []): ?array
    {
        $input = Cache::get($this->inputKey($chatId));
        if (!is_array($input)) return null;
        if (($input['type'] ?? '') === 'discount') return $this->applyDiscount($chatId, $text);
        if (($input['type'] ?? '') !== 'receipt') return null;
        return $this->submitReceipt($chatId, (int) ($input['order_id'] ?? 0), $text, $message);
    }

    public function submitReceipt(int|string $chatId, int $orderId, string $text, array $message): array
    {
        $order = $this->ownedPendingOrder($chatId, $orderId);
        if (!$order) return $this->error('سفارش در انتظار پرداخت پیدا نشد.');
        if ($order->card_payment_receipt) {
            Cache::forget($this->inputKey($chatId));
            return $this->receiptPendingReply($order, $order->renews_order_id ? 'تمدید سرویس' : 'خرید سرویس جدید');
        }
        $photo = collect($message['photo'] ?? [])->last();
        $document = $message['document'] ?? null;
        $fileId = $photo['file_id'] ?? $document['file_id'] ?? null;
        $storedPath = null;
        if ($fileId) $storedPath = $this->downloadSecretaryFile((string) $fileId, $order->id);
        if (!$storedPath && trim($text) === '') return $this->error('تصویر رسید قابل دریافت نبود؛ لطفاً دوباره ارسال کنید یا شماره پیگیری را بنویسید.');

        $order->card_payment_receipt = $storedPath ?: 'text_receipt:' . trim($text);
        $order->save();
        Cache::forget($this->inputKey($chatId));
        $sent = $this->notifyReceiptAdmin($order, $storedPath, trim($text));
        if (!$sent) {
            Cache::put($this->inputKey($chatId), ['type' => 'receipt', 'order_id' => $order->id], now()->addHours(6));
            return $this->error('رسید ذخیره شد اما ارسال آن برای واحد مالی با اختلال روبه‌رو شد؛ لطفاً چند دقیقه دیگر دوباره تلاش کنید.');
        }
        return $this->receiptPendingReply($order, $order->renews_order_id ? 'تمدید سرویس' : 'خرید سرویس جدید', true);
    }

    private function receiptPendingReply(Order $order, string $kind, bool $justSubmitted = false): array
    {
        $title = $justSubmitted
            ? "✅ <b>رسید سفارش #{$order->id} با موفقیت ثبت شد</b>"
            : "🧾 <b>وضعیت سفارش #{$order->id}</b>";
        $shiftNotice = ShiftScheduleService::getReceiptNotice();
        $text = $title . "\n\n" .
            "نوع سفارش: <b>{$kind}</b>\n" .
            "مبلغ: <code>" . number_format((float) $order->amount) . " تومان</code>\n" .
            "وضعیت: <b>در انتظار بررسی واحد مالی</b>\n\n" .
            "رسید به همین سفارش متصل شده و نیازی نیست آن را دوباره ارسال کنید. نتیجهٔ تأیید یا رد پرداخت در همین گفتگو به شما اعلام می‌شود. پس از تأیید، ساخت سرویس انجام می‌شود و QR و لینک اتصال به‌صورت خودکار همین‌جا برایتان ارسال خواهد شد.\n\n" .
            $shiftNotice . "\n\n" .
            "اگر دربارهٔ واریز یا سفارش نیاز به پیگیری دارید، می‌توانید از دکمه‌های زیر استفاده کنید.";
        return [
            'text' => $text,
            'buttons' => [
                [['text' => '🔄 مشاهده وضعیت سفارش', 'callback_data' => 'sec_invoice_' . $order->id]],
                [['text' => '👨🏻‍💻 ارتباط با پشتیبانی', 'url' => 'https://t.me/RoozanehHelp']],
            ],
        ];
    }

    public function rememberDeliveryRoute(Order $order, int|string $chatId, ?string $businessConnectionId): void
    {
        Cache::put('telegram_delivery_route_' . $order->id, [
            'surface' => 'friday',
            'chat_id' => (string) $chatId,
            'business_connection_id' => $businessConnectionId,
        ], now()->addDays(7));
    }

    private function notifyReceiptAdmin(Order $order, ?string $storedPath, string $receiptText): bool
    {
        $settings = Setting::query()->pluck('value', 'key');
        $token = trim((string) $settings->get('telegram_bot_token', ''));
        $target = $settings->get('telegram_receipt_channel_id') ?: $settings->get('telegram_admin_chat_id');
        if ($token === '' || !$target) return false;
        $user = $order->user;
        $type = $order->renews_order_id ? 'تمدید سرویس' : 'خرید سرویس';
        $body = "🧾 <b>رسید جدید سفارش #{$order->id}</b>\n\n" .
            "👤 <a href=\"tg://user?id={$user->telegram_chat_id}\">{$this->e($user->name)}</a> · <code>{$user->telegram_chat_id}</code>\n" .
            "📌 نوع: <b>{$type}</b>\n💵 مبلغ: <code>" . number_format((float) $order->amount) . " تومان</code>";
        if ($receiptText !== '') $body .= "\n🔖 پیگیری: <code>{$this->e($receiptText)}</code>";
        $replyMarkup = json_encode(['inline_keyboard' => [[
            ['text' => '✅ تأیید پرداخت', 'callback_data' => 'admin_approve_order_' . $order->id],
            ['text' => '❌ رد پرداخت', 'callback_data' => 'admin_reject_order_' . $order->id],
        ]]], JSON_UNESCAPED_UNICODE);
        try {
            $url = "https://api.telegram.org/bot{$token}/" . ($storedPath ? 'sendPhoto' : 'sendMessage');
            if ($storedPath) {
                $absolute = Storage::disk('public')->path($storedPath);
                return Http::timeout(20)->attach('photo', fopen($absolute, 'r'), basename($absolute))->post($url, [
                    'chat_id' => $target, 'caption' => $body, 'parse_mode' => 'HTML', 'reply_markup' => $replyMarkup,
                ])->successful();
            }
            return Http::timeout(15)->post($url, [
                'chat_id' => $target, 'text' => $body, 'parse_mode' => 'HTML', 'reply_markup' => $replyMarkup,
            ])->successful();
        } catch (\Throwable $e) {
            Log::error('Secretary receipt admin notification failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function downloadSecretaryFile(string $fileId, int $orderId): ?string
    {
        $token = trim((string) env('SECRETARY_BOT_TOKEN', ''));
        if ($token === '') return null;
        try {
            $file = Http::timeout(15)->get("https://api.telegram.org/bot{$token}/getFile", ['file_id' => $fileId]);
            $path = $file->json('result.file_path');
            if (!$file->successful() || !$path) return null;
            $bytes = Http::timeout(25)->get("https://api.telegram.org/file/bot{$token}/{$path}");
            if (!$bytes->successful()) return null;
            $extension = pathinfo((string) $path, PATHINFO_EXTENSION) ?: 'jpg';
            $stored = 'receipts/secretary_' . $orderId . '_' . now()->format('YmdHis') . '.' . preg_replace('/[^a-z0-9]/i', '', $extension);
            Storage::disk('public')->put($stored, $bytes->body());
            return $stored;
        } catch (\Throwable $e) {
            Log::warning('Secretary receipt download failed', ['order_id' => $orderId]);
            return null;
        }
    }

    private function ensureUser(int|string $chatId, ?string $fullName, ?string $username): User
    {
        $existing = $this->user($chatId);
        if ($existing) return $existing;
        return User::create([
            'name' => trim((string) $fullName) ?: ($username ?: 'کاربر روزنه'),
            'email' => (string) $chatId . '@telegram.user',
            'password' => Hash::make(Str::random(32)),
            'telegram_chat_id' => (string) $chatId,
            'referral_code' => Str::random(8),
            'bot_state' => null,
        ]);
    }

    private function user(int|string $chatId): ?User
    {
        return User::where('telegram_chat_id', (string) $chatId)->first();
    }

    private function renewableOrders(User $user)
    {
        return $user->orders()->with('plan')->whereIn('status', ['paid', 'active', 'completed'])
            ->whereNotNull('plan_id')->latest()->get()->unique('panel_username')->values();
    }

    private function ownedPendingOrder(int|string $chatId, int $orderId): ?Order
    {
        $user = $this->user($chatId);
        return $user?->orders()->with('plan')->whereKey($orderId)->where('status', 'pending')->first();
    }

    private function stateKey(int|string $chatId): string { return 'sec_commerce_' . $chatId; }
    private function inputKey(int|string $chatId): string { return 'sec_commerce_input_' . $chatId; }
    private function e(mixed $value): string { return htmlspecialchars(trim((string) $value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    private function error(string $message): array { return ['text' => '⚠️ ' . $this->e($message), 'buttons' => [[['text' => '🛍 بازگشت به فروشگاه', 'callback_data' => 'sec_view_durations']]]]; }
}
