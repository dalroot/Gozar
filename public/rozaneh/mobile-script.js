document.addEventListener('DOMContentLoaded', () => {
    // 1. Navigation Tab Bar Switcher
    const tabItems = document.querySelectorAll('.tab-item');
    const pages = document.querySelectorAll('.app-page');

    tabItems.forEach(item => {
        item.addEventListener('click', () => {
            const pageId = item.getAttribute('data-page-id');
            const targetPage = document.getElementById(pageId);

            if (targetPage) {
                // Remove active classes & add hidden
                tabItems.forEach(tab => tab.classList.remove('active'));
                pages.forEach(page => {
                    page.classList.remove('active');
                    page.classList.add('hidden');
                });

                // Add active classes & remove hidden
                item.classList.add('active');
                targetPage.classList.remove('hidden');
                targetPage.classList.add('active');
            }
        });
    });

    // Quick Banner Shortcuts Click Handler
    const quickBanners = document.querySelectorAll('[data-page-target]');
    quickBanners.forEach(banner => {
        banner.addEventListener('click', () => {
            const targetId = banner.getAttribute('data-page-target');
            const targetTab = document.querySelector(`[data-page-id="${targetId}"]`);
            if (targetTab) {
                targetTab.click();
            }
        });
    });


    // 2. Power Connect & Big Timer Simulator
    const powerConnectBtn = document.getElementById('power-connect-btn');
    const sunStatusText = document.getElementById('sun-status-text');
    const bigTimerDisplay = document.getElementById('big-timer-display');
    const compactIpVal = document.getElementById('compact-ip-val');

    // Session Data Traffic DOM Elements
    const sessionDataCard = document.getElementById('session-data-card');
    const sessionDataStatus = document.getElementById('session-data-status');
    const sessionTotalVal = document.getElementById('session-total-val');
    const sessionDlVal = document.getElementById('session-dl-val');
    const sessionUlVal = document.getElementById('session-ul-val');

    // Unified Server & IP DOM Elements
    const activeServerFlag = document.getElementById('active-server-flag');
    const activeServerName = document.getElementById('active-server-name');
    const activeServerPing = document.getElementById('active-server-ping');
    const activeServerIpSub = document.getElementById('active-server-ip-sub');

    // Status label & IP line (for state classes)
    const sunStatusLabel = document.getElementById('sun-status-label');
    const compactIpLine = document.getElementById('compact-ip-line');

    let initialRealIp = '185.210.142.89';
    let isConnected = false;
    let isConnecting = false;
    
    let trafficInterval = null;
    let connectionTimerInterval = null;
    let timerSeconds = 0;

    let sessionDlBytes = 0;
    let sessionUlBytes = 0;

    function formatBytes(bytes) {
        if (bytes === 0) return '0.00 MB';
        const mb = bytes / (1024 * 1024);
        if (mb >= 1024) {
            return (mb / 1024).toFixed(2) + ' GB';
        }
        return mb.toFixed(2) + ' MB';
    }

    function formatDuration(sec) {
        const hrs = Math.floor(sec / 3600);
        const mins = Math.floor((sec % 3600) / 60);
        const secs = sec % 60;
        const pad = n => n < 10 ? '0' + n : n;
        return `${pad(hrs)}:${pad(mins)}:${pad(secs)}`;
    }

    // Fetch user's real live public IP on load
    fetch('https://api.ipify.org?format=json')
        .then(res => res.json())
        .then(data => {
            if (data && data.ip) {
                initialRealIp = data.ip;
                if (!isConnected && compactIpVal) {
                    compactIpVal.innerHTML = `<span dir="ltr">${initialRealIp}</span>`;
                }
            }
        })
        .catch(() => {});

    // Preset VPN IPs mapping per server
    const serverVpnIps = {
        germany: { ip: '142.132.204.18', flag: 'de' },
        netherlands: { ip: '188.166.42.10', flag: 'nl' },
        france: { ip: '51.159.22.84', flag: 'fr' },
        finland: { ip: '95.217.112.5', flag: 'fi' },
        uk: { ip: '178.62.70.19', flag: 'gb' }
    };
    let activeServerKey = 'germany';

    if (powerConnectBtn) {
        powerConnectBtn.addEventListener('click', () => {
            if (isConnecting) return;

            if (!isConnected) {
                // Initiate Connecting Flow
                isConnecting = true;
                powerConnectBtn.classList.add('connecting');
                if (sunStatusText) sunStatusText.textContent = 'در حال برقراری...';
                if (sunStatusLabel) {
                    sunStatusLabel.classList.remove('connected');
                    sunStatusLabel.classList.add('connecting');
                }

                setTimeout(() => {
                    isConnecting = false;
                    isConnected = true;
                    
                    powerConnectBtn.classList.remove('connecting');
                    powerConnectBtn.classList.add('connected');
                    if (sunStatusText) sunStatusText.textContent = 'متصل';
                    if (sunStatusLabel) {
                        sunStatusLabel.classList.remove('connecting');
                        sunStatusLabel.classList.add('connected');
                    }
                    if (compactIpLine) compactIpLine.classList.add('connected');

                    // Start Live Connection Big Timer
                    timerSeconds = 0;
                    if (bigTimerDisplay) bigTimerDisplay.innerHTML = `<span dir="ltr">00:00:00</span>`;
                    
                    if (connectionTimerInterval) clearInterval(connectionTimerInterval);
                    connectionTimerInterval = setInterval(() => {
                        timerSeconds++;
                        if (bigTimerDisplay) {
                            bigTimerDisplay.innerHTML = `<span dir="ltr">${formatDuration(timerSeconds)}</span>`;
                        }
                    }, 1000);

                    // Update Compact IP Line to Secure VPN IP
                    const currentVpnInfo = serverVpnIps[activeServerKey] || serverVpnIps.germany;
                    if (compactIpVal) compactIpVal.innerHTML = `<span dir="ltr">${currentVpnInfo.ip}</span>`;

                    if (activeServerPing) {
                        activeServerPing.style.borderColor = 'rgba(16, 185, 129, 0.5)';
                    }
                    
                    // Activate Live Session Consumed Traffic Cards
                    sessionDlBytes = 0;
                    sessionUlBytes = 0;
                    
                    if (trafficInterval) clearInterval(trafficInterval);
                    trafficInterval = setInterval(() => {
                        // Simulate random download & upload chunks per second
                        const addDl = Math.floor(Math.random() * (650000 - 180000) + 180000);
                        const addUl = Math.floor(Math.random() * (180000 - 45000) + 45000);
                        
                        sessionDlBytes += addDl;
                        sessionUlBytes += addUl;

                        if (sessionDlVal) sessionDlVal.innerHTML = `<span dir="ltr">${formatBytes(sessionDlBytes)}</span>`;
                        if (sessionUlVal) sessionUlVal.innerHTML = `<span dir="ltr">${formatBytes(sessionUlBytes)}</span>`;
                    }, 1000);
                }, 1000);

            } else {
                // Disconnect Flow
                isConnected = false;
                powerConnectBtn.classList.remove('connected');
                powerConnectBtn.classList.remove('connecting');
                if (sunStatusText) sunStatusText.textContent = 'غیر متصل';
                if (sunStatusLabel) {
                    sunStatusLabel.classList.remove('connected');
                    sunStatusLabel.classList.remove('connecting');
                }
                if (compactIpLine) compactIpLine.classList.remove('connected');

                // Stop & Reset Live Connection Big Timer
                if (connectionTimerInterval) clearInterval(connectionTimerInterval);
                timerSeconds = 0;
                if (bigTimerDisplay) bigTimerDisplay.innerHTML = `<span dir="ltr">00:00:00</span>`;

                // Revert Compact IP Line to Original Real Local IP
                if (compactIpVal) compactIpVal.innerHTML = `<span dir="ltr">${initialRealIp}</span>`;

                if (activeServerPing) {
                    activeServerPing.style.borderColor = '';
                }
                
                // Reset & Stop Session Traffic Accumulator
                if (trafficInterval) clearInterval(trafficInterval);
                
                if (sessionDlVal) sessionDlVal.innerHTML = `<span dir="ltr">0.00 MB</span>`;
                if (sessionUlVal) sessionUlVal.innerHTML = `<span dir="ltr">0.00 MB</span>`;
            }
        });
    }

    // 3. Server Selector
    const homeChangeServerBtn = document.getElementById('home-change-server-btn');
    const serverItemRows = document.querySelectorAll('.server-item-row');

    // Shortcut: Click server on Home page redirects to Server list Tab
    if (homeChangeServerBtn) {
        homeChangeServerBtn.addEventListener('click', () => {
            const serversTab = document.querySelector('[data-page-id="page-servers"]');
            if (serversTab) {
                serversTab.click();
            }
        });
    }

    // Server Item Row Selection
    serverItemRows.forEach(row => {
        row.addEventListener('click', () => {
            // Remove selection from all servers
            serverItemRows.forEach(r => r.classList.remove('selected'));
            
            // Add selection to active server
            row.classList.add('selected');

            // Read attributes
            const serverKey = row.getAttribute('data-server') || 'germany';
            activeServerKey = serverKey;
            
            const flagCode = row.getAttribute('data-flag');
            const serverName = row.getAttribute('data-name');
            const serverPing = row.getAttribute('data-ping');

            // Update Home active server card
            if (activeServerFlag) {
                activeServerFlag.src = `https://flagcdn.com/w40/${flagCode}.png`;
                activeServerFlag.alt = serverName;
            }
            if (activeServerName) {
                activeServerName.innerHTML = `<i class="ph-bold ph-map-pin"></i> <span>${serverName}</span>`;
            }
            if (activeServerPing) {
                activeServerPing.innerHTML = `<i class="ph-bold ph-cell-signal-high"></i> <span dir="ltr">${serverPing} ms • عالی</span>`;
            }

            // If connected, update IP subtitle to selected server's IP
            const currentVpnInfo = serverVpnIps[activeServerKey] || serverVpnIps.germany;
            if (isConnected && activeServerIpSub) {
                activeServerIpSub.innerHTML = `<i class="ph-fill ph-circle dot-icon" style="color: var(--accent-emerald);"></i> <span>آی‌پی امن: <span class="ltr-val" dir="ltr">${currentVpnInfo.ip}</span></span>`;
            } else if (!isConnected && activeServerIpSub) {
                activeServerIpSub.innerHTML = `<i class="ph-fill ph-circle dot-icon" style="color: var(--text-muted);"></i> <span>آی‌پی محلی: <span class="ltr-val" dir="ltr">${initialRealIp}</span></span>`;
            }

            // Auto switch back to Home page after short delay
            setTimeout(() => {
                const homeTab = document.querySelector('[data-page-id="page-home"]');
                if (homeTab) {
                    homeTab.click();
                }
            }, 300);
        });
    });

    // 4. Invite friends referral link copy logic
    const copyReferralLinkBtn = document.getElementById('copy-referral-link-btn');
    if (copyReferralLinkBtn) {
        copyReferralLinkBtn.addEventListener('click', () => {
            const referralLink = 'https://t.me/RozanehVpnBot?start=ref_rozaneh_user';
            
            navigator.clipboard.writeText(referralLink).then(() => {
                const btnText = copyReferralLinkBtn.querySelector('span');
                const btnIcon = copyReferralLinkBtn.querySelector('i');
                
                if (btnText && btnIcon) {
                    const originalText = btnText.textContent;
                    const originalIconClass = btnIcon.className;
                    
                    btnText.textContent = 'لینک کپی شد ✓';
                    btnIcon.className = 'ph-bold ph-check';
                    copyReferralLinkBtn.style.background = 'var(--color-success)';
                    
                    setTimeout(() => {
                        btnText.textContent = originalText;
                        btnIcon.className = originalIconClass;
                        copyReferralLinkBtn.style.background = '';
                    }, 2000);
                }
            }).catch(err => {
                console.error('Failed to copy referral link: ', err);
            });
        });
    }

    // 5. Website Mobile Navigation Drawer Controls

    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    const mobileMenuDrawer = document.getElementById('mobile-menu-drawer');
    const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');

    function openMobileDrawer() {
        if (mobileMenuDrawer && mobileMenuOverlay) {
            mobileMenuDrawer.classList.add('active');
            mobileMenuOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMobileDrawer() {
        if (mobileMenuDrawer && mobileMenuOverlay) {
            mobileMenuDrawer.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', openMobileDrawer);
    }

    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', closeMobileDrawer);
    }

    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', closeMobileDrawer);
    }

    mobileNavLinks.forEach(link => {
        link.addEventListener('click', closeMobileDrawer);
    });
});

