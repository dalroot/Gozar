<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @include('rozaneh.admin.partials.head', ['title' => 'کانفیگ‌ها & پروتکل‌ها'])
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
                
                <!-- PANE 4: Config Generator -->
                <div id="dash-configs" class="dash-pane active space-y-6">
                    <div class="saas-card p-6 max-w-2xl mx-auto">
                        <div class="text-center mb-6">
                            <div class="w-12 h-12 mx-auto rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl mb-3">
                                <i class="ph-bold ph-key"></i>
                            </div>
                            <h3 class="font-bold text-xl text-[var(--color-text)]">مولد کانفیگ V2Ray</h3>
                            <p class="text-xs text-[var(--color-text-muted)] font-medium">ساخت سریع کانفیگ اختصاصی برای کاربران</p>
                        </div>
                        <form class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">پروتکل</label>
                                <select class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-[var(--color-primary)] text-sm font-en">
                                    <option>VLESS (Reality)</option>
                                    <option>VMess (WS)</option>
                                    <option>Trojan</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">حجم (گیگابایت)</label>
                                    <input type="number" value="50" class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-[var(--color-primary)] text-sm font-en">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">مدت زمان (روز)</label>
                                    <input type="number" value="30" class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-[var(--color-primary)] text-sm font-en">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">نام کاربری / شناسه</label>
                                <input type="text" placeholder="مثال: user_1234" class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-[var(--color-primary)] text-sm font-en">
                            </div>
                            <button type="button" class="w-full py-3 rounded-xl bg-amber-500 text-white font-bold shadow-sm hover:opacity-90 mt-2">
                                جنریت کانفیگ
                            </button>
                        </form>
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