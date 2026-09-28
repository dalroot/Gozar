// 1. Simulating KPI Data Updates (Overview page only)
const usersKpi = document.getElementById('admin-total-users');
const serversKpi = document.getElementById('admin-active-servers');
const revenueKpi = document.getElementById('admin-today-revenue');
const loadKpi = document.getElementById('admin-network-load');

if (usersKpi && serversKpi && revenueKpi && loadKpi) {
    setTimeout(() => {
        usersKpi.textContent = '۲,۵۴۰';
        serversKpi.textContent = '۴۲';
        revenueKpi.textContent = '۴.۵M';
        loadKpi.textContent = '۳۸٪';
    }, 800);
    
    setInterval(() => {
        const randomLoad = Math.floor(Math.random() * (45 - 35 + 1)) + 35;
        loadKpi.textContent = randomLoad + '٪';
    }, 3000);
}

// 2. Config Generator Form Submission Simulation
const configGeneratorBtn = document.querySelector('#dash-configs button[type="button"]');
if (configGeneratorBtn) {
    configGeneratorBtn.addEventListener('click', () => {
        const originalText = configGeneratorBtn.textContent;
        configGeneratorBtn.textContent = 'در حال ساخت کانفیگ...';
        configGeneratorBtn.classList.add('opacity-80');
        
        setTimeout(() => {
            configGeneratorBtn.textContent = '✓ کانفیگ با موفقیت ساخته شد';
            configGeneratorBtn.classList.remove('bg-amber-500');
            configGeneratorBtn.classList.add('bg-emerald-500');
            
            setTimeout(() => {
                configGeneratorBtn.textContent = originalText;
                configGeneratorBtn.classList.remove('bg-emerald-500');
                configGeneratorBtn.classList.add('bg-amber-500');
                configGeneratorBtn.classList.remove('opacity-80');
            }, 3000);
        }, 1200);
    });
}

// 3. Settings Form Save Simulation
const settingsSaveBtn = document.querySelector('#dash-settings button[type="button"]');
if (settingsSaveBtn) {
    settingsSaveBtn.addEventListener('click', () => {
        const originalText = settingsSaveBtn.textContent;
        settingsSaveBtn.textContent = 'در حال ذخیره...';
        settingsSaveBtn.classList.add('opacity-80');
        
        setTimeout(() => {
            settingsSaveBtn.textContent = '✓ تنظیمات ذخیره شد';
            settingsSaveBtn.classList.remove('bg-[var(--color-primary)]');
            settingsSaveBtn.classList.add('bg-emerald-500');
            
            setTimeout(() => {
                settingsSaveBtn.textContent = originalText;
                settingsSaveBtn.classList.remove('bg-emerald-500');
                settingsSaveBtn.classList.add('bg-[var(--color-primary)]');
                settingsSaveBtn.classList.remove('opacity-80');
            }, 3000);
        }, 800);
    });
}
