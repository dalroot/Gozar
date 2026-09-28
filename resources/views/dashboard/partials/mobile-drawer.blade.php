<!-- Mobile Navigation Drawer Backdrop & Content -->
<div id="mobile-drawer" class="fixed inset-0 z-50 lg:hidden pointer-events-none opacity-0 transition-opacity duration-300">
    <div id="mobile-drawer-overlay" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
    <aside id="mobile-drawer-content" class="absolute top-0 right-0 w-72 h-full bg-white p-5 shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                <span class="font-extrabold text-sm text-gray-800">منوی پنل کاربری</span>
                <button id="btn-close-mobile-drawer" class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 font-bold">✕</button>
            </div>

            <!-- Profile Box in Drawer -->
            <div class="p-3 rounded-xl bg-[#FFF8F1] border border-[#EEDFD5] mb-4 text-right">
                <div class="flex items-center gap-2.5 mb-1.5">
                    <div class="w-9 h-9 rounded-lg bg-[#FFF0E6] border border-orange-300 flex items-center justify-center font-extrabold text-[var(--color-primary)] text-sm flex-shrink-0">
                        <i class="ph-bold ph-user text-base"></i>
                    </div>
                    <div class="overflow-hidden">
                        <span class="block text-xs font-bold text-gray-900 truncate">{{ auth()->user()->name ?? 'کاربر روزنه' }}</span>
                        <span class="block text-[10px] text-gray-600 font-mono truncate" dir="ltr" style="text-align: right;">{{ auth()->user()->email ?? auth()->user()->phone ?? '09120000000' }}</span>
                    </div>
                </div>
                <span class="inline-block px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 font-bold text-[10px]">اشتراک فعال پرو ✓</span>
            </div>

            <!-- Drawer Nav Links -->
            <div class="space-y-1">
                <button data-tab="overview" class="drawer-tab-btn active w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-extrabold text-gray-700 hover:bg-orange-50 hover:text-[var(--color-primary)] transition-all">
                    <i class="ph-bold ph-squares-four text-base text-[var(--color-primary)]"></i>
                    <span>نمای کلی و آمار</span>
                </button>
                <button data-tab="services" class="drawer-tab-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-extrabold text-gray-700 hover:bg-orange-50 hover:text-[var(--color-primary)] transition-all">
                    <i class="ph-bold ph-lightning text-base text-amber-500"></i>
                    <span>سرویس‌های من</span>
                </button>
                <button data-tab="wallet" class="drawer-tab-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-extrabold text-gray-700 hover:bg-orange-50 hover:text-[var(--color-primary)] transition-all">
                    <i class="ph-bold ph-wallet text-base text-emerald-500"></i>
                    <span>کیف پول</span>
                </button>
                <button data-tab="servers" class="drawer-tab-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-extrabold text-gray-700 hover:bg-orange-50 hover:text-[var(--color-primary)] transition-all">
                    <i class="ph-bold ph-globe text-base text-sky-500"></i>
                    <span>سرورها و پینگ</span>
                </button>
                <button data-tab="apps" class="drawer-tab-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-extrabold text-gray-700 hover:bg-orange-50 hover:text-[var(--color-primary)] transition-all">
                    <i class="ph-bold ph-download-simple text-base text-purple-500"></i>
                    <span>دانلود برنامه‌ها</span>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="w-full pt-4 border-t border-gray-100">
            @csrf
            <button type="submit" class="w-full py-2.5 rounded-xl bg-red-50 text-red-600 font-bold text-xs border border-red-200 hover:bg-red-600 hover:text-white transition-all flex items-center justify-center gap-2">
                <i class="ph-bold ph-sign-out text-base"></i>
                <span>خروج از حساب</span>
            </button>
        </form>
    </aside>
</div>
