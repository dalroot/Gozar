<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <base href="{{ asset('rozaneh/') }}/">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سیاست حریم خصوصی | روزنه VPN</title>
    
    <!-- Google Fonts: Fredoka & Vazirmatn -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Vazirmatn:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#FF7A3C',
                        secondary: '#FFD369',
                        accent: '#2563EB',
                        pink: '#EC4899',
                        purple: '#8B5CF6',
                        blue: '#3B82F6',
                        bg: '#FFF7ED',
                        'bg-alt': '#FFF1E2',
                        'bg-card': '#FFFFFF',
                        border: '#FED7AA',
                        text: '#431407',
                        'text-muted': '#7C2D12',
                    },
                    fontFamily: {
                        sans: ['Vazirmatn', 'sans-serif'],
                        fredoka: ['Fredoka', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Style for Premium Claymorphism -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="bg-[#FFF7ED] font-sans antialiased text-[#431407]">
    <div class="pet-grooming">
        <!-- Floating Navbar -->
        <nav class="navbar-pet">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="index.html" class="flex items-center gap-2">
                        <div class="w-12 h-12 rounded-2xl bg-[var(--color-primary)] flex items-center justify-center border-3 border-[rgba(0,0,0,0.1)] shadow-[3px_3px_0_rgba(255,122,60,0.3)]">
                            <i class="ph-fill ph-sun-dim text-white text-2xl"></i>
                        </div>
                        <span class="text-xl font-bold text-[var(--color-text)]">روزنه</span>
                    </a>
                    <div class="hidden md:flex items-center gap-6 sm:gap-8">
                        <a href="index.html#services" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer font-semibold">ویژگی‌ها</a>
                        <a href="index.html#gallery" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer font-semibold">سرورها</a>
                        <a href="index.html#pricing" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer font-semibold">اشتراک‌ها</a>
                        <a href="index.html#checkout-section" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer font-semibold">آموزش اتصال</a>
                        <a href="mobile.html" target="_blank" class="text-[var(--color-primary)] hover:opacity-80 transition-colors cursor-bold flex items-center gap-1 font-bold">شبیه‌ساز اپلیکیشن <i class="ph-bold ph-smartphone text-lg"></i></a>
                    </div>
                    <div class="hidden md:flex items-center gap-4" id="nav-auth-btn">
                        <a href="index.html?auth=open" class="px-5 py-2.5 rounded-2xl bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white font-black text-xs shadow-[var(--shadow-clay-sm)] transition-all flex items-center gap-2">
                            <i class="ph-bold ph-user-circle text-base"></i>
                            <span>ورود / ثبت‌نام</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 py-12 pt-28 sm:pt-32">
            <div class="clay-card bg-white p-6 sm:p-12 text-right">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 rounded-3xl bg-orange-100 flex items-center justify-center border-3 border-[var(--color-border)] mx-auto mb-4">
                        <i class="ph-fill ph-shield-check text-[var(--color-primary)] text-3xl"></i>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-[var(--color-text)] mb-3">سیاست حریم خصوصی</h1>
                    <p class="text-[var(--color-text-muted)] font-semibold text-lg">تعهد راستین روزنه به عدم ذخیره‌سازی داده‌های ارتباطی (Zero-Logs Policy) ☀️</p>
                </div>

                <div class="space-y-8 text-justify text-[var(--color-text)] leading-relaxed">
                    <section id="no-logs">
                        <h2 class="text-xl font-black mb-3 text-[var(--color-primary)] flex items-center gap-2">
                            <i class="ph-bold ph-eye-slash"></i> تعهد عدم ثبت فعالیت (No-Logs Policy)
                        </h2>
                        <p class="text-sm font-medium">
                            در روزنه، حریم خصوصی شما اولویت مطلق ماست. زیرساخت شبکه و سرورهای ما به گونه‌ای مهندسی و پیکربندی شده‌اند که هیچ‌گونه اطلاعاتی از فعالیت‌های دیجیتال شما ثبت، نظارت یا ذخیره نمی‌شود.
                        </p>
                    </section>

                    <section class="bg-red-50 p-6 rounded-2xl border-2 border-red-200">
                        <h3 class="font-bold text-red-800 mb-3 flex items-center gap-2">
                            <i class="ph-bold ph-x-circle text-red-600"></i> مواردی که هرگز جمع‌آوری یا ذخیره نمی‌شوند:
                        </h3>
                        <ul class="space-y-2.5 text-sm font-medium text-red-900 list-disc list-inside pr-2">
                            <li>آدرس‌های IP ورودی (آدرس IP واقعی دستگاه شما).</li>
                            <li>آدرس‌های IP خروجی (سرورهایی که به آن‌ها متصل می‌شوید).</li>
                            <li>تاریخچه مرور وب (وب‌سایت‌ها و صفحاتی که بازدید می‌کنید).</li>
                            <li>ترافیک داده‌ها و محتوای رد و بدل شده در طول اتصال.</li>
                            <li>درخواست‌های دی‌ان‌اس (DNS Queries).</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-black mb-3 text-[var(--color-primary)] flex items-center gap-2">
                            <i class="ph-bold ph-database"></i> چه اطلاعاتی را ذخیره می‌کنیم؟
                        </h2>
                        <p class="text-sm font-medium mb-3">
                            ما تنها حداقل اطلاعات فنی لازم برای مدیریت اشتراک‌ها و پایداری شبکه را پردازش می‌کنیم:
                        </p>
                        <ul class="space-y-2.5 text-sm font-medium list-disc list-inside pr-2">
                            <li><strong>اطلاعات حساب تلگرام:</strong> شناسه عددی تلگرام و کد اشتراک شما (فقط برای تطبیق خرید و تحویل کانفیگ‌ها).</li>
                            <li><strong>میزان کل مصرف ترافیک:</strong> حجم کل مصرف شده (فقط برای پایش منصفانه ترافیک پهنای باند سرورها).</li>
                            <li><strong>تعداد دستگاه‌های متصل فعال:</strong> تعداد همزمان اتصالات به منظور جلوگیری از سوءاستفاده و اشتراک‌گذاری عمومی کانفیگ‌ها.</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-black mb-3 text-[var(--color-primary)] flex items-center gap-2">
                            <i class="ph-bold ph-lock"></i> امنیت داده‌ها و رمزنگاری
                        </h2>
                        <p class="text-sm font-medium">
                            تمام داده‌هایی که از کانال‌های اتصالی روزنه عبور می‌کنند با استفاده از الگوریتم‌های استاندارد نظامی اعم از AES-256 و ChaCha20 رمزنگاری می‌شوند. این لایه امنیتی مانع از شنود اطلاعات توسط ارائه‌دهندگان خدمات اینترنت (ISP) یا هکرهای شبکه‌های وای‌فای عمومی می‌شود.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-black mb-3 text-[var(--color-primary)] flex items-center gap-2">
                            <i class="ph-bold ph-cookie"></i> سیاست کوکی‌ها (Cookies)
                        </h2>
                        <p class="text-sm font-medium">
                            وب‌سایت روزنه از هیچ‌گونه کوکی ردیابی یا تبلیغاتی شخص ثالث استفاده نمی‌کند. ما تنها از متغیرهای محلی مرورگر (Local Storage) جهت ذخیره کردن وضعیت لاگین اشتراک شما استفاده می‌کنیم تا با هر بار ورود به سایت، نیاز به وارد کردن مجدد کد اشتراک خود نداشته باشید.
                        </p>
                    </section>
                </div>
                
                <div class="text-center mt-12 pt-6 border-t-2 border-[var(--color-border)]">
                    <a href="index.html" class="btn-secondary text-sm py-2.5 px-6">بازگشت به صفحه اصلی</a>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-[var(--color-bg)] border-t-3 border-[var(--color-border)] py-12 sm:py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">
                    <div class="col-span-2 md:col-span-1">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-[var(--color-primary)] flex items-center justify-center border-3 border-[rgba(0,0,0,0.1)]">
                                <i class="ph-fill ph-sun-dim text-white text-2xl"></i>
                            </div>
                            <span class="text-xl font-bold text-[var(--color-text)]">روزنه</span>
                        </div>
                        <p class="text-[var(--color-text-muted)] text-sm mb-6 font-medium">زیرساخت اختصاصی و مهندسی‌شده برای اتصالات پایدار و امن دیجیتال ☀️</p>
                        <div class="flex gap-3">
                            <a href="https://t.me/RozanehVpnBot" target="_blank" class="w-10 h-10 rounded-xl bg-[var(--color-bg-alt)] border-2 border-[var(--color-border)] flex items-center justify-center text-[var(--color-text-muted)] hover:bg-[var(--color-primary)] hover:text-white hover:border-[var(--color-primary)] transition-colors cursor-pointer"><i class="ph-bold ph-telegram-logo text-xl"></i></a>
                            <a href="#" class="w-10 h-10 rounded-xl bg-[var(--color-bg-alt)] border-2 border-[var(--color-border)] flex items-center justify-center text-[var(--color-text-muted)] hover:bg-[var(--color-primary)] hover:text-white hover:border-[var(--color-primary)] transition-colors cursor-pointer"><i class="ph-bold ph-twitter-logo text-xl"></i></a>
                            <a href="#" class="w-10 h-10 rounded-xl bg-[var(--color-bg-alt)] border-2 border-[var(--color-border)] flex items-center justify-center text-[var(--color-text-muted)] hover:bg-[var(--color-primary)] hover:text-white hover:border-[var(--color-primary)] transition-colors cursor-pointer"><i class="ph-bold ph-instagram-logo text-xl"></i></a>
                            <a href="#" class="w-10 h-10 rounded-xl bg-[var(--color-bg-alt)] border-2 border-[var(--color-border)] flex items-center justify-center text-[var(--color-text-muted)] hover:bg-[var(--color-primary)] hover:text-white hover:border-[var(--color-primary)] transition-colors cursor-pointer"><i class="ph-bold ph-github-logo text-xl"></i></a>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-bold text-[var(--color-text)] mb-4">دانلود کلاینت</h4>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">نسخه اندروید (APK)</a></li>
                            <li><a href="#" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">دریافت از گوگل پلی</a></li>
                            <li><a href="#" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">نسخه آیفون (iOS)</a></li>
                            <li><a href="#" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">نسخه ویندوز</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-[var(--color-text)] mb-4">پشتیبانی و آموزش</h4>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">آموزش اتصال</a></li>
                            <li><a href="https://t.me/RozanehVpnBot" target="_blank" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">پشتیبانی تلگرام</a></li>
                            <li><a href="#" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">سوالات متداول</a></li>
                            <li><a href="#" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">وضعیت سرورها</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-[var(--color-text)] mb-4">قوانین و امنیت</h4>
                        <ul class="space-y-3">
                            <li><a href="privacy.html" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">حریم خصوصی</a></li>
                            <li><a href="terms.html" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">شرایط استفاده</a></li>
                            <li><a href="privacy.html#no-logs" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">سیاست عدم ثبت لاگ</a></li>
                            <li><a href="about.html" class="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors cursor-pointer text-sm font-medium">درباره روزنه</a></li>
                        </ul>
                    </div>
                </div>
                <div class="pt-8 border-t-3 border-[var(--color-border)] flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-[var(--color-text-muted)] text-sm font-medium">© ۲۰۲۶ روزنه. تمامی حقوق محفوظ است. ☀️</p>
                    <div class="flex flex-wrap gap-6 text-sm text-[var(--color-text-muted)] font-semibold">
                        <a href="privacy.html" class="hover:text-[var(--color-primary)] transition-colors">حریم خصوصی</a>
                        <a href="terms.html" class="hover:text-[var(--color-primary)] transition-colors">شرایط سرویس</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Login Modal overlay -->
    <div id="login-modal" class="modal-overlay">
        <div class="clay-card p-6 sm:p-8 w-full max-w-md bg-white relative">
            <button id="close-login" class="absolute top-4 left-4 text-[var(--color-text-muted)] hover:text-[var(--color-primary)] font-bold text-xl cursor-pointer bg-transparent border-none">
                <i class="ph-bold ph-x"></i>
            </button>
            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-3xl bg-orange-100 flex items-center justify-center border-3 border-[var(--color-border)] mx-auto mb-3">
                    <i class="ph-fill ph-user-key text-[var(--color-primary)] text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[var(--color-text)]">ورود به حساب کاربری</h3>
                <p class="text-xs text-[var(--color-text-muted)] mt-1">کد اشتراک خود را وارد کنید تا وضعیت اتصال را ببینید</p>
            </div>
            <div class="space-y-4 text-right">
                <div>
                    <label class="block text-xs font-bold text-[var(--color-text-muted)] mb-2">کد اشتراک تلگرام</label>
                    <div class="relative">
                        <input type="text" id="auth-code-input" placeholder="مثال: rozaneh_user_7894" class="w-full px-4 py-3 rounded-2xl border-3 border-[var(--color-border)] focus:outline-none focus:border-[var(--color-primary)] font-mono text-left placeholder:text-right" style="direction: ltr;">
                    </div>
                </div>
                <button id="login-submit-btn" class="btn-primary w-full py-3.5 rounded-2xl font-bold text-sm justify-center">ورود به پنل</button>
                <div class="text-center text-[10px] text-[var(--color-text-muted)] font-medium">کد اشتراک شما پس از اولین دریافت اکانت در ربات تلگرام پیامک یا ارسال می‌شود.</div>
            </div>
        </div>
    </div>

    <!-- JS code for interactivity -->
    <script src="auth-script.js"></script>
    <script src="script.js"></script>
</body>
</html>
