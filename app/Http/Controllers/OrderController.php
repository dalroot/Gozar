<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Models\DiscountCode;
use App\Models\DiscountCodeUsage;
use App\Models\Inbound;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\MarzbanService;
use App\Services\XUIService;
use App\Services\PasargadService;
use App\Services\RemnawaveService;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Create a new pending order for a specific plan.
     */


    public function store(Plan $plan)
    {

        if (class_exists('Modules\MultiServer\Models\Location')) {

            return redirect()->route('order.select-server', $plan->id);
        }

        // 2. حالت عادی (تک سرور)
        $order = Auth::user()->orders()->create([
            'plan_id' => $plan->id,
            'status' => 'pending',
            'source' => 'web',
            'discount_amount' => 0,
            'discount_code_id' => null,
            'amount' => $plan->price,
        ]);

        Auth::user()->notifications()->create([
            'type' => 'new_order_created',
            'title' => 'سفارش جدید ثبت شد',
            'message' => "سفارش شما برای پلن {$plan->name} ایجاد شد.",
            'link' => route('order.show', $order->id),
        ]);

        return redirect()->route('order.show', $order->id);
    }

    /**
     * Show the payment method selection page for an order.
     */
    public function show(Order $order)
    {
        if (Auth::id() !== $order->user_id) {
            abort(403, 'شما به این صفحه دسترسی ندارید.');
        }

        if ($order->status === 'paid') {
            return redirect()->route('dashboard')->with('status', 'این سفارش قبلاً پرداخت شده است.');
        }

        $settings = Setting::all()->pluck('value', 'key');

        return view('payment.show', ['order' => $order, 'settings' => $settings]);
    }

    /**
     * Show the bank card details and receipt upload form.
     */
    public function processCardPayment(Order $order)
    {
        if (Auth::id() !== $order->user_id) {
            abort(403);
        }


        $order->update(['payment_method' => 'card']);


        $originalAmount = $order->plan ? $order->plan->price : $order->amount;
        $discountAmount = session('discount_amount', 0);
        $finalAmount = $originalAmount - $discountAmount;

        $order->update([
            'discount_amount' => $discountAmount,
            'amount' => $finalAmount
        ]);


        return redirect()->route('payment.card.show', $order->id);
    }

    /**
     * Show the form to enter the wallet charge amount.
     */
    public function showChargeForm()
    {
        return view('wallet.charge');
    }

    /**
     * Create a new pending order for charging the wallet.
     */
    public function createChargeOrder(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:10000']);
        $order = Auth::user()->orders()->create([
            'plan_id' => null,
            'amount' => $request->amount,
            'status' => 'pending',
            'source' => 'web',
        ]);

        Auth::user()->notifications()->create([
            'type' => 'wallet_charge_pending',
            'title' => 'درخواست شارژ کیف پول ثبت شد!',
            'message' => "سفارش شارژ کیف پول به مبلغ " . number_format($request->amount) . " تومان در انتظار پرداخت شماست.",
            'link' => route('order.show', $order->id),
        ]);

        return redirect()->route('order.show', $order->id);
    }

    /**
     * Create a new pending order to renew an existing service.
     */
    public function renew(Order $order)
    {
        if (Auth::id() !== $order->user_id || $order->status !== 'paid') {
            abort(403);
        }

        $newOrder = $order->replicate();
        $newOrder->created_at = now();
        $newOrder->status = 'pending';
        $newOrder->source = 'web';
        $newOrder->config_details = null;
        $newOrder->expires_at = null;
        $newOrder->renews_order_id = $order->id;
        $newOrder->discount_amount = 0;
        $newOrder->discount_code_id = null;
        $newOrder->amount = $order->plan->price; // مبلغ اصلی بدون تخفیف
        $newOrder->save();

        Auth::user()->notifications()->create([
            'type' => 'renewal_order_created',
            'title' => 'درخواست تمدید سرویس ثبت شد!',
            'message' => "سفارش تمدید سرویس {$order->plan->name} با موفقیت ثبت شد و در انتظار پرداخت است.",
            'link' => route('order.show', $newOrder->id),
        ]);

        return redirect()->route('order.show', $newOrder->id)->with('status', 'سفارش تمدید شما ایجاد شد. لطفاً هزینه را پرداخت کنید.');
    }

    /**
     * Apply discount code to an order.
     */
    public function applyDiscountCode(Request $request, Order $order)
    {
        if (Auth::id() !== $order->user_id || $order->status !== 'pending') {
            Log::warning('Discount Code - Access Denied', [
                'user_id' => Auth::id(),
                'order_user_id' => $order->user_id,
                'order_status' => $order->status
            ]);
            return response()->json(['error' => 'دسترسی غیرمجاز یا سفارش نامعتبر'], 403);
        }

        Log::info('Discount Code Search', [
            'code' => $request->code,
            'current_time' => now()->toDateTimeString(),
            'order_id' => $order->id
        ]);

        $code = DiscountCode::where('code', $request->code)->first();

        if (!$code) {
            Log::error('Discount Code Not Found', ['code' => $request->code]);
            return response()->json(['error' => 'کد تخفیف پیدا نشد. دقت کنید کد را صحیح وارد کنید.'], 400);
        }

        Log::info('Discount Code Found', [
            'code' => $code->toArray(),
            'server_time' => now()->toDateTimeString(),
            'is_active' => $code->is_active,
            'starts_at' => $code->starts_at?->toDateTimeString(),
            'expires_at' => $code->expires_at?->toDateTimeString(),
        ]);

        if (!$code->is_active) {
            return response()->json(['error' => 'کد تخفیف غیرفعال است'], 400);
        }

        if ($code->starts_at && $code->starts_at > now()) {
            return response()->json(['error' => 'کد تخفیف هنوز شروع نشده. زمان شروع: ' . $code->starts_at], 400);
        }

        if ($code->expires_at && $code->expires_at < now()) {
            return response()->json(['error' => 'کد تخفیف منقضی شده. زمان انقضا: ' . $code->expires_at], 400);
        }

        $totalAmount = $order->plan_id ? $order->plan->price : $order->amount;

        Log::info('Order Info for Discount', [
            'order_id' => $order->id,
            'plan_id' => $order->plan_id,
            'amount' => $totalAmount,
            'is_wallet' => !$order->plan_id,
            'is_renewal' => (bool)$order->renews_order_id
        ]);

        $isWalletCharge = !$order->plan_id;
        $isRenewal = (bool)$order->renews_order_id;

        if (!$code->isValidForOrder(
            amount: $totalAmount,
            planId: $order->plan_id,
            isWallet: $isWalletCharge,
            isRenewal: $isRenewal
        )) {
            return response()->json(['error' => 'این کد تخفیف برای این سفارش قابل استفاده نیست. شرایط استفاده را بررسی کنید.'], 400);
        }

        $discountAmount = $code->calculateDiscount($totalAmount);
        $finalAmount = $totalAmount - $discountAmount;

        Log::info('Discount Calculated', [
            'original_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount
        ]);

        // ذخیره هم در دیتابیس و هم در سشن
        $order->update([
            'discount_amount' => $discountAmount,
            'discount_code_id' => $code->id
        ]);

        session([
            'discount_code' => $code->code,
            'discount_amount' => $discountAmount,
            'discount_applied_order_id' => $order->id
        ]);

        return response()->json([
            'success' => true,
            'discount' => number_format($discountAmount),
            'original_amount' => number_format($totalAmount),
            'final_amount' => number_format($finalAmount),
            'message' => "کد تخفیف اعمال شد! تخفیف: " . number_format($discountAmount) . " تومان"
        ]);
    }

    /**
     * Handle the submission of the payment receipt file.
     */


    // نمایش صفحه انتخاب سرور (مخصوص ماژول MultiServer)
    public function selectServer(Plan $plan)
    {
        if (!class_exists('Modules\MultiServer\Models\Location')) {
            abort(404);
        }

        // دریافت لوکیشن‌ها و سرورهایی که فعال هستند و ظرفیت دارند
        $locations = \Modules\MultiServer\Models\Location::where('is_active', true)
            ->with(['servers' => function ($query) {
                $query->where('is_active', true)
                    ->whereRaw('current_users < capacity'); // فقط سرورهای دارای ظرفیت
            }])
            ->whereHas('servers', function ($query) {
                $query->where('is_active', true)
                    ->whereRaw('current_users < capacity');
            })
            ->get();

        return view('payment.select-server', compact('plan', 'locations'));
    }

    // ثبت سفارش با سرور انتخاب شده
    public function storeWithServer(Request $request, Plan $plan)
    {
        $request->validate([
            'server_id' => 'required|exists:ms_servers,id'
        ]);

        // چک کردن ظرفیت سرور
        $server = \Modules\MultiServer\Models\Server::find($request->server_id);
        if ($server->current_users >= $server->capacity) {
            return redirect()->back()->with('error', 'متأسفانه ظرفیت این سرور تکمیل شده است.');
        }

        // ساخت سفارش
        $order = Auth::user()->orders()->create([
            'plan_id' => $plan->id,
            'server_id' => $request->server_id,
            'status' => 'pending',
            'source' => 'web',
            'discount_amount' => 0,
            'discount_code_id' => null,
            'amount' => $plan->price,
        ]);

        Auth::user()->notifications()->create([
            'type' => 'new_order_created',
            'title' => 'سفارش جدید ثبت شد',
            'message' => "سفارش شما برای پلن {$plan->name} در سرور {$server->name} ایجاد شد.",
            'link' => route('order.show', $order->id),
        ]);

        return redirect()->route('order.show', $order->id);
    }


    public function showCardPaymentPage(Order $order)
    {
        if (Auth::id() !== $order->user_id) {
            abort(403);
        }


        if ($order->status === 'paid') {
            return redirect()->route('dashboard')->with('status', 'این سفارش قبلاً پرداخت شده است.');
        }


        $settings = Setting::all()->pluck('value', 'key');


        $finalAmount = $order->amount;

        return view('payment.card-receipt', [
            'order' => $order,
            'settings' => $settings,
            'finalAmount' => $finalAmount,
        ]);
    }

    public function submitCardReceipt(Request $request, Order $order)
    {
        $request->validate(['receipt' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048']);

        // اگر مبلغ نهایی قبلاً ذخیره نشده، از سشن بخون
        if ($order->amount == ($order->plan->price ?? 0)) {
            $discountAmount = session('discount_amount', 0);
            $finalAmount = ($order->plan->price ?? $order->amount) - $discountAmount;

            $order->update([
                'discount_amount' => $discountAmount,
                'amount' => $finalAmount
            ]);
        }

        $path = $request->file('receipt')->store('receipts', 'public');

        // ذخیره فقط رسید (مبلغ قبلاً تنظیم شده)
        $order->update(['card_payment_receipt' => $path]);

        // بقیه کد تخفیف رو فقط اگر ثبت نشده
        if (session('discount_code') && session('discount_applied_order_id') == $order->id) {
            $discountCode = DiscountCode::where('code', session('discount_code'))->first();

            if ($discountCode && !DiscountCodeUsage::where('order_id', $order->id)->exists()) {
                DiscountCodeUsage::create([
                    'discount_code_id' => $discountCode->id,
                    'user_id' => Auth::id(),
                    'order_id' => $order->id,
                    'discount_amount' => session('discount_amount', 0),
                    'original_amount' => $order->plan->price ?? $order->amount,
                ]);

                $discountCode->increment('used_count');
            }
        }

        Auth::user()->notifications()->create([
            'type' => 'card_receipt_submitted',
            'title' => 'رسید پرداخت شما ارسال شد!',
            'message' => "رسید پرداخت سفارش #{$order->id} با موفقیت دریافت شد و در انتظار تایید مدیر است.",
            'link' => route('order.show', $order->id),
        ]);

        session()->forget(['discount_code', 'discount_amount', 'discount_applied_order_id']);

        return redirect()->route('dashboard')->with('status', 'رسید شما با موفقیت ارسال شد. پس از تایید توسط مدیر، سرویس شما فعال خواهد شد.');
    }

    /**
     * Process instant payment from the user's wallet balance.
     */
    public function processWalletPayment(Order $order)
    {
        if (auth()->id() !== $order->user_id) {
            abort(403);
        }

        if (!$order->plan) {
            return redirect()->back()->with('error', 'این عملیات برای شارژ کیف پول مجاز نیست.');
        }

        $user = auth()->user();
        $plan = $order->plan;
        $originalPrice = $plan->price;

        $discountAmount = $order->discount_amount ?? session('discount_amount', 0);
        $finalPrice = $originalPrice - $discountAmount;

        if ($user->balance < $finalPrice) {
            return redirect()->back()->with('error', 'موجودی کیف پول شما برای انجام این عملیات کافی نیست.');
        }

        try {
            DB::transaction(function () use ($order, $user, $plan, $originalPrice, $finalPrice, $discountAmount) {

                $user->decrement('balance', $finalPrice);


                $user->notifications()->create([
                    'type' => 'wallet_deducted',
                    'title' => 'کسر از کیف پول شما',
                    'message' => "مبلغ " . number_format($finalPrice) . " تومان برای سفارش #{$order->id} از کیف پول شما کسر شد.",
                    'link' => route('dashboard', ['tab' => 'order_history']),
                ]);

                // ثبت استفاده از کد تخفیف
                if (session('discount_code') && session('discount_applied_order_id') == $order->id) {
                    $discountCode = DiscountCode::where('code', session('discount_code'))->first();

                    if ($discountCode && !DiscountCodeUsage::where('order_id', $order->id)->exists()) {
                        DiscountCodeUsage::create([
                            'discount_code_id' => $discountCode->id,
                            'user_id' => $user->id,
                            'order_id' => $order->id,
                            'discount_amount' => $discountAmount,
                            'original_amount' => $originalPrice,
                        ]);

                        $discountCode->increment('used_count');
                    }
                }

                // تنظیمات
                $settings = Setting::all()->pluck('value', 'key');
                $success = false;
                $finalConfig = '';
                $panelType = $settings->get('panel_type');
                $isRenewal = (bool) $order->renews_order_id;

                $originalOrder = $isRenewal ? Order::find($order->renews_order_id) : null;
                if ($isRenewal && !$originalOrder) {
                    throw new \Exception('سفارش اصلی جهت تمدید یافت نشد.');
                }

                // برای تمدید، از ID سفارش اصلی استفاده کن
                $uniqueUsername = $order->panel_username ?? "user-{$user->id}-order-" . ($isRenewal ? $originalOrder->id : $order->id);
                $newExpiresAt = $isRenewal
                    ? (new \DateTime($originalOrder->expires_at))->modify("+{$plan->duration_days} days")
                    : now()->addDays($plan->duration_days);

                $timestamp = $newExpiresAt->getTimestamp();

                // ==========================================
                // پنل MARZBAN
                // ==========================================
                if ($panelType === 'marzban') {
                    $marzbanService = new MarzbanService(
                        $settings->get('marzban_host'),
                        $settings->get('marzban_sudo_username'),
                        $settings->get('marzban_sudo_password'),
                        $settings->get('marzban_node_hostname')
                    );

                    $userData = [
                        'expire' => $timestamp,
                        'data_limit' => $plan->volume_gb * 1073741824
                    ];

                    $response = $isRenewal
                        ? $marzbanService->updateUser($uniqueUsername, $userData)
                        : $marzbanService->createUser(array_merge($userData, ['username' => $uniqueUsername]));

                    if ($response && (isset($response['subscription_url']) || isset($response['username']))) {
                        $finalConfig = $marzbanService->generateSubscriptionLink($response);
                        $success = true;
                    }
                }

                // ==========================================
                // پنل Remnawave
                // ==========================================
                elseif ($panelType === 'remnawave') {
                    $remnawaveService = new RemnawaveService(
                        $settings->get('remnawave_host'),
                        $settings->get('remnawave_api_token'),
                        $settings->get('remnawave_node_hostname')
                    );

                    $userData = [
                        'expire' => $timestamp,
                        'data_limit' => $plan->volume_gb * 1073741824
                    ];

                    $response = $isRenewal
                        ? $remnawaveService->updateUser($uniqueUsername, $userData)
                        : $remnawaveService->createUser(array_merge($userData, ['username' => $uniqueUsername]));

                    if ($response && (isset($response['subscriptionUrl']) || isset($response['username']))) {
                        $finalConfig = $remnawaveService->generateSubscriptionLink($response);
                        $success = true;
                    }
                }

                // ==========================================
                // پنل Pasargad (PasarGuard)
                // ==========================================
                elseif ($panelType === 'pasargad') {
                    $pasargadService = new PasargadService(
                        $settings->get('pasargad_host'),
                        $settings->get('pasargad_sudo_username'),
                        $settings->get('pasargad_sudo_password'),
                        $settings->get('pasargad_node_hostname')
                    );

                    if ($isRenewal) {
                        $response = $pasargadService->updateUser($uniqueUsername, [
                            'expire' => $timestamp,
                            'data_limit' => $plan->volume_gb * 1073741824,
                            'status' => 'active',
                        ]);
                        $resetRes = $pasargadService->resetUserTraffic($uniqueUsername);
                        if ($response !== null && $resetRes !== false) {
                            $finalConfig = $originalOrder->config_details;
                            $success = true;
                        }
                    } else {
                        $response = $pasargadService->createUser([
                            'username' => $uniqueUsername,
                            'expire' => $timestamp,
                            'data_limit' => $plan->volume_gb * 1073741824,
                            'group_ids' => [(int)($plan->pasargad_group_id ?? $settings->get('pasargad_paid_group_id') ?? 1)],
                        ]);
                        if ($response && (isset($response['subscription_url']) || isset($response['username']))) {
                            $finalConfig = $response['subscription_url'] ?? $pasargadService->generateSubscriptionLink($uniqueUsername);
                            $success = true;
                        }
                    }
                }

                // ==========================================
                // پنل X-UI (SANAEI)
                // ==========================================
                elseif ($panelType === 'xui') {
                    $xuiService = new XUIService(
                        $settings->get('xui_host'),
                        $settings->get('xui_user'),
                        $settings->get('xui_pass')
                    );

                    if (!$xuiService->login()) {
                        throw new \Exception('خطا در لاگین به پنل X-UI.');
                    }

                    // تعیین اینباندهای هدف به صورت کاملاً پویا و بدون هاردکد
                    $settingInbounds = $settings->get('xui_target_inbounds');
                    $parsed = !empty($settingInbounds) ? (is_array($settingInbounds) ? $settingInbounds : json_decode($settingInbounds, true)) : null;
                    if (!is_array($parsed) && !empty($settingInbounds)) {
                        $parsed = array_filter(array_map('trim', explode(',', (string)$settingInbounds)));
                    }
                    if (!empty($parsed)) {
                        $targetInboundIds = array_values(array_unique(array_map('intval', $parsed)));
                    } else {
                        $targetInboundIds = $xuiService->getActiveInboundIds();
                    }

                    $primaryInboundId = $targetInboundIds[0] ?? (int) $settings->get('xui_default_inbound_id', 3);
                    $inbound = Inbound::whereJsonContains('inbound_data->id', $primaryInboundId)->first() ?: Inbound::first();
                    $inboundData = $inbound ? (is_string($inbound->inbound_data) ? json_decode($inbound->inbound_data, true) : $inbound->inbound_data) : [];

                    $clientData = [
                        'email' => $uniqueUsername,
                        'total' => $plan->volume_gb * 1073741824,
                        'expiryTime' => $timestamp * 1000
                    ];

                    $linkType = $settings->get('xui_link_type', 'single');
                    if ($linkType === 'subscription') {
                        $clientData['subId'] = Str::random(16);
                    }

                    $response = $xuiService->addClient($targetInboundIds, $clientData);

                        if ($response && isset($response['success']) && $response['success']) {
                            $linkType = $settings->get('xui_link_type', 'single');

                            if ($linkType === 'subscription') {
                                $subId = $response['generated_subId'];
                                $subBaseUrl = trim($settings->get('xui_subscription_url_base') ?? '');

                                if ($subBaseUrl && $subId) {
                                    $parsedSub = parse_url($subBaseUrl);
                                    $subPath = trim($parsedSub['path'] ?? '', '/');
                                    $finalConfig = !empty($subPath) 
                                        ? (rtrim($subBaseUrl, '/') . '/' . $subId) 
                                        : (rtrim($subBaseUrl, '/') . '/sub/' . $subId);
                                    $success = true;
                                } else {
                                    throw new \Exception('خطا در ساخت لینک سابسکریپشن.');
                                }
                            } else {
                                $uuid = $response['generated_uuid'];
                                $streamSettings = $inboundData['streamSettings'] ?? [];

                                if (is_string($streamSettings)) {
                                    $streamSettings = json_decode($streamSettings, true) ?? [];
                                }

                                $parsedUrl = parse_url($settings->get('xui_host'));
                                $serverIpOrDomain = !empty($inboundData['listen']) ? $inboundData['listen'] : $parsedUrl['host'];
                                $port = $inboundData['port'];
                                $remark = $inboundData['remark'];

                                $paramsArray = [
                                    'type' => $streamSettings['network'] ?? null,
                                    'security' => $streamSettings['security'] ?? null,
                                    'path' => $streamSettings['wsSettings']['path'] ?? ($streamSettings['grpcSettings']['serviceName'] ?? null),
                                    'sni' => $streamSettings['tlsSettings']['serverName'] ?? null,
                                    'host' => $streamSettings['wsSettings']['headers']['Host'] ?? null
                                ];

                                $params = http_build_query(array_filter($paramsArray));
                                $fullRemark = $uniqueUsername . '|' . $remark;

                                $finalConfig = "vless://{$uuid}@{$serverIpOrDomain}:{$port}?{$params}#" . urlencode($fullRemark);
                                $success = true;
                            }
                        } else {
                            $errorMsg = $response['msg'] ?? 'خطای نامشخص';
                            throw new \Exception('خطا در ساخت کاربر در پنل X-UI: ' . $errorMsg);
                        }

                    if (!$success) {
                        throw new \Exception('خطا در ارتباط با سرور برای فعال‌سازی سرویس.');
                    }
                } else {
                    throw new \Exception('نوع پنل در تنظیمات مشخص نشده است.');
                }

                // ==========================================
                // ذخیره سفارشات
                // ==========================================
                if ($isRenewal) {
                    $originalOrder->update([
                        'config_details' => $finalConfig,
                        'expires_at' => $newExpiresAt->format('Y-m-d H:i:s')
                    ]);

                    $user->update(['show_renewal_notification' => true]);

                    $user->notifications()->create([
                        'type' => 'service_renewed',
                        'title' => 'سرویس شما تمدید شد!',
                        'message' => "سرویس {$originalOrder->plan->name} با موفقیت تمدید شد.",
                        'link' => route('dashboard', ['tab' => 'my_services']),
                    ]);
                } else {
                    $order->update([
                        'config_details' => $finalConfig,
                        'expires_at' => $newExpiresAt
                    ]);

                    $user->notifications()->create([
                        'type' => 'service_purchased',
                        'title' => 'سرویس شما فعال شد!',
                        'message' => "سرویس {$plan->name} با موفقیت خریداری و فعال شد.",
                        'link' => route('dashboard', ['tab' => 'my_services']),
                    ]);
                }

                // آپدیت وضعیت سفارش
                $order->update([
                    'status' => 'paid',
                    'payment_method' => 'wallet'
                ]);

                // تراکنش
                Transaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'amount' => $finalPrice,
                    'type' => 'purchase',
                    'status' => 'completed',
                    'description' => ($isRenewal ? "تمدید سرویس" : "خرید سرویس") . " {$plan->name} از کیف پول" . ($discountAmount > 0 ? " (تخفیف: " . number_format($discountAmount) . " تومان)" : "")
                ]);

                OrderPaid::dispatch($order);
            });
        } catch (\Exception $e) {
            Log::error('Wallet Payment Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            Auth::user()->notifications()->create([
                'type' => 'payment_failed',
                'title' => 'خطا در پرداخت با کیف پول!',
                'message' => "پرداخت سفارش شما با خطا مواجه شد: " . $e->getMessage(),
                'link' => route('dashboard', ['tab' => 'order_history']),
            ]);

            return redirect()->route('dashboard')->with('error', 'پرداخت با خطا مواجه شد: ' . $e->getMessage());
        }


        session()->forget(['discount_code', 'discount_amount', 'discount_applied_order_id']);

        return redirect()->route('dashboard')->with('status', 'سرویس شما با موفقیت فعال شد.');
    }
    /**
     * Process crypto payment — redirect to crypto detail page.
     */
    public function processCryptoPayment(Order $order)
    {
        if (Auth::id() !== $order->user_id) abort(403);

        $order->update(['payment_method' => 'crypto']);

        return redirect()->route('payment.crypto.show', $order->id);
    }

    /**
     * Show crypto payment detail page (addresses, hash submission).
     */
    public function showCryptoDetailPage(Order $order)
    {
        if (Auth::id() !== $order->user_id) abort(403);

        if ($order->status === 'paid') {
            return redirect()->route('dashboard')->with('status', 'این سفارش قبلاً پرداخت شده است.');
        }

        $settings = Setting::all()->pluck('value', 'key');
        return view('payment.crypto-detail', compact('order', 'settings'));
    }

    /**
     * Submit crypto transaction hash.
     */
    public function submitCryptoHash(Request $request, Order $order)
    {
        if (Auth::id() !== $order->user_id) abort(403);

        $request->validate(['tx_hash' => 'required|string|min:10|max:255']);

        $order->update(['card_payment_receipt' => 'crypto_hash:' . $request->tx_hash]);

        Auth::user()->notifications()->create([
            'type'    => 'crypto_hash_submitted',
            'title'   => 'هش تراکنش رمزارز ارسال شد',
            'message' => "هش تراکنش برای سفارش #{$order->id} دریافت شد و در انتظار تأیید ادمین است.",
            'link'    => route('dashboard'),
        ]);

        return redirect()->route('dashboard')->with('status', '✅ هش تراکنش شما با موفقیت ارسال شد. پس از تأیید ادمین، سرویس فعال خواهد شد.');
    }

    /**
     * Stub — redirect to bank gateway page (gateway not connected yet).
     */
    public function processBankGateway(Order $order)
    {
        if (Auth::id() !== $order->user_id) abort(403);

        $order->update(['payment_method' => 'bank_gateway']);

        return redirect()->route('payment.bank.show', $order->id);
    }

    /**
     * Show bank gateway stub page.
     */
    public function showBankGatewayPage(Order $order)
    {
        if (Auth::id() !== $order->user_id) abort(403);

        if ($order->status === 'paid') {
            return redirect()->route('dashboard')->with('status', 'این سفارش قبلاً پرداخت شده است.');
        }

        $settings = Setting::all()->pluck('value', 'key');
        return view('payment.bank-gateway', compact('order', 'settings'));
    }
}
