<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @include('rozaneh.admin.partials.head', ['title' => 'تراکنش‌ها & مالی'])
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
                
                <!-- PANE 5: Billing -->
                <div id="dash-billing" class="dash-pane active space-y-6">
                    <div class="saas-card p-6">
                        <h3 class="font-bold text-xl text-[var(--color-text)] mb-6">تراکنش‌ها و امور مالی</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-right border-collapse">
                                <thead>
                                    <tr class="border-b border-[var(--color-border)] text-xs text-[var(--color-text-muted)]">
                                        <th class="py-3 px-4">شناسه تراکنش</th>
                                        <th class="py-3 px-4">مبلغ (تومان)</th>
                                        <th class="py-3 px-4">کاربر</th>
                                        <th class="py-3 px-4">تاریخ</th>
                                        <th class="py-3 px-4">وضعیت</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm font-medium">
                                    <tr class="border-b border-gray-100">
                                        <td class="py-3 px-4 font-en text-xs">TRX-98421</td>
                                        <td class="py-3 px-4 font-bold">۳۹۰,۰۰۰</td>
                                        <td class="py-3 px-4">کاربر #9842</td>
                                        <td class="py-3 px-4 text-xs">امروز ۱۲:۳۰</td>
                                        <td class="py-3 px-4"><span class="text-emerald-600 font-bold text-xs">موفق</span></td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="py-3 px-4 font-en text-xs">TRX-98420</td>
                                        <td class="py-3 px-4 font-bold">۱۷۵,۰۰۰</td>
                                        <td class="py-3 px-4">کاربر #1122</td>
                                        <td class="py-3 px-4 text-xs">دیروز ۱۵:۴۵</td>
                                        <td class="py-3 px-4"><span class="text-emerald-600 font-bold text-xs">موفق</span></td>
                                    </tr>
                                </tbody>
                            </table>
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