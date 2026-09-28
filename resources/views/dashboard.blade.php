<!DOCTYPE html>
<html lang="fa" dir="rtl" class="overflow-x-hidden max-w-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>روزنه وی‌پی‌ان | پنل مدیریت اشتراک</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">
    
    <!-- Google Fonts: Vazirmatn & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --color-primary: #EA580C;
            --color-primary-dark: #C2410C;
            --color-text: #431407;
            --color-border: #EEDFD5;
        }
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
            width: 100% !important;
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .dash-nav-btn.active, .drawer-tab-btn.active {
            background-color: #FFF7ED;
            color: #EA580C;
            border-right: 3px solid #EA580C;
        }
        .dash-pane {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
    </style>
</head>
<body class="bg-[#FFF8F1] text-[var(--color-text)] antialiased min-h-screen flex flex-col selection:bg-orange-100 selection:text-orange-900 overflow-x-hidden max-w-full">

    <!-- 1. MODULAR HEADER NAV -->
    @include('dashboard.partials.header')

    <!-- 2. MODULAR MOBILE DRAWER -->
    @include('dashboard.partials.mobile-drawer')

    <!-- 3. MAIN DASHBOARD CONTAINER -->
    <main class="flex-1 py-4 sm:py-8 w-full max-w-full overflow-x-hidden">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 w-full">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-6 items-start w-full min-w-0">
                
                <!-- MODULAR RIGHT SIDEBAR -->
                @include('dashboard.partials.sidebar')

                <!-- MODULAR MAIN CONTENT AREA -->
                <div class="lg:col-span-3 space-y-4 sm:space-y-6 w-full min-w-0 overflow-hidden">
                    
                    <!-- 1. Overview Tab -->
                    @include('dashboard.partials.overview-cards')

                    <!-- 2. My Services Tab -->
                    @include('dashboard.partials.my-services')

                    <!-- 3. Wallet Tab -->
                    @include('dashboard.partials.wallet-widget')

                    <!-- 4. Servers Tab -->
                    @include('dashboard.partials.servers-ping')

                    <!-- 5. App Downloads Tab -->
                    @include('dashboard.partials.app-downloads')

                </div>
            </div>
        </div>
    </main>

    <!-- Navigation & Drawer Toggle JS Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('[data-dash-tab]');
            const panes = document.querySelectorAll('.dash-pane');
            const drawer = document.getElementById('mobile-drawer');
            const drawerOverlay = document.getElementById('mobile-drawer-overlay');
            const btnOpenDrawer = document.getElementById('btn-toggle-mobile-drawer');
            const btnCloseDrawer = document.getElementById('btn-close-mobile-drawer');

            function switchTab(tabName) {
                panes.forEach(pane => {
                    if (pane.id === 'pane-' + tabName) {
                        pane.classList.remove('hidden');
                    } else {
                        pane.classList.add('hidden');
                    }
                });

                tabButtons.forEach(btn => {
                    if (btn.getAttribute('data-dash-tab') === tabName) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }

            tabButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-dash-tab');
                    switchTab(targetTab);
                    closeDrawer();
                });
            });

            function openDrawer() {
                if (!drawer) return;
                drawer.classList.remove('pointer-events-none', 'opacity-0');
                const content = document.getElementById('mobile-drawer-content');
                if (content) content.classList.remove('translate-x-full');
            }

            function closeDrawer() {
                if (!drawer) return;
                drawer.classList.add('pointer-events-none', 'opacity-0');
                const content = document.getElementById('mobile-drawer-content');
                if (content) content.classList.add('translate-x-full');
            }

            if (btnOpenDrawer) btnOpenDrawer.addEventListener('click', openDrawer);
            if (btnCloseDrawer) btnCloseDrawer.addEventListener('click', closeDrawer);
            if (drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);
        });
    </script>
</body>
</html>
