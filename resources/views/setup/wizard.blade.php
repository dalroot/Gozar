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

        <!-- Setup Card / Form Container -->
        <div id="setupCard">
            <form id="setupForm" class="space-y-6">
                <input type="hidden" name="token" value="{{ $token }}">

                <!-- Step 1: Domain & SSL -->
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center gap-2 text-indigo-400 font-semibold text-sm">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 text-xs font-bold">۱</span>
                        <span>دامنه و گواهینامه امنیتی SSL</span>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">دامنه یا ساب‌دامنه پنل (بدون https)</label>
                        <div class="flex gap-2">
                            <input type="text" id="domain" name="domain" placeholder="panel.yourdomain.com" required
                                   class="flex-1 bg-slate-900 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors font-mono" dir="ltr">
                            <button type="button" id="checkDnsBtn" onclick="verifyDns()"
                                    class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-medium text-slate-200 rounded-xl transition-colors whitespace-nowrap">
                                تست DNS
                            </button>
                        </div>
                        <div id="dnsResult" class="hidden text-xs mt-2 p-2.5 rounded-lg"></div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">ایمیل مدیر (جهت ثبت SSL و حساب کاربری ادمین)</label>
                        <input type="email" id="admin_email" name="admin_email" placeholder="admin@yourdomain.com" required
                               class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors font-mono" dir="ltr">
                    </div>
                </div>

                <!-- Step 2: Telegram Bot -->
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center gap-2 text-indigo-400 font-semibold text-sm">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 text-xs font-bold">۲</span>
                        <span>ربات تلگرام (اختیاری اما پیشنهادی)</span>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">توکن ربات تلگرام (از @BotFather)</label>
                        <input type="text" id="bot_token" name="bot_token" placeholder="123456789:AAH..." 
                               class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors font-mono" dir="ltr">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5 flex justify-between items-center">
                            <span>چت‌آی‌دی عددی ادمین (Chat ID)</span>
                            <span class="text-[11px] text-indigo-400">شناسایی خودکار با زدن Start در ربات</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="text" id="admin_chat_id" name="admin_chat_id" placeholder="مثال: 123456789"
                                   class="flex-1 bg-slate-900 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors font-mono" dir="ltr">
                            <button type="button" id="checkBotBtn" onclick="verifyBot()"
                                    class="px-4 py-2.5 bg-indigo-600/80 hover:bg-indigo-600 border border-indigo-500/50 text-xs font-medium text-white rounded-xl transition-colors whitespace-nowrap flex items-center gap-1.5">
                                <span>🚀 تست و ارسال پیام</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                            💡 <b>راهنمایی:</b> ابتدا در تلگرام ربات ساخته شده را باز کنید و دکمه <b>Start</b> را بزنید. سپس روی دکمه «تست و ارسال پیام» کلیک کنید تا سیستم چت‌آی‌دی شما را شناسایی کرده و پیام تایید ارسال نماید.
                        </p>
                        <div id="botResult" class="hidden text-xs mt-2 p-2.5 rounded-lg"></div>
                    </div>
                </div>

                <!-- Step 3: Admin Credentials -->
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center gap-2 text-indigo-400 font-semibold text-sm">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 text-xs font-bold">۳</span>
                        <span>رمز عبور پنل مدیریت (Filament 3)</span>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">رمز عبور دلخواه برای ورود به پنل</label>
                        <div class="relative">
                            <input type="password" id="admin_password" name="admin_password" placeholder="حداقل ۶ کاراکتر" required minlength="6"
                                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors font-mono" dir="ltr">
                            <button type="button" onclick="togglePassVisibility('admin_password')" class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 hover:text-white text-xs">
                                👁
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn"
                        class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white font-bold text-sm shadow-xl shadow-indigo-500/25 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                    <span>تکمیل و راه‌اندازی نهایی پلتفرم</span>
                    <svg class="w-4 h-4 -rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>

        <!-- Success Screen (Displayed upon successful setup) -->
        <div id="successCard" class="hidden space-y-6">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 mb-3 shadow-lg shadow-emerald-500/20">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white">راه‌اندازی Gozar با موفقیت انجام شد! 🎉</h2>
                <p class="text-xs text-slate-400 mt-1">پیکربندی دیتابیس، وب‌سرور و گواهینامه امنیتی تکمیل گردید.</p>
            </div>

            <!-- Prominent Warning Notice -->
            <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 text-xs text-amber-200 leading-relaxed space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-amber-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>توجه امنیتی بسیار مهم:</span>
                </div>
                <p>لطفاً اطلاعات ورود زیر را حتماً در یک مکان امن (مانند نوت‌بوک یا پسورد منیجر) ذخیره کنید. پس از بستن این برگه، این مشخصات دیگر در این صفحه نمایش داده نخواهند شد.</p>
            </div>

            <!-- Credentials Box -->
            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4">
                <div>
                    <span class="block text-xs font-medium text-slate-400 mb-1">🌐 آدرس پنل مدیریت (Admin URL):</span>
                    <div class="flex gap-2">
                        <input type="text" id="finalAdminUrl" readonly class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-indigo-300 font-mono select-all" dir="ltr">
                        <button type="button" onclick="copyInput('finalAdminUrl', this)" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-xs rounded-xl text-slate-300 transition-colors whitespace-nowrap">کپی آدرس</button>
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-medium text-slate-400 mb-1">👤 ایمیل مدیر:</span>
                    <div class="flex gap-2">
                        <input type="text" id="finalAdminEmail" readonly class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono select-all" dir="ltr">
                        <button type="button" onclick="copyInput('finalAdminEmail', this)" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-xs rounded-xl text-slate-300 transition-colors whitespace-nowrap">کپی ایمیل</button>
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-medium text-slate-400 mb-1">🔑 رمز عبور مدیر:</span>
                    <div class="flex gap-2">
                        <input type="text" id="finalAdminPassword" readonly class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-emerald-400 font-mono select-all" dir="ltr">
                        <button type="button" onclick="copyInput('finalAdminPassword', this)" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-xs rounded-xl text-slate-300 transition-colors whitespace-nowrap">کپی رمز</button>
                    </div>
                </div>
            </div>

            <!-- Copy All Button -->
            <button type="button" id="copyAllBtn" onclick="copyAllCredentials()"
                    class="w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 text-xs font-semibold transition-all flex items-center justify-center gap-2">
                <span>📋 کپی همه مشخصات ورود به صورت یکجا</span>
            </button>

            <!-- Proceed to Admin Panel Button -->
            <a id="adminPanelLink" href="#" target="_blank"
               class="block w-full text-center py-3.5 px-6 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold text-sm shadow-xl shadow-emerald-500/25 transition-all duration-200">
                🚀 ورود به پنل مدیریت Gozar
            </a>
        </div>

        <!-- Progress Overlay (Hidden by default) -->
        <div id="progressModal" class="hidden fixed inset-0 bg-slate-950/85 backdrop-blur-md z-50 flex flex-col items-center justify-center p-6 text-center">
            <div class="w-16 h-16 border-4 border-indigo-500/20 border-t-indigo-500 rounded-full animate-spin mb-4"></div>
            <h3 class="text-lg font-bold text-white mb-2">در حال اعمال تنظیمات و دریافت SSL...</h3>
            <p class="text-sm text-slate-400 max-w-sm">لطفاً چند ثانیه شکیبا باشید. تنظیمات در حال نهایی‌سازی در سرور است...</p>
        </div>

    </div>

    <script>
        const token = "{{ $token }}";

        function togglePassVisibility(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

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
            const adminChatId = document.getElementById('admin_chat_id').value.trim();
            const btn = document.getElementById('checkBotBtn');
            const res = document.getElementById('botResult');

            if (!botToken) {
                alert('لطفاً ابتدا توکن ربات را وارد کنید.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'در حال تست و ارسال...';
            res.classList.remove('hidden');
            res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-slate-800 text-slate-300';
            res.innerHTML = 'در حال ارتباط با API تلگرام...';

            try {
                const url = `/setup/check-bot?token=${encodeURIComponent(token)}&bot_token=${encodeURIComponent(botToken)}&admin_chat_id=${encodeURIComponent(adminChatId)}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
                    res.innerHTML = data.message;

                    if (data.detected_chat_id && !document.getElementById('admin_chat_id').value) {
                        document.getElementById('admin_chat_id').value = data.detected_chat_id;
                    }
                } else {
                    res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-rose-500/20 text-rose-300 border border-rose-500/30';
                    res.innerHTML = `❌ ${data.message}`;
                }
            } catch (err) {
                res.className = 'text-xs mt-2 p-2.5 rounded-lg bg-rose-500/20 text-rose-300 border border-rose-500/30';
                res.innerHTML = 'خطا در اعتبارسنجی ربات.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🚀 تست و ارسال پیام';
            }
        }

        function copyInput(id, btn) {
            const input = document.getElementById(id);
            navigator.clipboard.writeText(input.value).then(() => {
                const originalText = btn.innerText;
                btn.innerText = 'کپی شد ✅';
                btn.classList.add('text-emerald-400');
                setTimeout(() => {
                    btn.innerText = originalText;
                    btn.classList.remove('text-emerald-400');
                }, 2000);
            });
        }

        function copyAllCredentials() {
            const url = document.getElementById('finalAdminUrl').value;
            const email = document.getElementById('finalAdminEmail').value;
            const pass = document.getElementById('finalAdminPassword').value;

            const text = `⚡️ اطلاعات ورود به پنل مدیریت Gozar\n---------------------------------------\n🌐 آدرس پنل: ${url}\n👤 ایمیل ادمین: ${email}\n🔑 رمز عبور: ${pass}\n---------------------------------------`;

            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('copyAllBtn');
                btn.innerText = '✅ تمام مشخصات با موفقیت در کلیپ‌بورد کپی شد!';
                btn.classList.add('text-emerald-400', 'border-emerald-500/50');
                setTimeout(() => {
                    btn.innerText = '📋 کپی همه مشخصات ورود به صورت یکجا';
                    btn.classList.remove('text-emerald-400', 'border-emerald-500/50');
                }, 3000);
            });
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
                        modal.classList.add('hidden');
                        document.getElementById('setupCard').classList.add('hidden');

                        document.getElementById('finalAdminUrl').value = data.admin_url;
                        document.getElementById('finalAdminEmail').value = data.admin_email;
                        document.getElementById('finalAdminPassword').value = document.getElementById('admin_password').value;
                        document.getElementById('adminPanelLink').href = data.admin_url;

                        document.getElementById('successCard').classList.remove('hidden');
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }, 3000);
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
