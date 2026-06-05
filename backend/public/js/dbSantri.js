// dbSantri.js
class SyaJagadDashboard {
    constructor() {
        const rawUser = window.userData || {};
        const rawPayment = window.paymentData || {};

        this.userData = {
            name: rawUser.name || 'Santri Baru',
            nis: rawUser.nis || '-',
            email: rawUser.email || '-',
            username: rawUser.username || '-',
            birthdate: rawUser.tgl_lahir || '-',
            address: rawUser.alamat || '-',
            joinDate: rawUser.joinDate || '-',
            totalPaid: 0,
            totalTx: 0,
            lastPayment: '-',
            riskLevel: 10,
            denda: 0,
        };

        this.paymentData = {
            invoices: rawPayment.invoices || [],
            history: rawPayment.history || [],
            message: rawPayment.message || 'Belum ada tagihan saat ini.',
        };

        this.activeInvoices = this.paymentData.invoices.map(invoice => this.normalizeInvoice(invoice));
        this.riwayatData = this.paymentData.history.map(invoice => this.normalizeInvoice(invoice));
        this.tagihanData = [...this.activeInvoices, ...this.riwayatData]
            .sort((a, b) => new Date(b.due_date || 0) - new Date(a.due_date || 0));
        this.selectedInvoice = null;
        this.selectedPaymentMethod = 'qris';
        this.notifications = [];
        this.currentTagihanFilter = 'semua';
        this.currentTagihanSearch = '';
        this.chatStorageKey = `syajagad-chat-${this.userData.nis || this.userData.username || 'santri'}`;
        this.chatbotBusy = false;

        this.init();
    }

    init() {
        this.updateUI();
        this.bindEvents();
        this.loadAIPrediction();
        this.loadNotifications();
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
        document.getElementById('profilTglLahir').textContent = this.formatDisplayDate(this.userData.birthdate);
        document.getElementById('profilAlamat').textContent = this.userData.address;

        const totalPaid = this.riwayatData.reduce((sum, tx) => sum + tx.amount, 0);
        const totalTx = this.riwayatData.length;
        const lastPayment = this.riwayatData.length ? this.getPaidDate(this.riwayatData[0]) : '-';
        const totalDenda = this.activeInvoices.reduce((sum, tagihan) => sum + (tagihan.penalty || 0), 0);

        document.getElementById('totalPaid').textContent = this.formatRupiah(totalPaid);
        document.getElementById('totalTx').textContent = `${totalTx} transaksi berhasil`;
        document.getElementById('lastPayment').textContent = lastPayment;

        if (totalDenda > 0) {
            document.getElementById('totalDenda').textContent = this.formatRupiah(totalDenda);
            document.getElementById('totalDendaCard').style.display = 'block';
            const overdueCount = this.activeInvoices.filter((tagihan) => tagihan.status === 'terlambat').length;
            const dendaSub = document.getElementById('totalDendaSub');
            if (dendaSub) {
                dendaSub.textContent = `${overdueCount} tagihan terkena denda bulanan`;
            }
        } else {
            document.getElementById('totalDendaCard').style.display = 'none';
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
        document.getElementById('notifBtn')?.addEventListener('click', () => this.showNotifications());

        document.getElementById('bayarSekarangBtn').addEventListener('click', () => {
            this.switchPage('tagihan');
        });

        // Payment method selection
        document.querySelectorAll('.pm-option').forEach(option => {
            option.addEventListener('click', () => {
                document.querySelectorAll('.pm-option').forEach(item => item.classList.remove('active'));
                option.classList.add('active');
                this.selectedPaymentMethod = option.dataset.method;
                this.updatePaymentMethodDetails();
            });
        });

        // Filter tagihan
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', (e) => this.filterTagihan(e.target.dataset.filter));
        });

        document.getElementById('searchTagihan')?.addEventListener('input', (e) => {
            this.currentTagihanSearch = e.target.value.trim().toLowerCase();
            this.renderTagihanList();
        });

        document.getElementById('tagihanList')?.addEventListener('click', (e) => {
            const payButton = e.target.closest('.tc-pay-btn[data-invoice-id]');
            const detailButton = e.target.closest('.tc-detail-btn[data-invoice-id]');

            if (detailButton) {
                this.openPaymentDetail(Number(detailButton.dataset.invoiceId));
                return;
            }

            if (!payButton) return;

            const invoiceId = Number(payButton.dataset.invoiceId);
            const invoice = this.activeInvoices.find(item => Number(item.id) === invoiceId);
            this.openPaymentModal(invoice);
        });

        // Quick chatbot
        document.getElementById('chatbotToggle')?.addEventListener('click', () => this.toggleChatbot());
        document.getElementById('chatbotClose')?.addEventListener('click', () => this.toggleChatbot(false));
        document.getElementById('chatbotClear')?.addEventListener('click', () => this.clearChatbotMessages());
        document.getElementById('chatbotOptions')?.addEventListener('click', (e) => {
            const option = e.target.closest('button[data-intent]');
            if (!option) return;

            this.askChatbot(option.dataset.intent, option.textContent.trim());
        });
        document.getElementById('chatbotForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            const input = document.getElementById('chatbotInput');
            const message = input?.value.trim() || '';
            if (!message) return;
            if (input) input.value = '';
            this.askChatbot(null, message);
        });

        // Other modal handlers
        this.bindModalHandlers();
        this.bindProfileHandlers();
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
        document.getElementById('paymentDetailClose')?.addEventListener('click', () => this.closePaymentDetail());
        document.getElementById('paymentDetailOverlay')?.addEventListener('click', () => this.closePaymentDetail());

        // Logout confirm
        document.getElementById('confirmLogout').addEventListener('click', () => {
            document.getElementById('logoutForm')?.submit();
        });

        // Payment
        document.getElementById('modalPay').addEventListener('click', () => {
            this.submitPayment();
        });

        // Success modal close
        document.getElementById('successClose').addEventListener('click', () => {
            document.getElementById('successModal').classList.remove('active');
            this.switchPage('dashboard');
        });

        // Show password toggle
        document.getElementById('showPassword').addEventListener('click', (e) => {
            const button = e.target.closest('button');
            const field = button.closest('.password-field').querySelector('span');
            const icon = button.querySelector('i');
            if (field.textContent === '************') {
                field.textContent = 'Password tidak dapat ditampilkan';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.textContent = '************';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    bindProfileHandlers() {
        document.getElementById('editProfileBtn')?.addEventListener('click', () => this.openEditProfileModal());
        document.getElementById('changePasswordBtn')?.addEventListener('click', () => this.openChangePasswordModal());
        document.getElementById('editProfileForm')?.addEventListener('submit', (e) => this.submitProfileUpdate(e));
        document.getElementById('changePasswordForm')?.addEventListener('submit', (e) => this.submitPasswordChange(e));
    }

    openEditProfileModal() {
        document.getElementById('editProfileName').value = this.userData.name === '-' ? '' : this.userData.name;
        document.getElementById('editProfileEmail').value = this.userData.email === '-' ? '' : this.userData.email;
        document.getElementById('editProfileUsername').value = this.userData.username === '-' ? '' : this.userData.username;
        document.getElementById('editProfileAlamat').value = this.userData.address === '-' ? '' : this.userData.address;
        this.setFormFeedback('editProfileFeedback', '');
        document.getElementById('editProfileModal').classList.add('active');
    }

    openChangePasswordModal() {
        document.getElementById('changePasswordForm')?.reset();
        this.setFormFeedback('changePasswordFeedback', '');
        document.getElementById('changePasswordModal').classList.add('active');
    }

    async submitProfileUpdate(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const payload = Object.fromEntries(new FormData(form).entries());
        this.setFormFeedback('editProfileFeedback', 'Menyimpan...');

        try {
            const result = await this.sendJson('/santri/profile', 'PUT', payload);
            const user = result.user || {};

            this.userData = {
                ...this.userData,
                name: user.name || this.userData.name,
                nis: user.nis || this.userData.nis,
                email: user.email || this.userData.email,
                username: user.username || this.userData.username,
                birthdate: user.tgl_lahir || this.userData.birthdate,
                address: user.alamat || this.userData.address,
            };
            this.updateUI();
            document.getElementById('editProfileModal').classList.remove('active');
            alert(result.message || 'Profil berhasil diperbarui.');
        } catch (error) {
            this.setFormFeedback('editProfileFeedback', error.message || 'Profil belum bisa diperbarui.', true);
        }
    }

    async submitPasswordChange(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const payload = Object.fromEntries(new FormData(form).entries());
        this.setFormFeedback('changePasswordFeedback', 'Menyimpan...');

        try {
            const result = await this.sendJson('/santri/password', 'POST', payload);
            form.reset();
            document.getElementById('changePasswordModal').classList.remove('active');
            alert(result.message || 'Password berhasil diganti.');
        } catch (error) {
            this.setFormFeedback('changePasswordFeedback', error.message || 'Password belum bisa diganti.', true);
        }
    }

    async sendJson(url, method, payload) {
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload),
        });
        const result = await response.json();

        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : null;
            throw new Error(firstError || result.message || 'Permintaan gagal diproses.');
        }

        return result;
    }

    setFormFeedback(id, message, isError = false) {
        const feedback = document.getElementById(id);
        if (!feedback) return;

        feedback.textContent = message;
        feedback.classList.toggle('error', isError);
    }

    loadAIPrediction() {
        const aiRiskLabel = document.getElementById('aiRiskLabel');
        const aiRiskValue = document.getElementById('aiRiskValue');
        const riskFill = document.getElementById('riskFill');
        const aiAdviceText = document.getElementById('aiAdviceText');
        const aiUpdated = document.getElementById('aiUpdated');
        const aiSourceBadge = document.getElementById('aiSourceBadge');
        const aiNextAction = document.getElementById('aiNextAction');

        if (!aiRiskLabel || !aiRiskValue || !riskFill || !aiAdviceText || !aiUpdated || !aiSourceBadge || !aiNextAction) {
            return;
        }

        fetch('/ai/payment-insight', { headers: { Accept: 'application/json' } })
            .then(response => response.ok ? response.json() : Promise.reject(new Error('Gagal memuat insight AI')))
            .then(result => {
                const data = result.data || {};
                const score = Math.min(Math.max(Number(data.risk_score || 0), 0), 100);

                aiRiskLabel.textContent = data.risk_label || 'Rendah';
                aiRiskValue.textContent = `${score}%`;
                riskFill.style.width = `${score}%`;
                riskFill.classList.toggle('high', score >= 75);
                riskFill.classList.toggle('medium', score >= 40 && score < 75);
                riskFill.classList.toggle('low', score < 40);
                aiAdviceText.textContent = data.recommendation || data.reason || 'Belum ada rekomendasi.';
                aiNextAction.textContent = data.next_action || 'Pantau tagihan aktif';
                aiSourceBadge.textContent = data.source === 'openai' ? `OpenAI ${data.model || ''}`.trim() : 'Analisis Lokal';
                aiUpdated.textContent = new Date().toLocaleTimeString('id-ID');
            })
            .catch(() => {
                const unpaid = this.activeInvoices.filter(t => t.status !== 'lunas').length;
                const overdue = this.activeInvoices.filter(t => t.status === 'terlambat').length;
                const score = Math.min(100, overdue * 40 + unpaid * 20);

                aiRiskLabel.textContent = score >= 75 ? 'Tinggi' : score >= 40 ? 'Sedang' : 'Rendah';
                aiRiskValue.textContent = `${score}%`;
                riskFill.style.width = `${score}%`;
                aiAdviceText.textContent = unpaid ? 'Lunasi tagihan aktif agar risiko keterlambatan turun.' : 'Semua tagihan aman.';
                aiNextAction.textContent = unpaid ? 'Buka menu Tagihan' : 'Pertahankan pembayaran tepat waktu';
                aiSourceBadge.textContent = 'Analisis Lokal';
                aiUpdated.textContent = new Date().toLocaleTimeString('id-ID');
            });
    }

    renderDynamicContent() {
        this.renderTagihanAktif();
        this.renderTagihanList();
        this.renderRiwayat();
    }

    renderTagihanAktif() {
        const unpaid = this.activeInvoices.filter(t => t.status !== 'lunas');
        const container = document.getElementById('tagihanAktifList');
        
        if (unpaid.length === 0) {
            container.innerHTML = '<div class="no-data"><i class="fas fa-check-circle"></i><p>Semua tagihan sudah lunas!</p></div>';
            document.getElementById('navTagihanBadge').style.display = 'none';
            return;
        }
        document.getElementById('navTagihanBadge').textContent = unpaid.length;
        document.getElementById('navTagihanBadge').style.display = 'block';

        container.innerHTML = unpaid.map(tagihan => `
            <div class="tagihan-item ${tagihan.status === 'terlambat' ? 'late' : 'unpaid'}">
                <div class="ti-left">
                    <div class="ti-icon ${tagihan.status === 'terlambat' ? 'late' : ''}">
                        <i class="fas ${tagihan.status === 'terlambat' ? 'fa-exclamation-triangle' : 'fa-file-alt'}"></i>
                    </div>
                    <div class="ti-info">
                        <span class="ti-name">${tagihan.name}</span>
                        <span class="ti-date">Jatuh tempo: ${this.getDueDate(tagihan)}</span>
                    </div>
                </div>
                <div class="ti-right">
                    <span class="ti-amount">${this.formatRupiah(tagihan.total || tagihan.amount)}</span>
                    <span class="ti-status ${tagihan.status}">${this.getStatusLabel(tagihan.status)}</span>
                </div>
            </div>
        `).join('');
    }

    renderTagihanList() {
        const container = document.getElementById('tagihanList');
        const filtered = this.getFilteredTagihan();

        if (!filtered.length) {
            container.innerHTML = '<div class="no-data"><i class="fas fa-search"></i><p>Tagihan tidak ditemukan.</p></div>';
            return;
        }

        container.innerHTML = filtered.map(tagihan => this.createTagihanCard(tagihan)).join('');
    }

    createTagihanCard(tagihan) {
        const isUnpaid = tagihan.status !== 'lunas';
        const statusClass = tagihan.status;
        const iconClass = tagihan.status === 'lunas' ? 'lunas' : tagihan.status;
        const icon = tagihan.status === 'lunas' ? 'fa-check-circle' :
                    tagihan.status === 'terlambat' ? 'fa-exclamation-triangle' : 'fa-clock';
        
        let headerContent = `
            <div class="tc-icon ${iconClass}">
                <i class="fas ${icon}"></i>
            </div>
            <div>
                <h4>${tagihan.name}</h4>
        `;

        if (isUnpaid) {
            headerContent += `<span>Jatuh tempo: ${this.getDueDate(tagihan)}</span>`;
        } else {
            headerContent += `<span>${this.getPaidDate(tagihan) || 'Dibayar'} ${tagihan.status === 'terlambat' ? '(Menunggak)' : ''}</span>`;
        }
        headerContent += `</div>`;

        let badge = `<span class="tc-badge ${statusClass}">${this.getStatusLabel(tagihan.status)}</span>`;

        let footer = '';
        if (isUnpaid) {
            footer = `
                <div class="tc-payment-methods">
                    <span class="pm-tag">QRIS</span>
                    <span class="pm-tag">BCA VA</span>
                    <span class="pm-tag">Mandiri VA</span>
                </div>
                <button class="tc-pay-btn" data-invoice-id="${tagihan.id}">
                    <i class="fas fa-credit-card"></i> Bayar Sekarang
                </button>
            `;
        } else {
            footer = `
                <div class="tc-trx-id">
                    <i class="fas fa-receipt"></i>
                    <span>TRX-${tagihan.id.toString().padStart(4, '0')}</span>
                </div>
                <button class="tc-detail-btn" data-invoice-id="${tagihan.id}">
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
                        <span class="tc-value">${this.formatRupiah(tagihan.total || tagihan.amount)}</span>
                    </div>
                    <div class="tc-meta">
                        <div class="tc-meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>${this.getBillingPeriod(tagihan)}</span>
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
        const riwayat = this.riwayatData;
        const container = document.getElementById('riwayatList');
        document.getElementById('riwayatCount').textContent = `${riwayat.length} Transaksi`;

        if (!riwayat.length) {
            container.innerHTML = '<div class="no-data"><i class="fas fa-clock"></i><p>Belum ada transaksi.</p></div>';
            return;
        }

        container.innerHTML = riwayat.map(tx => `
            <div class="tx-item">
                <div class="tx-icon ${tx.status}">
                    <i class="fas ${tx.status === 'terlambat' ? 'fa-exclamation' : 'fa-check'}"></i>
                </div>
                <div class="tx-info">
                    <span class="tx-name">${tx.name}</span>
                    <span class="tx-meta">${this.getPaidDate(tx) || '-'} - ${tx.method || 'QRIS'}</span>
                </div>
                <div class="tx-right">
                    <span class="tx-amount">${this.formatRupiah(tx.amount)}</span>
                    <span class="tx-status ${tx.status}">${tx.status === 'terlambat' ? 'Menunggak' : 'Lunas'}</span>
                </div>
            </div>
        `).join('');
    }

    openPaymentModal(tagihan) {
        if (!tagihan) {
            return;
        }

        this.selectedInvoice = tagihan;
        this.selectedPaymentMethod = 'qris';
        document.querySelectorAll('.pm-option').forEach(item => {
            item.classList.toggle('active', item.dataset.method === 'qris');
        });
        this.updatePaymentMethodDetails();

        document.getElementById('modalTagihanName').textContent = tagihan.name;
        document.getElementById('modalTagihanAmount').textContent = this.formatRupiah(tagihan.total);
        document.getElementById('modalTagihanBase').textContent = this.formatRupiah(tagihan.amount);
        document.getElementById('modalTagihanPenalty').textContent = this.formatRupiah(tagihan.penalty || 0);
        document.getElementById('modalTagihanTotal').textContent = this.formatRupiah(tagihan.total);
        document.getElementById('paymentModal').classList.add('active');
    }

    async openPaymentDetail(invoiceId) {
        const panel = document.getElementById('paymentDetailPanel');
        const overlay = document.getElementById('paymentDetailOverlay');
        const body = document.getElementById('paymentDetailBody');
        if (!panel || !overlay || !body) return;

        panel.classList.add('active');
        overlay.classList.add('active');
        body.innerHTML = '<div class="detail-loading">Memuat detail pembayaran...</div>';

        try {
            const response = await fetch(`/payment/detail/${invoiceId}`, { headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Detail pembayaran tidak ditemukan.');

            this.renderPaymentDetail(result.data);
        } catch (error) {
            body.innerHTML = `<div class="detail-error">${error.message || 'Gagal memuat detail pembayaran.'}</div>`;
        }
    }

    closePaymentDetail() {
        document.getElementById('paymentDetailPanel')?.classList.remove('active');
        document.getElementById('paymentDetailOverlay')?.classList.remove('active');
    }

    renderPaymentDetail(detail) {
        const body = document.getElementById('paymentDetailBody');
        if (!body) return;

        const transactionNumber = detail.order_id || detail.transaction_id || `TRX-${String(detail.id).padStart(4, '0')}`;
        const proofUrl = detail.proof?.is_image && detail.proof?.url ? detail.proof.url : '';
        const proofFilename = detail.proof?.filename || `bukti-pembayaran-${detail.id}.jpg`;
        const pngFilename = `bukti-pembayaran-${String(transactionNumber).replace(/[^a-z0-9-]/gi, '-')}.png`;
        const proofBlock = proofUrl
            ? `
                <div class="proof-preview">
                    <img src="${proofUrl}" alt="Bukti pembayaran ${detail.name || ''}" loading="lazy">
                </div>
                <div class="detail-actions">
                    <a class="modal-cancel" href="${proofUrl}" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Lihat Bukti</a>
                    <a class="modal-pay" href="${proofUrl}" download="${proofFilename}"><i class="fas fa-download"></i> Download Gambar</a>
                    <button class="modal-pay" type="button" id="downloadPaymentDetail" data-filename="${pngFilename}"><i class="fas fa-download"></i> Download PNG</button>
                </div>
            `
            : `
                <div class="proof-empty">
                    <i class="fas fa-image"></i>
                    <span>Bukti pembayaran belum tersedia.</span>
                </div>
                <div class="detail-actions">
                    <button class="modal-pay" type="button" id="downloadPaymentDetail" data-filename="${pngFilename}"><i class="fas fa-download"></i> Download PNG</button>
                </div>
            `;

        body.innerHTML = `
            <div class="payment-receipt" id="paymentReceipt">
                <div class="receipt-header">
                    <div>
                        <span>Bukti Pembayaran</span>
                        <strong>SyaJagad</strong>
                    </div>
                    <span class="receipt-status ${detail.status}">${detail.status_label || '-'}</span>
                </div>
                <div class="receipt-title">
                    <strong>${detail.name || '-'}</strong>
                    <span>${detail.description || 'Pembayaran SPP Pesantren'}</span>
                </div>
                <div class="receipt-grid">
                    <div><span>No. Transaksi</span><strong>${transactionNumber}</strong></div>
                    <div><span>Tanggal Bayar</span><strong>${detail.paid_date || detail.updated_at || '-'}</strong></div>
                    <div><span>Nama Santri</span><strong>${detail.student?.name || '-'}</strong></div>
                    <div><span>NIS</span><strong>${detail.student?.nis || '-'}</strong></div>
                    <div><span>Metode</span><strong>${detail.method || '-'}</strong></div>
                    <div><span>Jatuh Tempo</span><strong>${detail.due_date || '-'}</strong></div>
                </div>
                <div class="receipt-lines">
                    <div><span>Pokok</span><strong>${this.formatRupiah(detail.amount || 0)}</strong></div>
                    <div><span>Denda</span><strong>${this.formatRupiah(detail.penalty || 0)}</strong></div>
                    <div><span>Sudah Dibayar</span><strong>${this.formatRupiah(detail.paid_amount || 0)}</strong></div>
                    <div><span>Outstanding</span><strong>${this.formatRupiah(detail.outstanding || 0)}</strong></div>
                    <div class="receipt-total"><span>Total Tagihan</span><strong>${this.formatRupiah(detail.total || 0)}</strong></div>
                </div>
                <div class="receipt-meta">
                    <span>Order ID: ${detail.order_id || '-'}</span>
                    <span>Transaction ID: ${detail.transaction_id || '-'}</span>
                </div>
                <div class="receipt-footer">SyaJagad - Sistem Pembayaran SPP Pesantren</div>
            </div>
            <div class="detail-section">
                <h4>Bukti Pembayaran</h4>
                ${proofBlock}
            </div>
        `;

        document.getElementById('downloadPaymentDetail')?.addEventListener('click', (event) => {
            this.downloadPaymentDetailPng(event.currentTarget.dataset.filename || pngFilename);
        });
    }

    async downloadPaymentDetailPng(filename) {
        const receipt = document.getElementById('paymentReceipt');
        if (!receipt) return;

        if (typeof html2canvas === 'undefined') {
            alert('Fitur download PNG belum siap. Muat ulang halaman lalu coba lagi.');
            return;
        }

        const canvas = await html2canvas(receipt, {
            backgroundColor: '#ffffff',
            scale: Math.min(window.devicePixelRatio || 1, 2),
            useCORS: true,
        });
        const link = document.createElement('a');
        link.download = filename;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }

    updatePaymentMethodDetails() {
        const qrisHelp = document.getElementById('paymentHelpQris');
        const vaHelp = document.getElementById('paymentHelpVA');
        const bankName = document.getElementById('vaBankName');

        if (!qrisHelp || !vaHelp || !bankName) {
            return;
        }

        if (this.selectedPaymentMethod === 'qris') {
            qrisHelp.style.display = 'block';
            vaHelp.style.display = 'none';
        } else {
            qrisHelp.style.display = 'none';
            vaHelp.style.display = 'block';
            if (this.selectedPaymentMethod === 'bca') {
                bankName.textContent = 'BCA';
            } else if (this.selectedPaymentMethod === 'mandiri') {
                bankName.textContent = 'Mandiri';
            }
        }
    }

    loadNotifications() {
        fetch('/notifications', { headers: { Accept: 'application/json' } })
            .then(response => response.ok ? response.json() : Promise.reject(new Error('Gagal memuat notifikasi')))
            .then(result => {
                this.notifications = result.data || [];
                const badge = document.getElementById('notifBadge');
                if (badge) {
                    badge.textContent = result.unread || 0;
                    badge.style.display = result.unread > 0 ? 'flex' : 'none';
                }
            })
            .catch(error => console.error(error));
    }

    showNotifications() {
        if (!this.notifications.length) {
            alert('Belum ada notifikasi.');
            return;
        }

        const message = this.notifications
            .slice(0, 8)
            .map(item => `${item.read ? 'Sudah dibaca' : 'Baru'} - ${item.title}\n${item.message}`)
            .join('\n\n');

        alert(message);

        fetch('/notifications/read-all', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        }).then(() => this.loadNotifications());
    }

    toggleChatbot(forceState = null) {
        const shell = document.getElementById('chatbotShell');
        if (!shell) return;

        const shouldOpen = forceState === null ? !shell.classList.contains('active') : forceState;
        shell.classList.toggle('active', shouldOpen);

        if (shouldOpen) {
            this.restoreChatbotMessages();
            setTimeout(() => {
                const body = document.getElementById('chatbotMessages');
                if (body) body.scrollTop = body.scrollHeight;
            }, 60);
        }
    }

    appendChatMessage(role, message, extraClass = '', persist = true) {
        const body = document.getElementById('chatbotMessages');
        if (!body) return null;

        const bubble = document.createElement('div');
        bubble.className = `chatbot-message ${role} ${extraClass}`.trim();

        const text = document.createElement('span');
        text.textContent = message;
        bubble.appendChild(text);

        const time = document.createElement('small');
        time.className = 'chatbot-time';
        time.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        bubble.appendChild(time);

        body.appendChild(bubble);
        body.scrollTop = body.scrollHeight;
        if (persist && !extraClass.includes('loading')) {
            this.persistChatbotMessage(role, message);
        }

        return bubble;
    }

    clearChatbotMessages() {
        const body = document.getElementById('chatbotMessages');
        if (!body) return;

        body.innerHTML = '';
        localStorage.removeItem(this.chatStorageKey);
        this.appendChatMessage('bot', 'Riwayat pesan sudah dibersihkan. Pilih menu cepat untuk mulai lagi.');
    }

    async askChatbot(intent, label) {
        if (this.chatbotBusy) return;
        this.chatbotBusy = true;
        this.toggleChatbot(true);
        this.appendChatMessage('user', label);
        const loadingBubble = this.appendChatMessage('bot', 'Sebentar, saya cek data tagihan kamu...', 'loading');
        document.getElementById('chatbotOptions')?.classList.add('is-loading');

        try {
            const response = await fetch('/chatbot/quick', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(intent ? { intent } : { message: label }),
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Asisten belum bisa menjawab pilihan ini.');
            }

            loadingBubble?.remove();
            this.appendChatMessage('bot', result.data?.message || 'Data berhasil dicek, tetapi belum ada pesan yang bisa ditampilkan.');
        } catch (error) {
            loadingBubble?.remove();
            this.appendChatMessage('bot', error.message || 'Asisten sedang tidak tersedia. Silakan coba lagi.', 'error');
        } finally {
            this.chatbotBusy = false;
            document.getElementById('chatbotOptions')?.classList.remove('is-loading');
        }
    }

    persistChatbotMessage(role, message) {
        const saved = JSON.parse(localStorage.getItem(this.chatStorageKey) || '[]');
        saved.push({ role, message, time: Date.now() });
        localStorage.setItem(this.chatStorageKey, JSON.stringify(saved.slice(-30)));
    }

    restoreChatbotMessages() {
        const body = document.getElementById('chatbotMessages');
        if (!body || body.dataset.restored === 'true') return;

        const saved = JSON.parse(localStorage.getItem(this.chatStorageKey) || '[]');
        if (!saved.length) {
            body.dataset.restored = 'true';
            return;
        }

        body.innerHTML = '';
        saved.forEach((item) => this.appendChatMessage(item.role, item.message, '', false));
        body.dataset.restored = 'true';
    }

    submitPayment() {
        if (!this.selectedInvoice) {
            alert('Pilih tagihan terlebih dahulu.');
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const payload = {
            invoice_id: this.selectedInvoice.id,
            payment_method: this.selectedPaymentMethod,
        };

        fetch('/payment/checkout', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify(payload),
        })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Gagal memproses pembayaran');
                }
                return data;
            })
            .then(data => {
                if (!window.snap) {
                    alert('Midtrans Snap belum dimuat.');
                    return;
                }

                window.snap.pay(data.snap_token, {
                    uiMode: this.selectedPaymentMethod === 'qris' ? 'qr' : 'auto',
                    onSuccess: result => this.handleMidtransSuccess(result),
                    onPending: result => this.handleMidtransPending(result),
                    onError: result => this.handleMidtransError(result),
                    onClose: () => alert('Pembayaran dibatalkan. Silakan coba lagi.'),
                });
            })
            .catch(error => {
                console.error(error);
                alert(error.message || 'Terjadi kesalahan saat memproses pembayaran');
            });
    }

    handleMidtransSuccess(result) {
        this.confirmPayment(result, 'success');
    }

    handleMidtransPending(result) {
        this.confirmPayment(result, 'pending');
    }

    confirmPayment(result, status) {
        if (!this.selectedInvoice) return;

        const paymentMethod = this.selectedPaymentMethod.toUpperCase();
        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch('/payment/confirm', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify({
                invoice_id: this.selectedInvoice.id,
                order_id: result.order_id,
                transaction_id: result.transaction_id,
                payment_method: this.selectedPaymentMethod,
                status,
            }),
        })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Gagal mengonfirmasi pembayaran');
                }

                document.getElementById('paymentModal').classList.remove('active');

                if (!data.success) {
                    alert(data.message || 'Pembayaran masih diproses. Silakan cek kembali beberapa saat lagi.');
                    return;
                }

                document.getElementById('successMethod').textContent = paymentMethod;
                document.getElementById('successAmount').textContent = this.formatRupiah(this.selectedInvoice.total);
                document.getElementById('successTrxId').textContent = result.order_id || `TRX-${Date.now().toString().slice(-6)}`;
                document.getElementById('successMessage').textContent = `Tagihan ${this.selectedInvoice.name} telah berhasil dibayar.`;
                document.getElementById('successModal').classList.add('active');

                this.selectedInvoice.status = 'lunas';
                this.selectedInvoice.paid_date = new Date().toLocaleDateString('id-ID');
                this.selectedInvoice.method = this.selectedPaymentMethod;
                const paidInvoice = this.normalizeInvoice({
                    id: this.selectedInvoice.id + 1000,
                    name: this.selectedInvoice.name,
                    paid_date: this.selectedInvoice.paid_date,
                    amount: this.selectedInvoice.total,
                    status: 'lunas',
                    method: this.selectedPaymentMethod,
                });
                this.riwayatData.unshift(paidInvoice);
                this.activeInvoices = this.activeInvoices.filter(invoice => invoice.id !== this.selectedInvoice.id);
                this.tagihanData = this.tagihanData.map(invoice => invoice.id === this.selectedInvoice.id
                    ? { ...invoice, status: 'lunas', paid_date: this.selectedInvoice.paid_date, method: this.selectedPaymentMethod }
                    : invoice
                );
                this.renderDynamicContent();
                this.loadNotifications();
            })
            .catch(error => {
                console.error('Konfirmasi pembayaran gagal', error);
                alert(error.message || 'Pembayaran belum bisa dikonfirmasi. Coba cek status beberapa saat lagi.');
            });
    }

    handleMidtransError(result) {
        console.error('Midtrans error', result);
        alert('Terjadi kesalahan pada Midtrans. Silakan coba lagi.');
    }

    filterTagihan(status) {
        this.currentTagihanFilter = status;
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.filter === status);
        });

        this.renderTagihanList();
    }

    getFilteredTagihan() {
        return this.tagihanData.filter(tagihan => {
            const statusMatch = this.currentTagihanFilter === 'semua'
                || tagihan.status === this.currentTagihanFilter
                || (this.currentTagihanFilter === 'belum' && tagihan.status === 'belum');

            if (!statusMatch) {
                return false;
            }

            if (!this.currentTagihanSearch) {
                return true;
            }

            const haystack = [
                tagihan.name,
                tagihan.description,
                this.getStatusLabel(tagihan.status),
                this.getDueDate(tagihan),
                this.getPaidDate(tagihan),
                this.formatRupiah(tagihan.total || tagihan.amount),
            ].join(' ').toLowerCase();

            return haystack.includes(this.currentTagihanSearch);
        });
    }

    normalizeInvoice(invoice) {
        const dueDate = invoice.due_date || invoice.dueDate || '';
        let status = invoice.status || 'belum';

        if (status !== 'lunas' && dueDate) {
            const due = new Date(`${dueDate}T23:59:59`);
            if (!Number.isNaN(due.getTime()) && due < new Date()) {
                status = 'terlambat';
            }
        }

        const amount = Number(invoice.amount || 0);
        const penalty = Number(invoice.penalty || 0);

        return {
            ...invoice,
            due_date: dueDate,
            dueDate,
            amount,
            penalty,
            total: Number(invoice.total || amount + penalty),
            status,
        };
    }

    getStatusLabel(status) {
        if (status === 'lunas') return 'Lunas';
        if (status === 'terlambat') return 'Menunggak';
        if (status === 'cicilan') return 'Cicilan';
        return 'Belum Bayar';
    }

    getBillingPeriod(tagihan) {
        const name = tagihan.name || '';
        const semesterMatch = name.match(/semester\s+([a-z0-9]+)/i);

        if (semesterMatch) {
            return `Semester ${semesterMatch[1].toUpperCase()}`;
        }

        const dueDate = tagihan.due_date || tagihan.dueDate;
        if (!dueDate) {
            return 'SPP per semester';
        }

        const date = new Date(`${dueDate}T00:00:00`);
        if (Number.isNaN(date.getTime())) {
            return 'SPP per semester';
        }

        const month = date.getMonth();
        const year = date.getFullYear();
        if (![0, 6].includes(month)) {
            return `Periode semester ${year}`;
        }

        const semester = month === 6 ? 'Ganjil' : 'Genap';

        return `Semester ${semester} ${year}`;
    }

    formatDisplayDate(value) {
        if (!value || value === '-') return '-';

        const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
        if (Number.isNaN(date.getTime())) {
            return String(value).slice(0, 10);
        }

        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    }

    formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }

    getDueDate(tagihan) {
        return tagihan.dueDate || tagihan.due_date || '-';
    }

    getPaidDate(tagihan) {
        return tagihan.paidDate || tagihan.paid_date || null;
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
