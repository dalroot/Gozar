<!-- APP DOWNLOADS TAB CONTENT (App Store & Google Play Style) -->
<div id="pane-apps" class="dash-pane hidden space-y-7">

    <!-- Store Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200/80">
        <div>
            <div class="flex items-center gap-2 text-xs font-black text-orange-600 bg-orange-50 border border-orange-200 px-3 py-1 rounded-full w-fit mb-2">
                <i class="ph-bold ph-storefront"></i>
                <span>استور نرم‌افزارهای روزنه</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-gray-900">برنامه‌های اتصال تمام دستگاه‌ها</h3>
            <p class="text-xs font-bold text-gray-500 mt-1">دانلود مستقیم، اپ‌استور اپل، گوگل‌پلی و گیت‌هاب با یک کلیک</p>
        </div>

        <!-- Platform Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 bg-white p-1.5 rounded-2xl border border-[#FED7AA] shadow-sm text-xs font-black">
            <button onclick="filterAppPlatform('all')" id="app-filter-all" class="app-filter-btn active px-3.5 py-2 rounded-xl bg-[var(--color-primary)] text-white shadow-sm transition-all flex items-center gap-1.5">
                <i class="ph-bold ph-squares-four"></i>
                <span>همه دستگاه‌ها</span>
            </button>
            <button onclick="filterAppPlatform('android')" id="app-filter-android" class="app-filter-btn px-3.5 py-2 rounded-xl text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-all flex items-center gap-1.5">
                <i class="ph-bold ph-android-logo text-emerald-600"></i>
                <span>اندروید</span>
            </button>
            <button onclick="filterAppPlatform('ios')" id="app-filter-ios" class="app-filter-btn px-3.5 py-2 rounded-xl text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-all flex items-center gap-1.5">
                <i class="ph-bold ph-apple-logo text-gray-900"></i>
                <span>آیفون (iOS)</span>
            </button>
            <button onclick="filterAppPlatform('windows')" id="app-filter-windows" class="app-filter-btn px-3.5 py-2 rounded-xl text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-all flex items-center gap-1.5">
                <i class="ph-bold ph-windows-logo text-sky-600"></i>
                <span>ویندوز</span>
            </button>
        </div>
    </div>

    <!-- Store Apps Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

        <!-- 1. v2rayNG (Android) -->
        <div class="app-card-item platform-android p-6 rounded-[28px] bg-white border border-[#FED7AA] shadow-[0_10px_30px_-10px_rgba(234,88,12,0.06)] hover:shadow-xl hover:border-orange-400 transition-all flex flex-col justify-between space-y-4">
            <div>
                <!-- App Store Header Layout -->
                <div class="flex items-start gap-4 mb-3">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center text-2xl font-bold shadow-md shadow-emerald-500/20 flex-shrink-0">
                        <i class="ph-bold ph-android-logo"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-base font-black text-gray-900 truncate">v2rayNG</h4>
                        <span class="block text-[11px] font-bold text-gray-500 truncate">توسعه‌دهنده: 233boy</span>
                        <div class="flex items-center gap-1.5 mt-1 text-[11px] font-extrabold text-amber-500">
                            <span>★ 4.9</span>
                            <span class="text-gray-300">•</span>
                            <span class="text-gray-500 font-normal">۱۰۰ هزار+ نصب</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs font-bold text-gray-600 leading-relaxed bg-orange-50/50 p-3 rounded-2xl border border-orange-100/80 mb-3">
                    محبوب‌ترین نرم‌افزار رسمی اندروید جهت اتصال به کانفیگ‌های VLESS، REALITY و gRPC با پینگ فوق‌العاده پایین.
                </p>

                <!-- Feature Chips -->
                <div class="flex flex-wrap gap-1.5 text-[10px] font-black">
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">پیشنهاد اصلی</span>
                    <span class="px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 border border-orange-200">REALITY</span>
                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700">رایگان</span>
                </div>
            </div>

            <!-- Download Actions (App Store / Play Store Buttons) -->
            <div class="space-y-2 pt-2 border-t border-gray-100">
                <a href="https://github.com/233boy/v2rayNG/releases" target="_blank" class="w-full py-3 rounded-2xl bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white text-xs font-black transition-all flex items-center justify-center gap-2 shadow-md shadow-orange-500/20">
                    <i class="ph-bold ph-download-simple text-base"></i>
                    <span>دانلود مستقیم فایل APK</span>
                </a>
                <div class="grid grid-cols-2 gap-2 text-[11px] font-bold">
                    <a href="https://play.google.com/store/apps/details?id=com.v2ray.ang" target="_blank" class="py-2 rounded-xl bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-800 flex items-center justify-center gap-1.5">
                        <i class="ph-bold ph-google-play-logo text-emerald-600 text-sm"></i>
                        <span>گوگل پلی</span>
                    </a>
                    <a href="https://github.com/233boy/v2rayNG/releases" target="_blank" class="py-2 rounded-xl bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-800 flex items-center justify-center gap-1.5">
                        <i class="ph-bold ph-github-logo text-gray-900 text-sm"></i>
                        <span>گیت‌هاب</span>
                    </a>
                </div>
            </div>
        </div>


        <!-- 2. Streisand (iOS / iPhone) -->
        <div class="app-card-item platform-ios p-6 rounded-[28px] bg-white border border-[#FED7AA] shadow-[0_10px_30px_-10px_rgba(234,88,12,0.06)] hover:shadow-xl hover:border-orange-400 transition-all flex flex-col justify-between space-y-4">
            <div>
                <!-- App Store Header Layout -->
                <div class="flex items-start gap-4 mb-3">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-gray-800 to-black text-white flex items-center justify-center text-2xl font-bold shadow-md shadow-gray-900/20 flex-shrink-0">
                        <i class="ph-bold ph-apple-logo"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-base font-black text-gray-900 truncate">Streisand</h4>
                        <span class="block text-[11px] font-bold text-gray-500 truncate">اپل استور رسمی (iOS)</span>
                        <div class="flex items-center gap-1.5 mt-1 text-[11px] font-extrabold text-amber-500">
                            <span>★ 4.8</span>
                            <span class="text-gray-300">•</span>
                            <span class="text-gray-500 font-normal">مخصوص آیفون و آیپد</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs font-bold text-gray-600 leading-relaxed bg-orange-50/50 p-3 rounded-2xl border border-orange-100/80 mb-3">
                    بهترین و باکیفیت‌ترین نرم‌افزار آیفون با پشتیبانی از کدهای سابسکرایپشن روزنه و اسکن سریع کد QR.
                </p>

                <!-- Feature Chips -->
                <div class="flex flex-wrap gap-1.5 text-[10px] font-black">
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">ویژه آیفون</span>
                    <span class="px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 border border-orange-200">بدون تبلیغات</span>
                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700">App Store</span>
                </div>
            </div>

            <!-- Download Action Button (Apple GET Style) -->
            <div class="space-y-2 pt-2 border-t border-gray-100">
                <a href="https://apps.apple.com/app/streisand/id6450534064" target="_blank" class="w-full py-3 rounded-2xl bg-gray-900 hover:bg-black text-white text-xs font-black transition-all flex items-center justify-center gap-2 shadow-md">
                    <i class="ph-bold ph-apple-logo text-base"></i>
                    <span>دریافت مستقیم از App Store</span>
                </a>
            </div>
        </div>


        <!-- 3. v2rayN (Windows) -->
        <div class="app-card-item platform-windows p-6 rounded-[28px] bg-white border border-[#FED7AA] shadow-[0_10px_30px_-10px_rgba(234,88,12,0.06)] hover:shadow-xl hover:border-orange-400 transition-all flex flex-col justify-between space-y-4">
            <div>
                <!-- App Store Header Layout -->
                <div class="flex items-start gap-4 mb-3">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 text-white flex items-center justify-center text-2xl font-bold shadow-md shadow-sky-500/20 flex-shrink-0">
                        <i class="ph-bold ph-windows-logo"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-base font-black text-gray-900 truncate">v2rayN</h4>
                        <span class="block text-[11px] font-bold text-gray-500 truncate">نسخه ویندوز (Windows 10 / 11)</span>
                        <div class="flex items-center gap-1.5 mt-1 text-[11px] font-extrabold text-amber-500">
                            <span>★ 4.9</span>
                            <span class="text-gray-300">•</span>
                            <span class="text-gray-500 font-normal">کامپیوتر و لپ‌تاپ</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs font-bold text-gray-600 leading-relaxed bg-orange-50/50 p-3 rounded-2xl border border-orange-100/80 mb-3">
                    کلاینت قدرتمند و حرفه‌ای ویندوز جهت اتصال به هسته VLESS و ترافیک کلی سیستم با قابلیت پروکسی کل سیستم.
                </p>

                <!-- Feature Chips -->
                <div class="flex flex-wrap gap-1.5 text-[10px] font-black">
                    <span class="px-2.5 py-1 rounded-lg bg-sky-50 text-sky-700 border border-sky-200">مخصوص ویندوز</span>
                    <span class="px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 border border-orange-200">System Proxy</span>
                </div>
            </div>

            <!-- Download Action Button -->
            <div class="space-y-2 pt-2 border-t border-gray-100">
                <a href="https://github.com/233boy/v2rayN/releases" target="_blank" class="w-full py-3 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-black transition-all flex items-center justify-center gap-2 shadow-md shadow-sky-600/20">
                    <i class="ph-bold ph-download-simple text-base"></i>
                    <span>دانلود مستقیم نسخه ویندوز (ZIP)</span>
                </a>
            </div>
        </div>


        <!-- 4. FoXray (iOS / iPhone) -->
        <div class="app-card-item platform-ios p-6 rounded-[28px] bg-white border border-[#FED7AA] shadow-[0_10px_30px_-10px_rgba(234,88,12,0.06)] hover:shadow-xl hover:border-orange-400 transition-all flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-start gap-4 mb-3">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center text-2xl font-bold shadow-md flex-shrink-0">
                        <i class="ph-bold ph-lightning"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-base font-black text-gray-900 truncate">FoXray</h4>
                        <span class="block text-[11px] font-bold text-gray-500 truncate">آیفون و آیپد (iOS)</span>
                        <div class="flex items-center gap-1.5 mt-1 text-[11px] font-extrabold text-amber-500">
                            <span>★ 4.7</span>
                            <span class="text-gray-300">•</span>
                            <span class="text-gray-500 font-normal">اسکن سریع QR</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs font-bold text-gray-600 leading-relaxed bg-orange-50/50 p-3 rounded-2xl border border-orange-100/80 mb-3">
                    نرم‌افزار فوق‌العاده باکیفیت و سریع iOS با موتور قدرتمند Xray جهت اتصال آنی.
                </p>
            </div>

            <div class="space-y-2 pt-2 border-t border-gray-100">
                <a href="https://apps.apple.com/app/foxray/id6448892567" target="_blank" class="w-full py-3 rounded-2xl bg-gray-900 hover:bg-black text-white text-xs font-black transition-all flex items-center justify-center gap-2 shadow-md">
                    <i class="ph-bold ph-apple-logo text-base"></i>
                    <span>دریافت از App Store</span>
                </a>
            </div>
        </div>


        <!-- 5. NekoBox (Android) -->
        <div class="app-card-item platform-android p-6 rounded-[28px] bg-white border border-[#FED7AA] shadow-[0_10px_30px_-10px_rgba(234,88,12,0.06)] hover:shadow-xl hover:border-orange-400 transition-all flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-start gap-4 mb-3">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white flex items-center justify-center text-2xl font-bold shadow-md flex-shrink-0">
                        <i class="ph-bold ph-cube"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-base font-black text-gray-900 truncate">NekoBox</h4>
                        <span class="block text-[11px] font-bold text-gray-500 truncate">توسعه‌دهنده: MatsuriDayo</span>
                        <div class="flex items-center gap-1.5 mt-1 text-[11px] font-extrabold text-amber-500">
                            <span>★ 4.9</span>
                            <span class="text-gray-300">•</span>
                            <span class="text-gray-500 font-normal">پایداری بالا</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs font-bold text-gray-600 leading-relaxed bg-orange-50/50 p-3 rounded-2xl border border-orange-100/80 mb-3">
                    کلاینت پیشرفته سنگ‌باکس اندروید با ابزارهای تست پینگ و انتخاب خودکار سریع‌ترین سرور.
                </p>
            </div>

            <div class="space-y-2 pt-2 border-t border-gray-100">
                <a href="https://github.com/MatsuriDayo/NekoBoxForAndroid/releases" target="_blank" class="w-full py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-black transition-all flex items-center justify-center gap-2 shadow-md shadow-purple-600/20">
                    <i class="ph-bold ph-download-simple text-base"></i>
                    <span>دانلود مستقیم NekoBox APK</span>
                </a>
            </div>
        </div>

    </div>

</div>

<!-- Filter JavaScript -->
<script>
    function filterAppPlatform(platform) {
        const filterBtns = document.querySelectorAll('.app-filter-btn');
        const appItems = document.querySelectorAll('.app-card-item');

        filterBtns.forEach(btn => {
            if (btn.id === 'app-filter-' + platform) {
                btn.classList.add('bg-[var(--color-primary)]', 'text-white', 'shadow-sm');
                btn.classList.remove('text-gray-700', 'hover:bg-orange-50');
            } else {
                btn.classList.remove('bg-[var(--color-primary)]', 'text-white', 'shadow-sm');
                btn.classList.add('text-gray-700');
            }
        });

        appItems.forEach(item => {
            if (platform === 'all' || item.classList.contains('platform-' + platform)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
</script>
