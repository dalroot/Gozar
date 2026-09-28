<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @include('rozaneh.admin.partials.head', ['title' => 'تنظیمات سیستم'])
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
                
                <!-- PANE 6: Settings -->
                <div id="dash-settings" class="dash-pane active space-y-6">
                    <div class="saas-card p-6 max-w-2xl mx-auto">
                        <h3 class="font-bold text-xl text-[var(--color-text)] mb-6">تنظیمات سیستم و ربات</h3>
                        <form class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">توکن ربات تلگرام</label>
                                <input type="password" value="123456789:ABCDEFGH-IJKLMNOP" class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 text-sm font-en">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">کلید API درگاه پرداخت (زرین‌پال)</label>
                                <input type="password" value="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 text-sm font-en">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">آدرس پنل ثانویه (X-UI)</label>
                                <input type="text" value="https://panel.rozaneh.net:2053" class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 text-sm font-en">
                            </div>
                            <button type="button" class="w-full py-3 rounded-xl bg-[var(--color-primary)] text-white font-bold shadow-sm hover:opacity-90 mt-4">
                                ذخیره تنظیمات
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