<!-- SERVICES TAB CONTENT ("اشتراک‌های من") -->
<div id="pane-services" class="dash-pane hidden space-y-5">

    <!-- Tab Header -->
    <div class="flex items-center gap-2 pb-3 border-b border-gray-200/60">
        <span class="text-xl">⚡</span>
        <h3 class="text-base font-black text-gray-900">اشتراک‌های من</h3>
    </div>

    <!-- Active Subscription Card -->
    <div class="rounded-3xl bg-white border border-orange-200 shadow-sm overflow-hidden">

        <!-- Top Status Bar -->
        <div class="flex items-center justify-between px-5 py-3 bg-orange-50 border-b border-orange-100">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-black text-emerald-700">فعال · نامحدود</span>
            </div>
            <code class="text-[11px] font-mono text-gray-500 bg-white px-2 py-0.5 rounded-lg border border-gray-200" dir="ltr">8zpxrcfksw2q57lk</code>
        </div>

        <!-- Card Body -->
        <div class="p-5 space-y-4">

            <!-- Plan Name & Location -->
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center flex-shrink-0 shadow-sm shadow-orange-200">
                    <i class="ph-bold ph-user-circle text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-black text-gray-900" dir="ltr">tawana</p>
                    <p class="text-xs text-gray-400 mt-0.5">آخرین ورود: ۱ اوت ۲۰۲۶، ۰۲:۳۲</p>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-3 rounded-2xl bg-gray-50 border border-gray-100">
                    <p class="text-[10px] text-gray-400 font-bold mb-1">مصرف</p>
                    <p class="text-sm font-black text-orange-600 font-mono" dir="ltr">17.07 MB</p>
                </div>
                <div class="p-3 rounded-2xl bg-gray-50 border border-gray-100">
                    <p class="text-[10px] text-gray-400 font-bold mb-1">حجم کل</p>
                    <p class="text-xl font-black text-gray-700">∞</p>
                </div>
                <div class="p-3 rounded-2xl bg-gray-50 border border-gray-100">
                    <p class="text-[10px] text-gray-400 font-bold mb-1">آخرین اتصال</p>
                    <p class="text-[10px] font-black text-gray-700 font-mono" dir="ltr">08/01 02:32</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 pt-1">
                <button onclick="switchTab('wallet')" class="flex-1 py-2.5 rounded-2xl bg-[var(--color-primary)] text-white text-xs font-black hover:opacity-90 transition-opacity cursor-pointer">
                    تمدید اشتراک
                </button>
                <button onclick="navigator.clipboard.writeText('https://panel.abbasiusd.com:2096/sub/8zpxrcfksw2q57lk'); alert('✓ لینک SUB کپی شد');" class="flex-1 py-2.5 rounded-2xl bg-orange-50 text-orange-800 border border-orange-200 text-xs font-black hover:bg-orange-100 transition-colors cursor-pointer">
                    کپی لینک SUB
                </button>
            </div>
        </div>
    </div>


    <!-- Config Protocols -->
    <div class="rounded-3xl bg-white border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="text-xs font-black text-gray-700 flex items-center gap-1.5">
                <span>🔗</span>
                <span>کانفیگ‌های اتصال</span>
            </p>
            <button onclick="navigator.clipboard.writeText('vless://8zpxrcfksw2q57lk@de.rozaneh.net:443?security=reality&type=tcp#VlessTCPTR-tawana\nwireguard://8zpxrcfksw2q57lk@de.rozaneh.net:51820#WireGuardwire-tawana'); alert('✓ همه کانفیگ‌ها کپی شدند');" class="text-[10px] font-black text-gray-500 hover:text-gray-700 cursor-pointer transition-colors">
                کپی همه
            </button>
        </div>

        <div class="divide-y divide-gray-100">
            <!-- VLESS -->
            <div class="flex items-center justify-between px-5 py-3.5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-orange-400 to-amber-500 text-white flex items-center justify-center text-sm">⚡</div>
                    <div>
                        <p class="text-xs font-black text-gray-900">VLESS TCP REALITY</p>
                        <p class="text-[10px] text-gray-400 font-mono" dir="ltr">VlessTCPTR-tawana</p>
                    </div>
                </div>
                <button onclick="navigator.clipboard.writeText('vless://8zpxrcfksw2q57lk@de.rozaneh.net:443?security=reality&type=tcp#VlessTCPTR-tawana'); alert('✓ کپی شد');" class="p-2 rounded-xl bg-gray-50 hover:bg-orange-50 border border-gray-200 hover:border-orange-200 transition-colors cursor-pointer">
                    <i class="ph-bold ph-copy text-sm text-gray-600 hover:text-orange-600"></i>
                </button>
            </div>

            <!-- WireGuard -->
            <div class="flex items-center justify-between px-5 py-3.5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-400 to-blue-500 text-white flex items-center justify-center text-sm">🛡️</div>
                    <div>
                        <p class="text-xs font-black text-gray-900">WireGuard</p>
                        <p class="text-[10px] text-gray-400 font-mono" dir="ltr">WireGuardwire-tawana</p>
                    </div>
                </div>
                <button onclick="navigator.clipboard.writeText('wireguard://8zpxrcfksw2q57lk@de.rozaneh.net:51820#WireGuardwire-tawana'); alert('✓ کپی شد');" class="p-2 rounded-xl bg-gray-50 hover:bg-sky-50 border border-gray-200 hover:border-sky-200 transition-colors cursor-pointer">
                    <i class="ph-bold ph-copy text-sm text-gray-600 hover:text-sky-600"></i>
                </button>
            </div>

            <!-- MTProto -->
            <div class="flex items-center justify-between px-5 py-3.5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center text-sm">✈️</div>
                    <div>
                        <p class="text-xs font-black text-gray-900">پروکسی تلگرام (MTProto)</p>
                        <p class="text-[10px] text-gray-400 font-mono" dir="ltr">MTProtoFAKETLSLink 3</p>
                    </div>
                </div>
                <a href="https://t.me/proxy?server=de.rozaneh.net&port=443&secret=ee8zpxrcfksw2q57lk777777777777777777" target="_blank" class="p-2 rounded-xl bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-colors cursor-pointer">
                    <i class="ph-bold ph-paper-plane-tilt text-sm text-blue-600"></i>
                </a>
            </div>
        </div>
    </div>

</div>
