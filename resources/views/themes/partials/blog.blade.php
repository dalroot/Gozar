<style>
.blog-nexus-section {
    background: radial-gradient(circle at 50% 50%, #150E22 0%, #08050D 100%) !important;
    position: relative;
}
.blog-nexus-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: 
        linear-gradient(rgba(139, 92, 246, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(139, 92, 246, 0.04) 1px, transparent 1px);
    background-size: 45px 45px;
    background-position: center;
    pointer-events: none;
    z-index: 1;
}
.blog-nexus-card {
    background-color: #120E21 !important;
    border: 1.5px solid rgba(139, 92, 246, 0.15) !important;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.blog-nexus-card:hover {
    transform: translateY(-6px) !important;
    border-color: rgba(6, 182, 212, 0.45) !important; /* Cyan glow border */
    box-shadow: 0 20px 40px rgba(139, 92, 246, 0.12) !important;
}
</style>

<!-- Latest from Blog Section (Immersive Dark Bento Grid) -->
<section id="blog-section" class="py-20 sm:py-28 blog-nexus-section relative overflow-hidden border-t border-b border-[#FFE4CB]/60">
    
    <!-- Neon Glow Orbs in Background -->
    <div class="absolute top-1/4 right-1/4 w-96 h-96 rounded-full bg-fuchsia-600/8 blur-[120px] pointer-events-none z-0"></div>
    <div class="absolute bottom-1/4 left-1/4 w-96 h-96 rounded-full bg-cyan-600/8 blur-[120px] pointer-events-none z-0"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- Section Header -->
        <div class="text-center mb-12 sm:mb-16">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-purple-500/10 border border-purple-500/20 text-[#D946EF] text-xs sm:text-sm font-extrabold mb-4">
                <i class="ph-bold ph-book-open-text text-base"></i>
                <span>وبلاگ روزنه</span>
            </div>
            <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold text-white mb-4 leading-tight">
                آگاهی بیشتر، <span class="bg-gradient-to-r from-[#D946EF] via-[#8B5CF6] to-[#06B6D4] bg-clip-text text-transparent">اتصالی هوشمندتر</span>
            </h2>
            <p class="text-base sm:text-lg text-slate-400 max-w-2xl mx-auto font-medium leading-relaxed">
                راهنماها و مقالات آموزشی تخصصی برای وبگردی آزاد، امنیت دیجیتال و بهینه‌سازی پینگ بازی‌ها.
            </p>
        </div>

        <!-- Bento Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Card 1: Featured Post (Reality Protocol) - Spans 2 columns on desktop -->
            <div class="lg:col-span-2 blog-nexus-card backdrop-blur-md rounded-[24px] p-6 flex flex-col md:flex-row gap-6 cursor-pointer group shadow-xl">
                <!-- Image Container -->
                <div class="w-full md:w-1/2 aspect-[16/10] md:aspect-auto rounded-2xl overflow-hidden relative">
                    <img src="/rozaneh/images/blog_security.jpg?v=3" alt="پروتکل Reality" class="w-full h-full object-cover object-center group-hover:scale-103 transition-transform duration-300"/>
                </div>
                <!-- Content Container -->
                <div class="w-full md:w-1/2 flex flex-col justify-between py-2">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-fuchsia-500/10 text-fuchsia-400 text-[11px] font-bold mb-3">
                            <i class="ph-bold ph-calendar-blank"></i>
                            <span>۱۰ تیر ۱۴۰۵ | امنیت دیجیتال</span>
                        </div>
                        <h3 class="text-xl font-extrabold text-white mb-3 group-hover:text-[#06B6D4] transition-colors leading-snug">
                            پروتکل Reality چیست و چگونه از حریم خصوصی شما محافظت می‌کند؟
                        </h3>
                        <p class="text-sm text-[#C0BFCF] leading-relaxed font-medium">
                            بررسی فنی پروتکل نوین شبیه‌سازی ترافیک و روش‌های افزایش پایداری و امنیت داده‌های تبادلی در تونل‌های Reality جهت جلوگیری از ردیابی.
                        </p>
                    </div>
                    <div class="flex items-center justify-end mt-6">
                        <span class="inline-flex items-center gap-1 text-[#06B6D4] font-extrabold text-sm group-hover:gap-2 transition-all">
                            <span>ادامه مطلب</span>
                            <i class="ph-bold ph-arrow-left text-xs"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Standard Post (Gaming Speed) - Spans 1 column on desktop -->
            <div class="blog-nexus-card backdrop-blur-md rounded-[24px] p-6 flex flex-col justify-between cursor-pointer group shadow-xl">
                <div>
                    <!-- Image Container -->
                    <div class="rounded-2xl overflow-hidden aspect-[16/10] mb-4 relative">
                        <img src="/rozaneh/images/blog_speed.jpg?v=3" alt="کاهش پینگ بازی" class="w-full h-full object-cover object-center group-hover:scale-103 transition-transform duration-300"/>
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-cyan-500/10 text-cyan-400 text-[11px] font-bold mb-3">
                        <i class="ph-bold ph-calendar-blank"></i>
                        <span>۸ تیر ۱۴۰۵ | گیمینگ</span>
                    </div>
                    <h3 class="text-lg font-extrabold text-white mb-2 group-hover:text-[#06B6D4] transition-colors leading-snug">
                        راهنمای جامع کاهش پینگ و رفع کامل جیتر در بازی‌های آنلاین
                    </h3>
                    <p class="text-sm text-[#C0BFCF] leading-relaxed font-medium">
                        چگونه با تنظیم هوشمند DNS، بهینه‌سازی مسیرهای ارتباطی و انتخاب پروتکل‌های مناسب، جیتر را به حداقل برسانیم؟
                    </p>
                </div>
                <div class="flex items-center justify-end mt-6">
                    <span class="inline-flex items-center gap-1 text-[#06B6D4] font-extrabold text-sm group-hover:gap-2 transition-all">
                        <span>ادامه مطلب</span>
                        <i class="ph-bold ph-arrow-left text-xs"></i>
                    </span>
                </div>
            </div>

            <!-- Card 3: Wide Post (Servers Port) - Spans all 3 columns on desktop for a balanced grid -->
            <div class="lg:col-span-3 blog-nexus-card backdrop-blur-md rounded-[24px] p-6 flex flex-col md:flex-row gap-6 cursor-pointer group shadow-xl">
                <!-- Image Container -->
                <div class="w-full md:w-1/3 aspect-[16/9] md:aspect-auto rounded-2xl overflow-hidden relative">
                    <img src="/rozaneh/images/blog_servers.jpg?v=3" alt="سرورهای ۱۰ گیگابیتی" class="w-full h-full object-cover object-center group-hover:scale-103 transition-transform duration-300"/>
                </div>
                <!-- Content Container -->
                <div class="w-full md:w-2/3 flex flex-col justify-between py-2">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-500/10 text-blue-400 text-[11px] font-bold mb-3">
                            <i class="ph-bold ph-calendar-blank"></i>
                            <span>۴ تیر ۱۴۰۵ | زیرساخت شبکه</span>
                        </div>
                        <h3 class="text-xl font-extrabold text-white mb-3 group-hover:text-[#06B6D4] transition-colors leading-snug">
                            چرا پهنای باند ۱۰ گیگابیتی سرورها برای استریم باکیفیت حیاتی است؟
                        </h3>
                        <p class="text-sm text-[#C0BFCF] leading-relaxed font-medium">
                            بررسی عملکرد فنی و ظرفیت پورت سرورها در پخش بدون بافر ویدئوهای 4K و پایداری لایواستریم در پلتفرم‌های یوتیوب و نتفلیکس. پهنای باند بالا از گلوگاه‌های ترافیکی در ساعات اوج مصرف جلوگیری می‌کند.
                        </p>
                    </div>
                    <div class="flex items-center justify-end mt-6">
                        <span class="inline-flex items-center gap-1 text-[#06B6D4] font-extrabold text-sm group-hover:gap-2 transition-all">
                            <span>ادامه مطلب</span>
                            <i class="ph-bold ph-arrow-left text-xs"></i>
                        </span>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>