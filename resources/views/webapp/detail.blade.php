<!-- resources/views/webapp/detail.blade.php -->
@extends('webapp.layout')

@section('content')
    <div class="mb-6 flex items-center gap-3 relative z-10">
        <a href="{{ route('webapp.index', ['tg_id' => request('tg_id')]) }}" class="w-10 h-10 glass-panel rounded-full flex items-center justify-center text-white active:scale-90 transition shadow-[0_0_15px_rgba(255,255,255,0.05)]">
            <i class="ri-arrow-right-line text-xl"></i>
        </a>
        <h1 class="text-xl font-bold text-white tracking-wide">وضعیت اشتراک</h1>
    </div>

    <!-- Main Status Card -->
    <div class="glass-panel p-6 rounded-[2rem] relative overflow-hidden shadow-2xl border border-white/10 mt-4">
        <!-- Background Glows -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-500/20 blur-3xl rounded-full"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-purple-500/20 blur-3xl rounded-full"></div>
        
        <div class="relative z-10 flex flex-col items-center mb-6">
            <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-blue-500 to-purple-600 p-1 mb-4 shadow-[0_0_30px_rgba(59,130,246,0.3)]">
                <div class="w-full h-full bg-[#09090b] rounded-full flex items-center justify-center backdrop-blur-sm relative overflow-hidden">
                    <i class="ri-radar-line text-4xl text-white animate-pulse"></i>
                    <!-- Scanline effect -->
                    <div class="absolute inset-0 bg-gradient-to-b from-transparent via-blue-500/20 to-transparent w-full h-full animate-[scan_2s_ease-in-out_infinite]"></div>
                </div>
            </div>
            <h2 class="text-2xl font-black text-white tracking-wide">{{ $order->plan->name ?? 'سرویس ویژه' }}</h2>
            <div class="flex items-center gap-1 mt-2 text-green-400 bg-green-400/10 px-3 py-1 rounded-full border border-green-400/20 shadow-[0_0_10px_rgba(74,222,128,0.2)]">
                <span class="relative flex h-2 w-2 mr-1">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <span class="text-xs font-bold tracking-widest">متصل به X-Ray</span>
            </div>
        </div>

        @php
            // Mock data for X-Ray usage until panel is connected
            $totalVolume = $order->plan->volume_gb ?? 50;
            $usedVolume = $totalVolume * 0.35; // Mock 35% used
            $usagePercent = ($usedVolume / $totalVolume) * 100;
            
            $totalDays = 30; // Assuming 1 month
            $daysLeft = max(0, \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($order->expires_at), false));
            $daysPercent = ($daysLeft / $totalDays) * 100;
        @endphp

        <!-- Data Usage (X-Ray Stats) -->
        <div class="space-y-6 relative z-10 mt-8 bg-black/20 p-5 rounded-2xl border border-white/5">
            
            <!-- Traffic Progress -->
            <div>
                <div class="flex justify-between items-end mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-400">
                            <i class="ri-pie-chart-2-fill"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">مصرف ترافیک</p>
                            <p class="text-sm text-white font-bold font-inter mt-0.5">{{ number_format($usedVolume, 1) }} <span class="text-xs text-gray-500">از</span> {{ $totalVolume }} GB</p>
                        </div>
                    </div>
                    <span class="text-blue-400 font-bold font-inter text-sm">{{ round($usagePercent) }}%</span>
                </div>
                <div class="h-2 w-full bg-gray-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-600 to-blue-400 rounded-full relative" style="width: {{ $usagePercent }}%">
                        <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
                    </div>
                </div>
            </div>

            <!-- Expiration Progress -->
            <div>
                <div class="flex justify-between items-end mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center text-purple-400">
                            <i class="ri-timer-flash-fill"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">زمان باقی‌مانده</p>
                            <p class="text-sm text-white font-bold font-inter mt-0.5">{{ $daysLeft }} <span class="text-xs text-gray-500">روز</span></p>
                        </div>
                    </div>
                    <span class="text-purple-400 font-bold font-inter text-sm">{{ \Carbon\Carbon::parse($order->expires_at)->format('Y/m/d') }}</span>
                </div>
                <div class="h-2 w-full bg-gray-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-purple-600 to-purple-400 rounded-full relative" style="width: {{ min(100, $daysPercent) }}%">
                        <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
                    </div>
                </div>
            </div>
            
            <!-- Technical Details -->
            <div class="pt-4 mt-2 border-t border-white/5 flex justify-between items-center">
                <div class="flex flex-col">
                    <span class="text-[10px] text-gray-500">شناسه سرویس (Xray)</span>
                    <span class="text-gray-300 font-inter text-xs font-medium mt-1">{{ $order->panel_username ?? 'USER-'.$order->id }}</span>
                </div>
                <div class="flex flex-col text-left">
                    <span class="text-[10px] text-gray-500">وضعیت اتصال</span>
                    <span class="text-green-400 text-xs font-bold mt-1">Online</span>
                </div>
            </div>
        </div>

        <!-- Config Details -->
        <div class="mt-6 relative z-10">
            <div class="flex justify-between items-center mb-2">
                <p class="text-xs text-gray-400 font-medium">کد اتصال (vless/vmess):</p>
            </div>
            <div class="relative group">
                <textarea id="configLink" readonly class="w-full bg-black/40 text-gray-300 text-[11px] font-inter p-4 rounded-xl border border-white/10 outline-none resize-none h-24 shadow-inner custom-scrollbar" style="line-height: 1.6;">{{ trim(preg_replace('/^.*?(http|vless|vmess|trojan|ss)(:\/\/[^\s]+).*$/is', '$1$2', $order->config_details)) ?: $order->config_details }}</textarea>
                
                <button onclick="copyConfig()" id="copyBtn" class="absolute bottom-3 left-3 bg-blue-600 hover:bg-blue-500 text-white backdrop-blur-md px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all active:scale-95 shadow-[0_0_15px_rgba(37,99,235,0.4)] border border-blue-500/50">
                    <i class="ri-file-copy-line text-sm"></i> <span>کپی و اتصال</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Additional Animations -->
    <style>
        @keyframes scan {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100%); }
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); opacity: 0; }
            50% { opacity: 0.5; }
            100% { transform: translateX(100%); opacity: 0; }
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
    </style>

    <script>
        function copyConfig() {
            const copyText = document.getElementById("configLink");
            const btn = document.getElementById("copyBtn");
            const originalHtml = btn.innerHTML;
            const originalClasses = btn.className;
            
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            
            navigator.clipboard.writeText(copyText.value).then(() => {
                btn.innerHTML = '<i class="ri-check-double-line text-sm"></i> <span>کپی شد!</span>';
                btn.className = "absolute bottom-3 left-3 bg-green-500 text-white backdrop-blur-md px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all shadow-[0_0_15px_rgba(34,197,94,0.4)] border border-green-400";
                
                if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.HapticFeedback) {
                    window.Telegram.WebApp.HapticFeedback.notificationOccurred('success');
                }
                
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.className = originalClasses;
                }, 2000);
            });
        }
    </script>
@endsection
