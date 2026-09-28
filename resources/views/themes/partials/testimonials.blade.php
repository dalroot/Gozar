        <!-- Testimonials Section (Interactive Studio-Grade Carousel) -->
        <section id="testimonials" class="py-16 sm:py-24 bg-[#FFF7ED] relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Section Header -->
                <div class="text-center mb-10 sm:mb-14">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#FFEDD5] border border-[#FED7AA] text-[#FF7A3C] text-xs sm:text-sm font-extrabold mb-4">
                        <i class="ph-bold ph-chat-teardrop-dots text-base"></i>
                        <span>دیدگاه متخصصان</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold text-[#38140B] mb-4 leading-tight">
                        تجربه استفاده در <span class="bg-gradient-to-r from-[#FF7A3C] to-[#F75C1E] bg-clip-text text-transparent">جریان‌های کاری واقعی</span>
                    </h2>
                    <p class="text-base sm:text-lg text-[#7C2D12] max-w-2xl mx-auto font-medium leading-relaxed">
                        نظرات واقعی متخصصانی که برای کار روزانه خود به پایداری شبکه نیاز دارند.
                    </p>
                </div>

                <!-- Testimonials Slider Shell -->
                <div class="testimonial-slider-container relative max-w-4xl mx-auto px-2 sm:px-12">
                    
                    <!-- Side Arrows Navigation -->
                    <button type="button" id="testimonial-prev" class="testimonial-nav-btn prev-btn" aria-label="نظر قبلی">
                        <i class="ph-bold ph-caret-right text-lg"></i>
                    </button>
                    <button type="button" id="testimonial-next" class="testimonial-nav-btn next-btn" aria-label="نظر بعدی">
                        <i class="ph-bold ph-caret-left text-lg"></i>
                    </button>

                    <!-- Slider Viewport Window -->
                    <div class="testimonial-slider-viewport overflow-hidden w-full rounded-3xl py-2" dir="ltr">
                        <!-- Slider Track -->
                        <div class="testimonial-slider-track flex transition-all duration-500 ease-out" id="testimonial-slider-track">
                            
                            <!-- Testimonial Card 1 -->
                            <div class="testimonial-card flex-shrink-0 w-full px-2" data-index="0" dir="rtl">
                                <div class="bg-white border-2 border-[#FFE4CB] rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex gap-1 text-amber-400 text-sm">
                                            <i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i>
                                        </div>
                                        <i class="ph-fill ph-quotes text-3xl text-orange-300"></i>
                                    </div>
                                    <p class="text-[#38140B]/90 text-sm sm:text-base font-medium leading-relaxed mb-6">
                                        «به عنوان یک توسعه‌دهنده نرم‌افزار، قطعی‌های لحظه‌ای در ابزارهایی مانند داکر و گیت‌هاب جریان کار ما را فلج می‌کرد. با روزنه پایداری اتصال بسیار مطلوبی را تجربه می‌کنیم.»
                                    </p>
                                    <div class="flex items-center gap-3 pt-4 border-t border-[#FFE4CB]">
                                        <img src="{{ asset('rozaneh/images/avatar_amir.jpg') }}" alt="امیرحسین امیری" class="w-12 h-12 rounded-full object-cover border-2 border-[#FF7A3C]"/>
                                        <div>
                                            <div class="font-extrabold text-sm text-[#38140B]">امیرحسین امیری</div>
                                            <div class="text-xs text-[#7C2D12] font-semibold">مهندس ارشد نرم‌افزار</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonial Card 2 -->
                            <div class="testimonial-card flex-shrink-0 w-full px-2" data-index="1" dir="rtl">
                                <div class="bg-white border-2 border-[#FFE4CB] rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex gap-1 text-amber-400 text-sm">
                                            <i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i>
                                        </div>
                                        <i class="ph-fill ph-quotes text-3xl text-rose-300"></i>
                                    </div>
                                    <p class="text-[#38140B]/90 text-sm sm:text-base font-medium leading-relaxed mb-6">
                                        «دسترسی پایدار به فیگما بدون از دست رفتن پینگ و کیفیت لود تصاویر در حین کار تیمی برای پروژه‌های طراحی ما حیاتی بود. روزنه تاخیر بسیار پایینی برای کار ما دارد.»
                                    </p>
                                    <div class="flex items-center gap-3 pt-4 border-t border-[#FFE4CB]">
                                        <img src="{{ asset('rozaneh/images/avatar_sara.jpg') }}" alt="سارا رضایی" class="w-12 h-12 rounded-full object-cover border-2 border-rose-500"/>
                                        <div>
                                            <div class="font-extrabold text-sm text-[#38140B]">سارا رضایی</div>
                                            <div class="text-xs text-[#7C2D12] font-semibold">طراح رابط کاربری (UI/UX)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonial Card 3 -->
                            <div class="testimonial-card flex-shrink-0 w-full px-2" data-index="2" dir="rtl">
                                <div class="bg-white border-2 border-[#FFE4CB] rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex gap-1 text-amber-400 text-sm">
                                            <i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i>
                                        </div>
                                        <i class="ph-fill ph-quotes text-3xl text-purple-300"></i>
                                    </div>
                                    <p class="text-[#38140B]/90 text-sm sm:text-base font-medium leading-relaxed mb-6">
                                        «من پینگ پایین برای بازی‌های آنلاین و پایداری بالا در حین استریم کردن برام اولویت اصلی بود. روزنه تا امروز بهترین کیفیت و بدون پکت لاس رو برام تضمین کرده.»
                                    </p>
                                    <div class="flex items-center gap-3 pt-4 border-t border-[#FFE4CB]">
                                        <img src="{{ asset('rozaneh/images/avatar_pouria.jpg') }}" alt="پوریا راد" class="w-12 h-12 rounded-full object-cover border-2 border-purple-500"/>
                                        <div>
                                            <div class="font-extrabold text-sm text-[#38140B]">پوریا راد</div>
                                            <div class="text-xs text-[#7C2D12] font-semibold">استریمر و تولیدکننده محتوا</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Dots Indicator Navigation -->
                    <div class="testimonial-dots-nav flex items-center justify-center gap-2 mt-6" id="testimonial-dots">
                        <button type="button" class="testimonial-dot active" data-slide="0" aria-label="اسلاید ۱"></button>
                        <button type="button" class="testimonial-dot" data-slide="1" aria-label="اسلاید ۲"></button>
                        <button type="button" class="testimonial-dot" data-slide="2" aria-label="اسلاید ۳"></button>
                    </div>
                </div>

                <!-- Trust Stats Bar -->
                <div class="mt-12 sm:mt-16 bg-white border-2 border-[#FFE4CB] rounded-3xl p-6 sm:p-8 grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8 shadow-xs">
                    <div class="text-center">
                        <div class="text-2xl sm:text-3xl font-extrabold text-[#FF7A3C]"><bdi dir="ltr">۲,۵۰۰+</bdi></div>
                        <div class="text-xs sm:text-sm text-[#7C2D12] font-semibold">کاربر فعال</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl sm:text-3xl font-extrabold text-blue-600"><bdi dir="ltr">۴.۹ / ۵</bdi></div>
                        <div class="text-xs sm:text-sm text-[#7C2D12] font-semibold">رضایت عمومی</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl sm:text-3xl font-extrabold text-pink-600"><bdi dir="ltr">۹۹.۹٪</bdi></div>
                        <div class="text-xs sm:text-sm text-[#7C2D12] font-semibold">پایداری شبکه (SLA)</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl sm:text-3xl font-extrabold text-purple-600"><bdi dir="ltr">۱۵+</bdi></div>
                        <div class="text-xs sm:text-sm text-[#7C2D12] font-semibold">دیتاسنتر فعال</div>
                    </div>
                </div>
            </div>
        </section>

<script>
(function() {
    function initTestimonials() {
        var track = document.getElementById('testimonial-slider-track');
        var prevBtn = document.getElementById('testimonial-prev');
        var nextBtn = document.getElementById('testimonial-next');
        var dotsNav = document.getElementById('testimonial-dots');
        
        if (!track) return;
        
        var cards = track.querySelectorAll('.testimonial-card');
        var dots = dotsNav ? dotsNav.querySelectorAll('.testimonial-dot') : [];
        var total = cards.length;
        if (total === 0) return;
        
        var currentIndex = 0;
        var timer = null;
        
        function goToSlide(index) {
            currentIndex = (index + total) % total;
            track.style.marginRight = '0px';
            track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';
            
            dots.forEach(function(dot, i) {
                if (i === currentIndex) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }
        
        function startAuto() {
            stopAuto();
            timer = setInterval(function() {
                goToSlide(currentIndex + 1);
            }, 4000);
        }
        
        function stopAuto() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }
        
        if (prevBtn) {
            prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                stopAuto();
                goToSlide(currentIndex - 1);
                startAuto();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                stopAuto();
                goToSlide(currentIndex + 1);
                startAuto();
            });
        }
        
        dots.forEach(function(dot, idx) {
            dot.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                stopAuto();
                goToSlide(idx);
                startAuto();
            });
        });
        
        var touchStartX = 0;
        track.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
            stopAuto();
        }, { passive: true });
        
        track.addEventListener('touchend', function(e) {
            var touchEndX = e.changedTouches[0].clientX;
            var diffX = touchStartX - touchEndX;
            if (Math.abs(diffX) > 40) {
                if (diffX > 0) {
                    goToSlide(currentIndex + 1);
                } else {
                    goToSlide(currentIndex - 1);
                }
            }
            startAuto();
        }, { passive: true });
        
        goToSlide(0);
        startAuto();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTestimonials);
    } else {
        initTestimonials();
    }
})();
</script>
