/**
 * Rozaneh VPN Authentication & Session Manager
 */
(function() {
    const STORAGE_KEY = 'rozaneh_user_session';

    const RozanehAuth = {
        getUser: function() {
            try {
                const data = localStorage.getItem(STORAGE_KEY);
                return data ? JSON.parse(data) : null;
            } catch (e) {
                return null;
            }
        },

        setUser: function(userData) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(userData));
                this.updateHeaderUI();
                return true;
            } catch (e) {
                return false;
            }
        },

        logout: function() {
            localStorage.removeItem(STORAGE_KEY);
            window.location.reload();
        },

        loginUser: function(identifier, type = 'phone', telegramId = '') {
            const user = {
                identifier: identifier,
                type: type, // 'phone' or 'email'
                name: type === 'phone' ? 'کاربر ' + identifier.slice(-4) : identifier.split('@')[0],
                telegramId: telegramId,
                loggedInAt: new Date().toISOString(),
                plan: 'اشتراک ۵۰ گیگابایت روزنه',
                expiresInDays: 30,
                usedGb: 0,
                totalGb: 50.0
            };
            this.setUser(user);
            return user;
        },

        updateHeaderUI: function() {
            const user = this.getUser();
            const authNavBtn = document.getElementById('nav-auth-btn');
            if (!authNavBtn) return;

            if (user) {
                authNavBtn.innerHTML = `
                    <div class="flex items-center gap-2">
                        <a href="dashboard.html" class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-orange-50 border-2 border-orange-300 text-orange-900 font-extrabold text-xs shadow-sm hover:bg-orange-100 transition-all cursor-pointer">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>ورود به پنل کاربری (${user.name})</span>
                        </a>
                        <button id="btn-user-logout-main" class="p-2 rounded-xl bg-gray-100 text-gray-500 hover:text-rose-600 transition-colors text-xs cursor-pointer" title="خروج از حساب">
                            <i class="ph-bold ph-sign-out text-base"></i>
                        </button>
                    </div>
                `;

                const logoutBtn = document.getElementById('btn-user-logout-main');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', () => this.logout());
                }
            } else {
                authNavBtn.innerHTML = `
                    <button id="btn-open-auth-modal" data-target="auth-modal" class="btn-open-dialog px-5 py-2.5 rounded-2xl bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white font-black text-xs shadow-[var(--shadow-clay-sm)] transition-all flex items-center gap-2 cursor-pointer">
                        <i class="ph-bold ph-user-circle text-base"></i>
                        <span>ورود / ثبت‌نام</span>
                    </button>
                `;
                const openBtn = document.getElementById('btn-open-auth-modal');
                if (openBtn) {
                    openBtn.addEventListener('click', () => this.openModal());
                }
            }
        },

        openDashboardModal: function() {
            const user = this.getUser();
            const dashModal = document.getElementById('user-dashboard-modal');
            if (!dashModal) return;

            if (user) {
                const nameEl = document.getElementById('dash-user-name');
                const idEl = document.getElementById('dash-user-identifier');
                const volEl = document.getElementById('dash-user-vol-text');
                const daysEl = document.getElementById('dash-user-days-text');
                
                if (nameEl) nameEl.textContent = user.name || 'کاربر روزنه';
                if (idEl) idEl.textContent = user.identifier || '09120000000';
                if (volEl) volEl.textContent = `${user.usedGb || 0} GB / ${user.totalGb || 50} GB`;
                if (daysEl) daysEl.textContent = `${user.expiresInDays || 30} روز باقی‌مانده`;
            }

            if (typeof dashModal.showModal === 'function') {
                dashModal.showModal();
            } else {
                dashModal.setAttribute('open', 'true');
            }
        },

        closeDashboardModal: function() {
            const dashModal = document.getElementById('user-dashboard-modal');
            if (dashModal) {
                if (typeof dashModal.close === 'function') {
                    dashModal.close();
                } else {
                    dashModal.removeAttribute('open');
                }
            }
        },

        openModal: function() {
            const modal = document.getElementById('auth-modal');
            if (modal) {
                if (typeof modal.showModal === 'function') {
                    modal.showModal();
                } else {
                    modal.setAttribute('open', 'true');
                }
            }
        },

        closeModal: function() {
            const modal = document.getElementById('auth-modal');
            if (modal) {
                if (typeof modal.close === 'function') {
                    modal.close();
                } else {
                    modal.removeAttribute('open');
                }
            }
        }
    };

    window.RozanehAuth = RozanehAuth;

    // DOM Ready Setup
    document.addEventListener('DOMContentLoaded', () => {
        RozanehAuth.updateHeaderUI();

        // Auth Modal DOM Setup
        const modal = document.getElementById('auth-modal');
        if (!modal) return;

        const tabPhone = document.getElementById('auth-tab-phone');
        const tabEmail = document.getElementById('auth-tab-email');
        const inputPhoneGroup = document.getElementById('auth-group-phone');
        const inputEmailGroup = document.getElementById('auth-group-email');
        const phoneInput = document.getElementById('auth-input-phone');
        const emailInput = document.getElementById('auth-input-email');
        const telegramIdInput = document.getElementById('auth-input-telegram-id');
        const authForm = document.getElementById('auth-form');
        const btnSendOtp = document.getElementById('btn-send-otp');
        const closeModalBtn = document.getElementById('btn-close-auth-modal');

        let activeTab = 'phone';

        if (tabPhone && tabEmail) {
            tabPhone.addEventListener('click', (e) => {
                e.preventDefault();
                activeTab = 'phone';
                tabPhone.classList.add('bg-orange-500', 'text-white');
                tabPhone.classList.remove('bg-gray-100', 'text-gray-600');
                tabEmail.classList.add('bg-gray-100', 'text-gray-600');
                tabEmail.classList.remove('bg-orange-500', 'text-white');

                if (inputPhoneGroup) inputPhoneGroup.classList.remove('hidden');
                if (inputEmailGroup) inputEmailGroup.classList.add('hidden');

                if (phoneInput) phoneInput.required = true;
                if (emailInput) emailInput.required = false;
            });

            tabEmail.addEventListener('click', (e) => {
                e.preventDefault();
                activeTab = 'email';
                tabEmail.classList.add('bg-orange-500', 'text-white');
                tabEmail.classList.remove('bg-gray-100', 'text-gray-600');
                tabPhone.classList.add('bg-gray-100', 'text-gray-600');
                tabPhone.classList.remove('bg-orange-500', 'text-white');

                if (inputEmailGroup) inputEmailGroup.classList.remove('hidden');
                if (inputPhoneGroup) inputPhoneGroup.classList.add('hidden');

                if (emailInput) emailInput.required = true;
                if (phoneInput) phoneInput.required = false;
            });
        }

        const closeDashBtn = document.getElementById('btn-close-user-dashboard');
        if (closeDashBtn) {
            closeDashBtn.addEventListener('click', (e) => {
                e.preventDefault();
                RozanehAuth.closeDashboardModal();
            });
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', (e) => {
                e.preventDefault();
                RozanehAuth.closeModal();
            });
        }

        if (authForm) {
            authForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const identifierInput = activeTab === 'phone' ? phoneInput : emailInput;
                const val = identifierInput ? identifierInput.value.trim() : '';
                const tid = telegramIdInput ? telegramIdInput.value.trim() : '';

                if (!val) {
                    alert('لطفاً اطلاعات ورودی را وارد نمایید.');
                    return;
                }

                if (btnSendOtp) {
                    btnSendOtp.disabled = true;
                    btnSendOtp.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i> <span>در حال ثبت و ورود...</span>';
                }

                setTimeout(() => {
                    RozanehAuth.loginUser(val, activeTab, tid);
                    RozanehAuth.closeModal();
                    if (btnSendOtp) {
                        btnSendOtp.disabled = false;
                        btnSendOtp.innerHTML = '<span>ورود به حساب / ثبت‌نام</span><i class="ph-bold ph-arrow-left text-sm"></i>';
                    }
                    // Redirect directly to full Standalone User Dashboard
                    window.location.href = 'dashboard.html';
                }, 600);
            });
        }
    });
})();
