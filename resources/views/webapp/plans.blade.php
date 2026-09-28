<!-- resources/views/webapp/plans.blade.php -->
@extends('webapp.layout')

@section('content')
    <div class="text-center mb-8 relative z-10">
        <h1 class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-purple-400 mb-2">خرید سرویس</h1>
        <p class="text-gray-400 text-sm">پلن مورد نظر خود را با بهترین کیفیت انتخاب کنید.</p>
    </div>

    <div class="space-y-4">
        @foreach($plans as $index => $plan)
            @php
                $isRecommended = $index === 1 || $plan->volume_gb >= 50; // just an example to highlight
            @endphp
            <div class="relative group">
                <!-- Glowing border for recommended -->
                @if($isRecommended)
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition duration-1000 group-hover:duration-200"></div>
                @endif
                
                <div class="glass-panel p-6 rounded-2xl relative flex flex-col justify-between h-full shadow-xl border border-white/5">
                    @if($isRecommended)
                        <div class="absolute -top-3 right-6 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-lg border border-white/10">
                            پیشنهاد ویژه
                        </div>
                    @endif
                    
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-white tracking-wide">{{ $plan->name }}</h3>
                            <div class="text-gray-400 text-sm mt-1 flex items-center gap-1.5">
                                <i class="ri-calendar-event-line text-blue-400"></i> {{ $plan->duration_days }} روزه
                            </div>
                        </div>
                        <div class="text-left">
                            <span class="text-2xl font-black font-inter text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-300">{{ number_format($plan->price) }}</span>
                            <span class="block text-[10px] text-gray-500 font-medium mt-0.5">تومان</span>
                        </div>
                    </div>
                    
                    <div class="mb-6 flex gap-2">
                        <span class="bg-blue-500/10 text-blue-400 px-3 py-1.5 rounded-lg text-xs font-inter font-semibold border border-blue-500/20 flex items-center gap-1">
                            <i class="ri-database-2-line"></i> {{ $plan->volume_gb }} GB
                        </span>
                        <span class="bg-purple-500/10 text-purple-400 px-3 py-1.5 rounded-lg text-xs font-semibold border border-purple-500/20 flex items-center gap-1">
                            <i class="ri-rocket-2-line"></i> پرسرعت
                        </span>
                    </div>
                    
                    <form action="{{ route('order.store', $plan->id) }}" method="POST" class="w-full mt-auto relative z-20">
                        @csrf
                        <input type="hidden" name="tg_id" value="{{ request('tg_id') }}">
                        <button type="button" onclick="confirmPurchase(this)" class="w-full glass-button text-white font-bold py-3.5 rounded-xl text-sm tracking-wide flex items-center justify-center gap-2">
                            <i class="ri-shopping-bag-3-line text-lg"></i>
                            خرید اشتراک
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function confirmPurchase(btn) {
            const tg = window.Telegram.WebApp;
            tg.showConfirm('آیا از خرید این پلن اطمینان دارید؟ هزینه از کیف پول کسر خواهد شد.', function(confirmed) {
                if(confirmed) {
                    // Disable button and show loading
                    btn.disabled = true;
                    btn.innerHTML = '<i class="ri-loader-4-line animate-spin text-lg"></i> پردازش...';
                    btn.closest('form').submit();
                }
            });
        }
    </script>
@endsection
