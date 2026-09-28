<!-- Admin Sidebar Navigation Partial -->
@php
    $currentRoute = Route::currentRouteName();
@endphp

<aside class="w-full lg:w-64 bg-white border-2 border-[var(--color-border)] rounded-2xl p-4 shadow-xs space-y-2 flex-shrink-0">
    <div class="px-3 py-2 text-xs font-black text-gray-400 uppercase tracking-wider">منوی اصلی مدیریت</div>
    
    <a href="{{ route('admin.index') }}" class="admin-nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all {{ $currentRoute == 'admin.index' ? 'active' : 'text-gray-700 hover:bg-orange-50 hover:text-orange-600' }}">
        <i class="ph-bold ph-chart-pie-slice text-lg"></i>
        <span>داشبورد آمار کلی</span>
    </a>

    <a href="{{ route('admin.users') }}" class="admin-nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all {{ $currentRoute == 'admin.users' ? 'active' : 'text-gray-700 hover:bg-orange-50 hover:text-orange-600' }}">
        <i class="ph-bold ph-users text-lg"></i>
        <span>مدیریت کاربران</span>
    </a>

    <a href="{{ route('admin.servers') }}" class="admin-nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all {{ $currentRoute == 'admin.servers' ? 'active' : 'text-gray-700 hover:bg-orange-50 hover:text-orange-600' }}">
        <i class="ph-bold ph-hard-drives text-lg"></i>
        <span>مدیریت سرورها</span>
    </a>

    <a href="{{ route('admin.configs') }}" class="admin-nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all {{ $currentRoute == 'admin.configs' ? 'active' : 'text-gray-700 hover:bg-orange-50 hover:text-orange-600' }}">
        <i class="ph-bold ph-shield-check text-lg"></i>
        <span>کانفیگ‌ها & پروتکل‌ها</span>
    </a>

    <a href="{{ route('admin.billing') }}" class="admin-nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all {{ $currentRoute == 'admin.billing' ? 'active' : 'text-gray-700 hover:bg-orange-50 hover:text-orange-600' }}">
        <i class="ph-bold ph-receipt text-lg"></i>
        <span>تراکنش‌ها & مالی</span>
    </a>

    <a href="{{ route('admin.settings') }}" class="admin-nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all {{ $currentRoute == 'admin.settings' ? 'active' : 'text-gray-700 hover:bg-orange-50 hover:text-orange-600' }}">
        <i class="ph-bold ph-gear text-lg"></i>
        <span>تنظیمات سیستم</span>
    </a>
</aside>
