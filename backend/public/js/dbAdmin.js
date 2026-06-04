document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const menuToggle = document.getElementById('menuToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const breadcrumbCurrent = document.getElementById('breadcrumbCurrent');
    const currentDate = document.getElementById('currentDate');
    const greetingTime = document.getElementById('greetingTime');
    const notifBtn = document.getElementById('notifBtn');
    const messageBtn = document.getElementById('messageBtn');
    const adminNotificationModal = document.getElementById('adminNotificationModal');
    const adminNotificationClose = document.getElementById('adminNotificationClose');
    const adminNotificationDismiss = document.getElementById('adminNotificationDismiss');
    const adminNotificationEmail = document.getElementById('adminNotificationEmail');
    const addSantriBtn = document.getElementById('addSantriBtn');
    const addSantriModal = document.getElementById('addSantriModal');
    const addSantriClose = document.getElementById('addSantriClose');
    const addSantriCancel = document.getElementById('addSantriCancel');
    const saveSantriBtn = document.getElementById('saveSantriBtn');
    const searchSantri = document.getElementById('searchSantri');
    const searchPembayaran = document.getElementById('searchPembayaran');
    const filterStatus = document.getElementById('filterStatus');
    const santriGender = document.getElementById('santriGender');
    const santriModalTitle = document.getElementById('santriModalTitle');
    const santriInvoiceModal = document.getElementById('santriInvoiceModal');
    const closeInvoiceModal = document.getElementById('closeInvoiceModal');
    const closeInvoiceModalBtn = document.getElementById('closeInvoiceModalBtn');
    const saveInstitutionBtn = document.getElementById('saveInstitutionBtn');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const manageRolesBtn = document.getElementById('manageRolesBtn');
    const bankMandiri = document.getElementById('bankMandiri');
    const bankBca = document.getElementById('bankBca');
    const savePeriodBtn = document.getElementById('savePeriodBtn');
    const saveInvoiceBtn = document.getElementById('saveInvoiceBtn');
    const reportAngkatan = document.getElementById('reportAngkatan');
    const reportMethod = document.getElementById('reportMethod');
    const reportStatus = document.getElementById('reportStatus');
    const reportStartDate = document.getElementById('reportStartDate');
    const reportEndDate = document.getElementById('reportEndDate');

    let santriData = [];
    let paymentData = [];
    let reportData = [];
    let statsData = {};
    let editingSantriId = null;
    let activeInvoiceSantriId = null;

    const apiRoutes = {
        stats: '/admin/stats',
        santri: '/admin/santri',
        payments: '/admin/payments',
        reports: '/admin/reports',
        auditLogs: '/admin/audit-logs',
        permissions: '/admin/permissions',
    };

    let paymentChart = null;
    let revenueChart = null;
    let demographyChart = null;

    const buildChart = (ctx, type, data, options = {}) => {
        if (!ctx || typeof Chart === 'undefined') return null;
        return new Chart(ctx, {
            type,
            data,
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 16 } },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: 'rgba(203,213,225,0.35)' } },
                },
            }, options),
        });
    };

    const renderCharts = (payments = [], santri = []) => {
        const paymentCtx = document.getElementById('paymentChart');
        const revenueCtx = document.getElementById('revenueChart');
        const demographyCtx = document.getElementById('demographyChart');

        const payableRows = payments.filter((item) => item.status !== 'no_invoice');
        const monthLabels = Array.from(new Set(payableRows.map((item) => item.month || 'Lainnya'))).slice(0, 8);
        const angkatanLabels = Array.from(new Set(santri.map((item) => item.angkatan || 'Tidak Diketahui')));

        const getStatusSum = (month, status) => payableRows
            .filter((item) => (item.month || 'Lainnya') === month && item.status === status)
            .reduce((sum, item) => sum + Number(item.total || 0), 0);

        const paidCounts = monthLabels.map((month) => payableRows.filter((item) => (item.month || 'Lainnya') === month && item.status === 'lunas').length);
        const unpaidCounts = monthLabels.map((month) => payableRows.filter((item) => (item.month || 'Lainnya') === month && ['belum', 'terlambat'].includes(item.status)).length);
        const paidRevenue = monthLabels.map((month) => getStatusSum(month, 'lunas'));
        const outstandingRevenue = monthLabels.map((month) => payableRows
            .filter((item) => (item.month || 'Lainnya') === month && ['belum', 'terlambat'].includes(item.status))
            .reduce((sum, item) => sum + Number(item.total || 0), 0));

        const angkatanCounts = angkatanLabels.map((angkatan) => santri.filter((item) => (item.angkatan || 'Tidak Diketahui') === angkatan).length);

        if (paymentChart) paymentChart.destroy();
        if (revenueChart) revenueChart.destroy();
        if (demographyChart) demographyChart.destroy();

        paymentChart = buildChart(paymentCtx, 'line', {
            labels: monthLabels,
            datasets: [
                {
                    label: 'Sudah Bayar',
                    data: paidCounts,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34,197,94,0.15)',
                    tension: 0.35,
                    fill: true,
                },
                {
                    label: 'Belum Bayar',
                    data: unpaidCounts,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(249,115,22,0.15)',
                    tension: 0.35,
                    fill: true,
                },
            ],
        });

        revenueChart = buildChart(revenueCtx, 'line', {
            labels: monthLabels,
            datasets: [
                {
                    label: 'Pendapatan Lunas',
                    data: paidRevenue,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.12)',
                    tension: 0.35,
                    fill: true,
                },
                {
                    label: 'Tunggakan',
                    data: outstandingRevenue,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.1)',
                    tension: 0.35,
                    fill: true,
                },
            ],
        }, {
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: 'rgba(203,213,225,0.35)' }, ticks: { callback: (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value) } },
            },
        });

        demographyChart = buildChart(demographyCtx, 'doughnut', {
            labels: angkatanLabels.length ? angkatanLabels : ['Belum ada santri'],
            datasets: [{
                data: angkatanCounts.length ? angkatanCounts : [1],
                backgroundColor: ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'],
            }],
        });
    };

    const setText = (id, value) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    };

    const setStatus = (id, message, type = 'success') => {
        const element = document.getElementById(id);
        if (!element) return;

        element.textContent = message;
        element.classList.remove('success', 'error');
        element.classList.add(type);
    };

    const loadSettingValues = () => {
        const savedInstitution = JSON.parse(localStorage.getItem('syajagadInstitution') || '{}');
        Object.entries(savedInstitution).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.value = value;
        });

        const savedPeriod = JSON.parse(localStorage.getItem('syajagadPaymentPeriod') || '{}');
        if (savedPeriod.periodStart && document.getElementById('periodStart')) document.getElementById('periodStart').value = savedPeriod.periodStart;
        if (savedPeriod.periodEnd && document.getElementById('periodEnd')) document.getElementById('periodEnd').value = savedPeriod.periodEnd;

        if (bankMandiri) bankMandiri.checked = localStorage.getItem('syajagadBankMandiri') !== 'false';
        if (bankBca) bankBca.checked = localStorage.getItem('syajagadBankBca') !== 'false';
    };

    const updateAdminSummary = () => {
        const outstanding = Number(statsData.outstanding || 0);
        const totalPenalty = Number(statsData.totalPenalty || 0);
        const pending = Number(statsData.pendingVerification || 0);
        const overdue = Number(statsData.totalTunggak || 0);
        const pendingAmount = Number(statsData.pendingVerificationAmount || 0);
        const overdueAmount = Number(statsData.overdueAmount || 0);
        const paidCount = Number(statsData.totalPaid || 0);

        setText('reportRevenue', formatRupiah(statsData.totalRevenue || 0));
        setText('reportOverdue', `${overdue} Santri`);
        setText('reportOutstanding', formatRupiah(outstanding));
        setText('reportCollectibility', `${statsData.collectibility || 0}%`);
        setText('reportPending', `${pending} Transaksi | ${formatRupiah(pendingAmount)}`);
        setText('paymentOverdueCount', `${overdue} Santri`);
        setText('paymentOverdueAmount', formatRupiah(overdueAmount));
        setText('paymentPaidCount', `${paidCount} Santri`);
        setText('paymentPaidAmount', formatRupiah(statsData.totalRevenue || 0));
        setText('settingPenalty', formatRupiah(totalPenalty));
        setText('settingSemesterFee', formatRupiah(statsData.semesterFee || 2200000));
        setText('settingTotalPayment', `Total Pembayaran: ${formatRupiah(statsData.totalTagihan || 0)}`);
        setText('notifBadge', overdue + pending);
        setText('messageBadge', santriData.filter((item) => item.email).length);
        setText('highRiskCount', `${statsData.highRiskSantri || 0} risiko tinggi`);
        renderRiskInsights(statsData.topRiskSantri || []);
    };

    const renderRiskInsights = (items) => {
        const container = document.getElementById('riskInsightList');
        if (!container) return;

        if (!items.length) {
            container.innerHTML = '<div class="text-center">Belum ada data risiko pembayaran.</div>';
            return;
        }

        container.innerHTML = items.map((item) => `
            <div class="risk-insight-item">
                <div>
                    <strong>${item.name}</strong>
                    <span>NIS ${item.nis || '-'} - ${item.risk_reason}</span>
                </div>
                <span class="risk-badge ${riskClass(item.risk_label)}">${item.risk_label} ${item.risk_score}</span>
            </div>
        `).join('');
    };

    const updateBankStatus = () => {
        const activeBanks = [];
        if (bankMandiri?.checked) activeBanks.push('Mandiri VA');
        if (bankBca?.checked) activeBanks.push('BCA VA');

        setText('bankStatusText', activeBanks.length ? `${activeBanks.join(' dan ')} aktif.` : 'Belum ada integrasi bank aktif.');
        localStorage.setItem('syajagadBankMandiri', bankMandiri?.checked ? 'true' : 'false');
        localStorage.setItem('syajagadBankBca', bankBca?.checked ? 'true' : 'false');
    };

    const openSidebar = () => {
        sidebar?.classList.add('active');
        sidebarOverlay?.classList.add('active');
    };

    const closeSidebar = () => {
        sidebar?.classList.remove('active');
        sidebarOverlay?.classList.remove('active');
    };

    menuToggle?.addEventListener('click', openSidebar);
    sidebarClose?.addEventListener('click', closeSidebar);
    sidebarOverlay?.addEventListener('click', closeSidebar);

    document.querySelectorAll('.nav-item[data-page]').forEach((item) => {
        item.addEventListener('click', (event) => {
            event.preventDefault();

            const pageId = item.dataset.page;
            document.querySelectorAll('.nav-item[data-page]').forEach((navItem) => {
                navItem.classList.remove('active');
            });
            document.querySelectorAll('.page').forEach((page) => {
                page.classList.remove('active');
            });

            item.classList.add('active');
            document.getElementById(`page-${pageId}`)?.classList.add('active');

            if (breadcrumbCurrent) {
                const label = item.querySelector('span')?.textContent?.trim();
                breadcrumbCurrent.textContent = label || 'Dashboard';
            }

            closeSidebar();
        });
    });

    const formatRupiah = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const riskClass = (label = '') => {
        const normalized = label.toLowerCase();
        if (normalized === 'tinggi') return 'high';
        if (normalized === 'sedang') return 'medium';
        return 'low';
    };

    const updateTime = () => {
        if (currentDate) {
            currentDate.textContent = new Intl.DateTimeFormat('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            }).format(new Date());
        }

        if (greetingTime) {
            const hour = new Date().getHours();
            greetingTime.textContent = hour < 11
                ? 'Selamat Pagi'
                : hour < 15
                    ? 'Selamat Siang'
                    : hour < 18
                        ? 'Selamat Sore'
                        : 'Selamat Malam';
        }
    };

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    const fetchStats = async () => {
        try {
            const response = await fetch(apiRoutes.stats, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat statistik admin');
            const data = await response.json();
            statsData = data;
            setText('totalSantri', data.totalSantri);
            setText('totalPaid', `${data.totalPaid} Santri`);
            setText('totalUnpaid', `${data.totalUnpaid} Santri`);
            setText('totalPemasukan', formatRupiah(data.totalRevenue));
            setText('totalBayar', formatRupiah(data.totalRevenue));
            setText('totalTagihan', formatRupiah(data.totalTagihan));
            setText('sisaTagihan', formatRupiah(Math.max(data.totalTagihan - data.totalRevenue, 0)));
            updateAdminSummary();
            fetchAuditLogs();
            fetchPermissions();
        } catch (error) {
            console.error(error);
        }
    };

    const hasPermission = (permission) => {
        const permissions = window.adminPermissions || [];
        return permissions.includes(permission);
    };

    const fetchAuditLogs = async () => {
        const container = document.getElementById('auditLogList');
        if (!container || !hasPermission('view_audit_logs')) {
            if (container) container.innerHTML = '<div class="text-center">Akun ini tidak memiliki izin melihat audit log.</div>';
            return;
        }

        try {
            const response = await fetch(apiRoutes.auditLogs, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat audit log');
            const result = await response.json();
            renderAuditLogs(result.data || []);
        } catch (error) {
            container.innerHTML = `<div class="text-center">${error.message}</div>`;
        }
    };

    const renderAuditLogs = (logs) => {
        const container = document.getElementById('auditLogList');
        if (!container) return;

        if (!logs.length) {
            container.innerHTML = '<div class="text-center">Belum ada aktivitas tercatat.</div>';
            return;
        }

        container.innerHTML = logs.map((log) => `
            <div class="audit-log-item">
                <div>
                    <strong>${log.description}</strong>
                    <span>${log.actor} - ${log.created_at}</span>
                </div>
                <code>${log.action}</code>
            </div>
        `).join('');
    };

    const fetchPermissions = async () => {
        const container = document.getElementById('adminPermissionList');
        if (!container || !hasPermission('manage_permissions')) {
            if (container) container.innerHTML = '<div class="text-center">Akun ini tidak memiliki izin mengelola permission.</div>';
            return;
        }

        try {
            const response = await fetch(apiRoutes.permissions, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat permission admin');
            const result = await response.json();
            renderPermissions(result.admins || [], result.permissions || {});
        } catch (error) {
            container.innerHTML = `<div class="text-center">${error.message}</div>`;
        }
    };

    const renderPermissions = (admins, permissions) => {
        const container = document.getElementById('adminPermissionList');
        if (!container) return;

        if (!admins.length) {
            container.innerHTML = '<div class="text-center">Belum ada admin.</div>';
            return;
        }

        container.innerHTML = admins.map((admin) => `
            <div class="permission-admin-item" data-admin-id="${admin.id}">
                <div class="permission-admin-head">
                    <div>
                        <strong>${admin.name}</strong>
                        <span>${admin.email || admin.username || '-'}</span>
                    </div>
                    <button class="btn-primary small" data-action="save-permission" data-id="${admin.id}">
                        Simpan
                    </button>
                </div>
                <div class="permission-checks">
                    ${Object.entries(permissions).map(([key, label]) => `
                        <label class="permission-check">
                            <input type="checkbox" value="${key}" ${admin.permissions.includes(key) ? 'checked' : ''}>
                            <span>${label}</span>
                        </label>
                    `).join('')}
                </div>
            </div>
        `).join('');
    };

    const savePermissions = async (adminId) => {
        const item = document.querySelector(`.permission-admin-item[data-admin-id="${adminId}"]`);
        if (!item) return;

        const permissions = Array.from(item.querySelectorAll('input[type="checkbox"]:checked')).map((input) => input.value);

        try {
            const response = await fetch(`/admin/users/${adminId}/permissions`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ permissions }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Gagal menyimpan permission');
            setStatus('permissionSaveStatus', result.message || 'Permission berhasil disimpan.');
            fetchAuditLogs();
        } catch (error) {
            setStatus('permissionSaveStatus', error.message || 'Gagal menyimpan permission', 'error');
        }
    };

    const fetchSantri = async (search = '') => {
        try {
            const url = new URL(apiRoutes.santri, window.location.origin);
            if (search) url.searchParams.set('search', search);
            const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat data santri');
            const json = await response.json();
            santriData = json.data;
            filterSantri();
            renderCharts(paymentData, santriData);
            updateAdminSummary();
        } catch (error) {
            console.error(error);
        }
    };

    const renderSantri = (list) => {
        const container = document.getElementById('santriTableBody');
        if (!container) return;

        if (list.length === 0) {
            container.innerHTML = '<tr><td colspan="7" class="text-center">Belum ada data santri.</td></tr>';
            return;
        }

        const getStatusClass = (status) => {
            const slug = status?.toLowerCase().replace(/\s+/g, '-');
            return `status-badge ${slug}`;
        };

        container.innerHTML = list.map((santri) => `
            <tr>
                <td><input type="checkbox"></td>
                <td>${santri.name}</td>
                <td>${santri.nis}</td>
                <td>${santri.gender || '-'}</td>
                <td>${santri.angkatan || new Date(santri.created_at).getFullYear()}</td>
                <td>
                    <span class="${getStatusClass(santri.status)}">${santri.status}</span>
                    <span class="${getStatusClass(santri.payment_status)}">${santri.payment_status || '-'}</span>
                    <span class="risk-badge ${riskClass(santri.risk_label)}">${santri.risk_label || 'Rendah'} ${santri.risk_score ?? 0}</span>
                </td>
                <td>
                    <button class="btn-secondary small" data-action="view" data-id="${santri.id}">Lihat</button>
                    <button class="btn-primary small" data-action="invoice" data-id="${santri.id}">Tagihan</button>
                    <button class="btn-warning small" data-action="edit" data-id="${santri.id}">Edit</button>
                    <button class="btn-danger small" data-action="delete" data-id="${santri.id}">Hapus</button>
                </td>
            </tr>
        `).join('');
    };

    const filterSantri = () => {
        const searchTerm = searchSantri?.value.trim().toLowerCase() || '';
        const statusFilter = filterStatus?.value || '';

        const filtered = santriData.filter((item) => {
            const matchesSearch = searchTerm === '' ||
                item.name.toLowerCase().includes(searchTerm) ||
                (item.nis || '').toLowerCase().includes(searchTerm) ||
                (item.gender || '').toLowerCase().includes(searchTerm) ||
                (item.username || '').toLowerCase().includes(searchTerm) ||
                (item.email || '').toLowerCase().includes(searchTerm);

            const studentStatus = (item.santri_status || item.status || '').toLowerCase();
            const paymentStatus = (item.payment_status || '').toLowerCase();
            const matchesStatus = statusFilter === ''
                || studentStatus === statusFilter
                || paymentStatus === statusFilter;
            return matchesSearch && matchesStatus;
        });

        renderSantri(filtered);
    };

    const fetchPayments = async () => {
        try {
            const response = await fetch(apiRoutes.payments, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat pembayaran');
            const json = await response.json();
            paymentData = json.data;
            filterPayments();
            renderRecentPayments(json.data.filter((item) => item.status === 'lunas').slice(0, 5));
            renderCharts(json.data, santriData);
            fetchReportData();
        } catch (error) {
            console.error(error);
        }
    };

    const filterPayments = () => {
        const searchTerm = searchPembayaran?.value.trim().toLowerCase() || '';

        const filtered = paymentData.filter((item) => {
            return searchTerm === '' ||
                item.name.toLowerCase().includes(searchTerm) ||
                (item.nis || '').toLowerCase().includes(searchTerm) ||
                (item.gender || '').toLowerCase().includes(searchTerm) ||
                (item.angkatan || '').toLowerCase().includes(searchTerm);
        });

        renderPayments(filtered);
    };

    const reportQuery = () => {
        const params = new URLSearchParams();
        if (reportAngkatan?.value) params.set('angkatan', reportAngkatan.value);
        if (reportMethod?.value) params.set('metode', reportMethod.value);
        if (reportStatus?.value) params.set('status', reportStatus.value);
        if (reportStartDate?.value) params.set('start_date', reportStartDate.value);
        if (reportEndDate?.value) params.set('end_date', reportEndDate.value);
        return params;
    };

    const fetchReportData = async () => {
        const url = new URL(apiRoutes.reports, window.location.origin);
        const params = reportQuery();
        params.forEach((value, key) => url.searchParams.set(key, value));

        try {
            const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal memuat laporan');
            const json = await response.json();
            reportData = json.data || [];
            renderReportTable(reportData);
        } catch (error) {
            console.error(error);
            renderReportTable([]);
        }
    };

    const renderReportTable = (rows) => {
        const container = document.getElementById('reportTableBody');
        if (!container) return;

        if (!rows.length) {
            container.innerHTML = '<tr><td colspan="10" class="text-center">Tidak ada data laporan sesuai filter.</td></tr>';
            return;
        }

        const statusClass = (label) => `status-badge ${String(label || '').toLowerCase().replace(/\s+/g, '-')}`;
        container.innerHTML = rows.map((row) => `
            <tr>
                <td>${row['Nama Santri'] || '-'}</td>
                <td>${row['NIS/NIP'] || '-'}</td>
                <td>${row.Angkatan || '-'}</td>
                <td>${row.Tagihan || '-'}</td>
                <td>${formatRupiah(row.Pokok || 0)}</td>
                <td>${formatRupiah(row.Denda || 0)}</td>
                <td>${formatRupiah(row.Total || 0)}</td>
                <td><span class="${statusClass(row.Status)}">${row.Status || '-'}</span></td>
                <td>${row.Metode || '-'}</td>
                <td>${row['Tanggal Bayar'] || '-'}</td>
            </tr>
        `).join('');
    };

    const renderPayments = (list) => {
        const container = document.getElementById('pembayaranTableBody');
        if (!container) return;

        if (list.length === 0) {
            container.innerHTML = '<tr><td colspan="8" class="text-center">Belum ada data pembayaran.</td></tr>';
            return;
        }

        const getStatusClass = (statusLabel, rawStatus) => {
            const status = (statusLabel || rawStatus || '').toLowerCase().replace(/\s+/g, '-');
            return `status-badge ${status}`;
        };

        container.innerHTML = list.map((item) => {
            const label = item.status_label || (item.status === 'lunas' ? 'Lunas' : item.status === 'terlambat' ? 'Menunggak' : 'Belum Bayar');
            return `
            <tr>
                <td><input type="checkbox"></td>
                <td>${item.name}</td>
                <td>${item.nis}</td>
                <td>${item.gender || '-'}</td>
                <td>${item.angkatan || '-'}</td>
                <td><span class="${getStatusClass(item.student_status_label || item.santri_status, item.santri_status)}">${item.student_status_label || '-'}</span></td>
                <td>${item.month}</td>
                <td><span class="${getStatusClass(label, item.status)}">${label}</span></td>
            </tr>
        `;
        }).join('');
    };

    const renderRecentPayments = (items) => {
        const container = document.getElementById('pembayaranTerbaruList');
        if (!container) return;

        if (items.length === 0) {
            container.innerHTML = '<tr><td colspan="4" class="text-center">Belum ada pembayaran terbaru.</td></tr>';
            return;
        }

        container.innerHTML = items.map((item) => `
            <tr>
                <td>${item.name}</td>
                <td>${item.month}</td>
                <td>${item.payment_date || '-'}</td>
                <td>Lunas</td>
            </tr>
        `).join('');
    };

    const closeAddSantriModal = () => {
        addSantriModal?.classList.remove('active');
        editingSantriId = null;
        santriModalTitle.textContent = 'Tambah Data Santri';
        saveSantriBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Santri';
        clearSantriErrors();
    };

    const fieldMap = {
        name: 'Name',
        nis: 'NIS',
        gender: 'Gender',
        santri_status: 'Status',
        email: 'Email',
        username: 'Username',
        password: 'Password',
        tgl_lahir: 'Birthday',
        alamat: 'Address',
    };

    const showSantriError = (field, message) => {
        const mapped = fieldMap[field] || field.charAt(0).toUpperCase() + field.slice(1);
        const element = document.getElementById(`errorSantri${mapped}`);
        if (element) element.textContent = message;
    };

    const clearSantriErrors = () => {
        ['Name', 'NIS', 'Gender', 'Status', 'Email', 'Username', 'Password', 'Birthday', 'Address'].forEach((field) => {
            const element = document.getElementById(`errorSantri${field}`);
            if (element) element.textContent = '';
        });
    };

    const openAddSantriModal = () => {
        editingSantriId = null;
        santriModalTitle.textContent = 'Tambah Data Santri';
        saveSantriBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Santri';
        resetSantriForm();
        clearSantriErrors();
        addSantriModal?.classList.add('active');
    };

    const openEditSantriModal = async (id) => {
        try {
            const response = await fetch(`${apiRoutes.santri}/${id}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Gagal memuat data santri');
            const result = await response.json();
            const santri = result.data;

            editingSantriId = santri.id;
            santriModalTitle.textContent = 'Ubah Data Santri';
            saveSantriBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Perubahan';
            document.getElementById('santriName').value = santri.name;
            document.getElementById('santriNIS').value = santri.nis;
            document.getElementById('santriGender').value = santri.gender || '';
            document.getElementById('santriStatus').value = santri.santri_status || 'aktif';
            document.getElementById('santriEmail').value = santri.email;
            document.getElementById('santriUsername').value = santri.username;
            document.getElementById('santriPassword').value = '';
            document.getElementById('santriBirthday').value = santri.tgl_lahir || '';
            document.getElementById('santriAddress').value = santri.alamat || '';
            clearSantriErrors();
            addSantriModal?.classList.add('active');
        } catch (error) {
            console.error(error);
            alert(error.message || 'Gagal memuat data santri.');
        }
    };

    const openInvoiceModal = (student, invoices) => {
        activeInvoiceSantriId = student.id;
        setText('invoiceModalStudent', `${student.name} - NIS ${student.nis}`);
        setText('invoiceModalRisk', `Risiko ${student.risk_label || 'Rendah'} (${student.risk_score ?? 0}) - ${student.risk_reason || 'Pembayaran tertib'}`);
        resetInvoiceForm();
        const invoiceBody = document.getElementById('invoiceModalBody');
        if (!invoiceBody) return;

        if (invoices.length === 0) {
            invoiceBody.innerHTML = '<tr><td colspan="7" class="text-center">Belum ada invoice untuk santri ini.</td></tr>';
        } else {
            invoiceBody.innerHTML = invoices.map((invoice) => `
                <tr>
                    <td>${invoice.name}</td>
                    <td>${formatRupiah(invoice.amount || invoice.total)}</td>
                    <td>${formatRupiah(invoice.penalty || 0)}</td>
                    <td>${formatRupiah(invoice.total)}</td>
                    <td>${invoice.status_label}</td>
                    <td>${invoice.due_date || '-'}</td>
                    <td>${invoice.updated_at || '-'}</td>
                </tr>
            `).join('');
        }

        santriInvoiceModal?.classList.add('active');
    };

    const closeInvoiceModalHandler = () => {
        santriInvoiceModal?.classList.remove('active');
        activeInvoiceSantriId = null;
    };

    const clearInvoiceErrors = () => {
        ['Name', 'DueDate', 'Amount', 'Penalty'].forEach((field) => {
            const element = document.getElementById(`errorInvoice${field}`);
            if (element) element.textContent = '';
        });
        setStatus('invoiceSaveStatus', '', 'success');
    };

    const showInvoiceError = (field, message) => {
        const fieldMap = {
            name: 'Name',
            due_date: 'DueDate',
            amount: 'Amount',
            penalty: 'Penalty',
        };
        const element = document.getElementById(`errorInvoice${fieldMap[field] || field}`);
        if (element) element.textContent = message;
    };

    const getSemesterInfo = (dateValue) => {
        if (!dateValue) return null;

        const date = new Date(`${dateValue}T00:00:00`);
        if (Number.isNaN(date.getTime())) return null;

        const month = date.getMonth() + 1;
        if (![1, 7].includes(month)) return null;

        return {
            name: `SPP Semester ${month === 7 ? 'Ganjil' : 'Genap'} ${date.getFullYear()}`,
            valid: true,
        };
    };

    const nextSemesterDueDate = () => {
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth() + 1;

        if (month < 7) {
            return `${year}-07-15`;
        }

        return `${year + 1}-01-15`;
    };

    const updateInvoiceSemesterName = () => {
        const dueInput = document.getElementById('invoiceDueDate');
        const nameInput = document.getElementById('invoiceName');
        const info = getSemesterInfo(dueInput?.value || '');

        if (nameInput) {
            nameInput.value = info?.name || '';
        }
    };

    const resetInvoiceForm = () => {
        if (!document.getElementById('invoiceDueDate')) return;
        document.getElementById('invoiceDueDate').value = nextSemesterDueDate();
        updateInvoiceSemesterName();
        if (document.getElementById('invoiceAmount')) document.getElementById('invoiceAmount').value = '2200000';
        if (document.getElementById('invoicePenalty')) document.getElementById('invoicePenalty').value = '0';
        if (document.getElementById('invoiceDescription')) document.getElementById('invoiceDescription').value = '';
        clearInvoiceErrors();
    };

    const reloadInvoiceModal = async () => {
        if (!activeInvoiceSantriId) return;
        const response = await fetch(`${apiRoutes.santri}/${activeInvoiceSantriId}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Gagal memuat ulang invoice');
        const result = await response.json();
        openInvoiceModal(result.data, result.data.invoices || []);
    };

    const saveInvoice = async () => {
        if (!activeInvoiceSantriId) return;

        const payload = {
            name: document.getElementById('invoiceName').value.trim(),
            due_date: document.getElementById('invoiceDueDate').value,
            amount: Number(document.getElementById('invoiceAmount').value || 0),
            penalty: Number(document.getElementById('invoicePenalty').value || 0),
            description: document.getElementById('invoiceDescription').value.trim(),
        };

        if (!getSemesterInfo(payload.due_date)) {
            clearInvoiceErrors();
            showInvoiceError('due_date', 'Pilih tanggal jatuh tempo pada bulan Januari atau Juli.');
            return;
        }

        try {
            clearInvoiceErrors();
            setStatus('invoiceSaveStatus', 'Menyimpan tagihan...', 'success');
            const response = await fetch(`${apiRoutes.santri}/${activeInvoiceSantriId}/invoices`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(payload),
            });
            const result = await response.json();

            if (!response.ok) {
                if (result.errors) {
                    Object.entries(result.errors).forEach(([field, messages]) => {
                        showInvoiceError(field, messages[0]);
                    });
                }
                throw new Error(result.message || 'Gagal menyimpan tagihan');
            }

            setStatus('invoiceSaveStatus', 'Tagihan berhasil dibuat.');
            await reloadInvoiceModal();
            await fetchStats();
            await fetchSantri(searchSantri?.value || '');
            await fetchPayments();
        } catch (error) {
            console.error(error);
            setStatus('invoiceSaveStatus', error.message || 'Terjadi kesalahan saat menyimpan tagihan', 'error');
        }
    };

    const resetSantriForm = () => {
        document.getElementById('santriName').value = '';
        document.getElementById('santriNIS').value = '';
        document.getElementById('santriGender').value = '';
        document.getElementById('santriStatus').value = 'aktif';
        document.getElementById('santriEmail').value = '';
        document.getElementById('santriUsername').value = '';
        document.getElementById('santriPassword').value = '';
        document.getElementById('santriBirthday').value = '';
        document.getElementById('santriAddress').value = '';
    };

    const saveSantri = async () => {
        clearSantriErrors();
        const payload = {
            name: document.getElementById('santriName').value.trim(),
            nis: document.getElementById('santriNIS').value.trim(),
            gender: document.getElementById('santriGender').value,
            santri_status: document.getElementById('santriStatus').value,
            email: document.getElementById('santriEmail').value.trim(),
            username: document.getElementById('santriUsername').value.trim(),
            password: document.getElementById('santriPassword').value,
            tgl_lahir: document.getElementById('santriBirthday').value,
            alamat: document.getElementById('santriAddress').value.trim(),
        };

        const url = editingSantriId ? `${apiRoutes.santri}/${editingSantriId}` : apiRoutes.santri;
        const method = editingSantriId ? 'PUT' : 'POST';

        try {
            saveSantriBtn.disabled = true;
            saveSantriBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            const response = await fetch(url, {
                method,
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();
            if (!response.ok) {
                if (result.errors) {
                    Object.entries(result.errors).forEach(([field, messages]) => {
                        showSantriError(field, messages[0]);
                    });
                }
                throw new Error(result.message || 'Gagal menyimpan santri');
            }

            closeAddSantriModal();
            resetSantriForm();
            await fetchStats();
            await fetchSantri(searchSantri?.value || '');
            await fetchPayments();
            alert(editingSantriId ? 'Santri berhasil diubah.' : 'Santri berhasil ditambahkan.');
        } catch (error) {
            console.error(error);
            alert(error.message || 'Terjadi kesalahan saat menyimpan santri');
        } finally {
            saveSantriBtn.disabled = false;
            saveSantriBtn.innerHTML = editingSantriId
                ? '<i class="fas fa-save"></i> Simpan Perubahan'
                : '<i class="fas fa-save"></i> Simpan Santri';
        }
    };

    const performSantriAction = async (action, id) => {
        if (action === 'view' || action === 'invoice') {
            try {
                const response = await fetch(`${apiRoutes.santri}/${id}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Gagal memuat data santri');
                const result = await response.json();
                openInvoiceModal(result.data, result.data.invoices || []);
            } catch (error) {
                console.error(error);
                alert(error.message || 'Gagal membuka detail santri');
            }
            return;
        }

        if (action === 'edit') {
            openEditSantriModal(id);
            return;
        }

        if (action === 'delete') {
            if (!confirm('Hapus santri ini beserta semua invoice terkait?')) {
                return;
            }

            try {
                const response = await fetch(`${apiRoutes.santri}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Gagal menghapus santri');
                fetchSantri(searchSantri?.value || '');
                alert('Santri berhasil dihapus.');
            } catch (error) {
                console.error(error);
                alert(error.message || 'Terjadi kesalahan saat menghapus santri');
            }
        }
    };

    addSantriBtn?.addEventListener('click', () => {
        openAddSantriModal();
    });
    addSantriClose?.addEventListener('click', closeAddSantriModal);
    addSantriCancel?.addEventListener('click', closeAddSantriModal);
    saveSantriBtn?.addEventListener('click', saveSantri);

    searchSantri?.addEventListener('input', () => {
        filterSantri();
    });

    filterStatus?.addEventListener('change', () => {
        filterSantri();
    });

    searchPembayaran?.addEventListener('input', () => {
        filterPayments();
    });

    document.getElementById('santriTableBody')?.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        const action = button.dataset.action;
        const id = button.dataset.id;
        performSantriAction(action, id);
    });

    closeInvoiceModal?.addEventListener('click', closeInvoiceModalHandler);
    closeInvoiceModalBtn?.addEventListener('click', closeInvoiceModalHandler);
    saveInvoiceBtn?.addEventListener('click', saveInvoice);
    document.getElementById('invoiceDueDate')?.addEventListener('change', updateInvoiceSemesterName);

    saveInstitutionBtn?.addEventListener('click', () => {
        const fields = ['institutionName', 'institutionAddress', 'institutionEmail', 'institutionPhone'];
        const payload = {};

        fields.forEach((id) => {
            payload[id] = document.getElementById(id)?.value.trim() || '';
        });

        if (!payload.institutionName || !payload.institutionAddress || !payload.institutionEmail) {
            setStatus('institutionSaveStatus', 'Nama, alamat, dan email wajib diisi.', 'error');
            return;
        }

        localStorage.setItem('syajagadInstitution', JSON.stringify(payload));
        setStatus('institutionSaveStatus', 'Profil instansi tersimpan di perangkat ini.');
    });

    changePasswordBtn?.addEventListener('click', async () => {
        const oldPassword = document.getElementById('oldPassword')?.value || '';
        const newPassword = document.getElementById('newPassword')?.value || '';
        const confirmPassword = document.getElementById('confirmNewPassword')?.value || '';

        if (!oldPassword || !newPassword || !confirmPassword) {
            setStatus('passwordChangeStatus', 'Lengkapi semua field kata sandi.', 'error');
            return;
        }

        if (newPassword.length < 8) {
            setStatus('passwordChangeStatus', 'Kata sandi baru minimal 8 karakter.', 'error');
            return;
        }

        if (newPassword !== confirmPassword) {
            setStatus('passwordChangeStatus', 'Konfirmasi kata sandi belum sama.', 'error');
            return;
        }

        try {
            const response = await fetch('/admin/password', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    old_password: oldPassword,
                    new_password: newPassword,
                    new_password_confirmation: confirmPassword,
                }),
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Gagal mengubah kata sandi.');
            }

            document.getElementById('oldPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmNewPassword').value = '';
            setStatus('passwordChangeStatus', result.message || 'Kata sandi berhasil diubah.');
        } catch (error) {
            setStatus('passwordChangeStatus', error.message || 'Gagal mengubah kata sandi.', 'error');
        }
    });

    manageRolesBtn?.addEventListener('click', () => {
        document.getElementById('adminPermissionList')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        fetchPermissions();
    });

    document.getElementById('adminPermissionList')?.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action="save-permission"]');
        if (!button) return;
        savePermissions(button.dataset.id);
    });

    document.querySelectorAll('.btn-export[data-format]').forEach((button) => {
        button.addEventListener('click', () => {
            const format = button.dataset.format;
            const params = reportQuery().toString();
            window.open(`/admin/reports/export/${format}${params ? `?${params}` : ''}`, '_blank');
        });
    });

    [reportAngkatan, reportMethod, reportStatus, reportStartDate, reportEndDate].forEach((element) => {
        element?.addEventListener('change', fetchReportData);
    });

    document.querySelectorAll('[data-setting-action]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.settingAction === 'penalty') {
                alert(`Total denda berjalan: ${formatRupiah(statsData.totalPenalty || 0)}. Nilai ini dihitung dari semua invoice.`);
                return;
            }

            alert(`SPP semesteran aktif: ${formatRupiah(statsData.semesterFee || 2200000)}.`);
        });
    });

    bankMandiri?.addEventListener('change', updateBankStatus);
    bankBca?.addEventListener('change', updateBankStatus);

    const openNotificationModal = () => {
        const container = document.getElementById('adminNotificationList');
        if (!container) return;

        const overdue = Number(statsData.totalTunggak || 0);
        const pending = Number(statsData.pendingVerification || 0);
        const cuti = Number(statsData.totalCuti || 0);
        const outstanding = Number(statsData.outstanding || 0);
        const pendingAmount = Number(statsData.pendingVerificationAmount || 0);

        const items = [
            {
                icon: 'fa-exclamation-triangle',
                title: `${overdue} santri menunggak`,
                text: `Total tunggakan berjalan ${formatRupiah(outstanding)}.`,
            },
            {
                icon: 'fa-clock',
                title: `${pending} transaksi menunggu verifikasi`,
                text: `Nominal menunggu verifikasi ${formatRupiah(pendingAmount)}.`,
            },
            {
                icon: 'fa-user-clock',
                title: `${cuti} santri berstatus cuti`,
                text: 'Santri cuti tetap tampil di data, tetapi statusnya dipisahkan.',
            },
        ];

        container.innerHTML = items.map((item) => `
            <div class="notification-item">
                <div class="notification-icon"><i class="fas ${item.icon}"></i></div>
                <div>
                    <strong>${item.title}</strong>
                    <span>${item.text}</span>
                </div>
            </div>
        `).join('');

        adminNotificationModal?.classList.add('active');
    };

    const closeNotificationModal = () => {
        adminNotificationModal?.classList.remove('active');
    };

    const openSantriEmail = () => {
        const recipients = Array.from(new Set(
            santriData
                .filter((item) => item.email && ['Menunggak', 'Belum Bayar', 'Cicilan'].includes(item.payment_status))
                .map((item) => item.email)
        ));
        const fallback = document.getElementById('institutionEmail')?.value || window.userData?.email || '';
        const subject = encodeURIComponent('Informasi Pembayaran SPP Semesteran');
        const body = encodeURIComponent('Assalamu\'alaikum, kami informasikan terkait status pembayaran SPP semesteran. Silakan cek dashboard SyaJagad atau hubungi admin untuk detail.');

        if (!recipients.length) {
            window.location.href = `mailto:${fallback}?subject=${subject}&body=${body}`;
            return;
        }

        window.location.href = `mailto:${fallback}?bcc=${encodeURIComponent(recipients.join(','))}&subject=${subject}&body=${body}`;
    };

    savePeriodBtn?.addEventListener('click', () => {
        const periodStart = document.getElementById('periodStart')?.value || '';
        const periodEnd = document.getElementById('periodEnd')?.value || '';

        if (!periodStart || !periodEnd || periodStart > periodEnd) {
            setStatus('periodSaveStatus', 'Periode pembayaran tidak valid.', 'error');
            return;
        }

        localStorage.setItem('syajagadPaymentPeriod', JSON.stringify({ periodStart, periodEnd }));
        setStatus('periodSaveStatus', 'Periode pembayaran tersimpan di perangkat ini.');
    });

    notifBtn?.addEventListener('click', () => {
        openNotificationModal();
    });

    messageBtn?.addEventListener('click', () => {
        openSantriEmail();
    });

    adminNotificationClose?.addEventListener('click', closeNotificationModal);
    adminNotificationDismiss?.addEventListener('click', closeNotificationModal);
    adminNotificationEmail?.addEventListener('click', openSantriEmail);

    updateTime();
    loadSettingValues();
    updateBankStatus();
    fetchStats();
    fetchSantri();
    fetchPayments();
});
