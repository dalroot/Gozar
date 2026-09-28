<!-- Floating Pill Navbar (Responsive Design Skill Standards) -->
<nav class="navbar-pet" role="navigation" aria-label="ناوبری اصلی">
    <div class="max-w-7xl mx-auto px-2 sm:px-4">
        <div class="flex items-center justify-between h-14 sm:h-16">
            
            <!-- Right Side (RTL Start): Logo & Brand Name with Fluid Typography -->
            <div class="flex items-center gap-2.5 sm:gap-3">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 group cursor-pointer" aria-label="صفحه اصلی روزنه">
                    <div class="nav-brand-icon flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-[#FF661A] to-[#D97706] text-white shadow-sm group-hover:scale-105 transition-transform duration-300">
                        <i class="ph-fill ph-sun text-xl sm:text-2xl"></i>
                    </div>
                    <span class="text-lg sm:text-2xl font-black text-[#38140B] tracking-tight">روزنه</span>
                </a>
            </div>

            <!-- Center Links (Desktop Navigation) -->
            <div class="hidden md:flex items-center gap-1 sm:gap-2">
                <a href="#services" class="nav-link-item px-3 py-2 text-sm font-semibold text-[#38140B] hover:text-[var(--color-primary)] transition-colors rounded-lg">ویژگی‌ها</a>
                <a href="#gallery" class="nav-link-item px-3 py-2 text-sm font-semibold text-[#38140B] hover:text-[var(--color-primary)] transition-colors rounded-lg">سرورها</a>
                <a href="#pricing" class="nav-link-item px-3 py-2 text-sm font-semibold text-[#38140B] hover:text-[var(--color-primary)] transition-colors rounded-lg">اشتراک‌ها</a>
                <a href="#checkout-section" class="nav-link-item px-3 py-2 text-sm font-semibold text-[#38140B] hover:text-[var(--color-primary)] transition-colors rounded-lg">آموزش اتصال</a>
                <a href="mobile.html" target="_blank" class="nav-link-item px-3 py-2 text-sm font-bold text-[var(--color-primary)] hover:opacity-80 transition-opacity rounded-lg">شبیه‌ساز اپلیکیشن</a>
            </div>

            <!-- Left Side (RTL End): Auth Button & Mobile Menu Trigger (Min 44x44px Touch Targets) -->
            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-nav-auth flex items-center gap-1.5 px-3.5 sm:px-4 py-2 min-h-[44px] rounded-full bg-[var(--color-primary)] text-white text-xs sm:text-sm font-bold shadow-sm hover:opacity-90 transition-opacity">
                        <i class="ph-bold ph-gauge text-base sm:text-lg"></i>
                        <span>داشبورد من</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-nav-auth flex items-center gap-1.5 px-3.5 sm:px-4 py-2 min-h-[44px] rounded-full bg-[var(--color-primary)] text-white text-xs sm:text-sm font-bold shadow-sm hover:opacity-90 transition-opacity">
                        <i class="ph-bold ph-user-circle text-base sm:text-lg"></i>
                        <span>ورود / ثبت‌نام</span>
                    </a>
                @endauth

                <!-- Hamburger Trigger with min 44x44px touch target & ARIA accessibility -->
                <button id="mobile-menu-toggle" class="mobile-menu-btn flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-xl bg-white/80 border border-[var(--color-border-subtle)] text-[#38140B] text-xl hover:bg-white transition-colors cursor-pointer" aria-label="باز کردن منوی موبایل" aria-expanded="false" aria-controls="mobile-menu-drawer">
                    <i class="ph ph-list"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Navigation Drawer Overlay -->
<div id="mobile-menu-overlay" class="mobile-menu-overlay" aria-hidden="true"></div>

<!-- Mobile Navigation Drawer (Accessible Dialog) -->
<div id="mobile-menu-drawer" class="mobile-menu-drawer" role="dialog" aria-modal="true" aria-label="منوی موبایل">
    <div class="flex items-center justify-between pb-4 mb-4 border-b border-[var(--color-border-subtle)]">
        <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#FF661A] to-[#D97706] flex items-center justify-center text-white shadow-md">
                <i class="ph-fill ph-sun text-xl"></i>
            </div>
            <span class="text-lg font-bold text-[#38140B]">روزنه</span>
        </div>
        <!-- Close button with min 44x44px touch target -->
        <button id="mobile-menu-close" class="w-11 h-11 min-w-[44px] min-h-[44px] rounded-xl bg-white border-2 border-[var(--color-border-subtle)] flex items-center justify-center text-[#38140B] text-lg hover:bg-[#FFF5EA] transition-colors cursor-pointer" aria-label="بستن منوی موبایل">
            <i class="ph ph-x"></i>
        </button>
    </div>
    
    <div class="flex flex-col gap-1 font-semibold text-base py-2">
        <a href="#services" class="mobile-nav-link text-[#38140B] hover:text-[var(--color-primary)] py-3 px-2 rounded-lg border-b border-[var(--color-border-subtle)]/50 flex items-center justify-between min-h-[44px] transition-colors">
            <span>ویژگی‌ها</span>
            <i class="ph ph-caret-left text-sm opacity-60"></i>
        </a>
        <a href="#gallery" class="mobile-nav-link text-[#38140B] hover:text-[var(--color-primary)] py-3 px-2 rounded-lg border-b border-[var(--color-border-subtle)]/50 flex items-center justify-between min-h-[44px] transition-colors">
            <span>سرورها</span>
            <i class="ph ph-caret-left text-sm opacity-60"></i>
        </a>
        <a href="#pricing" class="mobile-nav-link text-[#38140B] hover:text-[var(--color-primary)] py-3 px-2 rounded-lg border-b border-[var(--color-border-subtle)]/50 flex items-center justify-between min-h-[44px] transition-colors">
            <span>اشتراک‌ها</span>
            <i class="ph ph-caret-left text-sm opacity-60"></i>
        </a>
        <a href="#checkout-section" class="mobile-nav-link text-[#38140B] hover:text-[var(--color-primary)] py-3 px-2 rounded-lg border-b border-[var(--color-border-subtle)]/50 flex items-center justify-between min-h-[44px] transition-colors">
            <span>آموزش اتصال</span>
            <i class="ph ph-caret-left text-sm opacity-60"></i>
        </a>
        <a href="mobile.html" target="_blank" class="mobile-nav-link text-[var(--color-primary)] font-bold py-3 px-2 rounded-lg border-b border-[var(--color-border-subtle)]/50 flex items-center justify-between min-h-[44px] transition-colors">
            <span>شبیه‌ساز اپلیکیشن</span>
            <i class="ph-bold ph-smartphone text-lg"></i>
        </a>
    </div>

    <div class="mt-auto pt-6 border-t border-[var(--color-border-subtle)]">
        @auth
            <a href="{{ route('dashboard') }}" class="w-full min-h-[48px] py-3 rounded-full bg-gradient-to-r from-[#FF661A] to-[#D97706] text-white font-bold text-sm shadow-md flex items-center justify-center gap-2 hover:opacity-95 transition-opacity cursor-pointer">
                <i class="ph-bold ph-gauge text-lg"></i>
                <span>ورود به داشبورد</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="w-full min-h-[48px] py-3 rounded-full bg-gradient-to-r from-[#FF661A] to-[#D97706] text-white font-bold text-sm shadow-md flex items-center justify-center gap-2 hover:opacity-95 transition-opacity cursor-pointer">
                <i class="ph-bold ph-user-circle text-lg"></i>
                <span>ورود / ثبت‌نام حساب</span>
            </a>
        @endauth
    </div>
</div>
