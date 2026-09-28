<!-- resources/views/webapp/wheel.blade.php -->
@extends('webapp.layout')

@section('content')
    <div class="mb-6 flex items-center justify-between relative z-10">
        <h1 class="text-xl font-bold text-white tracking-wide">گردونه شانس <i class="ri-blaze-fill text-yellow-400"></i></h1>
        <div class="bg-black/30 px-3 py-1.5 rounded-full border border-white/5 flex items-center gap-1.5">
            <i class="ri-wallet-3-fill text-blue-400"></i>
            <span id="currentBalance" class="text-sm font-bold text-white font-inter">{{ number_format($user->balance) }}</span>
        </div>
    </div>

    <!-- Wheel Box -->
    <div class="glass-panel p-6 rounded-[2rem] relative overflow-hidden shadow-2xl border border-white/10 mt-4 flex flex-col items-center">
        <!-- Background Glows -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-yellow-500/10 blur-[50px] rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-purple-500/20 blur-3xl rounded-full"></div>
        
        <h2 class="text-lg font-black text-white tracking-wide mb-1 relative z-10 text-center">شانس خود را امتحان کنید!</h2>
        <p class="text-[11px] text-gray-400 mb-6 text-center relative z-10">هر ۲۴ ساعت یک بار بچرخانید و اعتبار رایگان بگیرید.</p>

        <!-- The Wheel UI -->
        <div class="relative w-64 h-64 mb-8 z-10">
            <!-- Outer Ring (Neon) -->
            <div class="absolute inset-0 rounded-full border-4 border-yellow-500/30 shadow-[0_0_30px_rgba(234,179,8,0.2)]"></div>
            <!-- Pointer (Top center) -->
            <div class="absolute -top-4 left-1/2 -translate-x-1/2 z-20 text-red-500 drop-shadow-[0_0_5px_rgba(239,68,68,0.8)]">
                <i class="ri-map-pin-fill text-4xl"></i>
            </div>

            <!-- The Spinning Wheel -->
            <div id="wheelContainer" class="w-full h-full rounded-full overflow-hidden relative transition-transform duration-[4000ms] ease-[cubic-bezier(0.1,0.9,0.2,1)]" style="transform: rotate(0deg);">
                <!-- CSS Conic Gradient for slices -->
                <div class="absolute inset-0 rounded-full border-2 border-white/10" 
                     style="background: conic-gradient(
                        #3b82f6 0deg 60deg,    /* Blue - پوچ */
                        #8b5cf6 60deg 120deg,  /* Purple - 500 */
                        #ec4899 120deg 180deg, /* Pink - 1000 */
                        #f59e0b 180deg 240deg, /* Amber - 2000 */
                        #10b981 240deg 300deg, /* Emerald - 5000 */
                        #ef4444 300deg 360deg  /* Red - 10000 */
                     );">
                </div>

                <!-- Labels -->
                <div class="absolute inset-0 pointer-events-none flex items-center justify-center font-bold text-white text-xs drop-shadow-md">
                    <span class="absolute top-4 left-1/2 -translate-x-1/2 rotate-0 origin-[50%_100px]">پوچ</span>
                    <span class="absolute top-10 right-8 rotate-60 origin-[-20px_60px]">۵۰۰ T</span>
                    <span class="absolute bottom-10 right-8 rotate-120 origin-[-20px_-20px]">۱,۰۰۰ T</span>
                    <span class="absolute bottom-4 left-1/2 -translate-x-1/2 rotate-180 origin-[50%_-60px]">۲,۰۰۰ T</span>
                    <span class="absolute bottom-10 left-8 rotate-240 origin-[80px_-20px]">۵,۰۰۰ T</span>
                    <span class="absolute top-10 left-8 rotate-[300deg] origin-[80px_60px]">ویژه!</span>
                </div>
                
                <!-- Center Dot -->
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-8 h-8 bg-zinc-900 rounded-full border-2 border-yellow-500 shadow-[0_0_15px_rgba(234,179,8,0.5)] z-10 flex items-center justify-center">
                    <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                </div>
            </div>
        </div>

        @if($canSpin)
            <button id="spinBtn" onclick="spinWheel()" class="w-full bg-gradient-to-r from-yellow-500 to-amber-600 hover:opacity-90 text-white px-4 py-4 rounded-xl text-base font-black tracking-wide transition-all active:scale-95 shadow-[0_0_20px_rgba(245,158,11,0.4)] border border-yellow-400/50 relative z-10 overflow-hidden group">
                <span class="relative z-10">بچرخان و برنده شو!</span>
                <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
            </button>
        @else
            <div class="w-full bg-black/40 border border-white/5 px-4 py-3 rounded-xl text-center relative z-10">
                <p class="text-sm font-bold text-gray-300 mb-1">فردا برگردید!</p>
                <p class="text-[11px] text-gray-500 font-inter">زمان چرخش بعدی: <span id="countdown" class="text-blue-400 font-bold ml-1">...</span></p>
            </div>
        @endif
    </div>
    
    <!-- Modal for Result -->
    <div id="resultModal" class="fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm hidden flex-col items-center justify-center px-4 opacity-0 transition-opacity duration-300">
        <div class="glass-panel p-8 rounded-3xl w-full max-w-sm flex flex-col items-center text-center transform scale-90 transition-transform duration-300" id="resultModalInner">
            <div id="resultIcon" class="w-20 h-20 rounded-full bg-blue-500/20 flex items-center justify-center mb-4 border-2 border-blue-500/50">
                <i class="ri-gift-2-line text-4xl text-blue-400"></i>
            </div>
            <h3 id="resultTitle" class="text-2xl font-black text-white mb-2"></h3>
            <p id="resultDesc" class="text-sm text-gray-400 mb-6"></p>
            <button onclick="closeModal()" class="w-full bg-white/10 hover:bg-white/20 border border-white/10 text-white font-bold py-3 rounded-xl transition">
                عالیه!
            </button>
        </div>
        <!-- Confetti Canvas -->
        <canvas id="confetti" class="absolute inset-0 pointer-events-none"></canvas>
    </div>

    <!-- Padding for bottom nav -->
    <div class="h-24"></div>

    <!-- Confetti JS for Win Animation -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        let isSpinning = false;
        let currentRotation = 0;

        function spinWheel() {
            if (isSpinning) return;
            isSpinning = true;

            const btn = document.getElementById('spinBtn');
            const wheel = document.getElementById('wheelContainer');
            
            btn.innerHTML = '<i class="ri-loader-4-line animate-spin text-xl"></i>';
            btn.classList.add('opacity-70', 'cursor-not-allowed');

            if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.HapticFeedback) {
                window.Telegram.WebApp.HapticFeedback.impactOccurred('medium');
            }

            fetch('{{ route("webapp.wheel.spin", ["tg_id" => request("tg_id")]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Slices are 60 degrees each.
                    // Index 0 (پوچ): 0-60 -> middle is ~30deg
                    // Index 1 (500): 60-120 -> middle is ~90deg
                    // ...
                    // Since pointer is at TOP (-90deg or 270deg relative to standard math), we calculate rotation to stop AT the pointer.
                    // We need the selected slice to land at the Top Pointer.
                    
                    const sliceAngle = 60;
                    // The slice's center angle in our drawing is (index * 60) + 30
                    const targetSliceCenter = (data.prize_index * sliceAngle) + (sliceAngle / 2);
                    
                    // The pointer is at 0 degrees (top center of the circle in CSS rotate). 
                    // To get a slice at `targetSliceCenter` to align with 0, we must rotate the wheel backward by that amount.
                    // Or mathematically: rotate = 360 - targetSliceCenter
                    
                    const spins = 5; // Extra full spins for effect
                    const targetRotation = (spins * 360) + (360 - targetSliceCenter);
                    
                    // Add some random offset within the slice so it doesn't always land exactly in the center
                    const randomOffset = Math.floor(Math.random() * 40) - 20; // -20 to +20 degrees
                    
                    currentRotation += targetRotation + randomOffset;
                    
                    wheel.style.transform = `rotate(${currentRotation}deg)`;

                    // Wait for animation to finish (4 seconds)
                    setTimeout(() => {
                        showResult(data.prize_amount, data.prize_title, data.new_balance);
                    }, 4200);

                } else {
                    alert(data.message);
                    isSpinning = false;
                    btn.innerHTML = 'بچرخان و برنده شو!';
                    btn.classList.remove('opacity-70', 'cursor-not-allowed');
                }
            })
            .catch(err => {
                alert('خطایی رخ داد. اینترنت خود را بررسی کنید.');
                isSpinning = false;
                btn.innerHTML = 'بچرخان و برنده شو!';
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
            });
        }

        function showResult(amount, title, newBalance) {
            const modal = document.getElementById('resultModal');
            const modalInner = document.getElementById('resultModalInner');
            const icon = document.getElementById('resultIcon');
            const titleEl = document.getElementById('resultTitle');
            const descEl = document.getElementById('resultDesc');
            
            if (amount > 0) {
                // Win
                icon.className = "w-20 h-20 rounded-full bg-green-500/20 flex items-center justify-center mb-4 border-2 border-green-500/50 shadow-[0_0_30px_rgba(34,197,94,0.3)]";
                icon.innerHTML = '<i class="ri-coins-fill text-4xl text-green-400"></i>';
                titleEl.innerText = title;
                titleEl.className = "text-2xl font-black text-green-400 mb-2 drop-shadow-md";
                descEl.innerText = "مبلغ جایزه به موجودی کیف پول شما اضافه شد!";
                
                // Fire confetti
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 },
                    colors: ['#22c55e', '#facc15', '#3b82f6']
                });
                
                if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.HapticFeedback) {
                    window.Telegram.WebApp.HapticFeedback.notificationOccurred('success');
                }
                
                // Update balance
                document.getElementById('currentBalance').innerText = new Intl.NumberFormat().format(newBalance);
            } else {
                // Lose (پوچ)
                icon.className = "w-20 h-20 rounded-full bg-gray-500/20 flex items-center justify-center mb-4 border-2 border-gray-500/50";
                icon.innerHTML = '<i class="ri-emotion-sad-line text-4xl text-gray-400"></i>';
                titleEl.innerText = title;
                titleEl.className = "text-2xl font-black text-gray-300 mb-2";
                descEl.innerText = "این بار شانس با شما یار نبود. فردا دوباره امتحان کنید!";
                
                if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.HapticFeedback) {
                    window.Telegram.WebApp.HapticFeedback.notificationOccurred('error');
                }
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Trigger reflow for transition
            void modal.offsetWidth;
            
            modal.classList.remove('opacity-0');
            modalInner.classList.remove('scale-90');
        }

        function closeModal() {
            // Reload page to update UI (remove spin button and show timer)
            window.location.reload();
        }

        // Countdown Timer Logic
        @if(!$canSpin && $nextSpinAt)
        const nextSpinTime = new Date('{{ $nextSpinAt }}').getTime();
        
        const countdownTimer = setInterval(function() {
            const now = new Date().getTime();
            const distance = nextSpinTime - now;
            
            if (distance < 0) {
                clearInterval(countdownTimer);
                document.getElementById("countdown").innerHTML = "همین الان!";
                window.location.reload();
                return;
            }
            
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById("countdown").innerHTML = hours + "h " + minutes + "m " + seconds + "s";
        }, 1000);
        @endif
    </script>
@endsection
