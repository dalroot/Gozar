<x-filament-widgets::widget class="fi-filament-info-widget">
    <x-filament::section class="overflow-hidden !p-0 border-none shadow-xl bg-gradient-to-br from-slate-900 via-indigo-950/70 to-slate-950 text-white rounded-2xl border border-indigo-500/20">
        <div class="relative p-6 sm:p-7">
            <!-- Background Glow Decoration -->
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-72 h-72 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-60 h-60 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 relative z-10">
                <div class="flex items-center gap-x-5">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-600 to-rose-500 shadow-lg shadow-indigo-500/30 p-1">
                        <div class="w-full h-full bg-slate-950 rounded-xl flex items-center justify-center">
                            <span class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-rose-400">G</span>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-2xl font-black tracking-tight text-white">سامانه هوشمند <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-rose-400">Gozar (گذر)</span></h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">نسخه ۲.۵ (Gozar Engine)</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1">پلتفرم یکپارچه مدیریت سرویس‌های اینترنت آزاد، ربات هوشمند فروش و مانیتورینگ سلامت سرورها</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="/admin/servers" class="flex items-center gap-2 px-3.5 py-2 bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 rounded-xl transition-all text-xs font-bold text-slate-200 shadow-sm">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                        <span>سرورها و نودها</span>
                    </a>

                    <a href="/admin/theme-settings" class="flex items-center gap-2 px-3.5 py-2 bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 rounded-xl transition-all text-xs font-bold text-slate-200 shadow-sm">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>تنظیمات ربات و سیستم</span>
                    </a>

                    <a href="https://github.com/dalroot/Gozar" target="_blank" class="flex items-center gap-2 px-3.5 py-2 bg-indigo-600/80 hover:bg-indigo-600 border border-indigo-500 rounded-xl transition-all text-xs font-bold text-white shadow-lg shadow-indigo-500/25">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                        <span>مخزن گیت‌هاب Gozar</span>
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
