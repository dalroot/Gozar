<!-- RIGHT SIDEBAR MENU -->
<aside class="dash-sidebar hidden lg:block lg:col-span-1 sticky top-20 min-w-0">
    
    <!-- User Profile Header Card -->
    <div class="p-4 rounded-3xl bg-[#FFF8F1] border border-[#FED7AA] text-right mb-4 shadow-sm">
        <div class="flex items-center gap-3 mb-2.5">
            <div class="w-11 h-11 rounded-2xl bg-orange-100 border border-orange-200 flex items-center justify-center text-xl flex-shrink-0 shadow-sm">
                👤
            </div>
            <div class="overflow-hidden min-w-0">
                <span class="block text-xs font-black text-gray-900 truncate">{{ auth()->user()->name ?? 'کاربر روزنه' }}</span>
                <span class="block text-[11px] text-gray-500 font-mono truncate" dir="ltr" style="text-align: right;">{{ auth()->user()->email ?? auth()->user()->phone ?? 'حساب کاربری' }}</span>
            </div>
        </div>
        <div class="flex items-center justify-between pt-2.5 border-t border-orange-200/60 text-[11px] font-bold text-gray-600">
            <span>موجودی حساب:</span>
            <span class="font-mono text-orange-600 font-black text-xs">{{ number_format(auth()->user()->balance ?? 0) }} تومان</span>
        </div>
    </div>

    <!-- Navigation Tabs with 3D Stickers -->
    <nav class="space-y-1.5 bg-white p-2.5 rounded-3xl border border-[#FED7AA] shadow-sm">
        <button data-tab="overview" class="dash-nav-btn active w-full flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-black text-gray-700 hover:bg-orange-50 hover:text-[var(--color-primary)] transition-all">
            <span class="text-base flex-shrink-0">📊</span>
            <span>نمای کلی و آمار</span>
        </button>

        <button data-tab="services" class="dash-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-black text-gray-700 hover:bg-orange-50 hover:text-[var(--color-primary)] transition-all">
            <span class="text-base flex-shrink-0">⚡</span>
            <span>سرویس‌های من</span>
        </button>

        <button data-tab="wallet" class="dash-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-black text-gray-700 hover:bg-orange-50 hover:text-[var(--color-primary)] transition-all">
            <span class="text-base flex-shrink-0">💳</span>
            <span>کیف پول و شارژ</span>
        </button>

        <button data-tab="servers" class="dash-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-black text-gray-700 hover:bg-orange-50 hover:text-[var(--color-primary)] transition-all">
            <span class="text-base flex-shrink-0">🌐</span>
            <span>سرورها و پینگ</span>
        </button>

        <button data-tab="apps" class="dash-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-black text-gray-700 hover:bg-orange-50 hover:text-[var(--color-primary)] transition-all">
            <span class="text-base flex-shrink-0">📱</span>
            <span>دانلود برنامه‌ها</span>
        </button>
    </nav>

</aside>
