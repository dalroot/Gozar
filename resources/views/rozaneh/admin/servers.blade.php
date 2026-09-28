<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @include('rozaneh.admin.partials.head', ['title' => 'مدیریت سرورها'])
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
                
                <!-- PANE 3: Servers -->
                <div id="dash-servers" class="dash-pane active space-y-6">
                    <div class="saas-card p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="font-bold text-xl text-[var(--color-text)]">وضعیت نودهای سرور</h3>
                                <p class="text-xs text-[var(--color-text-muted)] font-medium">مانیتورینگ و مدیریت سرورهای اتصال</p>
                            </div>
                            <button class="px-4 py-2 rounded-xl bg-emerald-500 text-white text-xs font-bold shadow-sm hover:opacity-90 flex items-center gap-2">
                                <i class="ph-bold ph-plus"></i> افزودن نود
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl border-2 border-emerald-200 bg-emerald-50 relative">
                                <div class="absolute top-4 left-4 w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-3xl">🇩🇪</span>
                                    <div>
                                        <h4 class="font-bold text-emerald-900">فرانکفورت ۰۱ (آلمان)</h4>
                                        <span class="text-xs font-en text-emerald-700">185.24.8.12</span>
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs font-bold text-emerald-800">
                                    <span>ظرفیت: ۴۲٪</span>
                                    <span>پینگ: ۲۲ms</span>
                                </div>
                            </div>
                            <div class="p-4 rounded-xl border-2 border-emerald-200 bg-emerald-50 relative">
                                <div class="absolute top-4 left-4 w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-3xl">🇳🇱</span>
                                    <div>
                                        <h4 class="font-bold text-emerald-900">آمستردام ۰۲ (هلند)</h4>
                                        <span class="text-xs font-en text-emerald-700">91.16.2.45</span>
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs font-bold text-emerald-800">
                                    <span>ظرفیت: ۳۶٪</span>
                                    <span>پینگ: ۲۸ms</span>
                                </div>
                            </div>
                            <div class="p-4 rounded-xl border-2 border-red-200 bg-red-50 relative">
                                <div class="absolute top-4 left-4 w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-3xl">🇫🇷</span>
                                    <div>
                                        <h4 class="font-bold text-red-900">پاریس ۰۱ (فرانسه)</h4>
                                        <span class="text-xs font-en text-red-700">54.12.9.8</span>
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs font-bold text-red-800">
                                    <span>آفلاین</span>
                                    <span>پینگ: --</span>
                                </div>
                            </div>
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