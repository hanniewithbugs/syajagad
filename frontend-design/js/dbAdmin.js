// admin-dashboard.js
class SyaJagadAdminDashboard {
    constructor() {
        this.adminData = {
            name: "Admin Ponpes JA",
            role: "Super Admin",
            totalSantri: 120,
            totalPaid: 85,
            totalUnpaid: 35,
            totalPemasukan: 42500000,
            totalTagihan: 50000000,
            sisaTagihan: 7500000
        };

        this.santriData = [
            { id: 1, nama: "Ahmad Santoso", nis: "2024001", kelamin: "Laki-laki", angkatan: "2024", status: "aktif" },
            { id: 2, nama: "Siti Aisyah", nis: "2024002", kelamin: "Perempuan", angkatan: "2024", status: "cuti" },
            { id: 3, nama: "Muhammad Rifqi", nis: "2024003", kelamin: "Laki-laki", angkatan: "2023", status: "aktif" },
            { id: 4, nama: "Fatimah Zahra", nis: "2024004", kelamin: "Perempuan", angkatan: "2024", status: "aktif" },
            { id: 5, nama: "Hassan Basri", nis: "2024005", kelamin: "Laki-laki", angkatan: "2023", status: "aktif" },
            // Add more data as needed
        ];

        this.pembayaranData = [
            { id: 1, nama: "Ahmad Santoso", nis: "2024001", kelamin: "L", angkatan: "2024", statusSantri: "Aktif", bulan: "Okt 2024", statusBayar: "lunas" },
            { id: 2, nama: "Siti Aisyah", nis: "2024002", kelamin: "P", angkatan: "2024", statusSantri: "Cuti", bulan: "Okt 2024", statusBayar: "belum" },
            { id: 3, nama: "Muhammad Rifqi", nis: "2024003", kelamin: "L", angkatan: "2023", statusSantri: "Aktif", bulan: "Okt 2024", statusBayar: "lunas" },
        ];

        this.pembayaranTerbaru = [
            { nama: "Ahmad Santoso", bulan: "Okt 2024", tanggal: "01 Okt 2024", status: "lunas" },
            { nama: "Muhammad Rifqi", bulan: "Okt 2024", tanggal: "28 Okt 2024", status: "lunas" },
            { nama: "Fatimah Zahra", bulan: "Sep 2024", tanggal: "20 Sep 2024", status: "lunas" },
        ];

        this.currentPage = {};
        this.itemsPerPage = 8;
        this.init();
    }

    init() {
        this.currentPage = {
            santri: 1,
            pembayaran: 1
        };
        this.updateUI();
        this.bindEvents();
        this.renderAllPages();
        this.initCharts();
        this.updateDateTime();
    }

    updateUI() {
        document.getElementById('totalSantri').textContent = this.adminData.totalSantri;
        document.getElementById('totalPaid').textContent = this.adminData.totalPaid;
        document.getElementById('totalUnpaid').textContent = this.adminData.totalUnpaid;
        document.getElementById('totalPemasukan').textContent = this.formatRupiah(this.adminData.totalPemasukan / 1000000) + 'M';
        document.getElementById('totalTagihan').textContent = this.formatRupiah(this.adminData.totalTagihan);
        document.getElementById('totalBayar').textContent = this.formatRupiah(this.adminData.totalPaid * 500000);
        document.getElementById('sisaTagihan').textContent = this.formatRupiah(this.adminData.sisaTagihan);
    }

    bindEvents() {
        // Sidebar toggle
        document.getElementById('menuToggle').addEventListener('click', () => this.toggleSidebar());
        document.getElementById('sidebarClose').addEventListener('click', () => this.toggleSidebar());
        document.getElementById('sidebarOverlay').addEventListener('click', () => this.toggleSidebar());

        // Navigation
        document.querySelectorAll('.nav-item[data-page]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchPage(e.currentTarget.dataset.page);
            });
        });

        // Search & Filter
        document.getElementById('searchSantri')?.addEventListener('input', (e) => this.searchSantri(e.target.value));
        document.getElementById('filterStatus')?.addEventListener('change', (e) => this.filterSantri(e.target.value));
        document.getElementById('searchPembayaran')?.addEventListener('input', (e) => this.searchPembayaran(e.target.value));

        // Notifications & Messages
        document.getElementById('notifBtn').addEventListener('click', () => alert('Notifikasi (3 baru)'));
        document.getElementById('messageBtn').addEventListener('click', () => alert('Pesan (2 baru)'));
        
        // Logout
        document.getElementById('logoutLink').addEventListener('click', (e) => {
            e.preventDefault();
            this.showLogoutModal();
        });

        // Modal handlers
        this.bindModalHandlers();

        // Pagination global handlers
        document.addEventListener('click', (e) => {
            if (e.target.closest('.pagination-btn')) {
                const page = parseInt(e.target.dataset.page);
                const tableType = e.target.closest('.table-container').id.includes('santri') ? 'santri' : 'pembayaran';
                if (!isNaN(page)) {
                    this.currentPage[tableType] = page;
                    this.renderTable(tableType);
                }
            }
            if (e.target.closest('.btn-edit')) {
                alert('Edit data: ' + e.target.closest('tr')?.querySelector('td:nth-child(2)')?.textContent);
            }
        });
    }

    toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    switchPage(pageId) {
        document.querySelectorAll('.page').forEach(page => page.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));

        document.getElementById(`page-${pageId}`).classList.add('active');
        document.querySelector(`[data-page="${pageId}"]`).classList.add('active');

        const breadcrumbs = {
            dashboard: 'Dashboard',
            santri: 'Data Santri',
            pembayaran: 'Pembayaran SPP',
            laporan: 'Laporan',
            pengaturan: 'Pengaturan'
        };
        document.getElementById('breadcrumbCurrent').textContent = breadcrumbs[pageId] || pageId;

        this.toggleSidebar();
        
        if (pageId === 'santri') this.renderSantriTable();
        if (pageId === 'pembayaran') this.renderPembayaranTable();
        if (pageId === 'dashboard') this.renderPembayaranTerbaru();
    }

    bindModalHandlers() {
        document.querySelectorAll('.modal-close, .modal-cancel, #logoutCancel').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.modal-overlay').classList.remove('active');
            });
        });

        document.getElementById('confirmLogout').addEventListener('click', () => {
            // Simulate logout and redirect
            setTimeout(() => {
                alert('Logout berhasil! Mengarahkan ke login.html...');
                window.location.href = 'login.html';
            }, 500);
        });
    }

    renderAllPages() {
        this.renderPembayaranTerbaru();
        this.renderSantriTable();
        this.renderPembayaranTable();
    }

    renderPembayaranTerbaru() {
        const container = document.getElementById('pembayaranTerbaruList');
        if (!container) return;

        container.innerHTML = this.pembayaranTerbaru.map(pembayaran => `
            <tr>
                <td>${pembayaran.nama}</td>
                <td>${pembayaran.bulan}</td>
                <td>${pembayaran.tanggal}</td>
                <td><span class="status ${pembayaran.status}">${pembayaran.status === 'lunas' ? 'Lunas' : 'Belum Lunas'}</span></td>
            </tr>
        `).join('');
    }

    renderSantriTable() {
        const filteredData = this.getFilteredSantriData();
        const start = (this.currentPage.santri - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const paginatedData = filteredData.slice(start, end);

        const tbody = document.getElementById('santriTableBody');
        tbody.innerHTML = paginatedData.map(santri => `
            <tr>
                <td><input type="checkbox"></td>
                <td>${santri.nama}</td>
                <td>${santri.nis}</td>
                <td>${santri.kelamin}</td>
                <td>${santri.angkatan}</td>
                <td><span class="status-badge ${santri.status}">${santri.status.charAt(0).toUpperCase() + santri.status.slice(1)}</span></td>
                <td><button class="btn-edit" title="Edit"><i class="fas fa-edit"></i></button></td>
            </tr>
        `).join('');

        this.renderPagination('santri', filteredData.length);
    }

    renderPembayaranTable() {
        const filteredData = this.getFilteredPembayaranData();
        const start = (this.currentPage.pembayaran - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const paginatedData = filteredData.slice(start, end);

        const tbody = document.getElementById('pembayaranTableBody');
        tbody.innerHTML = paginatedData.map(pembayaran => `
            <tr>
                <td><input type="checkbox"></td>
                <td>${pembayaran.nama}</td>
                <td>${pembayaran.nis}</td>
                <td>${pembayaran.kelamin}</td>
                <td>${pembayaran.angkatan}</td>
                <td><span class="status-badge ${pembayaran.statusSantri}">${pembayaran.statusSantri}</span></td>
                <td>${pembayaran.bulan}</td>
                <td><span class="status-pill ${pembayaran.statusBayar}">${pembayaran.statusBayar === 'lunas' ? 'Lunas' : 'Belum Lunas'}</span></td>
            </tr>
        `).join('');

        this.renderPagination('pembayaran', filteredData.length);
    }

    getFilteredSantriData() {
        const searchTerm = document.getElementById('searchSantri')?.value.toLowerCase() || '';
        const statusFilter = document.getElementById('filterStatus')?.value || '';

        return this.santriData.filter(santri => {
            const matchesSearch = santri.nama.toLowerCase().includes(searchTerm) ||
                                santri.nis.includes(searchTerm) ||
                                santri.angkatan.includes(searchTerm);
            const matchesStatus = !statusFilter || santri.status === statusFilter;
            return matchesSearch && matchesStatus;
        });
    }

    getFilteredPembayaranData() {
        const searchTerm = document.getElementById('searchPembayaran')?.value.toLowerCase() || '';
        return this.pembayaranData.filter(pembayaran => 
            pembayaran.nama.toLowerCase().includes(searchTerm) ||
            pembayaran.nis.includes(searchTerm) ||
            pembayaran.angkatan.includes(searchTerm)
        );
    }

    searchSantri(query) {
        this.currentPage.santri = 1;
        this.renderSantriTable();
    }

    searchPembayaran(query) {
        this.currentPage.pembayaran = 1;
        this.renderPembayaranTable();
    }

    filterSantri(status) {
        this.currentPage.santri = 1;
        this.renderSantriTable();
    }

    renderPagination(tableType, totalItems) {
        const totalPages = Math.ceil(totalItems / this.itemsPerPage);
        const currentPage = this.currentPage[tableType];
        const container = document.getElementById(tableType === 'santri' ? 'santriPagination' : 'pembayaranPagination');
        const infoContainer = document.getElementById(tableType === 'santri' ? 'santriPaginationInfo' : 'pembayaranPaginationInfo');
        
        if (!container) return;

        let paginationHTML = '';
        if (currentPage > 1) {
            paginationHTML += `<button class="pagination-btn" data-page="${currentPage - 1}">← Prev</button>`;
        }

        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            paginationHTML += `<button class="pagination-btn ${activeClass}" data-page="${i}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            paginationHTML += `<button class="pagination-btn" data-page="${currentPage + 1}">Next →</button>`;
        }

        container.innerHTML = paginationHTML;

        if (infoContainer) {
            const startItem = (currentPage - 1) * this.itemsPerPage + 1;
            const endItem = Math.min(currentPage * this.itemsPerPage, totalItems);
            infoContainer.textContent = `Menampilkan ${startItem}-${endItem} dari ${totalItems} data`;
        }
    }

    initCharts() {
        this.drawPaymentChart();
        this.drawRevenueChart();
        this.drawDemographyChart();
    }

    drawPaymentChart() {
        const canvas = document.getElementById('paymentChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const data = [75, 90, 85, 95]; // Juli 2024, Jan 2025, Juli 2025, Jan 2026
        const max = 100;
        const barWidth = 60;
        const padding = 50;

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        data.forEach((value, index) => {
            const barHeight = (value / max) * 140;
            const x = padding + index * (barWidth + 20);
            const y = canvas.height - padding - barHeight;

            // Bar gradient
            const gradient = ctx.createLinearGradient(x, y, x, canvas.height);
            gradient.addColorStop(0, '#22c55e');
            gradient.addColorStop(1, '#16a34a');
            
            ctx.fillStyle = gradient;
            ctx.fillRect(x, y, barWidth, barHeight);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(x, y, barWidth, 3);

            // Value label
            ctx.fillStyle = '#1e293b';
            ctx.font = 'bold 14px Inter';
            ctx.textAlign = 'center';
            ctx.fillText(value + '%', x + barWidth/2, y - 10);

            // Label bulan
            ctx.fillStyle = '#64748b';
            ctx.font = '12px Inter';
            ctx.textAlign = 'center';
            ctx.fillText(['Jul 24', 'Jan 25', 'Jul 25', 'Jan 26'][index], x + barWidth/2, canvas.height - 10);
        });

        // Axis
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(padding - 10, canvas.height - padding);
        ctx.lineTo(canvas.width - 10, canvas.height - padding);
        ctx.lineTo(canvas.width - 10, 20);
        ctx.stroke();
    }

    drawRevenueChart() {
        // Simplified line chart
        const canvas = document.getElementById('revenueChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        // Implementation similar to payment chart but for line graph
    }

    drawDemographyChart() {
        // Simplified pie chart
        const canvas = document.getElementById('demographyChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        // Implementation for donut/pie chart
    }

    updateDateTime() {
        const now = new Date();
        const date = now.toLocaleDateString('id-ID', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        document.getElementById('currentDate').textContent = date;
    }

    formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }
}

// Global instance
let dashboardInstance;

document.addEventListener('DOMContentLoaded', () => {
    dashboardInstance = new SyaJagadAdminDashboard();
    
    // Auto updates
    setInterval(() => {
        dashboardInstance.updateDateTime();
    }, 60000);
});