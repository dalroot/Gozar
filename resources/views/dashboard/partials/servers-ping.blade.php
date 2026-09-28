<!-- SERVERS TAB CONTENT -->
<div id="pane-servers" class="dash-pane hidden space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-black text-gray-900 flex items-center gap-2">
                <span class="text-2xl">🌐</span>
                <span>وضعیت لایو سرورهای روزنه</span>
            </h3>
            <p class="text-xs font-bold text-gray-500 mt-1">مانیتورینگ لحظه‌ای تاخیر (Ping) و پایداری سرورها</p>
        </div>
    </div>

    <!-- Servers Table -->
    <div class="bg-white rounded-[32px] border border-[#FED7AA] shadow-[0_10px_25px_-10px_rgba(234,88,12,0.05)] overflow-hidden">
        <table dir="rtl" class="w-full text-right text-xs">
            <thead>
                <tr class="bg-orange-50/70 text-gray-800 font-black border-b border-orange-100">
                    <th class="p-4">کشور / سرور</th>
                    <th class="p-4">پروتکل پشتیبانی‌شده</th>
                    <th class="p-4">تاخیر (Ping)</th>
                    <th class="p-4">وضعیت شبکه</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-bold text-gray-800">
                <tr>
                    <td class="p-4 flex items-center gap-2">🇩🇪 <span>آلمان (فرانکفورت - DE-01)</span></td>
                    <td class="p-4 font-mono text-gray-600">VLESS + REALITY</td>
                    <td class="p-4 font-mono text-emerald-600 font-black" dir="ltr">38 ms</td>
                    <td class="p-4"><span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black">عالی ✓</span></td>
                </tr>
                <tr>
                    <td class="p-4 flex items-center gap-2">🇳🇱 <span>هلند (آمستردام - NL-02)</span></td>
                    <td class="p-4 font-mono text-gray-600">VMESS + gRPC</td>
                    <td class="p-4 font-mono text-emerald-600 font-black" dir="ltr">44 ms</td>
                    <td class="p-4"><span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black">عالی ✓</span></td>
                </tr>
                <tr>
                    <td class="p-4 flex items-center gap-2">🇫🇮 <span>فنلاند (هلسینکی - FI-01)</span></td>
                    <td class="p-4 font-mono text-gray-600">Trojan + TLS</td>
                    <td class="p-4 font-mono text-emerald-600 font-black" dir="ltr">41 ms</td>
                    <td class="p-4"><span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black">عالی ✓</span></td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
