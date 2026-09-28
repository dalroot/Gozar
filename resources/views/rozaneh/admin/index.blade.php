<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @include('rozaneh.admin.partials.head', ['title' => 'آمار کلی'])
</head>
<body class="bg-[var(--color-bg)] text-[var(--color-text)] font-sans antialiased min-h-screen flex flex-col">

    <!-- Top Admin Header -->
    @include('rozaneh.admin.partials.header')

    <!-- Main Container Layout -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Right Navigation Sidebar -->
            @include('rozaneh.admin.partials.sidebar')

            <!-- Left Main Content Panes -->
            <main class="lg:col-span-9 relative">
                
                <!-- PANE 1: Dashboard Overview -->
                <div id="dash-overview" class="dash-pane active space-y-8">
                    
                    <!-- KPI Cards Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="saas-card p-5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-[var(--color-text-muted)]">کاربران فعال کل</span>
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                    <i class="ph-bold ph-users"></i>
                                </div>
                            </div>
                            <b class="text-2xl font-black text-[var(--color-text)] block mb-1" id="admin-total-users">--</b>
                        </div>

                        <div class="saas-card p-5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-[var(--color-text-muted)]">سرورهای آنلاین</span>
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                    <i class="ph-bold ph-hard-drives"></i>
                                </div>
                            </div>
                            <b class="text-2xl font-black text-[var(--color-text)] block mb-1" id="admin-active-servers">--</b>
                        </div>

                        <div class="saas-card p-5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-[var(--color-text-muted)]">درآمد امروز</span>
                                <div class="w-8 h-8 rounded-lg bg-orange-50 text-[var(--color-primary)] flex items-center justify-center font-bold">
                                    <i class="ph-bold ph-currency-dollar"></i>
                                </div>
                            </div>
                            <b class="text-2xl font-black text-[var(--color-text)] block mb-1" id="admin-today-revenue">--</b>
                        </div>

                        <div class="saas-card p-5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-[var(--color-text-muted)]">مصرف شبکه</span>
                                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                                    <i class="ph-bold ph-chart-bar"></i>
                                </div>
                            </div>
                            <b class="text-2xl font-black text-[var(--color-text)] block mb-1" id="admin-network-load">--</b>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Admin Script Interaction Logic -->
    <script src="admin-script.js"></script>
    @include('rozaneh.admin.partials.scripts')
</body>
</html>