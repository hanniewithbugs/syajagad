document.addEventListener('DOMContentLoaded', function () {

    // ===========================
    // SIDEBAR TOGGLE
    // ===========================
    const sidebar       = document.getElementById('sidebar');
    const sidebarClose  = document.getElementById('sidebarClose');
    const sidebarOverlay= document.getElementById('sidebarOverlay');
    const menuToggle    = document.getElementById('menuToggle');

    function openSidebar() {
        sidebar.classList.add('open');
        sidebarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    menuToggle?.addEventListener('click', openSidebar);
    sidebarClose?.addEventListener('click', closeSidebar);
    sidebarOverlay?.addEventListener('click', closeSidebar);

    // ===========================
    // PAGE NAVIGATION
    // ===========================
    const navItems          = document.querySelectorAll('.nav-item[data-page]');
    const pages             = document.querySelectorAll('.page');
    const breadcrumbCurrent = document.getElementById('breadcrumbCurrent');

    const pageLabels = {
        dashboard : 'Dashboard',
        tagihan   : 'Tagihan SPP',
        profil    : 'Profil Saya',
    };

    function switchPage(pageName) {
        // Hide all pages
        pages.forEach(p => p.classList.remove('active'));

        // Show target page
        const target = document.getElementById('page-' + pageName);
        if (target) {
            target.classList.add('active');
            target.style.animation = 'fadeInPage 0.4s ease';
        }

        // Update nav active state
        navItems.forEach(item => {
            item.classList.toggle('active', item.dataset.page === pageName);
        });

        // Update breadcrumb
        if (breadcrumbCurrent) {
            breadcrumbCurrent.textContent = pageLabels[pageName] || pageName;
        }

        // Close sidebar on mobile
        if (window.innerWidth <= 1024) closeSidebar();

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const page = this.dataset.page;
            if (page) switchPage(page);
        });
    });

    // Cross-link buttons
    document.getElementById('bayarSekarangBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        switchPage('tagihan');
    });

    document.getElementById('lihatSemuaTagihan')?.addEventListener('click', function (e) {
        e.preventDefault();
        switchPage('tagihan');
    });

    document.getElementById('bayarTagihanBtn')?.addEventListener('click', function () {
        openPaymentModal('SPP November 2024', 250000);
    });

    // ===========================
    // DATE & GREETING
    // ===========================
    function setDateAndGreeting() {
        const now  = new Date();
        const hour = now.getHours();

        // Date
        const dateEl = document.getElementById('currentDate');
        if (dateEl) {
            dateEl.textContent = now.toLocaleDateString('id-ID', {
                weekday : 'long',
                day     : 'numeric',
                month   : 'long',
                year    : 'numeric',
            });
        }

        // Greeting
        const greetingEl   = document.getElementById('greetingTime');
        const greetingEmoji= document.querySelector('.greeting-emoji');

        let greeting = 'Selamat Pagi';
        let emoji    = '☀️';

        if (hour >= 11 && hour < 15) { greeting = 'Selamat Siang';  emoji = '🌤️'; }
        else if (hour >= 15 && hour < 18) { greeting = 'Selamat Sore'; emoji = '🌅'; }
        else if (hour >= 18) { greeting = 'Selamat Malam'; emoji = '🌙'; }

        if (greetingEl)    greetingEl.textContent    = greeting;
        if (greetingEmoji) greetingEmoji.textContent = emoji;
    }

    setDateAndGreeting();

    // ===========================
    // FILTER TAGIHAN
    // ===========================
    const filterTabs    = document.querySelectorAll('.filter-tab');
    const tagihanCards  = document.querySelectorAll('.tagihan-card');
    const searchInput   = document.getElementById('searchTagihan');

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            filterTagihan();
        });
    });

    searchInput?.addEventListener('input', filterTagihan);

    function filterTagihan() {
        const activeFilter  = document.querySelector('.filter-tab.active')?.dataset.filter || 'semua';
        const searchVal     = searchInput?.value.toLowerCase() || '';

        tagihanCards.forEach(card => {
            const status    = card.dataset.status || '';
            const text      = card.textContent.toLowerCase();

            const matchFilter   = activeFilter === 'semua' || status === activeFilter;
            const matchSearch   = text.includes(searchVal);

            if (matchFilter && matchSearch) {
                card.style.display = 'block';
                card.style.animation = 'fadeInPage 0.3s ease';
            } else {
                card.style.display = 'none';
            }
        });

        // Empty state
        const visible   = [...tagihanCards].filter(c => c.style.display !== 'none');
        const listEl    = document.getElementById('tagihanList');
        const emptyEl   = document.getElementById('emptyState');

        if (visible.length === 0 && listEl) {
            if (!emptyEl) {
                const empty = document.createElement('div');
                empty.id    = 'emptyState';
                empty.style.cssText = `
                    text-align: center;
                    padding: 3rem;
                    color: #94a3b8;
                    background: white;
                    border-radius: 16px;
                    border: 1px solid #e2e8f0;
                `;
                empty.innerHTML = `
                    <i class="fas fa-inbox" style="font-size:3rem; margin-bottom:1rem; display:block; color:#cbd5e1"></i>
                    <p style="font-weight:600; font-size:1rem; color:#64748b">Tidak ada tagihan ditemukan</p>
                    <p style="font-size:0.85rem; margin-top:0.25rem">Coba ubah filter atau kata kunci pencarian</p>
                `;
                listEl.appendChild(empty);
            }
        } else {
            emptyEl?.remove();
        }
    }

    // ===========================
    // PAYMENT MODAL
    // ===========================
    const paymentModal  = document.getElementById('paymentModal');
    const successModal  = document.getElementById('successModal');
    const modalClose    = document.getElementById('modalClose');
    const modalCancel   = document.getElementById('modalCancel');
    const modalPay      = document.getElementById('modalPay');
    const successClose  = document.getElementById('successClose');
    const pmOptions     = document.querySelectorAll('.pm-option');

    // Expose globally for inline onclick
    window.openPaymentModal = function (name, amount) {
        const nameEl    = document.getElementById('modalTagihanName');
        const amountEl  = document.getElementById('modalTagihanAmount');

        if (nameEl)   nameEl.textContent   = name;
        if (amountEl) amountEl.textContent = 'Rp ' + amount.toLocaleString('id-ID');

        paymentModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    function closePaymentModal() {
        paymentModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    modalClose?.addEventListener('click', closePaymentModal);
    modalCancel?.addEventListener('click', closePaymentModal);

    paymentModal?.addEventListener('click', function (e) {
        if (e.target === paymentModal) closePaymentModal();
    });

    // Select payment method
    pmOptions.forEach(opt => {
        opt.addEventListener('click', function () {
            pmOptions.forEach(o => o.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Pay button
    modalPay?.addEventListener('click', function () {
        const selectedMethod = document.querySelector('.pm-option.active');
        const methodName     = selectedMethod?.querySelector('.pm-name')?.textContent || 'QRIS';

        // Show loading
        modalPay.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        modalPay.disabled  = true;

        setTimeout(() => {
            closePaymentModal();

            // Reset button
            modalPay.innerHTML = '<i class="fas fa-lock"></i> Lanjutkan Pembayaran';
            modalPay.disabled  = false;

            // Show success
            const successMethodEl = document.getElementById('successMethod');
            if (successMethodEl) successMethodEl.textContent = methodName;

            successModal.classList.add('active');
        }, 2000);
    });

    // Close success modal
    successClose?.addEventListener('click', function () {
        successModal.classList.remove('active');
        document.body.style.overflow = '';
        switchPage('dashboard');
    });

    successModal?.addEventListener('click', function (e) {
        if (e.target === successModal) {
            successModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // ===========================
    // NOTIFICATION BUTTON
    // ===========================
    document.getElementById('notifBtn')?.addEventListener('click', function () {
        showToast('📢 Tagihan SPP November 2024 belum dibayar!', 'warning');
    });

    // ===========================
    // TOAST NOTIFICATION
    // ===========================
    function showToast(message, type = 'success') {
        const existing = document.querySelector('.toast-notif');
        if (existing) existing.remove();

        const colors = {
            success : { bg: '#22c55e', icon: 'fa-check-circle' },
            warning : { bg: '#f97316', icon: 'fa-exclamation-circle' },
            error   : { bg: '#ef4444', icon: 'fa-times-circle' },
            info    : { bg: '#3b82f6', icon: 'fa-info-circle' },
        };

        const c     = colors[type] || colors.info;
        const toast = document.createElement('div');
        toast.className = 'toast-notif';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: -400px;
            background: ${c.bg};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            font-size: 0.9rem;
            z-index: 9999;
            transition: right 0.4s cubic-bezier(0.34,1.56,0.64,1);
            max-width: 360px;
            font-family: 'Inter', sans-serif;
        `;
        toast.innerHTML = `<i class="fas ${c.icon}" style="font-size:1.2rem"></i><span>${message}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => { toast.style.right = '20px'; }, 100);
        setTimeout(() => {
            toast.style.right = '-400px';
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }

    // ===========================
    // INJECT CSS ANIMATIONS
    // ===========================
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .page.active {
            animation: fadeInPage 0.4s ease;
        }
    `;
    document.head.appendChild(style);

    // ===========================
    // INIT
    // ===========================
    switchPage('dashboard');
    console.log('✅ SyaJagad Dashboard Santri Ready!');

    });

    

