<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>روزنه | پنل مدیریت {{ $title ?? '' }}</title>

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">

<!-- Google Fonts: Fredoka & Vazirmatn -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

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

<!-- Custom Style for Claymorphism & Responsiveness -->
<style>
    :root {
        --color-primary: #FF7A3C;
        --color-bg: #FFF7ED;
        --color-bg-alt: #FFF1E2;
        --color-border: #FED7AA;
        --color-text: #431407;
        --color-text-muted: #7C2D12;
    }
    html, body {
        overflow-x: hidden !important;
        max-width: 100vw !important;
        width: 100% !important;
        font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .clay-card {
        background: #ffffff;
        border: 2px solid var(--color-border);
        border-radius: 20px;
        box-shadow: 0 10px 25px -5px rgba(234, 88, 12, 0.05);
    }
    .admin-nav-item.active {
        background-color: #FF7A3C;
        color: #ffffff !important;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(255, 122, 60, 0.3);
    }
</style>

<!-- Phosphor Icons -->
<script src="https://unpkg.com/@phosphor-icons/web"></script>
