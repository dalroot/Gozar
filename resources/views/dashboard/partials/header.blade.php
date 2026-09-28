<!-- Header Navigation Bar -->
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-[#EEDFD5] transition-all w-full max-w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 w-full">
        <div class="flex items-center justify-between h-16 gap-2">
            
            <!-- Logo & Mobile Drawer Toggle -->
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <button id="btn-toggle-mobile-drawer" class="lg:hidden p-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 flex items-center justify-center min-w-[38px] min-h-[38px] flex-shrink-0" aria-label="منوی ناوبری">
                    <i class="ph-bold ph-list text-xl"></i>
                </button>
                <a href="/" class="flex items-center gap-2 group min-w-0" aria-label="روزنه وی‌پی‌ان">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-[var(--color-primary)] flex items-center justify-center text-white text-base sm:text-lg shadow-sm group-hover:scale-105 transition-transform flex-shrink-0">
                        <i class="ph-bold ph-rocket-launch"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-sm sm:text-base font-extrabold text-[var(--color-text)] leading-none tracking-tight truncate">روزنه <span class="text-[var(--color-primary)]">وی‌پی‌ان</span></span>
                        <span class="text-[9px] font-bold text-[var(--color-primary)] tracking-widest uppercase mt-0.5 truncate hidden sm:block">پنل مدیریت اختصاصی</span>
                    </div>
                </a>
            </div>

            <!-- Quick Action Header Buttons -->
            <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
                <a href="/" class="p-2 sm:px-3 sm:py-2 rounded-xl bg-orange-50 hover:bg-orange-100 text-[var(--color-primary)] font-bold text-xs flex items-center gap-1.5 transition-colors border border-orange-200/60" title="صفحه اصلی">
                    <i class="ph-bold ph-house text-base"></i>
                    <span class="hidden md:inline">صفحه اصلی</span>
                </a>
                <a href="https://t.me/rozaneh_vpn_bot" target="_blank" class="p-2 sm:px-3 sm:py-2 rounded-xl bg-sky-50 hover:bg-sky-100 text-sky-600 font-bold text-xs flex items-center gap-1.5 transition-colors border border-sky-200/60" title="پشتیبانی تلگرام">
                    <i class="ph-bold ph-paper-plane-tilt text-base"></i>
                    <span class="hidden md:inline">پشتیبانی</span>
                </a>
                
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="p-2 sm:px-3 sm:py-2 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs flex items-center gap-1.5 transition-colors border border-red-200/60" title="خروج">
                            <i class="ph-bold ph-sign-out text-base"></i>
                            <span class="hidden md:inline">خروج</span>
                        </button>
                    </form>
                @endauth
            </div>

        </div>
    </div>
</header>
