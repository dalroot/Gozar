<!-- OVERVIEW TAB CONTENT -->
<div id="pane-overview" class="dash-pane space-y-4 sm:space-y-6 w-full max-w-full overflow-hidden">
    
    <!-- 1. Hero Balance & Active Subscription Card -->
    <div class="p-5 sm:p-8 rounded-3xl text-white shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-5 border border-orange-950/40 w-full" style="background: radial-gradient(ellipse at 85% 50%, #3D1A06 0%, #1C0A04 45%, #150602 100%);">
        
        <!-- Floating Stat Pill -->
        <div class="absolute top-4 left-4 hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold text-emerald-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>+۵٪ پاداش شارژ کیف پول</span>
        </div>

        <div class="relative z-10 max-w-xl text-right space-y-2.5 w-full">
            <!-- Welcome Badge Pill -->
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-500/20 text-orange-300 text-xs font-black backdrop-blur-md border border-orange-500/30">
                @if(auth()->check() && auth()->user()->name)
                    👋 {{ auth()->user()->name }} عزیز، خوش آمدید
                @else
                    👋 کاربر گرامی روزنه‌ای، خوش آمدید
                @endif
            </span>
            
            <h2 class="text-xl sm:text-3xl font-black text-white leading-tight">
                مدیریت هوشمند، سریع و باثبات سرویس‌های شما
            </h2>
            <p class="text-xs sm:text-sm text-orange-200/80 font-medium leading-relaxed">
                مشاهده حجم باقی‌مانده، اعتبار حساب، تمدید فوری و دریافت لینک اتصال کانفیگ‌ها با ۱ کلیک.
            </p>
        </div>

        <div class="relative z-10 flex flex-wrap gap-2.5 w-full md:w-auto">
            <button data-dash-tab="services" class="flex-1 md:flex-initial px-5 py-3 rounded-2xl bg-gradient-to-r from-orange-500 to-amber-500 text-white font-extrabold text-xs sm:text-sm shadow-lg hover:shadow-orange-500/25 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                <i class="ph-bold ph-lightning text-base"></i>
                <span>اشتراک‌های من</span>
            </button>
            <button data-dash-tab="wallet" class="flex-1 md:flex-initial px-5 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold text-xs sm:text-sm backdrop-blur-md transition-all flex items-center justify-center gap-2">
                <i class="ph-bold ph-wallet text-base"></i>
                <span>شارژ کیف پول</span>
            </button>
        </div>
    </div>

    <!-- 2. Quick Stats Grid (3 Responsive Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 w-full">
        
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-[#FED7AA] shadow-xs flex items-center justify-between gap-3 min-w-0">
            <div class="min-w-0">
                <span class="block text-xs font-black text-gray-500 truncate">ترافیک باقی‌مانده</span>
                <span class="text-lg sm:text-xl font-black text-gray-900 font-mono mt-1 block truncate">۱۷.۸۷ <span class="text-xs font-sans text-gray-500 font-bold">MB</span></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-orange-50 border border-orange-200 text-[var(--color-primary)] flex items-center justify-center text-xl flex-shrink-0">
                <i class="ph-bold ph-chart-pie"></i>
            </div>
        </div>

        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-[#FED7AA] shadow-xs flex items-center justify-between gap-3 min-w-0">
            <div class="min-w-0">
                <span class="block text-xs font-black text-gray-500 truncate">زمان باقی‌مانده</span>
                <span class="text-lg sm:text-xl font-black text-gray-900 mt-1 block truncate">نامحدود</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center text-xl flex-shrink-0">
                <i class="ph-bold ph-hourglass"></i>
            </div>
        </div>

        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-[#FED7AA] shadow-xs flex items-center justify-between gap-3 min-w-0">
            <div class="min-w-0">
                <span class="block text-xs font-black text-gray-500 truncate">موجودی کیف پول</span>
                <span class="text-lg sm:text-xl font-black text-gray-900 font-mono mt-1 block truncate">{{ number_format(auth()->user()->balance ?? 0) }} <span class="text-xs font-sans text-gray-500 font-bold">تومان</span></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                <i class="ph-bold ph-wallet"></i>
            </div>
        </div>

    </div>
</div>
