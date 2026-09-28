<!-- resources/views/webapp/layout.blade.php -->
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LookaNet WebApp</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Telegram Web App SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <!-- Fonts -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Vazirmatn', 'sans-serif'],
                        inter: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        glass: {
                            50: 'rgba(255, 255, 255, 0.05)',
                            100: 'rgba(255, 255, 255, 0.1)',
                            200: 'rgba(255, 255, 255, 0.2)',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --tg-theme-bg-color: #09090b; /* Very dark zinc */
            --tg-theme-text-color: #ffffff;
            --tg-theme-secondary-bg-color: #18181b;
            --tg-theme-button-color: #3b82f6;
            --tg-theme-button-text-color: #ffffff;
            --tg-theme-hint-color: #a1a1aa;
        }

        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: var(--tg-theme-bg-color);
            color: var(--tg-theme-text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Abstract Background Orbs */
        .bg-orbs {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            overflow: hidden;
            background: #09090b;
        }
        .bg-orbs::before, .bg-orbs::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: float 10s infinite alternate ease-in-out;
        }
        .bg-orbs::before {
            top: -10%;
            left: -10%;
            width: 60vw;
            height: 60vw;
            background: rgba(59, 130, 246, 0.3); /* Blue */
        }
        .bg-orbs::after {
            bottom: -10%;
            right: -10%;
            width: 50vw;
            height: 50vw;
            background: rgba(168, 85, 247, 0.2); /* Purple */
            animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 30px); }
        }

        /* Glassmorphism Utilities */
        .glass-panel {
            background: rgba(24, 24, 27, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .glass-button {
            background: linear-gradient(135deg, var(--tg-theme-button-color), #2563eb);
            color: var(--tg-theme-button-text-color);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            transition: all 0.2s ease;
        }
        .glass-button:active {
            transform: scale(0.96);
        }

        .fade-in {
            animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Hide Scrollbar */
        ::-webkit-scrollbar { width: 0; background: transparent; }
    </style>
</head>
<body class="pb-24 select-none antialiased">

<div class="bg-orbs"></div>

<!-- Content Section -->
<div class="p-5 fade-in max-w-md mx-auto relative z-10">
    @yield('content')
</div>

<!-- Premium Bottom Navigation -->
<div class="fixed bottom-0 left-0 right-0 z-50 flex justify-center pb-5 pt-2 px-4 pointer-events-none">
    <div class="glass-panel w-full max-w-sm rounded-2xl flex justify-around p-2 pointer-events-auto shadow-2xl">
        <a href="{{ route('webapp.index', ['tg_id' => request('tg_id')]) }}"
           class="flex flex-col items-center py-2 px-3 rounded-xl w-full transition-all {{ request()->routeIs('webapp.index') ? 'text-blue-400 bg-blue-500/10' : 'text-gray-400 opacity-70 hover:opacity-100' }}">
            <i class="ri-dashboard-fill text-2xl mb-1"></i>
            <span class="text-[10px] font-medium">داشبورد</span>
        </a>

        <a href="{{ route('webapp.plans', ['tg_id' => request('tg_id')]) }}"
           class="flex flex-col items-center py-2 px-3 rounded-xl w-full transition-all {{ request()->routeIs('webapp.plans') ? 'text-blue-400 bg-blue-500/10' : 'text-gray-400 opacity-70 hover:opacity-100' }}">
            <i class="ri-store-2-fill text-2xl mb-1"></i>
            <span class="text-[10px] font-medium">سرویس‌ها</span>
        </a>

        <a href="{{ route('webapp.wheel', ['tg_id' => request('tg_id')]) }}"
           class="flex flex-col items-center py-2 px-3 rounded-xl w-full transition-all {{ request()->routeIs('webapp.wheel') ? 'text-purple-400 bg-purple-500/10' : 'text-gray-400 opacity-70 hover:opacity-100' }}">
            <div class="relative">
                <i class="ri-blaze-fill text-2xl mb-1 {{ request()->routeIs('webapp.wheel') ? 'animate-pulse' : '' }}"></i>
                <!-- Red dot indicator for free spin -->
                <div class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full animate-ping"></div>
                <div class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full"></div>
            </div>
            <span class="text-[10px] font-medium">شانس</span>
        </a>

        <a href="{{ route('webapp.referral', ['tg_id' => request('tg_id')]) }}"
           class="flex flex-col items-center py-2 px-3 rounded-xl w-full transition-all {{ request()->routeIs('webapp.referral') ? 'text-green-400 bg-green-500/10' : 'text-gray-400 opacity-70 hover:opacity-100' }}">
            <i class="ri-user-add-fill text-2xl mb-1"></i>
            <span class="text-[10px] font-medium">دعوت</span>
        </a>
    </div>
</div>

<script>
    const tg = window.Telegram.WebApp;
    tg.expand();
    tg.MainButton.hide();

    if (tg.themeParams) {
        const root = document.documentElement;
        if(tg.themeParams.bg_color) root.style.setProperty('--tg-theme-bg-color', tg.themeParams.bg_color);
        if(tg.themeParams.text_color) root.style.setProperty('--tg-theme-text-color', tg.themeParams.text_color);
        if(tg.themeParams.button_color) root.style.setProperty('--tg-theme-button-color', tg.themeParams.button_color);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const userId = tg.initDataUnsafe?.user?.id;

    if (!urlParams.has('tg_id') && userId) {
        urlParams.set('tg_id', userId);
        window.location.search = urlParams.toString();
    }
</script>
</body>
</html>
