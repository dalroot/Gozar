document.addEventListener('DOMContentLoaded', () => {
    // 1. Navigation Tab Switcher
    const petTabItems = document.querySelectorAll('.pet-tab-item');
    const petPages = document.querySelectorAll('.pet-page');

    petTabItems.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.getAttribute('data-pet-tab');
            const targetPage = document.getElementById(targetId);

            if (targetPage) {
                petTabItems.forEach(t => t.classList.remove('active'));
                petPages.forEach(p => p.classList.remove('active'));

                tab.classList.add('active');
                targetPage.classList.add('active');
            }
        });
    });

    // Quick Book Shortcut from Hero Banner
    const heroQuickBookBtn = document.getElementById('hero-quick-book-btn');
    if (heroQuickBookBtn) {
        heroQuickBookBtn.addEventListener('click', () => {
            const bookingTab = document.querySelector('[data-pet-tab="pet-page-booking"]');
            if (bookingTab) bookingTab.click();
        });
    }

    // View All Gallery Shortcut
    const viewAllGalleryBtn = document.getElementById('view-all-gallery-btn');
    if (viewAllGalleryBtn) {
        viewAllGalleryBtn.addEventListener('click', () => {
            const galleryTab = document.querySelector('[data-pet-tab="pet-page-gallery"]');
            if (galleryTab) galleryTab.click();
        });
    }

    // 2. Service Package Selector
    const serviceCards = document.querySelectorAll('.service-card');
    const bookingServiceSelect = document.getElementById('booking-service-select');

    serviceCards.forEach(card => {
        card.addEventListener('click', () => {
            serviceCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            const serviceId = card.getAttribute('data-service-id');
            if (bookingServiceSelect && serviceId) {
                bookingServiceSelect.value = serviceId;
            }

            // Navigate to Booking tab
            const bookingTab = document.querySelector('[data-pet-tab="pet-page-booking"]');
            if (bookingTab) bookingTab.click();
        });
    });

    // 3. Pet Type Selector (Dog vs Cat)
    const petTypeBtns = document.querySelectorAll('.pet-type-btn');
    petTypeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            petTypeBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // 4. Booking Confirmation Logic
    const confirmBookingBtn = document.getElementById('confirm-booking-btn');
    const bookingOwnerName = document.getElementById('booking-owner-name');
    const bookingPhoneInput = document.getElementById('booking-phone-input');

    if (confirmBookingBtn) {
        confirmBookingBtn.addEventListener('click', () => {
            const nameVal = bookingOwnerName ? bookingOwnerName.value.trim() : '';
            const phoneVal = bookingPhoneInput ? bookingPhoneInput.value.trim() : '';

            if (!nameVal || !phoneVal) {
                alert('لطفاً نام و شماره همراه خود را وارد کنید.');
                return;
            }

            // Success feedback animation
            const btnSpan = confirmBookingBtn.querySelector('span');
            const btnIcon = confirmBookingBtn.querySelector('i');

            if (btnSpan && btnIcon) {
                const origText = btnSpan.textContent;
                const origIcon = btnIcon.className;

                btnSpan.textContent = 'نوبت با موفقیت ثبت شد ✓';
                btnIcon.className = 'ph-bold ph-check';
                confirmBookingBtn.style.background = 'linear-gradient(135deg, #10B981, #059669)';

                setTimeout(() => {
                    btnSpan.textContent = origText;
                    btnIcon.className = origIcon;
                    confirmBookingBtn.style.background = '';
                    
                    // Navigate back to Home
                    const homeTab = document.querySelector('[data-pet-tab="pet-page-home"]');
                    if (homeTab) homeTab.click();
                }, 2000);
            }
        });
    }
});
