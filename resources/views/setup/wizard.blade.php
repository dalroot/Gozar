<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ راه‌اندازی و پیکربندی اولیه Gozar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-[#0f172a] text-slate-100 min-h-screen flex flex-col justify-center items-center p-4 selection:bg-indigo-500 selection:text-white">

    <div class="w-full max-w-xl bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-indigo-500/10 my-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-rose-500 p-1 mb-4 shadow-lg shadow-indigo-500/30">
                <div class="w-full h-full bg-slate-950 rounded-xl flex items-center justify-center">
                    <span class="text-3xl font-black tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-rose-400">G</span>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-white">ویزارد راه‌اندازی سریع <span class="text-indigo-400">Gozar</span></h1>
            <p class="text-slate-400 text-sm mt-1">مشخصات اولیه پلتفرم خود را در چند گام ساده وارد کنید</p>
        </div>

        <!-- Form -->
        <form id="setupForm" class="space-y-6">
            <input type="hidden" name="token" value="{{ $token }}">

            <!-- Step 1: Domain & SSL -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-5 space-y-4">
                <div class="flex items-center gap-2 text-indigo-400 font-semibold text-sm">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 text-xs">۱</span>
                    <span>دامنه و گواهینامه امنیتی SSL</span>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">دامنه یا ساب‌دامنه پنل (بدون https)</label>
                    <div class="flex gap-2">
                        <input type="text" id="domain" name="domain" placeholder="panel.yourdomain.com" required
                               class="flex-1 bg-slate-900 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors">
                        <button type="button" id="checkDnsBtn" onclick="verifyDns()"
                                class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-medium text-slate-200 rounded-xl transition-colors whitespace-nowrap">
                            تست DNS
                        </button>
                    </div>
                    <div id="dnsResult" class="hidden text-xs mt-2 p-2.5 rounded-lg"></div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">ایمیل مدیر (برای دریافت گواهی SSL و بازیابی)</label>
                    <input type="email" id="admin_email" name="admin_email" placeholder="admin@yourdomain.com" required
                           class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors">
                </div>
            </div>

            <!-- Step 2: Telegram Bot -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-5 space-y-4">
                <div class="flex items-center gap-2 text-indigo-400 font-semibold text-sm">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 text-xs">۲</span>
                    <span>ربات تلگرام (اختیاری)</span>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">توکن ربات تلگرام (از BotFather)</label>
                    <div class="flex gap-2">
                        <input type="text" id="bot_token" name="bot_token" placeholder="123456789:AAH..." 
                               class="flex-1 bg-slate-900 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors font-mono" dir="ltr">
                        <button type="button" id="checkBotBtn" onclick="verifyBot()"
                                class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-medium text-slate-200 rounded-xl transition-colors whitespace-nowrap">
                            تست توکن
                        </button>
                    </div>
                    <div id="botResult" class="hidden text-xs mt-2 p-2.5 rounded-lg"></div>
                </div>
            </div>

            <!-- Step 3: Admin Credentials -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-5 space-y-4">
                <div class="flex items-center gap-2 text-indigo-400 font-semibold text-sm">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 text-xs">۳</span>
                    <span>رمز عبور پنل مدیریت (Filament 3)</span>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">رمز عبور دلخواه برای ورود به پنل</label>
                    <input type="password" id="admin_password" name="admin_password" placeholder="حداقل ۶ کاراکتر" required minlength="6"
                           class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors" dir="ltr">
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="submitBtn"
                    class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white font-bold text-sm shadow-xl shadow-indigo-500/25 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                <span>تکمیل و راه‌اندازی نهایی پلتفرم</span>
                <svg class="w-4 h-4 -rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>

        <!-- Progress Overlay (Hidden by default) -->
        <div id="progressModal" class="hidden fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex flex-col items-center justify-center p-6 text-center">
            <div class="w-16 h-16 border-4 border-indigo-500/20 border-t-indigo-500 rounded-full animate-spin mb-4"></div>
            <h3 class="text-lg font-bold text-white mb-2">در حال اعمال تنظیمات و دریافت SSL...</h3>
            <p class="text-sm text-slate-400 max-w-sm">لطفاً چند ثانیه شکیبا باشید. به زودی به صورت خودکار به پنل مدیریت منتقل خواهید شد.</p>
        </div>

    </div>

    <script>
        const token = "{{ $token }}";

        async function verifyDns() {
            const domain = document.getElementById('domain').value.trim();
            const btn = document.getElementById('checkDnsBtn');
            const res = document.getElementById('dnsResult');

            if (!domain) {
                alert('لطفاً ابتدا دامنه را وارد کنید.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'در حال بررسی...';
            res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-slate-800 text-slate-300';
            res.innerHTML = 'در حال بررسی DNS...';
            res.classList.remove('hidden');

            try {
                const response = await fetch(`/setup/check-dns?token=${encodeURIComponent(token)}&domain=${encodeURIComponent(domain)}`);
                const data = await response.json();

                if (data.matched) {
                    res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
                } else {
                    res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-amber-500/20 text-amber-300 border border-amber-500/30';
                }
                res.innerHTML = data.message;
            } catch (err) {
                res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-rose-500/20 text-rose-300 border border-rose-500/30';
                res.innerHTML = 'خطا در ارتباط با سرور.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'تست DNS';
            }
        }

        async function verifyBot() {
            const botToken = document.getElementById('bot_token').value.trim();
            const btn = document.getElementById('checkBotBtn');
            const res = document.getElementById('botResult');

            if (!botToken) {
                alert('لطفاً ابتدا توکن ربات را وارد کنید.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'در حال بررسی...';
            res.classList.remove('hidden');

            try {
                const response = await fetch(`/setup/check-bot?token=${encodeURIComponent(token)}&bot_token=${encodeURIComponent(botToken)}`);
                const data = await response.json();

                if (data.success) {
                    res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
                    res.innerHTML = `✅ متصل شد: <b>${data.bot_name}</b> (${data.username})`;
                } else {
                    res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-rose-500/20 text-rose-300 border border-rose-500/30';
                    res.innerHTML = `❌ ${data.message}`;
                }
            } catch (err) {
                res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-rose-500/20 text-rose-300 border border-rose-500/30';
                res.innerHTML = 'خطا در اعتبارسنجی توکن.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'تست توکن';
            }
        }

        document.getElementById('setupForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const form = e.target;
            const submitBtn = document.getElementById('submitBtn');
            const modal = document.getElementById('progressModal');

            submitBtn.disabled = true;
            modal.classList.remove('hidden');

            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/setup/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 5000);
                } else {
                    alert('خطا در راه‌اندازی: ' + (data.message || 'مشخصات را بررسی کنید.'));
                    modal.classList.add('hidden');
                    submitBtn.disabled = false;
                }
            } catch (err) {
                alert('خطا در ارسال درخواست به سرور.');
                modal.classList.add('hidden');
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
