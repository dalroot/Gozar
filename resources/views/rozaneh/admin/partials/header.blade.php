<!-- Top Admin Header -->
<header class="bg-white border-b-2 border-[var(--color-border)] sticky top-0 z-40 shadow-xs w-full max-w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        
        <!-- Logo & Brand & Mobile Toggle -->
        <div class="flex items-center gap-3">
            <button id="btn-toggle-admin-menu" class="lg:hidden p-2 rounded-xl border border-orange-200 text-gray-700 hover:bg-orange-50 flex items-center justify-center min-w-[38px] min-h-[38px]">
                <i class="ph-bold ph-list text-xl"></i>
            </button>
            <a href="{{ route('admin.index') }}" class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-[var(--color-primary)] flex items-center justify-center border-2 border-[rgba(0,0,0,0.1)] shadow-xs">
                    <i class="ph-fill ph-sun-dim text-white text-xl"></i>
                </div>
                <div>
                    <span class="text-lg font-black text-[var(--color-text)]">پنل مدیریت روزنه</span>
                    <span class="block text-[10px] font-bold text-[var(--color-text-muted)] font-en">Rozaneh Core v4.2</span>
                </div>
            </a>
        </div>

        <!-- Live Search Bar -->
        <div class="hidden md:flex items-center gap-4 flex-1 max-w-md mx-4">
            <div class="relative w-full">
                <i class="ph-bold ph-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" placeholder="جستجوی سریع کاربر، کد پیگیری یا آی‌پی..." class="w-full pr-9 pl-4 py-2 text-xs rounded-xl bg-[var(--color-bg-alt)] border border-[var(--color-border)] focus:outline-none focus:border-[var(--color-primary)] font-medium">
            </div>
        </div>

        <!-- Quick Links & User Badge -->
        <div class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>سرورها آنلاین ۹۹.۹٪</span>
            </div>
            
            <a href="{{ route('home') }}" class="p-2 rounded-xl bg-orange-50 hover:bg-orange-100 border border-orange-200 text-orange-600 text-xs font-bold flex items-center gap-1.5 transition-colors" title="مشاهده وب‌سایت">
                <i class="ph-bold ph-house text-base"></i>
                <span class="hidden sm:inline">سایت</span>
            </a>

            <!-- Admin Profile Badge -->
            <div class="flex items-center gap-2 pl-2 border-r border-gray-200">
                <div class="w-8 h-8 rounded-full bg-orange-500 text-white font-bold flex items-center justify-center text-xs">
                    {{ mb_substr(auth()->user()->name ?? 'مدیر', 0, 1) }}
                </div>
                <div class="hidden md:flex flex-col text-right">
                    <span class="text-xs font-black text-gray-800">{{ auth()->user()->name ?? 'مدیر سیستم' }}</span>
                    <span class="text-[9px] font-bold text-orange-600">Super Admin</span>
                </div>
            </div>
        </div>

    </div>
</header>
