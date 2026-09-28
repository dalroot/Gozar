<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            درگاه پرداخت بانکی — سفارش #{{ $order->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl p-8 text-center space-y-6">

                {{-- آیکون و عنوان --}}
                <div class="text-6xl">🏦</div>

                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">درگاه پرداخت آنلاین</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">
                        @php $gatewayName = $settings->get('bank_gateway_name', 'درگاه بانکی'); @endphp
                        {{ $gatewayName }}
                    </p>
                </div>

                {{-- مبلغ --}}
                <div class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-700 rounded-xl p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">مبلغ قابل پرداخت</p>
                    <p class="text-3xl font-bold text-violet-600 dark:text-violet-400 mt-1">
                        {{ number_format($order->amount) }} <span class="text-lg font-normal">تومان</span>
                    </p>
                </div>

                {{-- پیام در حال آماده‌سازی --}}
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-5 text-right space-y-2">
                    <p class="font-semibold text-amber-800 dark:text-amber-300">⏳ این درگاه به زودی فعال می‌شود</p>
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        در حال راه‌اندازی درگاه پرداخت آنلاین ریالی هستیم. لطفاً از روش <strong>کارت‌به‌کارت</strong> استفاده کنید.
                    </p>
                </div>

                {{-- دکمه‌های انتخاب --}}
                <div class="flex flex-col gap-3">
                    <form method="POST" action="{{ route('payment.card.process', $order->id) }}">
                        @csrf
                        <button type="submit"
                                class="w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition">
                            💳 پرداخت با کارت‌به‌کارت
                        </button>
                    </form>

                    <a href="{{ route('order.show', $order->id) }}"
                       class="block py-3 px-6 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition text-center">
                        ← بازگشت و انتخاب روش دیگر
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
