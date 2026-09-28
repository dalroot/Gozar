@php
    use App\Models\Setting;

    $settings = Setting::all()->pluck('value', 'key');
    $activeAuthTheme = $settings->get('active_auth_theme', 'rozaneh');
    $brandName = $settings->get('auth_brand_name', 'روزنه');
@endphp

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $brandName }} - ورود به حساب کاربری</title>

    <!-- Google Fonts: Vazirmatn & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <link rel="stylesheet" href="{{ asset('themes/auth/' . $activeAuthTheme . '/css/style.css') }}?v={{ time() }}">
</head>
<body class="{{ $activeAuthTheme }}-auth-body">

<div class="auth-main-wrapper">
    <div class="auth-card-saas">

        <!-- RIGHT PANEL: AUTHENTICATION FORM -->
        <div class="auth-panel-right">

            <div class="auth-brand">
                <div class="brand-badge">
                    <div class="logo-icon-box">
                        <i class="ph-fill ph-sun"></i>
                    </div>
                    <span class="logo-text">{{ $brandName }}</span>
                </div>
                <h1 class="auth-title">ورود به {{ $brandName }}</h1>
                <p class="auth-subtitle">برای مدیریت اشتراک‌ها و سرویس‌های خود وارد شوید</p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" id="saas-login-form">
                @csrf

                <!-- Email Input Field -->
                <div class="form-step-group">
                    <label class="form-label" for="email">پست الکترونیک (ایمیل)</label>
                    <div class="input-container">
                        <i class="ph-bold ph-envelope-simple input-icon-right"></i>
                        <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@example.com">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="input-error-msg" />
                </div>

                <!-- Password Step (Revealed on continuation) -->
                <div id="password-wrapper" class="password-step @if($errors->any() || old('email')) show @endif">
                    <div class="form-step-group">
                        <div class="flex justify-between items-center mb-2">
                            <label class="form-label mb-0" for="password">رمز عبور</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" style="font-size: 12px; color: var(--color-primary); font-weight: 700; text-decoration: none;">رمز عبور را فراموش کرده‌اید؟</a>
                            @endif
                        </div>
                        <div class="input-container">
                            <i class="ph-bold ph-lock-key input-icon-right"></i>
                            <input id="password" class="form-input" type="password" name="password" autocomplete="current-password" placeholder="••••••••">
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="input-error-msg" />
                    </div>
                </div>

                <!-- Primary Action Button -->
                <button type="submit" id="btn-submit-action" class="btn-cta-primary">
                    <span id="btn-text">@if($errors->any() || old('email')) ورود به حساب @else ادامه @endif</span>
                    <i class="ph-bold ph-arrow-left"></i>
                </button>

                <!-- Google OAuth Sign-In Button -->
                <a href="{{ route('auth.google') }}" class="btn-google-action">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>ورود با حساب گوگل (Gmail)</span>
                </a>

                <!-- Privacy & Guarantee Badge -->
                <div class="privacy-badge">
                    <i class="ph-bold ph-shield-check"></i>
                    <span>🔒 حفظ حریم خصوصی • بدون ذخیره لاگ و اطلاعات شخصی</span>
                </div>

                <div class="back-to-site">
                    <a href="/">
                        <i class="ph-bold ph-arrow-right"></i>
                        <span>بازگشت به صفحه اصلی</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- LEFT PANEL: DASHBOARD VISUAL PREVIEW -->
        <div class="auth-panel-left">
            
            <div class="preview-badge">
                <i class="ph-bold ph-sparkle"></i>
                <span>پیش‌نمایش پنل کاربری</span>
            </div>

            <h3 class="preview-title">مدیریت هوشمند، سریع و آسان تمامی سرویس‌های روزنه</h3>

            <div class="dashboard-mockup">

                <!-- Card 1: My Subscriptions -->
                <div class="mockup-card">
                    <div class="mockup-card-header">
                        <div class="mockup-card-title">
                            <i class="ph-bold ph-lightning"></i>
                            <span>اشتراک‌های من</span>
                        </div>
                        <span class="status-dot-active">
                            <span></span> متصل و فعال
                        </span>
                    </div>

                    <div class="volume-info">
                        <span>حجم مصرفی / کل</span>
                        <span style="font-family: 'Outfit', sans-serif;">42.5 GB / 50 GB</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill"></div>
                    </div>
                </div>

                <!-- Card 2 & 3: Services & Servers -->
                <div class="mockup-row">
                    <div class="mockup-subcard">
                        <h4>وضعیت اتصال و سرور</h4>
                        <div class="server-item">
                            <span>🇩🇪 آلمان (فرانکفورت)</span>
                            <span class="ping-value">38 ms</span>
                        </div>
                    </div>

                    <div class="mockup-subcard">
                        <h4>دانلود برنامه‌ها</h4>
                        <div class="app-icons-row">
                            <div class="app-pill" title="iOS"><i class="ph-bold ph-apple-logo"></i></div>
                            <div class="app-pill" title="Android"><i class="ph-bold ph-android-logo"></i></div>
                            <div class="app-pill" title="Windows"><i class="ph-bold ph-windows-logo"></i></div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const emailInput = document.getElementById('email');
        const passwordWrapper = document.getElementById('password-wrapper');
        const passwordInput = document.getElementById('password');
        const btnSubmit = document.getElementById('btn-submit-action');
        const btnText = document.getElementById('btn-text');

        let isPasswordShown = passwordWrapper.classList.contains('show');

        btnSubmit.addEventListener('click', function(e) {
            if (!isPasswordShown) {
                const emailVal = emailInput.value.trim();
                if (emailVal !== '' && emailInput.checkValidity()) {
                    e.preventDefault();
                    passwordWrapper.classList.add('show');
                    passwordInput.setAttribute('required', 'required');
                    passwordInput.focus();
                    btnText.textContent = 'ورود به حساب';
                    isPasswordShown = true;
                }
            }
        });
    });
</script>
</body>
</html>