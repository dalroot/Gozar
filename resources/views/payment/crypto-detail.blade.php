<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            پرداخت با رمزارز — سفارش #{{ $order->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-xl">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-xl">{{ session('error') }}</div>
            @endif

            {{-- مبلغ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 text-right">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">مبلغ قابل پرداخت:</span>
                    <span class="text-2xl font-bold text-orange-500">{{ number_format($order->amount) }} تومان</span>
                </div>
                <p class="text-xs text-gray-400 mt-2">لطفاً معادل دلاری را در زمان پرداخت بر اساس نرخ روز محاسبه کنید.</p>
            </div>

            {{-- آدرس‌های رمزارز --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-5 text-right">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">🪙 آدرس‌های پرداخت</h3>

                @php
                    $usdtTrc20 = $settings->get('crypto_usdt_trc20', '');
                    $usdtBep20 = $settings->get('crypto_usdt_bep20', '');
                    $btc       = $settings->get('crypto_btc', '');
                    $instructions = $settings->get('crypto_instructions', 'پس از واریز، هش تراکنش را در کادر زیر وارد کنید.');
                @endphp

                @if($usdtTrc20)
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <button onclick="copyToClipboard('{{ $usdtTrc20 }}')" class="text-xs text-green-700 dark:text-green-400 hover:underline font-medium px-3 py-1 bg-green-100 dark:bg-green-800/40 rounded-lg transition">📋 کپی</button>
                        <span class="text-sm font-bold text-green-700 dark:text-green-400">USDT — شبکه TRC20 (ترون)</span>
                    </div>
                    <p id="addr-trc20" class="font-mono text-xs text-gray-700 dark:text-gray-300 break-all text-left bg-white dark:bg-gray-900 p-2 rounded">{{ $usdtTrc20 }}</p>
                </div>
                @endif

                @if($usdtBep20)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <button onclick="copyToClipboard('{{ $usdtBep20 }}')" class="text-xs text-yellow-700 dark:text-yellow-400 hover:underline font-medium px-3 py-1 bg-yellow-100 dark:bg-yellow-800/40 rounded-lg transition">📋 کپی</button>
                        <span class="text-sm font-bold text-yellow-700 dark:text-yellow-400">USDT — شبکه BEP20 (بایننس)</span>
                    </div>
                    <p class="font-mono text-xs text-gray-700 dark:text-gray-300 break-all text-left bg-white dark:bg-gray-900 p-2 rounded">{{ $usdtBep20 }}</p>
                </div>
                @endif

                @if($btc)
                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <button onclick="copyToClipboard('{{ $btc }}')" class="text-xs text-orange-700 dark:text-orange-400 hover:underline font-medium px-3 py-1 bg-orange-100 dark:bg-orange-800/40 rounded-lg transition">📋 کپی</button>
                        <span class="text-sm font-bold text-orange-700 dark:text-orange-400">BTC — بیتکوین</span>
                    </div>
                    <p class="font-mono text-xs text-gray-700 dark:text-gray-300 break-all text-left bg-white dark:bg-gray-900 p-2 rounded">{{ $btc }}</p>
                </div>
                @endif

                @if(!$usdtTrc20 && !$usdtBep20 && !$btc)
                    <div class="text-center text-gray-400 py-6">
                        <p class="text-4xl mb-3">🔜</p>
                        <p class="font-medium">آدرس‌های پرداخت به زودی تنظیم می‌شوند.</p>
                        <p class="text-sm mt-1">لطفاً با پشتیبانی تماس بگیرید.</p>
                    </div>
                @endif

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-sm text-gray-600 dark:text-gray-300 text-right">📌 {{ $instructions }}</p>
                </div>
            </div>

            {{-- ارسال هش تراکنش --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 text-right">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">📤 ارسال هش تراکنش</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">بعد از پرداخت، هش (TxID) تراکنش خود را از کیف پولتان کپی کرده و اینجا وارد کنید.</p>

                <form method="POST" action="{{ route('payment.crypto.submit-hash', $order->id) }}">
                    @csrf
                    @error('tx_hash')
                        <div class="text-red-500 text-sm mb-3">{{ $message }}</div>
                    @enderror
                    <div class="flex gap-3">
                        <input type="text" name="tx_hash" placeholder="مثال: 0xabc123..." required
                               class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-left font-mono text-sm">
                        <button type="submit"
                                class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold transition">
                            ✅ ارسال
                        </button>
                    </div>
                </form>
            </div>

            {{-- دکمه بازگشت --}}
            <a href="{{ route('order.show', $order->id) }}"
               class="block text-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 text-sm transition">
                ← بازگشت و انتخاب روش دیگر
            </a>

        </div>
    </div>

    <div id="copy-toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-gray-800 text-white px-5 py-2 rounded-full text-sm shadow-lg opacity-0 transition-opacity duration-300 pointer-events-none">
        ✅ آدرس کپی شد
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.getElementById('copy-toast');
                toast.classList.remove('opacity-0');
                toast.classList.add('opacity-100');
                setTimeout(() => { toast.classList.remove('opacity-100'); toast.classList.add('opacity-0'); }, 2000);
            });
        }
    </script>
</x-app-layout>
