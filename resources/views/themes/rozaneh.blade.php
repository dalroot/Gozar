<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <base href="{{ asset('rozaneh/') }}/">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>روزنه | راهکار پایدار دسترسی و پایداری شبکه</title>
    
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
    
    <!-- Custom Modular CSS Stylesheets -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/tokens.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="stylesheet" href="css/servers.css">
    <link rel="stylesheet" href="css/pricing.css">
    <link rel="stylesheet" href="css/faq.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="mobile.css">
    <link rel="stylesheet" href="css/mobile-hero.css">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="bg-[#FFF7ED] font-sans antialiased text-[#431407]">

        <!-- Floating Navbar Partial -->
        @include('themes.partials.navbar')

        <!-- Hero Section Partial -->
        @include('themes.partials.hero')

        <!-- Services Section Partial -->
        @include('themes.partials.services')

        <!-- Servers Section Partial -->
        @include('themes.partials.servers')

        <!-- Pricing Section Partial -->
        @include('themes.partials.pricing')

        <!-- Testimonials Section Partial -->
        @include('themes.partials.testimonials')

        <!-- Blog Section Partial -->
        @include('themes.partials.blog')

        <!-- FAQ Section Partial -->
        @include('themes.partials.faq')

        <!-- Footer & Modals Partial -->
        @include('themes.partials.footer')

    <!-- Main JavaScript Logic -->
    <script src="script.js"></script>
    <script src="mobile-script.js"></script>
</body>
</html>