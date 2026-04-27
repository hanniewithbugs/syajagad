// dbSantri.js
class SyaJagadDashboard {
    constructor() {
        this.userData = {
            name: "Ahmad Santoso",
            nis: "2024001",
            email: "ahmad.santoso@santri.syajagad.ac.id",
            username: "ahmad_santri",
            birthdate: "15 Mei 2008",
            address: "Jl. Pesantren Al-Hikmah No. 123, Jakarta",
            joinDate: "2024-01-15",
            totalPaid: 2500000,
            totalTx: 10,
            lastPayment: "01 Okt 2024",
            riskLevel: 15, // 0-100%
            denda: 100000 // Rp 50k/bulan x 2
        };

        this.tagihanData = [
            {
                id: 1,
                name: "SPP November 2024",
                amount: 250000,
                dueDate: "15 Nov 2024",
                status: "belum",
                denda: 0
            },
            {
                id: 2,
                name: "SPP Oktober 2024",
                amount: 250000,
                paidDate: "01 Okt 2024",
                status: "lunas",
                method: "QRIS"
            },
            {
                id: 3,
                name: "SPP Juli 2024",
                amount: 250000,
                paidDate: "20 Jul 2024",
                status: "terlambat",
                denda: 50000,
                method: "QRIS"
            }
        ];

        this.init();
    }

    init() {
        this.updateUI();
        this.bindEvents();
        this.loadAIPrediction();
        this.renderDynamicContent();
    }

    updateUI() {
        // Update profile info
        document.getElementById('sidebarName').textContent = this.userData.name;
        document.getElementById('sidebarNIS').textContent = `NIS: ${this.userData.nis}`;
        document.getElementById('topbarName').textContent = this.userData.name;
        document.getElementById('welcomeName').textContent = this.userData.name;
        document.getElementById('profilNama').textContent = this.userData.name;
        document.getElementById('profilNIS').textContent = this.userData.nis;
        document.getElementById('profilEmail').textContent = this.userData.email;
        document.getElementById('profilUsername').textContent = this.userData.username;
        document.getElementById('profilTglLahir').textContent = this.userData.birthdate;
        document.getElementById('profilAlamat').textContent = this.userData.address;

        // Update summary
        document.getElementById('totalPaid').textContent = this.formatRupiah(this.userData.totalPaid);
        document.getElementById('totalTx').textContent = `${this.userData.totalTx} transaksi berhasil`;
        document.getElementById('lastPayment').textContent = this.userData.lastPayment;

        // Show denda if exists
        if (this.userData.denda > 0) {
            document.getElementById('totalDenda').textContent = this.formatRupiah(this.userData.denda);
            document.getElementById('totalDendaCard').style.display = 'block';
        }

        // Update date & greeting
        this.updateDateTime();
    }

    bindEvents() {
        // Sidebar toggle
        document.getElementById('menuToggle').addEventListener('click', () => this.toggleSidebar());
        document.getElementById('sidebarClose').addEventListener('click', () => this.toggleSidebar());
        document.getElementById('sidebarOverlay').addEventListener('click', () => this.toggleSidebar());

        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const link = e.currentTarget.querySelector('.nav-link');
                if (!link.classList.contains('logout-link')) {
                    this.switchPage(e.currentTarget.dataset.page);
                }
            });
        });

        // Modals
        document.getElementById('logoutLink').addEventListener('click', (e) => {
            e.preventDefault();
            this.showLogoutModal();
        });

        // Payment modals
        document.getElementById('bayarSekarangBtn').addEventListener('click', () => {
            this.openPaymentModal(this.tagihanData[0]);
        });

        // Filter tagihan
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', (e) => this.filterTagihan(e.target.dataset.filter));
        });

        // Other modal handlers
        this.bindModalHandlers();
    }

    toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    switchPage(pageId) {
        // Hide all pages
        document.querySelectorAll('.page').forEach(page => page.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));

        // Show selected page
        document.getElementById(`page-${pageId}`).classList.add('active');
        document.querySelector(`[data-page="${pageId}"]`).classList.add('active');

        // Update breadcrumb
        const breadcrumbs = {
            dashboard: 'Dashboard',
            tagihan: 'Tagihan',
            profil: 'Profil'
        };
        document.getElementById('breadcrumbCurrent').textContent = breadcrumbs[pageId];

        this.toggleSidebar(); // Close sidebar
    }

    showLogoutModal() {
        document.getElementById('logoutModal').classList.add('active');
    }

    bindModalHandlers() {
        // Close modals
        document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.modal-overlay').classList.remove('active');
            });
        });

        // Logout confirm
        document.getElementById('confirmLogout').addEventListener('click', () => {
            // Simulate logout - redirect to login
            setTimeout(() => {
                window.location.href = '/login';
            }, 500);
        });

        // Payment
        document.getElementById('modalPay').addEventListener('click', () => {
            const method = document.querySelector('.pm-option.active').dataset.method;
            this.simulatePaymentSuccess(method);
        });

        // Success modal close
        document.getElementById('successClose').addEventListener('click', () => {
            document.getElementById('successModal').classList.remove('active');
            this.switchPage('dashboard');
        });

        // Show password toggle
        document.getElementById('showPassword').addEventListener('click', (e) => {
            const field = e.target.closest('.password-field').querySelector('span');
            const icon = e.target.querySelector('i');
            if (field.textContent === '************') {
                field.textContent = 'ahmad123456';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.textContent = '************';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    loadAIPrediction() {
        // Simulate AI prediction
        const riskLabels = ['Sangat Rendah', 'Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'];
        const adviceTexts = [
            'Pola pembayaran sangat baik! Terus jaga konsistensi.',
            'Pola pembayaran tepat waktu. Terus jaga konsistensi!',
            'Ada sedikit keterlambatan. Segera lunasi tagihan.',
            'Risiko tinggi! Segera bayar untuk hindari denda.',
            'Sangat urgent! Bayar segera untuk hindari sanksi.'
        ];

        const riskIndex = Math.floor(this.userData.riskLevel / 25);
        document.getElementById('aiRiskLabel').textContent = riskLabels[riskIndex];
        document.getElementById('aiRiskValue').textContent = `${this.userData.riskLevel}%`;
        document.getElementById('riskFill').style.width = `${this.userData.riskLevel}%`;
        document.getElementById('aiAdviceText').textContent = adviceTexts[riskIndex];
        document.getElementById('aiUpdated').textContent = new Date().toLocaleTimeString('id-ID');
    }

    renderDynamicContent() {
        this.renderTagihanAktif();
        this.renderTagihanList();
        this.renderRiwayat();
    }

    renderTagihanAktif() {
        const unpaid = this.tagihanData.filter(t => t.status === 'belum');
        const container = document.getElementById('tagihanAktifList');
        
        if (unpaid.length === 0) {
            container.innerHTML = '<div class="no-data"><i class="fas fa-check-circle"></i><p>Semua tagihan sudah lunas!</p></div>';
            document.getElementById('navTagihanBadge').style.display = 'none';
            return;
        }

        container.innerHTML = unpaid.map(tagihan => `
            <div class="tagihan-item unpaid">
                <div class="ti-left">
                    <div class="ti-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="ti-info">
                        <span class="ti-name">${tagihan.name}</span>
                        <span class="ti-date">Jatuh tempo: ${tagihan.dueDate}</span>
                    </div>
                </div>
                <div class="ti-right">
                    <span class="ti-amount">${this.formatRupiah(tagihan.amount)}</span>
                    <span class="ti-status unpaid">Belum Lunas</span>
                    <button class="ti-btn" onclick="dashboard.openPaymentModal(${JSON.stringify(tagihan)})">
                        <i class="fas fa-credit-card"></i> Bayar
                    </button>
                </div>
            </div>
        `).join('');
    }

    renderTagihanList() {
        const container = document.getElementById('tagihanList');
        container.innerHTML = this.tagihanData.map(tagihan => this.createTagihanCard(tagihan)).join('');
    }

    createTagihanCard(tagihan) {
        const isUnpaid = tagihan.status === 'belum';
        const statusClass = tagihan.status;
        const iconClass = isUnpaid ? 'unpaid' : tagihan.status;
        const icon = isUnpaid ? 'fa-clock' : 
                    tagihan.status === 'lunas' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        
        let headerContent = `
            <div class="tc-icon ${iconClass}">
                <i class="fas ${icon}"></i>
            </div>
            <div>
                <h4>${tagihan.name}</h4>
        `;

        if (isUnpaid) {
            headerContent += `<span>Jatuh tempo: ${tagihan.dueDate}</span>`;
        } else {
            headerContent += `<span>${tagihan.paidDate || 'Dibayar'} ${tagihan.status === 'terlambat' ? '(Terlambat)' : ''}</span>`;
        }
        headerContent += `</div>`;

        let badge = `<span class="tc-badge ${statusClass}">${isUnpaid ? 'Belum Lunas' : 
                    tagihan.status === 'lunas' ? 'Lunas' : 'Terlambat'}</span>`;

        let footer = '';
        if (isUnpaid) {
            footer = `
                <div class="tc-payment-methods">
                    <span class="pm-tag">QRIS</span>
                    <span class="pm-tag">BCA VA</span>
                    <span class="pm-tag">Mandiri VA</span>
                </div>
                <button class="tc-pay-btn" onclick="dashboard.openPaymentModal(${JSON.stringify(tagihan)})">
                    <i class="fas fa-credit-card"></i> Bayar Sekarang
                </button>
            `;
        } else {
            footer = `
                <div class="tc-trx-id">
                    <i class="fas fa-receipt"></i>
                    <span>TRX-${tagihan.id.toString().padStart(4, '0')}</span>
                </div>
                <button class="tc-detail-btn">
                    <i class="fas fa-eye"></i> Lihat Detail
                </button>
            `;
        }

        return `
            <div class="tagihan-card ${statusClass}" data-status="${tagihan.status}">
                <div class="tc-header">
                    <div class="tc-title">
                        ${headerContent}
                    </div>
                    ${badge}
                </div>
                <div class="tc-body">
                    <div class="tc-amount">
                        <span class="tc-label">${isUnpaid ? 'Total Tagihan' : 'Total Dibayar'}</span>
                        <span class="tc-value">${this.formatRupiah(tagihan.amount)}</span>
                    </div>
                    <div class="tc-meta">
                        <div class="tc-meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>${tagihan.name.split(' ')[1]} 2024</span>
                        </div>
                        ${tagihan.method ? `
                            <div class="tc-meta-item">
                                <i class="fas fa-qrcode"></i>
                                <span>Via ${tagihan.method}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
                <div class="tc-footer">
                    ${footer}
                </div>
            </div>
        `;
    }

    renderRiwayat() {
        const riwayat = this.tagihanData.filter(t => t.status !== 'belum');
        const container = document.getElementById('riwayatList');
        document.getElementById('riwayatCount').textContent = `${riwayat.length} Transaksi`;
        
        container.innerHTML = riwayat.map(tx => `
            <div class="tx-item">
                <div class="tx-icon ${tx.status}">
                    <i class="fas ${tx.status === 'terlambat' ? 'fa-exclamation' : 'fa-check'}"></i>
                </div>
                <div class="tx-info">
                    <span class="tx-name">${tx.name}</span>
                    <span class="tx-meta">${tx.paidDate} · ${tx.method || 'QRIS'}</span>
                </div>
                <div class="tx-right">
                    <span class="tx-amount">${this.formatRupiah(tx.amount)}</span>
                    <span class="tx-status ${tx.status}">${tx.status === 'terlambat' ? 'Terlambat' : 'Lunas'}</span>
                </div>
            </div>
        `).join('');
    }

    openPaymentModal(tagihan) {
        document.getElementById('modalTagihanName').textContent = tagihan.name;
        document.getElementById('modalTagihanAmount').textContent = this.formatRupiah(tagihan.amount);
        document.getElementById('paymentModal').classList.add('active');
    }

    simulatePaymentSuccess(method) {
        document.getElementById('successMethod').textContent = method.toUpperCase();
        document.getElementById('successAmount').textContent = 'Rp 250.000';
        document.getElementById('successTrxId').textContent = `TRX-${Date.now().toString().slice(-6)}`;
        document.getElementById('paymentModal').classList.remove('active');
        document.getElementById('successModal').classList.add('active');
        
        // Update data after payment
        setTimeout(() => {
            this.tagihanData[0].status = 'lunas';
            this.tagihanData[0].paidDate = new Date().toLocaleDateString('id-ID');
            this.tagihanData[0].method = method;
            this.renderDynamicContent();
        }, 1000);
    }

    filterTagihan(status) {
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.filter === status);
        });

        document.querySelectorAll('.tagihan-card').forEach(card => {
            if (status === 'semua' || card.dataset.status === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }

    updateDateTime() {
               const now = new Date();
        const time = now.toLocaleTimeString('id-ID');
        const date = now.toLocaleDateString('id-ID', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });

        // Update date
        document.getElementById('currentDate').textContent = date;

        // Update greeting
        const hour = now.getHours();
        let greeting = '';
        if (hour < 12) greeting = 'Selamat Pagi';
        else if (hour < 15) greeting = 'Selamat Siang';
        else if (hour < 18) greeting = 'Selamat Sore';
        else greeting = 'Selamat Malam';
        
        document.getElementById('greetingTime').textContent = greeting;

        // Update AI time every 30 seconds
        setTimeout(() => this.loadAIPrediction(), 30000);
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new SyaJagadDashboard();
    
    // Auto update date every minute
    setInterval(() => dashboard.updateDateTime(), 60000);
});