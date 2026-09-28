<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            پرداخت سفارش #{{ $order->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-xl">
                    <p>{{ session('status') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-xl">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            {{-- جزئیات فاکتور --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 text-right border-b dark:border-gray-700 pb-3 mb-4">
                    🧾 جزئیات فاکتور
                </h3>
                @php
                    $originalAmount = $order->plan ? ($order->plan->price ?? $order->amount) : $order->amount;
                    $discountAmount = $order->discount_amount ?? session('discount_amount', 0);
                    $finalAmount    = $originalAmount - $discountAmount;
                @endphp
                <div class="space-y-3 text-right">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 dark:text-gray-400">موضوع:</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">
                            @if ($order->plan)
                                {{ $order->renews_order_id ? 'تمدید سرویس' : 'خرید سرویس' }} ({{ $order->plan->name }})
                            @else
                                شارژ کیف پول
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 dark:text-gray-400">مبلغ اصلی:</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($originalAmount) }} تومان</span>
                    </div>
                    @if($discountAmount > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-400">تخفیف:</span>
                            <span class="font-semibold text-red-500">- {{ number_format($discountAmount) }} تومان</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center border-t dark:border-gray-700 pt-3">
                        <span class="text-gray-700 dark:text-gray-300 font-bold">مبلغ قابل پرداخت:</span>
                        <span class="font-bold text-xl text-green-600 dark:text-green-400">{{ number_format($finalAmount) }} تومان</span>
                    </div>
                </div>
            </div>

            {{-- کد تخفیف (فقط برای خرید/تمدید پلن) --}}
            @if($order->plan && !session('discount_code'))
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl p-6">
                    <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 text-right mb-3">🏷️ کد تخفیف دارید؟</h4>
                    <form id="discount-form" action="{{ route('order.applyDiscount', $order->id) }}" method="POST">
                        @csrf
                        <div class="flex gap-3">
                            <input type="text" name="code" id="discount-code"
                                   class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                   placeholder="مثلاً: YALDA1404">
                            <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">اعمال کد</button>
                        </div>
                    </form>
                    <div id="discount-message" class="mt-2 text-sm text-right"></div>
                </div>
            @elseif(session('discount_code'))
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700 rounded-xl p-4 text-center">
                    <p class="text-green-700 dark:text-green-300">✅ کد تخفیف <strong>{{ session('discount_code') }}</strong> اعمال شده است.</p>
                </div>
            @endif

            {{-- انتخاب روش پرداخت --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 text-right mb-6">
                    💳 روش پرداخت را انتخاب کنید
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- ۱. کیف پول داخلی --}}
                    @php $walletEnabled = (bool) ($settings->get('pay_enable_wallet', true)); @endphp
                    @if($walletEnabled && $order->plan)
                        @php $userBalance = auth()->user()->balance; $canPayWallet = $userBalance >= $finalAmount; @endphp
                        <form method="POST" action="{{ route('payment.wallet.process', $order->id) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full h-full text-right p-5 rounded-xl border-2 transition-all duration-200 group
                                        {{ $canPayWallet
                                            ? 'border-emerald-300 dark:border-emerald-600 hover:border-emerald-500 hover:shadow-lg hover:shadow-emerald-100 dark:hover:shadow-emerald-900/30 cursor-pointer'
                                            : 'border-gray-200 dark:border-gray-700 opacity-60 cursor-not-allowed bg-gray-50 dark:bg-gray-800/50' }}"
                                    {{ !$canPayWallet ? 'disabled' : '' }}>
                                <div class="flex items-start gap-3">
                                    <span class="text-3xl">👛</span>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-gray-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition">
                                            پرداخت از کیف پول
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">آنی و بدون نیاز به تأیید</p>
                                        <p class="text-xs mt-2 font-medium {{ $canPayWallet ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                                            موجودی: {{ number_format($userBalance) }} تومان
                                            @if(!$canPayWallet) — موجودی کافی نیست @endif
                                        </p>
                                    </div>
                                </div>
                            </button>
                        </form>
                    @endif

                    {{-- ۲. کارت‌به‌کارت --}}
                    @php $cardEnabled = (bool) ($settings->get('pay_enable_card', true)); @endphp
                    @if($cardEnabled)
                        <form method="POST" action="{{ route('payment.card.process', $order->id) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full h-full text-right p-5 rounded-xl border-2 border-blue-300 dark:border-blue-700 hover:border-blue-500 hover:shadow-lg hover:shadow-blue-100 dark:hover:shadow-blue-900/30 transition-all duration-200 group cursor-pointer">
                                <div class="flex items-start gap-3">
                                    <span class="text-3xl">💳</span>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                                            کارت‌به‌کارت
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ارسال رسید — تأیید توسط ادمین</p>
                                        <p class="text-xs mt-2 text-blue-600 dark:text-blue-400 font-medium">معمولاً کمتر از ۳۰ دقیقه</p>
                                    </div>
                                </div>
                            </button>
                        </form>
                    @endif

                    {{-- ۳. درگاه بانکی آنلاین --}}
                    @php $bankEnabled = (bool) ($settings->get('pay_enable_bank_gateway', false)); @endphp
                    @if($bankEnabled)
                        <form method="POST" action="{{ route('payment.bank.process', $order->id) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full h-full text-right p-5 rounded-xl border-2 border-violet-300 dark:border-violet-700 hover:border-violet-500 hover:shadow-lg hover:shadow-violet-100 dark:hover:shadow-violet-900/30 transition-all duration-200 group cursor-pointer">
                                <div class="flex items-start gap-3">
                                    <span class="text-3xl">🏦</span>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-gray-100 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition">
                                            درگاه بانکی آنلاین
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">پرداخت مستقیم ریالی</p>
                                        <p class="text-xs mt-2 text-violet-600 dark:text-violet-400 font-medium">آنی — بدون انتظار</p>
                                    </div>
                                </div>
                            </button>
                        </form>
                    @else
                        <div class="w-full text-right p-5 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 opacity-50 cursor-not-allowed">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl grayscale">🏦</span>
                                <div>
                                    <h4 class="font-bold text-gray-500 dark:text-gray-500">درگاه بانکی آنلاین</h4>
                                    <p class="text-xs mt-2 text-gray-400 font-medium">🔜 به زودی فعال می‌شود</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ۴. رمزارز --}}
                    @php $cryptoEnabled = (bool) ($settings->get('pay_enable_crypto', false)); @endphp
                    @if($cryptoEnabled)
                        <form method="POST" action="{{ route('payment.crypto.process', $order->id) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full h-full text-right p-5 rounded-xl border-2 border-orange-300 dark:border-orange-700 hover:border-orange-500 hover:shadow-lg hover:shadow-orange-100 dark:hover:shadow-orange-900/30 transition-all duration-200 group cursor-pointer">
                                <div class="flex items-start gap-3">
                                    <span class="text-3xl">🪙</span>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-gray-100 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition">
                                            پرداخت با رمزارز
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">USDT / BTC / ETH</p>
                                        <p class="text-xs mt-2 text-orange-600 dark:text-orange-400 font-medium">تأیید توسط ادمین</p>
                                    </div>
                                </div>
                            </button>
                        </form>
                    @else
                        <div class="w-full text-right p-5 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 opacity-50 cursor-not-allowed">
                            <div class="flex items-start gap-3">
                                <span class="text-3xl grayscale">🪙</span>
                                <div>
                                    <h4 class="font-bold text-gray-500 dark:text-gray-500">پرداخت با رمزارز</h4>
                                    <p class="text-xs mt-2 text-gray-400 font-medium">🔜 به زودی فعال می‌شود</p>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>

    @if($order->plan)
    <script>
        const discountForm = document.getElementById('discount-form');
        if (discountForm) {
            discountForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const form = e.target;
                const messageDiv = document.getElementById('discount-message');
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'در حال بررسی...';
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: new FormData(form)
                    });
                    const data = await response.json();
                    if (data.success) {
                        messageDiv.innerHTML = `<div class="text-green-600 dark:text-green-400">${data.message}</div>`;
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        messageDiv.innerHTML = `<div class="text-red-600 dark:text-red-400">${data.error}</div>`;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'اعمال کد';
                    }
                } catch (error) {
                    messageDiv.innerHTML = `<div class="text-red-600">خطا در ارتباط با سرور</div>`;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'اعمال کد';
                }
            });
        }
    </script>
    @endif
</x-app-layout>
