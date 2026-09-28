<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @include('rozaneh.admin.partials.head', ['title' => 'مدیریت کاربران'])
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
                
                <!-- PANE 2: Users Management -->
                <div id="dash-users" class="dash-pane active space-y-6">
                    <div class="saas-card p-6">
                        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
                            <div>
                                <h3 class="font-bold text-xl text-[var(--color-text)]">مدیریت کاربران و اشتراک‌ها</h3>
                                <p class="text-xs text-[var(--color-text-muted)] font-medium">لیست کل مشترکین روزنه، وضعیت تمدید و کانفیگ‌ها</p>
                            </div>
                            <button class="px-4 py-2 rounded-xl bg-[var(--color-primary)] text-white text-xs font-bold shadow-sm hover:opacity-90 flex items-center gap-2">
                                <i class="ph-bold ph-plus"></i> کاربر جدید
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-right border-collapse">
                                <thead>
                                    <tr class="border-b border-[var(--color-border)] text-xs text-[var(--color-text-muted)]">
                                        <th class="py-3 px-4">کاربر</th>
                                        <th class="py-3 px-4">شناسه / شماره</th>
                                        <th class="py-3 px-4">پلن فعال</th>
                                        <th class="py-3 px-4">وضعیت</th>
                                        <th class="py-3 px-4 text-left">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm font-medium">
                                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-4">کاربر ۱۱۲۲</td>
                                        <td class="py-3 px-4 font-en text-xs">09120001122</td>
                                        <td class="py-3 px-4"><span class="badge badge-accent">پرو ۳ ماهه</span></td>
                                        <td class="py-3 px-4"><span class="text-emerald-600 font-bold text-xs"><i class="ph-fill ph-check-circle"></i> فعال</span></td>
                                        <td class="py-3 px-4 text-left">
                                            <button class="p-1.5 rounded-lg bg-gray-100 text-gray-600 hover:text-[var(--color-primary)]"><i class="ph-bold ph-pencil-simple"></i></button>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-4">مهمان ۴۸۲</td>
                                        <td class="py-3 px-4 font-en text-xs">09351234567</td>
                                        <td class="py-3 px-4"><span class="badge" style="background:#f3f4f6;">رایگان ۲ گیگ</span></td>
                                        <td class="py-3 px-4"><span class="text-red-500 font-bold text-xs"><i class="ph-fill ph-x-circle"></i> منقضی</span></td>
                                        <td class="py-3 px-4 text-left">
                                            <button class="p-1.5 rounded-lg bg-gray-100 text-gray-600 hover:text-[var(--color-primary)]"><i class="ph-bold ph-pencil-simple"></i></button>
                                        </td>
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