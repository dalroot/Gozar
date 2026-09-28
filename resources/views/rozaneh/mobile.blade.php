<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <base href="{{ asset('rozaneh/') }}/">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اپلیکیشن موبایل روزنه | Rozaneh VPN</title>
    
    <!-- Google Fonts: Fredoka & Vazirmatn -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Vazirmatn:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Dedicated Mobile Styles -->
    <link rel="stylesheet" href="mobile-style.css">
</head>
<body>

    <!-- Phone mockup frame -->
    <div class="device-frame">
        <!-- Floating background blobs -->
        <div class="bg-blobs">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
        </div>

        <!-- App Header -->
        <header class="app-header">
            <a href="dashboard.html" class="app-header-btn" title="پنل کاربری" aria-label="پنل کاربری">
                <i class="ph-bold ph-user-circle"></i>
            </a>
            <div class="app-logo">
                <span>Rozaneh</span>
                <div class="app-logo-badge">
                    <i class="ph-fill ph-sun-dim"></i>
                </div>
            </div>
        </header>

        <!-- App Main Content Area -->
        <main class="app-content" id="app-pages-container">
            
            <!-- Page 1: Home (Default Active) -->
            <div class="app-page active" id="page-home">
                <!-- Power Connection Button -->
                <div class="connect-container">
                    <div class="power-button-outer" id="power-connect-btn">
                        <div class="power-button-inner">
                            <i class="ph-bold ph-power"></i>
                        </div>
                    </div>
                    <div class="connection-status-text" id="connection-state-title">قطع اتصال</div>
                    <div class="connection-status-sub" id="connection-state-desc">برای اتصال امن ضربه بزنید</div>
                </div>

                <!-- Active Server Card -->
                <div class="app-card active-server-card" id="home-change-server-btn">
                    <div class="server-info-flex">
                        <img src="https://flagcdn.com/w40/de.png" alt="آلمان" class="server-flag-oval" id="active-server-flag">
                        <div>
                            <div class="server-title" id="active-server-name">سرور فرانکفورت (آلمان)</div>
                            <div class="server-latency">پینگ عالی: <span id="active-server-ping">۳۵ ms</span></div>
                        </div>
                    </div>
                    <i class="ph-bold ph-caret-left text-[var(--text-muted)]"></i>
                </div>

                <!-- Live Metrics Row -->
                <div class="metrics-row">
                    <div class="metric-pill">
                        <div class="metric-pill-icon"><i class="ph-bold ph-arrow-down"></i></div>
                        <div class="metric-pill-info">
                            <div class="metric-pill-title">سرعت دانلود</div>
                            <div class="metric-pill-val" id="metric-download">0.0 Mbps</div>
                        </div>
                    </div>
                    <div class="metric-pill">
                        <div class="metric-pill-icon" style="color: var(--accent-secondary); background: rgba(219,39,119,0.1);"><i class="ph-bold ph-arrow-up"></i></div>
                        <div class="metric-pill-info">
                            <div class="metric-pill-title">سرعت آپلود</div>
                            <div class="metric-pill-val" id="metric-upload">0.0 Mbps</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page 2: Servers -->
            <div class="app-page" id="page-servers">
                <div class="app-card-title">
                    <i class="ph-bold ph-globe-hemisphere-west text-[var(--accent-primary)]"></i>
                    <span>لیست دیتاسنترهای فعال</span>
                </div>
                
                <div class="servers-list" id="bento-servers-container">
                    <!-- Germany -->
                    <div class="server-item-row selected" data-server="germany" data-flag="de" data-name="سرور فرانکفورت (آلمان)" data-ping="۳۵">
                        <div class="server-info-flex">
                            <img src="https://flagcdn.com/w40/de.png" alt="آلمان" class="server-flag-oval">
                            <span class="server-title">فرانکفورت (آلمان)</span>
                        </div>
                        <span class="server-latency">۳۵ ms</span>
                    </div>

                    <!-- Netherlands -->
                    <div class="server-item-row" data-server="netherlands" data-flag="nl" data-name="سرور آمستردام (هلند)" data-ping="۳۸">
                        <div class="server-info-flex">
                            <img src="https://flagcdn.com/w40/nl.png" alt="هلند" class="server-flag-oval">
                            <span class="server-title">آمستردام (هلند)</span>
                        </div>
                        <span class="server-latency">۳۸ ms</span>
                    </div>

                    <!-- France -->
                    <div class="server-item-row" data-server="france" data-flag="fr" data-name="سرور پاریس (فرانسه)" data-ping="۴۲">
                        <div class="server-info-flex">
                            <img src="https://flagcdn.com/w40/fr.png" alt="فرانسه" class="server-flag-oval">
                            <span class="server-title">پاریس (فرانسه)</span>
                        </div>
                        <span class="server-latency">۴۲ ms</span>
                    </div>

                    <!-- Finland -->
                    <div class="server-item-row" data-server="finland" data-flag="fi" data-name="سرور هلسینکی (فنلاند)" data-ping="۵۴">
                        <div class="server-info-flex">
                            <img src="https://flagcdn.com/w40/fi.png" alt="فنلاند" class="server-flag-oval">
                            <span class="server-title">هلسینکی (فنلاند)</span>
                        </div>
                        <span class="server-latency">۵۴ ms</span>
                    </div>

                    <!-- UK -->
                    <div class="server-item-row" data-server="uk" data-flag="gb" data-name="سرور لندن (بریتانیا)" data-ping="۵۸">
                        <div class="server-info-flex">
                            <img src="https://flagcdn.com/w40/gb.png" alt="بریتانیا" class="server-flag-oval">
                            <span class="server-title">لندن (بریتانیا)</span>
                        </div>
                        <span class="server-latency">۵۸ ms</span>
                    </div>
                </div>
            </div>

            <!-- Page 3: Dashboard -->
            <div class="app-page" id="page-dashboard">
                <div class="dashboard-header">
                    <div class="dashboard-avatar">R</div>
                    <div class="dashboard-user-title">کاربر روزنه</div>
                    <div class="dashboard-user-id">شناسه اکانت: rozaneh_mobile_789</div>
                </div>

                <!-- Traffic info card -->
                <div class="app-card">
                    <div class="app-card-title">
                        <i class="ph-bold ph-hard-drive text-[var(--accent-primary)]"></i>
                        <span>وضعیت ترافیک اشتراک</span>
                    </div>
                    <div class="traffic-progress-container">
                        <div class="traffic-legend">
                            <span>ترافیک مصرفی: ۰.۴ گیگابایت</span>
                            <span>کل حجم: ۲.۰ گیگابایت</span>
                        </div>
                        <div class="traffic-progress-bar">
                            <div class="traffic-progress-fill" id="dashboard-traffic-bar" style="width: 20%;"></div>
                        </div>
                        <div class="text-right w-full text-xs text-[var(--text-muted)] font-semibold mt-2">۲۸ روز اعتبار باقی‌مانده (تست هدیه اولیه)</div>
                    </div>
                </div>

                <!-- Invite Friends Card -->
                <div class="app-card">
                    <div class="app-card-title">
                        <i class="ph-bold ph-gift text-[var(--accent-secondary)]"></i>
                        <span>ترافیک هدیه رایگان و همیشگی</span>
                    </div>
                    <p class="text-xs text-[var(--text-muted)] font-semibold leading-relaxed mb-4">با دعوت دوستان خود به ربات تلگرام روزنه، ۵۰۰ مگابایت ترافیک ماهانه همیشگی به ازای هر دعوت موفق دریافت کنید.</p>
                    <button class="ip-copy-btn w-full flex items-center justify-center gap-2 py-3 bg-[var(--accent-primary)] text-white font-bold rounded-xl shadow-md hover:opacity-90" id="copy-referral-link-btn" style="height: 48px; border:none; box-shadow:none;">
                        <i class="ph-bold ph-share-network"></i>
                        <span>دریافت لینک دعوت تلگرام</span>
                    </button>
                </div>
            </div>

            <!-- Page 4: Settings -->
            <div class="app-page" id="page-settings">
                <!-- Protocol Selection Card -->
                <div class="app-card">
                    <div class="app-card-title">
                        <i class="ph-bold ph-cpu text-[var(--accent-primary)]"></i>
                        <span>پیکربندی اتصال</span>
                    </div>
                    <div class="setting-item">
                        <div class="setting-label">
                            پروتکل فعال
                            <span>بهینه‌ترین پروتکل رمزنگاری فعال</span>
                        </div>
                        <span class="text-xs font-black text-[var(--accent-primary)] bg-purple-50 px-3 py-1 rounded-lg border border-purple-100 font-mono">VLESS + Reality</span>
                    </div>
                    <div class="setting-item">
                        <div class="setting-label">
                            تونل ابری کلودفلر
                            <span>افزایش چشمگیر پایداری در همراه اول</span>
                        </div>
                        <label class="switch-control">
                            <input type="checkbox" checked id="setting-cloudflare-tunnel">
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Security controls -->
                <div class="app-card">
                    <div class="app-card-title">
                        <i class="ph-bold ph-shield-check text-[var(--accent-secondary)]"></i>
                        <span>حفاظت و امنیت</span>
                    </div>
                    <div class="setting-item">
                        <div class="setting-label">
                            قطع خودکار (Kill Switch)
                            <span>قطع ترافیک سیستم در صورت افت اتصال</span>
                        </div>
                        <label class="switch-control">
                            <input type="checkbox" id="setting-killswitch">
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-label">
                            تونل IPv6
                            <span>جلوگیری از نشت موقعیت مکانی بومی</span>
                        </div>
                        <label class="switch-control">
                            <input type="checkbox" checked id="setting-ipv6">
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

        </main>

        <!-- Bottom Tab Navigation Bar -->
        <nav class="tab-bar">
            <button class="tab-item" data-page-id="page-settings">
                <i class="ph-bold ph-gear"></i>
                <span>تنظیمات</span>
            </button>
            <button class="tab-item" data-page-id="page-dashboard">
                <i class="ph-bold ph-user-circle"></i>
                <span>داشبورد</span>
            </button>
            <button class="tab-item" data-page-id="page-servers">
                <i class="ph-bold ph-globe-hemisphere-west"></i>
                <span>سرورها</span>
            </button>
            <button class="tab-item active" data-page-id="page-home">
                <i class="ph-bold ph-house-simple"></i>
                <span>خانه</span>
            </button>
        </nav>
    </div>

    <!-- Dedicated Mobile JS Logic -->
    <script src="mobile-script.js"></script>
</body>
</html>
