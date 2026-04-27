// dbAdmin.js
class SyaJagadAdminDashboard {
    constructor() {
        this.adminData = {
            name: "Admin Utama",
            role: "Super Admin",
            totalSantri: 1245,
            totalPaid: 45200000,
            totalUnpaid: 23,
            totalPemasukan: 45200000,
            totalTagihan: 50000000,
            sisaTagihan: 4800000
        };

        this.santriData = [
            { id: 1, nama: "Ahmad Santoso", nis: "2024001", kelamin: "Laki-laki", angkatan: "2024", status: "aktif", terakhirBayar: "01 Okt 2024" },
            { id: 2, nama: "Siti Aisyah", nis: "2024002", kelamin: "Perempuan", angkatan: "2024", status: "cuti", terakhirBayar: "15 Sep 2024" },
            { id: 3, nama: "Muhammad Rifqi", nis: "2024003", kelamin: "Laki-laki", angkatan: "2023", status: "aktif", terakhirBayar: "28 Okt 2024" },
            // Add more sample data
        ];

        this.pembayaranTerbaru = [
            { nama: "Ahmad Santoso", bulan: "Okt 2024", tanggal: "01 Okt 2024", status: "lunas", jumlah: 250000 },
            { nama: "Muhammad Rifqi", bulan: "Okt 2024", tanggal: "28 Okt 2024", status: "lunas", jumlah: 250000 },
            { nama: "Fatimah Zahra", bulan: "Sep 2024", tanggal: "20 Sep 2024", status: "lunas", jumlah: 250000 },
        ];

        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.chartCtx = null;

        this.init();
    }

    init() {
        this.updateUI();
        this.bindEvents();
        this.renderDynamicContent();
        this.initCharts();
    }

    updateUI() {
        // Update admin profile
        document.getElementById('sidebarName').textContent = this.adminData.name;
        document.getElementById('sidebarRole').textContent = this.adminData.role;
        document.getElementById('topbarName').textContent = this.adminData.name;
        document.querySelector('.tp-role').textContent = this.adminData.role;

        // Update summary cards
        document.getElementById('totalSantri').textContent = this.adminData.totalSantri.toLocaleString();
        document.getElementById('totalPaid').textContent = this.formatRupiah(this.adminData.totalPaid / 1000) + 'M';
        document.getElementById('totalUnpaid').textContent = this.adminData.totalUnpaid;
        document.getElementById('totalPemasukan').textContent = this.formatRupiah(this.adminData.totalPemasukan / 1000) + 'M';
        document.getElementById('totalTagihan').textContent = this.formatRupiah(this.adminData.totalTagihan);
        document.getElementById('totalBayar').textContent = this.formatRupiah(this.adminData.totalPaid);
        document.getElementById('sisaTagihan').textContent = this.formatRupiah(this.adminData.sisaTagihan);

        // Update badges
        document.getElementById('santriBadge').textContent = this.adminData.totalSantri.toLocaleString();
        document.getElementById('pembayaranBadge').textContent = this.adminData.totalUnpaid;

        // Update date & greeting
        this.updateDateTime();
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

        // Logout
        document.getElementById('logoutLink').addEventListener('click', (e) => {
            e.preventDefault();
            this.showLogoutModal();
        });

        // Admin actions
        document.getElementById('searchSantri').addEventListener('input', (e) => this.searchSantri(e.target.value));
        document.getElementById('filterStatus').addEventListener('change', (e) => this.filterSantri(e.target.value));

        // Modal handlers
        this.bindModalHandlers();
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
        if (pageId === 'dashboard') this.renderDynamicContent();
    }

    bindModalHandlers() {
        // Close modals
        document.querySelectorAll('.modal-close, .modal-cancel, #logoutCancel').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.modal-overlay').classList.remove('active');
            });
        });

        // Logout confirm
        document.getElementById('confirmLogout').addEventListener('click', () => {
            setTimeout(() => {
                window.location.href = '/login';
            }, 500);
        });
    }

    renderDynamicContent() {
        this.renderPembayaranTerbaru();
        this.updateBadges();
    }

    renderPembayaranTerbaru() {
        const container = document.getElementById('pembayaranTerbaruList');
        container.innerHTML = this.pembayaranTerbaru.map(pembayaran => `
            <div class="table-row">
                <div class="row-info">
                    <span class="row-name">${pembayaran.nama}</span>
                    <span class="row-meta">${pembayaran.bulan}</span>
                </div>
                <div class="row-date">${pembayaran.tanggal}</div>
                <div class="row-status ${pembayaran.status}">
                    <span class="status-badge">${pembayaran.status === 'lunas' ? 'Lunas' : 'Menunggak'}</span>
                </div>
                <div class="row-amount">${this.formatRupiah(pembayaran.jumlah)}</div>
            </div>
        `).join('');
    }

    renderSantriTable() {
        const filteredData = this.getFilteredSantriData();
        const start = (this.currentPage - 1) * this.itemsPerPage;
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
                <td><span class="status-badge ${santri.status}">${santri.status.toUpperCase()}</span></td>
                <td>${santri.terakhirBayar}</td>
                <td>
                    <button class="btn-edit"><i class="fas fa-edit"></i></button>
                    <button class="btn-delete"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');

        this.renderPagination(filteredData.length);
    }

    getFilteredSantriData() {
        const searchTerm = document.getElementById('searchSantri').value.toLowerCase();
        const statusFilter = document.getElementById('filterStatus').value;

        return this.santriData.filter(santri => {
            const matchesSearch = santri.nama.toLowerCase().includes(searchTerm) ||
                                santri.nis.includes(searchTerm) ||
                                santri.angkatan.includes(searchTerm);
            const matchesStatus = !statusFilter || santri.status === statusFilter;
            return matchesSearch && matchesStatus;
        });
    }

    searchSantri(query) {
        this.currentPage = 1;
        this.renderSantriTable();
    }

    filterSantri(status) {
        this.currentPage = 1;
        this.renderSantriTable();
    }

    renderPagination(totalItems) {
        const totalPages = Math.ceil(totalItems / this.itemsPerPage);
        const container = document.getElementById('santriPagination');
        
        let paginationHTML = `
            <div class="pagination-info">
                Menampilkan ${Math.min((this.currentPage - 1) * this.itemsPerPage + 1, totalItems)}-${Math.min(this.currentPage * this.itemsPerPage, totalItems)} dari ${totalItems} santri
            </div>
            <div class="pagination-nav">
        `;

        if (this.currentPage > 1) {
            paginationHTML += `<button class="pagination-btn" onclick="dashboard.prevPage()">← Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === this.currentPage ? 'active' : '';
            paginationHTML += `<button class="pagination-btn ${activeClass}" onclick="dashboard.goToPage(${i})">${i}</button>`;
        }

        if (this.currentPage < totalPages) {
            paginationHTML += `<button class="pagination-btn" onclick="dashboard.nextPage()">Next →</button>`;
        }

        paginationHTML += '</div>';
        container.innerHTML = paginationHTML;
    }

    prevPage() { 
        if (this.currentPage > 1) {
            this.currentPage--;
            this.renderSantriTable();
        }
    }

    nextPage() {
        const totalPages = Math.ceil(this.getFilteredSantriData().length / this.itemsPerPage);
        if (this.currentPage < totalPages) {
            this.currentPage++;
            this.renderSantriTable();
        }
    }

    goToPage(page) {
        this.currentPage = page;
        this.renderSantriTable();
    }

    initCharts() {
        // Simple canvas charts (no Chart.js dependency)
        this.drawPaymentChart();
    }

    drawPaymentChart() {
        const canvas = document.getElementById('paymentChart');
        const ctx = canvas.getContext('2d');
        canvas.width = 400;
        canvas.height = 200;

        // Simple bar chart simulation
        const data = [120, 150, 180, 200, 220];
        const max = Math.max(...data);
        const barWidth = 50;
        const padding = 40;

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = var(--gray-100);
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        data.forEach((value, index) => {
            const barHeight = (value / max) * 140;
            const x = padding + index * (barWidth + 20);
            const y = canvas.height - padding - barHeight;

            // Bar
            ctx.fillStyle = var(--gold);
            ctx.fillRect(x, y, barWidth, barHeight);
            ctx.fillStyle = '#3D5300';
            ctx.fillRect(x, y, barWidth, 4); // Top line

            // Value label
            ctx.fillStyle = var(--text-primary);
            ctx.font = 'bold 12px Inter';
            ctx.textAlign = 'center';
            ctx.fillText(value, x + barWidth/2, y - 8);
        });

        // Axis
        ctx.strokeStyle = var(--gray-300);
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(padding - 10, canvas.height - padding);
        ctx.lineTo(canvas.width - 10, canvas.height - padding);
        ctx.lineTo(canvas.width - 10, 20);
        ctx.stroke();
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

        const hour = now.getHours();
        let greeting = '';
        if (hour < 12) greeting = 'Selamat Pagi';
        else if (hour < 15) greeting = 'Selamat Siang';
        else if (hour < 18) greeting = 'Selamat Sore';
        else greeting = 'Selamat Malam';
        
        document.getElementById('greetingTime').textContent = greeting;
    }

    formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }

    updateBadges() {
        // Simulate real-time updates
        document.getElementById('notifBadge').textContent = Math.floor(Math.random() * 5) + 1;
    }

    showLogoutModal() {
        document.getElementById('logoutModal').classList.add('active');
    }
}

// Global functions for onclick handlers
const dashboard = {
    prevPage: () => window.dashboardInstance.prevPage(),
    nextPage: () => window.dashboardInstance.nextPage(),
    goToPage: (page) => window.dashboardInstance.goToPage(page)
};

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardInstance = new SyaJagadAdminDashboard();
    
    // Auto update every 30 seconds
    setInterval(() => {
        dashboardInstance.updateBadges();
        dashboardInstance.updateDateTime();
    }, 30000);

    // Update date every minute
    setInterval(() => dashboardInstance.updateDateTime(), 60000);
});

class DataSantriManager {
    constructor(options = {}) {
        this.santriData = options.initialData || [];
        this.itemsPerPage = options.itemsPerPage || 8;
        this.currentPage = 1;

        this.tbody = document.getElementById('santriTableBody');
        this.searchInput = document.getElementById('searchSantri');
        this.paginationContainer = document.getElementById('santriPagination');

        this.editSantriCallback = options.onEditSantri || function(id) {
            alert(`Edit Santri dengan ID: ${id}`);
        };

        this.bindEvents();
        this.renderTable();
    }

    bindEvents() {
        if (this.searchInput) {
            this.searchInput.addEventListener('input', () => {
                this.currentPage = 1;
                this.renderTable();
            });
        }

        if (this.paginationContainer) {
            this.paginationContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('pagination-btn')) {
                    const page = parseInt(e.target.dataset.page);
                    if (!isNaN(page)) {
                        this.currentPage = page;
                        this.renderTable();
                    }
                } else if (e.target.closest('.edit-btn')) {
                    const id = e.target.closest('.edit-btn').dataset.id;
                    if (id) {
                        this.editSantriCallback(parseInt(id));
                    }
                }
            });
        }
    }

    filterData(searchTerm) {
        const term = searchTerm.trim().toLowerCase();
        if (!term) return this.santriData;

        return this.santriData.filter(santri => 
            santri.nama.toLowerCase().includes(term) ||
            santri.nis.includes(term) ||
            String(santri.angkatan).includes(term)
        );
    }

    renderTable() {
        if (!this.tbody) return;

        const filteredData = this.filterData(this.searchInput ? this.searchInput.value : '');
        const totalItems = filteredData.length;
        const totalPages = Math.ceil(totalItems / this.itemsPerPage);
        if (this.currentPage > totalPages) this.currentPage = totalPages || 1;

        const start = (this.currentPage - 1) * this.itemsPerPage;
        const selectedData = filteredData.slice(start, start + this.itemsPerPage);

        this.tbody.innerHTML = selectedData.map(s => `
            <tr>
                <td>${this.escapeHtml(s.nama)}</td>
                <td>${this.escapeHtml(s.nis)}</td>
                <td>${this.escapeHtml(s.kelamin)}</td>
                <td>${s.angkatan}</td>
                <td><span class="status ${s.status.toLowerCase()}">${this.escapeHtml(s.status)}</span></td>
                <td>
                    <button class="edit-btn" data-id="${s.id}" title="Edit Data" aria-label="Edit ${s.nama}">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        this.renderPagination(totalPages);
    }

    renderPagination(totalPages) {
        if (!this.paginationContainer) return;

        if (totalPages <= 1) {
            this.paginationContainer.innerHTML = '';
            return;
        }

        let html = '';
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="pagination-btn ${i === this.currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        this.paginationContainer.innerHTML = html;
    }

    escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}

