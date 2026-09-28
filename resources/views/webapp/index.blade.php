<!-- resources/views/webapp/index.blade.php -->
@extends('webapp.layout')

@section('content')
    <!-- User Profile Header -->
    <div class="glass-panel p-5 rounded-2xl mb-8 flex items-center justify-between border-b border-white/10 relative overflow-hidden">
        <!-- Shine effect -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/20 blur-2xl rounded-full -mr-10 -mt-10"></div>
        
        <div class="relative z-10 flex flex-col items-start w-full">
            <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-white to-gray-400 mb-2">سلام {{ $user->name }} 👋</h1>
            <div class="mt-2 w-full flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center border border-blue-500/30">
                        <i class="ri-wallet-3-fill text-blue-400 text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-gray-400 font-medium">موجودی کیف پول</span>
                        <p class="text-xl text-white font-bold font-inter leading-none mt-1">{{ number_format($user->balance) }} <span class="text-xs text-gray-500 font-normal">T</span></p>
                    </div>
                </div>
                <a href="#" class="bg-white/10 hover:bg-white/20 border border-white/10 text-white text-xs font-bold py-2 px-3 rounded-xl transition">
                    افزایش موجودی
                </a>
            </div>
        </div>
        <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-blue-600 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-lg ring-4 ring-white/5 relative z-10">
            {{ mb_substr($user->name, 0, 1) }}
        </div>
    </div>

    <!-- Active Services Section -->
    <div class="flex items-center justify-between mb-4 px-1">
        <h2 class="text-base font-bold text-gray-100 flex items-center gap-2">
            <div class="w-1.5 h-4 bg-blue-500 rounded-full"></div>
            سرویس‌های فعال من
        </h2>
        @if($activeServices->count() > 0)
            <span class="text-xs bg-white/10 px-2 py-1 rounded-md text-gray-300 font-inter">{{ $activeServices->count() }} سرویس</span>
        @endif
    </div>

    @if($activeServices->count() > 0)
        <div class="space-y-4">
            @foreach($activeServices as $order)
                <a href="{{ route('webapp.order', ['id' => $order->id, 'tg_id' => request('tg_id')]) }}" 
                   class="block glass-panel p-5 rounded-2xl active:scale-95 transition-all duration-300 relative overflow-hidden group">
                    <!-- Hover gradient border effect -->
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-purple-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    
                    <div class="flex justify-between items-start relative z-10">
                        <div class="flex flex-col gap-1">
                            <h3 class="font-bold text-white text-lg tracking-wide">{{ $order->plan->name }}</h3>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="relative flex h-2 w-2">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                <span class="text-[11px] text-green-400 font-medium tracking-widest">فعال و متصل</span>
                            </div>
                        </div>
                        <div class="text-left flex flex-col items-end gap-1">
                            <span class="bg-white/5 px-2.5 py-1 rounded-md text-xs text-gray-300 border border-white/5 font-inter">
                                <i class="ri-hard-drive-line mr-1 text-gray-400"></i> {{ $order->plan->volume_gb }} GB
                            </span>
                            <span class="text-[11px] text-gray-400 mt-1 font-inter flex items-center gap-1">
                                <i class="ri-timer-line"></i> {{ \Carbon\Carbon::parse($order->expires_at)->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="w-full bg-black/40 rounded-full h-1.5 mt-5 overflow-hidden border border-white/5">
                        <div class="bg-gradient-to-r from-blue-500 to-cyan-400 h-1.5 rounded-full relative" style="width: 75%">
                            <div class="absolute top-0 right-0 bottom-0 left-0 bg-[linear-gradient(45deg,rgba(255,255,255,0.15)_25%,transparent_25%,transparent_50%,rgba(255,255,255,0.15)_50%,rgba(255,255,255,0.15)_75%,transparent_75%,transparent)] bg-[length:1rem_1rem] animate-[progress_1s_linear_infinite]"></div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12 glass-panel rounded-2xl border border-dashed border-white/20 mt-4 relative overflow-hidden">
            <div class="absolute inset-0 bg-blue-500/5"></div>
            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4 relative z-10">
                <i class="ri-ghost-line text-3xl text-gray-400"></i>
            </div>
            <p class="text-gray-300 mb-6 relative z-10 font-medium">هنوز هیچ سرویس فعالی ندارید!</p>
            <a href="{{ route('webapp.plans', ['tg_id' => request('tg_id')]) }}" class="glass-button px-8 py-3 rounded-xl text-sm font-bold relative z-10 inline-flex items-center gap-2">
                <i class="ri-shopping-cart-2-line"></i> خرید سرویس جدید
            </a>
        </div>
    @endif

    <style>
        @keyframes progress {
            from { background-position: 1rem 0; }
            to { background-position: 0 0; }
        }
    </style>

    <!-- Padding for bottom nav -->
    <div class="h-24"></div>
@endsection
