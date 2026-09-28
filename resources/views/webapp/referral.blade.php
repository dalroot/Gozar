<!-- resources/views/webapp/referral.blade.php -->
@extends('webapp.layout')

@section('content')
    <div class="mb-6 flex items-center gap-3 relative z-10">
        <h1 class="text-xl font-bold text-white tracking-wide">دعوت از دوستان</h1>
    </div>

    <!-- Referral Stats Box -->
    <div class="glass-panel p-6 rounded-3xl relative overflow-hidden shadow-2xl border border-white/10 mt-4">
        <!-- Background Glows -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-green-500/20 blur-3xl rounded-full"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-blue-500/20 blur-3xl rounded-full"></div>
        
        <div class="relative z-10 flex flex-col items-center mb-6">
            <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-green-500 to-emerald-600 p-1 mb-4 shadow-[0_0_30px_rgba(34,197,94,0.3)]">
                <div class="w-full h-full bg-[#09090b] rounded-full flex items-center justify-center backdrop-blur-sm">
                    <i class="ri-team-fill text-4xl text-white"></i>
                </div>
            </div>
            <h2 class="text-2xl font-black text-white tracking-wide">کسب درآمد با رفرال</h2>
            <p class="text-sm text-gray-400 mt-2 text-center max-w-[250px]">با دعوت دوستان خود به ربات، <span class="text-green-400 font-bold">۲۰٪</span> از مبلغ هر خرید آن‌ها را هدیه بگیرید!</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 gap-4 mt-6 relative z-10">
            <div class="bg-black/30 p-4 rounded-2xl border border-white/5 flex flex-col items-center justify-center">
                <i class="ri-user-follow-line text-blue-400 text-2xl mb-1"></i>
                <span class="text-2xl font-bold text-white font-inter">{{ $totalInvited }}</span>
                <span class="text-[10px] text-gray-500 font-medium mt-1">کل دعوت‌ها</span>
            </div>
            
            <div class="bg-black/30 p-4 rounded-2xl border border-white/5 flex flex-col items-center justify-center">
                <i class="ri-coins-line text-yellow-400 text-2xl mb-1"></i>
                <span class="text-xl font-bold text-white font-inter">{{ number_format($totalEarned) }} <span class="text-[10px] text-gray-500">T</span></span>
                <span class="text-[10px] text-gray-500 font-medium mt-1">درآمد شما</span>
            </div>
        </div>

        <!-- Invite Link -->
        <div class="mt-8 relative z-10">
            <p class="text-xs text-gray-400 mb-2 font-medium">لینک دعوت اختصاصی شما:</p>
            <div class="flex items-center gap-2">
                <div class="relative flex-1 group">
                    <input type="text" id="inviteLink" readonly value="{{ $inviteLink }}" class="w-full bg-black/40 text-gray-300 text-[11px] font-inter p-4 rounded-xl border border-white/10 outline-none shadow-inner" />
                </div>
                
                <button onclick="copyInviteLink()" id="copyBtn" class="bg-green-600 hover:bg-green-500 text-white backdrop-blur-md w-12 h-12 rounded-xl flex items-center justify-center transition-all active:scale-95 shadow-[0_0_15px_rgba(34,197,94,0.4)] border border-green-500/50">
                    <i class="ri-file-copy-line text-lg"></i>
                </button>
            </div>
        </div>

        <!-- Share Button -->
        <div class="mt-4 relative z-10">
            <a href="https://t.me/share/url?url={{ urlencode($inviteLink) }}&text={{ urlencode('سلام! من از این ربات برای دور زدن فیلترینگ استفاده می‌کنم. تو هم از طریق این لینک عضو شو و اینترنت آزاد رو تجربه کن!') }}" target="_blank" class="w-full block text-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all active:scale-95 shadow-[0_0_15px_rgba(59,130,246,0.3)] border border-blue-500/30">
                <i class="ri-share-forward-fill ml-1 align-bottom"></i> ارسال برای دوستان در تلگرام
            </a>
        </div>
    </div>

    <!-- Padding for bottom nav -->
    <div class="h-24"></div>

    <script>
        function copyInviteLink() {
            const copyText = document.getElementById("inviteLink");
            const btn = document.getElementById("copyBtn");
            const originalHtml = btn.innerHTML;
            const originalClasses = btn.className;
            
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            
            navigator.clipboard.writeText(copyText.value).then(() => {
                btn.innerHTML = '<i class="ri-check-double-line text-lg"></i>';
                btn.className = "bg-green-400 text-white backdrop-blur-md w-12 h-12 rounded-xl flex items-center justify-center transition-all shadow-[0_0_15px_rgba(34,197,94,0.6)] border border-green-300";
                
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
