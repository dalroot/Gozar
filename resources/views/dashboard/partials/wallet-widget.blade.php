<!-- WALLET TAB CONTENT -->
<div id="pane-wallet" class="dash-pane hidden space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Wallet Balance & Top-Up Card -->
        <div class="p-7 rounded-[32px] bg-white border border-[#FED7AA] shadow-[0_10px_25px_-10px_rgba(234,88,12,0.05)] space-y-5">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div>
                    <span class="block text-xs font-black text-gray-500">موجودی کیف پول</span>
                    <span class="text-2xl font-black text-gray-900 font-mono mt-1 block">{{ number_format(auth()->user()->balance ?? 0) }} <span class="text-xs font-sans text-gray-500 font-bold">تومان</span></span>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-3xl font-bold shadow-sm">
                    💳
                </div>
            </div>

            <form method="POST" action="{{ route('payment.card.process', 1) }}" class="space-y-4">
                @csrf
                <label class="block text-xs font-black text-gray-800">مبلغ افزایش اعتبار (تومان)</label>
                <div class="relative">
                    <input type="number" name="amount" placeholder="مثال: ۱۰۰,۰۰۰" class="w-full px-4 py-3.5 rounded-2xl bg-gray-50 border border-gray-200 text-sm font-mono text-gray-900 outline-none focus:border-[var(--color-primary)] focus:bg-white transition-all" required />
                    <span class="absolute left-4 top-3.5 text-xs font-black text-orange-600">تومان</span>
                </div>
                <button type="submit" class="w-full py-3.5 rounded-2xl bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white font-black text-xs shadow-md shadow-orange-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <span class="text-base">⚡</span>
                    <span>شارژ آنی کیف پول</span>
                </button>
            </form>
        </div>

        <!-- Info Box -->
        <div class="p-7 rounded-[32px] bg-gradient-to-br from-orange-50 to-amber-50/60 border border-orange-200 flex flex-col justify-between space-y-4">
            <div>
                <h4 class="text-sm font-black text-orange-950 mb-3 flex items-center gap-2">
                    <span class="text-xl">🎁</span>
                    <span>مزایای شگفت‌انگیز کیف پول روزنه</span>
                </h4>
                <ul class="text-xs font-bold text-orange-900 space-y-2.5 leading-relaxed">
                    <li class="flex items-start gap-2">
                        <span class="text-orange-500 font-bold">✓</span>
                        <span>تکمیل آنی تمدید و خرید بدون معطلی در درگاه پرداخت</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-orange-500 font-bold">✓</span>
                        <span>دریافت ۵٪ پاداش شارژ هدیه برای تراکنش‌های بالای ۲۰۰ هزار تومان</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-orange-500 font-bold">✓</span>
                        <span>امنیت کامل، پشتیبانی ۲۴ ساعته و عدم نیاز به ارسال مجدد فیش</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

</div>
