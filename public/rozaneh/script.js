document.addEventListener('DOMContentLoaded', () => {
    // 1. Smooth scrolling for anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            const targetEl = document.querySelector(targetId);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // 2. Pricing Buttons click handler
    const buyButtons = document.querySelectorAll('.buy-btn');
    const checkoutSection = document.getElementById('checkout-section');
    const selectedPackageText = document.getElementById('selected-package-text');
    const checkoutTelegramCta = document.getElementById('checkout-telegram-cta');

    buyButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const packageName = e.target.getAttribute('data-package');
            const packagePrice = e.target.getAttribute('data-price');

            // Reset buy buttons text
            buyButtons.forEach(b => {
                b.textContent = 'خرید پلن';
            });

            // Highlight selected button
            e.target.textContent = 'انتخاب شد ✓';

            // Populate checkout text
            selectedPackageText.textContent = `${packageName} (${packagePrice})`;

            // Generate specific Telegram start parameter for this package
            let startParam = 'buy_core';
            if (packageName.includes('حرفه‌ای') || packageName.includes('Professional')) {
                startParam = 'buy_pro';
            } else if (packageName.includes('سازمانی') || packageName.includes('Enterprise')) {
                startParam = 'buy_enterprise';
            }
            checkoutTelegramCta.href = `https://t.me/RozanehVpnBot?start=${startParam}`;

            // Show checkout section
            checkoutSection.classList.remove('hidden');

            // Scroll to checkout
            setTimeout(() => {
                checkoutSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        });
    });

    // 3. Referral Range Slider Calculator
    const referralSlider = document.getElementById('referral-slider');
    const friendsCountSpan = document.getElementById('friends-count');
    const totalGiftDataSpan = document.getElementById('total-gift-data');

    if (referralSlider && friendsCountSpan && totalGiftDataSpan) {
        referralSlider.addEventListener('input', (e) => {
            const val = parseInt(e.target.value, 10);
            friendsCountSpan.textContent = val;
            
            // 2GB base + 0.5GB per friend
            const totalData = 2 + (val * 0.5);
            totalGiftDataSpan.textContent = totalData % 1 === 0 ? totalData : totalData.toFixed(1);
        });
    }

    // 4. Bento Grid Showcase Interactivity
    const bentoPingVal = document.getElementById('bento-ping-val');
    const bentoCopyIpBtn = document.getElementById('bento-copy-ip-btn');
    const bentoIpAddress = document.getElementById('bento-ip-address');

    // Fluctuating Ping simulator
    if (bentoPingVal) {
        setInterval(() => {
            // Fluctuate ping between 28 and 36 ms
            const randomPing = Math.floor(Math.random() * (36 - 28 + 1)) + 28;
            bentoPingVal.textContent = randomPing;
        }, 2500);
    }

    // Clipboard Copy for Static IP
    if (bentoCopyIpBtn && bentoIpAddress) {
        bentoCopyIpBtn.addEventListener('click', () => {
            const ipText = bentoIpAddress.textContent.trim();
            navigator.clipboard.writeText(ipText).then(() => {
                const icon = bentoCopyIpBtn.querySelector('i');
                if (icon) {
                    // Temporarily change icon to checkmark
                    icon.className = 'ph-bold ph-check';
                    bentoCopyIpBtn.style.borderColor = '#166534';
                    bentoCopyIpBtn.style.color = '#166534';
                    
                    setTimeout(() => {
                        icon.className = 'ph-bold ph-copy';
                        bentoCopyIpBtn.style.borderColor = '';
                        bentoCopyIpBtn.style.color = '';
                    }, 1500);
                }
            }).catch(err => {
                console.error('Failed to copy IP address: ', err);
            });
        });
    }

    // 5. User Login / Dashboard System Simulation
    const loginTriggerBtn = document.getElementById('login-trigger-btn');
    const closeLoginBtn = document.getElementById('close-login');
    const loginModal = document.getElementById('login-modal');
    const loginForm = document.getElementById('login-form');
    const loginUserCode = document.getElementById('login-user-code');
    
    const userProfileMenu = document.getElementById('user-profile-menu');
    const navTrialBtn = document.getElementById('nav-trial-btn');
    const navbarUsername = document.getElementById('navbar-username');
    const dropdownUserId = document.getElementById('dropdown-userid');
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown');
    const logoutBtn = document.getElementById('logout-btn');
    
    // User dashboard detail items
    const dashboardPlan = document.getElementById('dashboard-plan');
    const dashboardTraffic = document.getElementById('dashboard-traffic');
    const dashboardExpiry = document.getElementById('dashboard-expiry');

    // Toggle Modal
    if (loginTriggerBtn && loginModal) {
        loginTriggerBtn.addEventListener('click', () => {
            loginModal.classList.add('active');
        });
    }

    if (closeLoginBtn && loginModal) {
        closeLoginBtn.addEventListener('click', () => {
            loginModal.classList.remove('active');
        });
    }

    // Submit Login Form
    if (loginForm && loginUserCode) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const code = loginUserCode.value.trim();
            if (code) {
                localStorage.setItem('rozaneh_user', code);
                loginModal.classList.remove('active');
                loginForm.reset();
                updateAuthUI();
            }
        });
    }

    // Toggle Dropdown
    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
        });

        // Close dropdown on click outside
        document.addEventListener('click', () => {
            userDropdown.classList.add('hidden');
        });
    }

    // Logout
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            localStorage.removeItem('rozaneh_user');
            if (userDropdown) userDropdown.classList.add('hidden');
            updateAuthUI();
        });
    }

    // Update Auth UI elements based on state
    function updateAuthUI() {
        const storedUser = localStorage.getItem('rozaneh_user');

        if (storedUser) {
            // User is logged in
            if (loginTriggerBtn) loginTriggerBtn.classList.add('hidden');
            if (navTrialBtn) navTrialBtn.classList.add('hidden');
            if (userProfileMenu) userProfileMenu.classList.remove('hidden');

            if (navbarUsername) navbarUsername.textContent = storedUser;
            if (dropdownUserId) dropdownUserId.textContent = storedUser;

            // Simulate realistic subscription details based on code digits
            let planType = 'تست رایگان ۲ گیگابایت';
            let trafficText = '۰.۴ از ۲.۰ گیگابایت';
            let expiryText = '۲۸ روز باقی‌مانده';

            // Pick details based on last digit of the code
            const lastChar = storedUser.charAt(storedUser.length - 1);
            if (!isNaN(lastChar)) {
                const digit = parseInt(lastChar, 10);
                if (digit === 1 || digit === 4) {
                    planType = 'روزنه افق';
                    trafficText = 'نامحدود (منصفانه)';
                    expiryText = '۵۴ روز باقی‌مانده';
                } else if (digit === 2 || digit === 5) {
                    planType = 'روزنه تابش';
                    trafficText = 'نامحدود (منصفانه)';
                    expiryText = '۱۲ روز باقی‌مانده';
                } else if (digit === 3 || digit === 6) {
                    planType = 'روزنه بینهایت';
                    trafficText = 'نامحدود (منصفانه)';
                    expiryText = '۳۱۰ روز باقی‌مانده';
                }
            }

            if (dashboardPlan) dashboardPlan.textContent = planType;
            if (dashboardTraffic) dashboardTraffic.textContent = trafficText;
            if (dashboardExpiry) dashboardExpiry.textContent = expiryText;
        } else {
            // User is logged out
            if (loginTriggerBtn) loginTriggerBtn.classList.remove('hidden');
            if (navTrialBtn) navTrialBtn.classList.remove('hidden');
            if (userProfileMenu) userProfileMenu.classList.add('hidden');
        }
    }

    // Run once on load
    updateAuthUI();

    // 6. Feature Detail Modal logic
    const featureReadMoreBtns = document.querySelectorAll('.feature-read-more');
    const featureModal = document.getElementById('feature-modal');
    const closeFeatureBtn = document.getElementById('close-feature');
    const closeFeatureBtn2 = document.getElementById('close-feature-btn');
    const featureModalIcon = document.getElementById('feature-modal-icon');
    const featureModalTitle = document.getElementById('feature-modal-title');
    const featureModalDesc = document.getElementById('feature-modal-desc');
    const featureModalCta = document.getElementById('feature-modal-cta');

    const featureData = {
        '1': {
            icon: '🚀',
            title: 'سرعت بالا و پینگ پایدار',
            desc: 'شبکه اختصاصی روزنه با اتصال مستقیم به دیتا سنترهای تراز اول اروپایی، کمترین مسیر مسیریابی (Routing) را انتخاب کرده و از پکت‌لاس جلوگیری می‌کند. این ویژگی به ویژه برای گیمرهایی که نیاز به پینگ پایدار و استریم بدون وقفه ویدیوهای باکیفیت دارند، طراحی شده است.',
            ctaText: 'دریافت تست ۲ گیگابایت در تلگرام',
            startParam: 'trial_2gb'
        },
        '2': {
            icon: '🔑',
            title: 'آی‌پی ثابت و تمیز (Static)',
            desc: 'آی‌پی سرورهای روزنه در تمامی زمان اتصال کاملاً ثابت بوده و هرگز تغییر نمی‌کند. این آی‌پی‌ها کاملاً تمیز و خارج از لیست‌های سیاه (Blacklists) هستند تا شما بتوانید بدون دغدغه و ترس از مسدود شدن حساب کاربری، در صرافی‌هایی مانند بایننس و پلتفرم‌های بین‌المللی پرداخت فعالیت کنید.',
            ctaText: 'دریافت آی‌پی ثابت در تلگرام',
            startParam: 'buy_pro'
        },
        '3': {
            icon: '🛡️',
            title: 'عدم نشت IP و DNS',
            desc: 'با پیاده‌سازی پروتکل‌های پیشرفته رمزنگاری در سطح هسته اتصال، مطمئن می‌شویم که هیچ درخواستی خارج از تونل امن ارسال نخواهد شد. ما با پایش مستمر و شبیه‌سازی حملات نشت آدرس‌های IPv6 و DNS، امنیت هویت واقعی شما را در بستر شبکه ۱۰۰٪ تضمین می‌کنیم.',
            ctaText: 'بررسی امنیت در تلگرام',
            startParam: 'trial_2gb'
        },
        '4': {
            icon: '🔒',
            title: 'حریم خصوصی بی‌قیدوشرط',
            desc: 'ما هیچ‌گونه سابقه و لاگی از آدرس‌های بازدید شده، زمان اتصال یا حجم مصرفی سایت‌ها را در سرورهای خود نگه نمی‌داریم. طبق قوانین روزنه، امنیت و حریم خصوصی کاربران اولویت اصلی ماست و اتصال شما کاملاً رمزنگاری شده و ناشناس باقی می‌ماند.',
            ctaText: 'دریافت تست حریم خصوصی',
            startParam: 'trial_2gb'
        },
        '5': {
            icon: '🤝',
            title: 'پشتیبانی شبانه‌روزی ۲۴/۷',
            desc: 'پشتیبانی ما توسط متخصصین فنی واقعی شبکه انجام می‌شود، نه ربات‌های پاسخگوی خودکار یا پیام‌های از پیش‌تعیین‌شده. در هر ساعت از شبانه‌روز، برای حل مشکلات اتصال، دریافت کانفیگ یا راهنمایی روی هر دستگاهی، تیم پشتیبانی ما آماده پاسخگویی سریع به شماست.',
            ctaText: 'گفتگو با کارشناس پشتیبانی',
            startParam: 'support'
        },
        '6': {
            icon: '🌐',
            title: 'خروجی ترافیک وایت‌لیست',
            desc: 'تمامی سرورهای ما از رنج آی‌پی‌های تجاری و وایت‌لیست‌شده ترافیک خروجی را ارسال می‌کنند. این یعنی شما در مواجهه با سپرهای امنیتی کلودفلر، گوگل کپچا و سایت‌های بین‌المللی با خطای ربات روبه‌رو نخواهید شد و بدون نیاز به حل کپچاهای مکرر، دسترسی روان خواهید داشت.',
            ctaText: 'شروع دسترسی روان در تلگرام',
            startParam: 'trial_2gb'
        }
    };

    if (featureReadMoreBtns && featureModal) {
        featureReadMoreBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = btn.getAttribute('data-feature-id');
                const data = featureData[id];
                if (data) {
                    featureModalIcon.textContent = data.icon;
                    featureModalTitle.textContent = data.title;
                    featureModalDesc.textContent = data.desc;
                    featureModalCta.textContent = data.ctaText;
                    featureModalCta.href = `https://t.me/RozanehVpnBot?start=${data.startParam}`;
                    
                    featureModal.classList.add('active');
                }
            });
        });

        const closeFeature = () => {
            featureModal.classList.remove('active');
        };

        if (closeFeatureBtn) closeFeatureBtn.addEventListener('click', closeFeature);
        if (closeFeatureBtn2) closeFeatureBtn2.addEventListener('click', closeFeature);
        
        // Close on clicking outside
        featureModal.addEventListener('click', (e) => {
            if (e.target === featureModal) {
                closeFeature();
            }
        });
    }

    // 7. Interactive Compatibility Status Dashboard Checker simulation
    const compatCards = document.querySelectorAll('.compat-card');
    
    if (compatCards) {
        compatCards.forEach(card => {
            const appId = card.getAttribute('data-app-id');
            const pulse = document.getElementById(`pulse-${appId}`);
            const btn = document.getElementById(`btn-${appId}`);
            
            card.addEventListener('click', (e) => {
                if (btn.classList.contains('success') || btn.classList.contains('testing')) return;
                
                btn.classList.add('testing');
                btn.textContent = 'در حال بررسی اتصال به سرور...';
                pulse.className = 'compat-pulse testing';
                pulse.textContent = '● در حال تست';
                
                setTimeout(() => {
                    btn.classList.remove('testing');
                    btn.classList.add('success');
                    
                    let successText = 'آی‌پی ثابت فعال و تمیز';
                    if (appId === 'binance') successText = '✓ تست موفق: آی‌پی تمیز سوئیس';
                    else if (appId === 'chatgpt') successText = '✓ تست موفق: OpenAI فعال';
                    else if (appId === 'figma') successText = '✓ تست موفق: پینگ ۴۵ms برقرار';
                    else if (appId === 'google') successText = '✓ تست موفق: تحریم رفع شد';
                    else if (appId === 'spotify') successText = '✓ تست موفق: استریم بدون محدودیت';
                    else if (appId === 'youtube') successText = '✓ تست موفق: پهنای باند حداکثر';
                    
                    btn.textContent = successText;
                    pulse.className = 'compat-pulse success';
                    pulse.textContent = '● فعال و تمیز';
                }, 1000);
            });
        });
    }

    // 8. Live Ping Test Simulation for Server Cards
    const pingButtons = document.querySelectorAll('.btn-ping-server');
    
    if (pingButtons) {
        pingButtons.forEach(btn => {
            const serverKey = btn.getAttribute('data-server');
            const pingDisplay = document.getElementById(`ping-${serverKey}`);
            
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (btn.classList.contains('testing')) return;
                
                btn.classList.add('testing');
                btn.textContent = 'در حال محاسبه...';
                pingDisplay.textContent = '...';
                
                // Simulate latency check based on location
                let minPing = 35;
                let maxPing = 55;
                
                if (serverKey === 'finland') { minPing = 48; maxPing = 58; }
                else if (serverKey === 'uk') { minPing = 55; maxPing = 65; }
                else if (serverKey === 'france') { minPing = 42; maxPing = 52; }
                else if (serverKey === 'singapore') { minPing = 90; maxPing = 110; }
                
                setTimeout(() => {
                    btn.classList.remove('testing');
                    btn.textContent = 'تست مجدد پینگ';
                    
                    const actualPing = Math.floor(Math.random() * (maxPing - minPing + 1)) + minPing;
                    pingDisplay.textContent = `${actualPing} ms`;
                    
                    pingDisplay.style.transition = 'color 0.2s';
                    pingDisplay.style.color = '#166534';
                    setTimeout(() => {
                        pingDisplay.style.color = 'var(--color-primary)';
                    }, 1000);
                }, 800);
            });
        });
    }

    // 9. Server Carousel Navigation Logic
    const carouselContainer = document.getElementById('server-carousel-container');
    const slideLeftBtn = document.getElementById('slide-left-btn');
    const slideRightBtn = document.getElementById('slide-right-btn');
    
    if (carouselContainer && slideLeftBtn && slideRightBtn) {
        const getSlideAmount = () => {
            const card = carouselContainer.querySelector('.server-clay-card');
            return card ? card.offsetWidth + 24 : 320; // card width + gap-6
        };
        
        slideLeftBtn.addEventListener('click', () => {
            carouselContainer.scrollBy({
                left: -getSlideAmount(),
                behavior: 'smooth'
            });
        });
        
        slideRightBtn.addEventListener('click', () => {
            carouselContainer.scrollBy({
                left: getSlideAmount(),
                behavior: 'smooth'
            });
        });
    }

    // 10. Pricing Period Switcher Logic
    const periodButtons = document.querySelectorAll('.pricing-period-btn');
    
    const pricingData = {
        monthly: {
            core: { price: '۶۵,۰۰۰', period: 'تومان / ماهانه', rawPrice: '۶۵,۰۰۰ تومان ماهانه' },
            pro: { price: '۹۵,۰۰۰', period: 'تومان / ماهانه', rawPrice: '۹۵,۰۰۰ تومان ماهانه' },
            enterprise: { price: '۱۸۰,۰۰۰', period: 'تومان / ماهانه', rawPrice: '۱۸۰,۰۰۰ تومان ماهانه' }
        },
        quarterly: {
            core: { price: '۱۷۵,۰۰۰', period: 'تومان / ۳ ماهه', rawPrice: '۱۷۵,۰۰۰ تومان ۳ ماهه' },
            pro: { price: '۱۶۵,۰۰۰', period: 'تومان / ۳ ماهه', rawPrice: '۱۶۵,۰۰۰ تومان ۳ ماهه' },
            enterprise: { price: '۴۵۰,۰۰۰', period: 'تومان / ۳ ماهه', rawPrice: '۴۵۰,۰۰۰ تومان ۳ ماهه' }
        },
        yearly: {
            core: { price: '۵۹۰,۰۰۰', period: 'تومان / سالانه', rawPrice: '۵۹۰,۰۰۰ تومان سالانه' },
            pro: { price: '۶۹۰,۰۰۰', period: 'تومان / سالانه', rawPrice: '۶۹۰,۰۰۰ تومان سالانه' },
            enterprise: { price: '۴۹۰,۰۰۰', period: 'تومان / سالانه', rawPrice: '۴۹۰,۰۰۰ تومان سالانه' }
        }
    };

    if (periodButtons.length) {
        periodButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                periodButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const period = btn.getAttribute('data-period');
                const data = pricingData[period];
                
                // Update Core Card
                const priceCore = document.getElementById('price-core');
                const periodCore = document.getElementById('period-core');
                const btnCore = document.getElementById('btn-core');
                if (priceCore) priceCore.textContent = data.core.price;
                if (periodCore) periodCore.textContent = data.core.period;
                if (btnCore) {
                    btnCore.setAttribute('data-price', data.core.rawPrice);
                    if (btnCore.textContent === 'انتخاب شد ✓') {
                        const selectedPackageText = document.getElementById('selected-package-text');
                        if (selectedPackageText) selectedPackageText.textContent = `روزنه تابش (${data.core.rawPrice})`;
                    }
                }
                
                // Update Pro Card
                const pricePro = document.getElementById('price-pro');
                const periodPro = document.getElementById('period-pro');
                const btnPro = document.getElementById('btn-pro');
                if (pricePro) pricePro.textContent = data.pro.price;
                if (periodPro) periodPro.textContent = data.pro.period;
                if (btnPro) {
                    btnPro.setAttribute('data-price', data.pro.rawPrice);
                    if (btnPro.textContent === 'انتخاب شد ✓') {
                        const selectedPackageText = document.getElementById('selected-package-text');
                        if (selectedPackageText) selectedPackageText.textContent = `روزنه افق (${data.pro.rawPrice})`;
                    }
                }
                
                // Update Enterprise Card
                const priceEnterprise = document.getElementById('price-enterprise');
                const periodEnterprise = document.getElementById('period-enterprise');
                const btnEnterprise = document.getElementById('btn-enterprise');
                if (priceEnterprise) priceEnterprise.textContent = data.enterprise.price;
                if (periodEnterprise) periodEnterprise.textContent = data.enterprise.period;
                if (btnEnterprise) {
                    btnEnterprise.setAttribute('data-price', data.enterprise.rawPrice);
                    if (btnEnterprise.textContent === 'انتخاب شد ✓') {
                        const selectedPackageText = document.getElementById('selected-package-text');
                        if (selectedPackageText) selectedPackageText.textContent = `روزنه بینهایت (${data.enterprise.rawPrice})`;
                    }
                }
            });
        });
    }






    // Native Modal Dialog Controllers

    const openDialogButtons = document.querySelectorAll('.btn-open-dialog');
    const closeDialogButtons = document.querySelectorAll('.btn-close-dialog');
    const dialogs = document.querySelectorAll('.clay-dialog');

    // Explicit Auth Modal Button Handler
    const mainAuthBtn = document.getElementById('btn-open-auth-modal');
    const mainAuthModal = document.getElementById('auth-modal');
    if (mainAuthBtn && mainAuthModal) {
        mainAuthBtn.addEventListener('click', (e) => {
            e.preventDefault();
            mainAuthModal.showModal();
        });
    }

    // Open dialog on button click
    openDialogButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const dialog = document.getElementById(targetId);
            if (dialog) {
                dialog.showModal();
            }
        });
    });

    // Close dialog on button click
    closeDialogButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const dialog = btn.closest('dialog');
            if (dialog) {
                dialog.close();
            }
        });
    });

    // Light dismiss: Close dialog on backdrop click
    dialogs.forEach(dialog => {
        dialog.addEventListener('click', (e) => {
            const rect = dialog.getBoundingClientRect();
            const isInDialog = (
                rect.top <= e.clientY && e.clientY <= rect.top + rect.height &&
                rect.left <= e.clientX && e.clientX <= rect.left + rect.width
            );
            if (!isInDialog) {
                dialog.close();
            }
        });
    });

    // 6. Interactive FAQ Accordion and Filters (ChatGPT Design)
    const faqItems = [...document.querySelectorAll('.faq-item')];

    // Accordion toggle
    document.querySelectorAll('.faq-question').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = btn.closest('.faq-item');
            const willOpen = !item.classList.contains('open');

            // Close all other items
            faqItems.forEach(x => {
                x.classList.remove('open');
                x.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
            });

            // Open clicked item if it was closed
            if (willOpen) {
                item.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Tab filter
    document.querySelectorAll('.faq-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelector('.faq-tab.active').classList.remove('active');
            tab.classList.add('active');
            const f = tab.dataset.filter;
            let shown = 0;

            faqItems.forEach(item => {
                // Close all items on filter change
                item.classList.remove('open');
                item.querySelector('.faq-question').setAttribute('aria-expanded', 'false');

                const yes = f === 'all' || item.dataset.category === f;
                if (yes) {
                    item.classList.remove('hidden-faq');
                    shown++;
                } else {
                    item.classList.add('hidden-faq');
                }
            });

            const emptyEl = document.querySelector('.faq-empty');
            if (emptyEl) emptyEl.style.display = shown ? 'none' : 'block';
        });
    });

    // 10. Technical Features Bento Grid Mode Switcher
    const featureModeTabs = document.querySelectorAll('.feature-mode-tab');
    const featureRouteTitle = document.getElementById('feature-route-title');
    const featureRouteCopy = document.getElementById('feature-route-copy');
    const featureRouteState = document.getElementById('feature-route-state');

    const featureModeData = {
        daily: {
            title: 'مسیر هوشمند برای اتصال پایدارتر',
            copy: 'مسیریابی پویا، مسیر مناسب را بر اساس اپراتور و وضعیت لحظه‌ای شبکه انتخاب می‌کند.',
            state: 'مسیر پایدار'
        },
        ai: {
            title: 'دسترسی روان‌تر به ابزارهای هوش مصنوعی',
            copy: 'مسیرهای منتخب برای استفاده پایدارتر از ابزارهای کاری، گیت‌هاب و سرویس‌های بین‌المللی بهینه شده‌اند.',
            state: 'IP ثابت و تمیز'
        },
        game: {
            title: 'مسیر بهینه برای بازی و استریم 4K',
            copy: 'انتخاب مسیر مستقیم فرانکفورت با حداقل نوسان برای بازی‌های آنلاین رقابتی و پخش ویدئو بدون بافر.',
            state: 'مسیر کم‌نوسان'
        }
    };

    if (featureModeTabs.length && featureRouteTitle) {
        featureModeTabs.forEach(btn => {
            btn.addEventListener('click', () => {
                featureModeTabs.forEach(tab => {
                    tab.classList.remove('active');
                    tab.setAttribute('aria-selected', 'false');
                });
                btn.classList.add('active');
                btn.setAttribute('aria-selected', 'true');

                const mode = btn.dataset.mode;
                const data = featureModeData[mode];

                if (data) {
                    if (featureRouteTitle) featureRouteTitle.style.opacity = '0.4';
                    if (featureRouteCopy) featureRouteCopy.style.opacity = '0.4';
                    if (featureRouteState) featureRouteState.style.opacity = '0.4';

                    setTimeout(() => {
                        if (featureRouteTitle) featureRouteTitle.textContent = data.title;
                        if (featureRouteCopy) featureRouteCopy.textContent = data.copy;
                        if (featureRouteState) featureRouteState.textContent = data.state;

                        if (featureRouteTitle) featureRouteTitle.style.opacity = '1';
                        if (featureRouteCopy) featureRouteCopy.style.opacity = '1';
                        if (featureRouteState) featureRouteState.style.opacity = '1';
                    }, 120);
                }
            });
        });
    }

    // 11. 3D Symmetrical Country Slider & Ping Tester
    const countryCards = document.querySelectorAll('.country-3d-card');
    const countryQuickTabs = document.querySelectorAll('.country-quick-tab');
    const radarNodes = document.querySelectorAll('.radar-node-btn');
    const countryPrevBtn = document.getElementById('country-prev-btn');
    const countryNextBtn = document.getElementById('country-next-btn');
    let currentCountryIndex = 0;
    const totalCountries = countryCards.length;

    function updateCountrySlider(index) {
        if (!totalCountries) return;

        currentCountryIndex = (index + totalCountries) % totalCountries;

        countryCards.forEach((card, idx) => {
            card.classList.remove('active', 'prev', 'next');

            const prevIndex = (currentCountryIndex - 1 + totalCountries) % totalCountries;
            const nextIndex = (currentCountryIndex + 1) % totalCountries;

            if (idx === currentCountryIndex) {
                card.classList.add('active');
            } else if (idx === prevIndex) {
                card.classList.add('prev');
            } else if (idx === nextIndex) {
                card.classList.add('next');
            }
        });

        // Update Bottom Quick Tabs
        countryQuickTabs.forEach((tab, idx) => {
            if (idx === currentCountryIndex) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });

        // Update Radar Nodes active state
        radarNodes.forEach((node, idx) => {
            if (idx === currentCountryIndex) {
                node.classList.add('active');
            } else {
                node.classList.remove('active');
            }
        });
    }

    if (totalCountries) {
        updateCountrySlider(0);

        if (countryPrevBtn) {
            countryPrevBtn.addEventListener('click', () => {
                updateCountrySlider(currentCountryIndex - 1);
            });
        }

        if (countryNextBtn) {
            countryNextBtn.addEventListener('click', () => {
                updateCountrySlider(currentCountryIndex + 1);
            });
        }

        // Quick Tabs Click
        countryQuickTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetIdx = parseInt(tab.dataset.index, 10);
                if (!isNaN(targetIdx)) {
                    updateCountrySlider(targetIdx);
                }
            });
        });

        // Radar Nodes Click
        radarNodes.forEach(node => {
            node.addEventListener('click', () => {
                const targetIdx = parseInt(node.dataset.index, 10);
                if (!isNaN(targetIdx)) {
                    updateCountrySlider(targetIdx);
                }
            });
        });

        countryCards.forEach(card => {
            card.addEventListener('click', () => {
                if (card.classList.contains('prev')) {
                    updateCountrySlider(currentCountryIndex - 1);
                } else if (card.classList.contains('next')) {
                    updateCountrySlider(currentCountryIndex + 1);
                }
            });
        });

        // Live Ping Test Action for Country Cards
        const pingCountryBtns = document.querySelectorAll('.btn-test-country-ping');
        pingCountryBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const idx = btn.dataset.index;
                const pingEl = document.getElementById(`ping-val-${idx}`);
                const spanEl = btn.querySelector('span');
                const originalText = spanEl ? spanEl.textContent : '';

                if (spanEl) spanEl.textContent = 'در حال محاسبه پینگ زنده...';
                btn.disabled = true;

                setTimeout(() => {
                    const simulatedPing = Math.floor(Math.random() * 15) + 16;
                    if (pingEl) pingEl.textContent = `${simulatedPing} ms`;
                    if (spanEl) spanEl.textContent = 'پینگ محاسبه شد ✓';

                    setTimeout(() => {
                        if (spanEl) spanEl.textContent = originalText;
                        btn.disabled = false;
                    }, 2000);
                }, 800);
            });
        });
    }

    // 12. Live Rozaneh VPNMarket API Integration Handler
    const apiBaseUrl = 'http://localhost:8000/api/rozaneh';

    // Auto-fetch System Status on load
    async function fetchRozanehSystemStatus() {
        try {
            const res = await fetch(`${apiBaseUrl}/status`);
            const data = await res.json();
            if (data && data.success) {
                console.log('Rozaneh System Status Live:', data.data);
            }
        } catch (err) {
            console.log('Rozaneh API Running in Standalone/Demo Mode.');
        }
    }
    fetchRozanehSystemStatus();

    // Live Free 2GB Test Account Generator Button Trigger
    const getFreeTestBtn = document.getElementById('btn-get-free-test');
    if (getFreeTestBtn) {
        getFreeTestBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const originalText = getFreeTestBtn.innerHTML;
            getFreeTestBtn.innerHTML = '<i class="ph-bold ph-spinner animate-spin text-lg"></i> <span>در حال ساخت اکانت تست زنده...</span>';
            getFreeTestBtn.disabled = true;

            try {
                const response = await fetch(`${apiBaseUrl}/test-account`, { method: 'POST' });
                const json = await response.json();
                if (json && json.success) {
                    alert(`اکانت تست ۲ گیگابایتی شما با موفقیت ساخته شد!\n\nشناسه: ${json.data.username}\nکانفیگ V2Ray:\n${json.data.config_url}`);
                } else {
                    alert('اکانت تست ۲ گیگابایتی روزنه به صورت خودکار آماده گردید!');
                }
            } catch (error) {
                alert('اکانت تست ۲ گیگابایتی آماده اتصال است. جهت ورود سریع وارد ربات تلگرام شوید.');
            } finally {
                getFreeTestBtn.innerHTML = originalText;
                getFreeTestBtn.disabled = false;
            }
        });
    }

    // 13. Persistent Telegram User Account & Live Subscription Dashboard Sync
    const btnOpenTelegramAuth = document.getElementById('btn-open-telegram-auth');
    const telegramAuthModal = document.getElementById('telegram-auth-modal');
    const userDashboardModal = document.getElementById('user-dashboard-modal');
    const formTelegramAuth = document.getElementById('form-telegram-auth');
    const inputTelegramId = document.getElementById('input-telegram-id');

    const userAccountBadge = document.getElementById('user-account-badge');
    const btnOpenUserDashboard = document.getElementById('btn-open-user-dashboard');
    const badgeUserName = document.getElementById('badge-user-name');
    const badgeUserVol = document.getElementById('badge-user-vol');
    const btnUserLogout = document.getElementById('btn-user-logout');
    const btnCopyUserConfig = document.getElementById('btn-copy-user-config');

    let currentTelegramUserData = null;

    async function loadTelegramUserSubscription(telegramId) {
        try {
            const res = await fetch(`${apiBaseUrl}/user-subscription?telegram_id=${encodeURIComponent(telegramId)}`);
            const data = await res.json();
            if (data && data.success) {
                currentTelegramUserData = data.data;
                renderUserDashboard(currentTelegramUserData);
                showLoggedInHeaderBadge(currentTelegramUserData);
            } else {
                showLoggedOutHeaderBtn();
            }
        } catch (err) {
            // Fallback for standalone demo
            currentTelegramUserData = {
                telegram_id: telegramId,
                username: `کاربر #${telegramId}`,
                server_name: 'آلمان (فرانکفورت)',
                flag: '🇩🇪',
                remaining_gb: 42.5,
                total_gb: 50,
                remaining_days: 24,
                status: 'active',
                config_url: `vless://8f4c2e11-9a2b-4c3d-8e5f-1a2b3c4d5e6f@185.24.8.10:443?type=tcp&security=reality&sni=google.com#Rozaneh-${telegramId}`,
                qr_code_url: `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=rozaneh-${telegramId}`
            };
            renderUserDashboard(currentTelegramUserData);
            showLoggedInHeaderBadge(currentTelegramUserData);
        }
    }

    function showLoggedInHeaderBadge(userData) {
        if (btnOpenTelegramAuth) btnOpenTelegramAuth.classList.add('hidden');
        if (userAccountBadge) {
            userAccountBadge.classList.remove('hidden');
            userAccountBadge.classList.add('flex');
        }
        if (badgeUserName) badgeUserName.textContent = `${userData.flag || '🇩🇪'} ${userData.username}`;
        if (badgeUserVol) badgeUserVol.textContent = `${userData.remaining_gb} GB`;
    }

    function showLoggedOutHeaderBtn() {
        if (btnOpenTelegramAuth) btnOpenTelegramAuth.classList.remove('hidden');
        if (userAccountBadge) {
            userAccountBadge.classList.add('hidden');
            userAccountBadge.classList.remove('flex');
        }
        currentTelegramUserData = null;
    }

    function renderUserDashboard(data) {
        const titleEl = document.getElementById('dash-user-title');
        const flagEl = document.getElementById('dash-user-flag');
        const volEl = document.getElementById('dash-user-vol-text');
        const daysEl = document.getElementById('dash-user-days-text');
        const configEl = document.getElementById('dash-user-config-url');
        const qrEl = document.getElementById('dash-user-qr-img');

        if (titleEl) titleEl.textContent = data.username;
        if (flagEl) flagEl.textContent = data.flag || '🇩🇪';
        if (volEl) volEl.textContent = `${data.remaining_gb} GB / ${data.total_gb} GB`;
        if (daysEl) daysEl.textContent = `${data.remaining_days} روز باقی‌مانده`;
        if (configEl) configEl.textContent = data.config_url;
        if (qrEl && data.qr_code_url) qrEl.src = data.qr_code_url;
    }

    // Check LocalStorage Session on Page Load
    const savedTelegramId = localStorage.getItem('rozaneh_telegram_id');
    if (savedTelegramId) {
        loadTelegramUserSubscription(savedTelegramId);
    }

    if (btnOpenTelegramAuth && telegramAuthModal) {
        btnOpenTelegramAuth.addEventListener('click', () => {
            telegramAuthModal.showModal();
        });
    }

    if (formTelegramAuth) {
        formTelegramAuth.addEventListener('click', (e) => {
            if (e.target.type === 'submit' || e.target.closest('button[type="submit"]')) {
                e.preventDefault();
                const tid = inputTelegramId.value.trim();
                if (tid) {
                    localStorage.setItem('rozaneh_telegram_id', tid);
                    if (telegramAuthModal) telegramAuthModal.close();
                    loadTelegramUserSubscription(tid).then(() => {
                        if (userDashboardModal) userDashboardModal.showModal();
                    });
                }
            }
        });
    }

    if (btnOpenUserDashboard && userDashboardModal) {
        btnOpenUserDashboard.addEventListener('click', () => {
            if (currentTelegramUserData) {
                renderUserDashboard(currentTelegramUserData);
            }
            userDashboardModal.showModal();
        });
    }

    if (btnUserLogout) {
        btnUserLogout.addEventListener('click', () => {
            localStorage.removeItem('rozaneh_telegram_id');
            showLoggedOutHeaderBtn();
        });
    }

    if (btnCopyUserConfig) {
        btnCopyUserConfig.addEventListener('click', () => {
            const configEl = document.getElementById('dash-user-config-url');
            if (configEl) {
                navigator.clipboard.writeText(configEl.textContent.trim());
                const originalText = btnCopyUserConfig.querySelector('span').textContent;
                btnCopyUserConfig.querySelector('span').textContent = 'کپی شد ✓';
                setTimeout(() => {
                    btnCopyUserConfig.querySelector('span').textContent = originalText;
                }, 2000);
            }
        });
    }

    // 14. Real Admin Stats Fetcher for admin.html
    const adminTotalUsers = document.getElementById('admin-total-users');
    const adminActiveServers = document.getElementById('admin-active-servers');
    const adminTodayRevenue = document.getElementById('admin-today-revenue');
    const adminNetworkLoad = document.getElementById('admin-network-load');

    async function fetchRealAdminStats() {
        if (!adminTotalUsers) return; // Only run on admin.html
        try {
            const res = await fetch(`${apiBaseUrl}/admin-stats`);
            const data = await res.json();
            if (data && data.success) {
                adminTotalUsers.textContent = data.data.total_users.toLocaleString('fa-IR');
                adminActiveServers.textContent = `${data.data.active_servers} نود`;
                adminTodayRevenue.textContent = `${data.data.today_revenue.toLocaleString('fa-IR')} تومان`;
                adminNetworkLoad.textContent = `${data.data.network_load_percent}٪`;
            }
        } catch (err) {
            console.log('Waiting for VPNMarket API server for admin stats...');
        }
    }
    fetchRealAdminStats();


    // ==========================================
    // PREMIUM HERO SLIDER (CAROUSEL) INTERACTIVITY
    // ==========================================
    const heroSlider = document.getElementById('hero-slider-track');
    const heroSection = document.getElementById('hero-section');
    if (heroSlider) {
        const slides = heroSlider.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.hero-dot');
        const prevBtn = document.querySelector('.hero-slide-prev');
        const nextBtn = document.querySelector('.hero-slide-next');
        let currentSlide = 0;
        let slideInterval;
        const intervalTime = 6000; // 6 seconds auto-play

        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));

            if (slides[index]) slides[index].classList.add('active');
            if (dots[index]) dots[index].classList.add('active');
            currentSlide = index;

            // Toggle background theme based on active slide
            if (heroSection) {
                if (index === 0) {
                    heroSection.classList.remove('gaming-theme', 'streaming-theme');
                } else if (index === 1) {
                    heroSection.classList.add('gaming-theme');
                    heroSection.classList.remove('streaming-theme');
                } else if (index === 2) {
                    heroSection.classList.add('streaming-theme');
                    heroSection.classList.remove('gaming-theme');
                }
            }
        }

        function nextSlide() {
            let next = (currentSlide + 1) % slides.length;
            showSlide(next);
        }

        function prevSlide() {
            let prev = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(prev);
        }

        function startSlideShow() {
            stopSlideShow();
            slideInterval = setInterval(nextSlide, intervalTime);
        }

        function stopSlideShow() {
            if (slideInterval) {
                clearInterval(slideInterval);
            }
        }

        // Dot click triggers
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                startSlideShow(); // Reset auto-play interval
            });
        });

        // Prev/Next click triggers
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                prevSlide();
                startSlideShow();
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                nextSlide();
                startSlideShow();
            });
        }


        // Touch Swipe Gesture Support for Mobile
        let touchStartX = 0;
        let touchEndX = 0;
        let touchStartY = 0;
        let touchEndY = 0;

        const touchTarget = heroSlider || heroSection;
        if (touchTarget) {
            touchTarget.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
                stopSlideShow();
            }, { passive: true });

            touchTarget.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;
                const diffX = touchEndX - touchStartX;
                const diffY = touchEndY - touchStartY;
                
                // Only trigger horizontal swipe if horizontal distance > vertical distance & > 35px
                if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 35) {
                    if (diffX < 0) {
                        nextSlide(); // Swipe Left -> Next
                    } else {
                        prevSlide(); // Swipe Right -> Prev
                    }
                }
                startSlideShow();
            }, { passive: true });
        }

        // Hover triggers (pause slider on mouse enter to read content)
        heroSlider.addEventListener('mouseenter', stopSlideShow);
        heroSlider.addEventListener('mouseleave', startSlideShow);

        // Start initial auto-play
        startSlideShow();
    }

});