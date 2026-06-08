<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Santri - SyaJagad</title>
    <link rel="stylesheet" href="{{ asset('css/dbSantri.css') }}?v=650c3cd1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <img src="{{ asset('images/logo.jpg') }}" alt="SyaJagad" class="logo-img">
                </div>
                <span class="logo-text">SyaJagad</span>
            </div>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Profile Card -->
        <div class="sidebar-profile">
            <div class="profile-avatar">
                <div class="avatar-img">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="avatar-status online"></div>
            </div>
            <div class="profile-info">
                <h4 id="sidebarName">{{ Auth::user()->name }}</h4>
                <span class="profile-nis" id="sidebarNIS">NIS: {{ Auth::user()->nis }}</span>
                <span class="profile-badge">
                    <i class="fas fa-circle"></i>
                    <span id="profileStatus">Santri Aktif</span>
                </span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section-label">Menu Utama</div>
            <ul class="nav-list">
                <li class="nav-item active" data-page="dashboard">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <span>Dashboard</span>
                        <div class="nav-indicator"></div>
                    </a>
                </li>
                <li class="nav-item" data-page="tagihan">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <span>Tagihan</span>
                        <div class="nav-badge" id="navTagihanBadge">1</div>
                    </a>
                </li>
            </ul>

            <div class="nav-section-label">Akun</div>
            <ul class="nav-list">
                <li class="nav-item" data-page="profil">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <span>Profil Saya</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link logout-link" id="logoutLink">
                        <div class="nav-icon">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <span>Keluar</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <div class="footer-info">
                <i class="fas fa-shield-alt"></i>
                <span>SSL Secured</span>
            </div>
        </div>
    </aside>

    <!-- Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content" id="mainContent">

        <!-- TOP NAVBAR -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-breadcrumb">
                    <span class="breadcrumb-icon">
                        <i class="fas fa-home"></i>
                    </span>
                    <span class="breadcrumb-sep">/</span>
                    <span class="breadcrumb-current" id="breadcrumbCurrent">Dashboard</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span id="currentDate"></span>
                </div>
                <button class="topbar-notif" id="notifBtn">
                    <i class="fas fa-bell"></i>
                    <div class="notif-badge" id="notifBadge">1</div>
                </button>
                <div class="topbar-profile">
                    <div class="tp-avatar">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="tp-info">
                        <span class="tp-name" id="topbarName">Ahmad Santoso</span>
                        <span class="tp-role">Santri Aktif</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- ===== PAGE: DASHBOARD ===== -->
        <div class="page active" id="page-dashboard">
            <div class="page-content">

                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-left">
                        <div class="welcome-greeting">
                            <span class="greeting-time" id="greetingTime">Selamat Pagi</span>
                            <span class="greeting-emoji">Selamat datang</span>
                        </div>
                        <h2 id="welcomeName">Ahmad Santoso</h2>
                        <p id="welcomeMessage">Pantau tagihan SPP semester, status pembayaran, dan denda keterlambatan secara ringkas.</p>
                        <a href="#" class="welcome-btn" id="bayarSekarangBtn">
                            <i class="fas fa-credit-card"></i>
                            Bayar Tagihan
                        </a>
                    </div>
                    <div class="welcome-right">
                        <div class="welcome-illustration">
                            <div class="wi-mosque">
                                <i class="fas fa-mosque"></i>
                            </div>
                            <div class="wi-ai-brain">
                                <i class="fas fa-brain"></i>
                            </div>
                            <div class="wi-coins">
                                <div class="wi-coin c1">Rp</div>
                                <div class="wi-coin c2">Rp</div>
                                <div class="wi-coin c3">Rp</div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <!-- Summary Cards -->
                <div class="summary-grid">
                    <div class="summary-card card-success">
                        <div class="sc-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="sc-info">
                            <span class="sc-label">Total Sudah Dibayar</span>
                            <span class="sc-value" id="totalPaid">Rp 2.500.000</span>
                            <span class="sc-sub" id="totalTx">10 transaksi berhasil</span>
                        </div>
                    </div>

                    <div class="summary-card card-warning" id="totalDendaCard" style="display: none;">
                        <div class="sc-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="sc-info">
                            <span class="sc-label">Total Denda</span>
                            <span class="sc-value" id="totalDenda">Rp 100.000</span>
                            <span class="sc-sub" id="totalDendaSub">Denda berjalan per bulan</span>
                        </div>
                    </div>

                    <div class="summary-card card-info">
                        <div class="sc-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="sc-info">
                            <span class="sc-label">Pembayaran Terakhir</span>
                            <span class="sc-value" id="lastPayment">01 Okt 2024</span>
                            <span class="sc-sub">SPP Oktober - Lunas</span>
                        </div>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- AI Payment Insight -->
                    <div class="content-card ai-payment-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-brain"></i>
                                Ringkasan Risiko Tagihan
                            </h3>
                            <span class="card-badge" id="aiSourceBadge">Lokal</span>
                        </div>
                        <div class="card-body">
                            <div class="ai-risk-meter">
                                <div class="ai-risk-top">
                                    <div>
                                        <span class="ai-risk-label" id="aiRiskLabel">Memuat...</span>
                                        <strong id="aiRiskValue">0%</strong>
                                    </div>
                                    <span id="aiUpdated">-</span>
                                </div>
                                <div class="risk-track">
                                    <div class="risk-fill" id="riskFill"></div>
                                </div>
                            </div>
                            <p class="ai-advice" id="aiAdviceText">Sedang menyiapkan ringkasan tagihan.</p>
                            <div class="ai-next-action">
                                <i class="fas fa-bolt"></i>
                                <span id="aiNextAction">Menyiapkan rekomendasi</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tagihan Aktif -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-file-invoice-dollar"></i>
                                Tagihan Aktif
                            </h3>
                            <a href="#" class="card-link" id="lihatSemuaTagihan">Lihat Semua</a>
                        </div>
                        <div class="card-body" id="tagihanAktifList">
                            <!-- Dynamic content -->
                        </div>
                    </div>

                    <!-- Riwayat Transaksi -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-history"></i>
                                Riwayat Transaksi
                            </h3>
                            <span class="card-badge" id="riwayatCount">10 Transaksi</span>
                        </div>
                        <div class="card-body">
                            <div class="tx-list" id="riwayatList">
                                <!-- Dynamic content -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== PAGE: TAGIHAN ===== -->
        <div class="page" id="page-tagihan">
            <div class="page-content">
                <div class="page-header">
                    <h2>
                        <i class="fas fa-file-invoice-dollar"></i>
                        Tagihan SPP
                    </h2>
                    <p>Kelola tagihan SPP per semester dan status pembayarannya</p>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="semua">Semua</button>
                        <button class="filter-tab" data-filter="belum">Belum Bayar</button>
                        <button class="filter-tab" data-filter="lunas">Lunas</button>
                        <button class="filter-tab" data-filter="terlambat">Menunggak</button>
                    </div>
                    <div class="filter-search">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Cari tagihan..." id="searchTagihan">
                    </div>
                </div>

                <!-- Tagihan List -->
                <div class="tagihan-list" id="tagihanList">
                    <!-- Dynamic content -->
                </div>
            </div>
        </div>

        <!-- ===== PAGE: PROFIL ===== -->
        <div class="page" id="page-profil">
            <div class="page-content">
                <div class="page-header">
                    <h2>
                        <i class="fas fa-user-circle"></i>
                        Profil Santri
                    </h2>
                    <p>Kelola data diri dan informasi akun</p>
                </div>

                <div class="profile-grid">
                    <!-- Personal Info -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> Data Diri</h3>
                        </div>
                        <div class="card-body">
                            <div class="profile-field">
                                <label>Nama Lengkap</label>
                                <div class="profile-value" id="profilNama">Ahmad Santoso</div>
                            </div>
                            <div class="profile-field">
                                <label>Nomor Induk Santri (NIS)</label>
                                <div class="profile-value" id="profilNIS">2024001</div>
                            </div>
                            <div class="profile-field">
                                <label>Tanggal Lahir</label>
                                <div class="profile-value" id="profilTglLahir">15 Mei 2008</div>
                            </div>
                            <div class="profile-field">
                                <label>Alamat</label>
                                <div class="profile-value" id="profilAlamat">Jl. Pesantren Al-Hikmah No. 123, Jakarta</div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Info -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-cog"></i> Informasi Akun</h3>
                        </div>
                        <div class="card-body">
                            <div class="profile-field">
                                <label>Email</label>
                                <div class="profile-value" id="profilEmail">ahmad.santoso@santri.syajagad.ac.id</div>
                            </div>
                            <div class="profile-field">
                                <label>Username</label>
                                <div class="profile-value" id="profilUsername">ahmad_santri</div>
                            </div>
                            <div class="profile-field">
                                <label>Password</label>
                                <div class="profile-value password-field">
                                    <span>************</span>
                                    <button class="show-password" id="showPassword" type="button" title="Informasi password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="profile-actions">
                                <button class="btn-primary" id="editProfileBtn">
                                    <i class="fas fa-edit"></i>
                                    Edit Profil
                                </button>
                                <button class="btn-secondary" id="changePasswordBtn">
                                    <i class="fas fa-lock"></i>
                                    Ganti Password
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- ===== PAYMENT MODAL ===== -->
    <div class="modal-overlay" id="paymentModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-credit-card"></i>
                    Pilih Metode Pembayaran
                </h3>
                <button class="modal-close" id="modalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="modal-tagihan-info">
                    <span class="mti-label">Tagihan</span>
                    <span class="mti-name" id="modalTagihanName">SPP Semester Ganjil 2026</span>
                    <span class="mti-amount" id="modalTagihanAmount">Rp 0</span>
                </div>

                <div class="payment-summary">
                    <div class="summary-row">
                        <span>Tagihan SPP Semester</span>
                        <span id="modalTagihanBase">Rp 0</span>
                    </div>
                    <div class="summary-row">
                        <span>Denda</span>
                        <span id="modalTagihanPenalty">Rp 0</span>
                    </div>
                    <div class="summary-row total-row">
                        <strong>Total Bayar</strong>
                        <strong id="modalTagihanTotal">Rp 0</strong>
                    </div>
                </div>

                <div class="payment-methods">
                    <div class="pm-option active" data-method="qris">
                        <div class="pm-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="pm-info">
                            <span class="pm-name">QRIS</span>
                            <span class="pm-desc">Scan QR dari semua e-wallet</span>
                        </div>
                    </div>

                    <div class="pm-option" data-method="bca">
                        <div class="pm-icon bca">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="pm-info">
                            <span class="pm-name">BCA Virtual Account</span>
                            <span class="pm-desc">Transfer via ATM atau m-Banking BCA</span>
                        </div>
                    </div>

                    <div class="pm-option" data-method="mandiri">
                        <div class="pm-icon mandiri">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="pm-info">
                            <span class="pm-name">Mandiri Virtual Account</span>
                            <span class="pm-desc">Transfer via ATM atau Livin' Mandiri</span>
                        </div>
                    </div>
                </div>

                <div class="payment-method-details" id="paymentMethodDetails">
                    <div class="payment-detail qris active" id="paymentHelpQris">
                        <p>Untuk pembayaran QRIS, klik "Lanjut ke Midtrans". Midtrans akan menampilkan QR dinamis yang bisa dipindai dari e-wallet atau mobile banking yang mendukung QRIS.</p>
                        <div class="qris-placeholder">
                            <span>Gambar QRIS akan tersedia di popup Midtrans.</span>
                        </div>
                    </div>
                    <div class="payment-detail va" id="paymentHelpVA" style="display: none;">
                        <p>Untuk pembayaran Virtual Account, Midtrans akan membuat nomor VA khusus di popup pembayaran.</p>
                        <p><strong>Bank:</strong> <span id="vaBankName">BCA / Mandiri</span></p>
                        <p><strong>Nomor VA:</strong> akan muncul setelah transaksi dibuat oleh Midtrans.</p>
                        <p>Ikuti instruksi pembayaran dari Midtrans dan transfer sesuai nominal tagihan.</p>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="modal-cancel" id="modalCancel">Batal</button>
                <button class="modal-pay" id="modalPay">
                    <i class="fas fa-lock"></i>
                    Lanjut ke Midtrans
                </button>
            </div>
        </div>
    </div>

    <!-- ===== PAYMENT DETAIL PANEL ===== -->
    <div class="payment-detail-overlay" id="paymentDetailOverlay"></div>
    <aside class="payment-detail-panel" id="paymentDetailPanel" aria-live="polite">
        <div class="payment-detail-header">
            <div>
                <span>Detail Pembayaran</span>
                <strong>Rincian Tagihan</strong>
            </div>
            <button type="button" id="paymentDetailClose" aria-label="Tutup detail pembayaran">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="payment-detail-body" id="paymentDetailBody">
            <div class="detail-loading">Memuat detail pembayaran...</div>
        </div>
    </aside>

    <!-- ===== LOGOUT CONFIRM MODAL ===== -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-box logout-modal">
            <div class="modal-header">
                <i class="fas fa-sign-out-alt"></i>
                <h3>Konfirmasi Keluar</h3>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin keluar dari akun SyaJagad?</p>
                <p class="logout-warning">Anda akan perlu login kembali untuk mengakses dashboard.</p>
            </div>
            <div class="modal-footer">
                <button class="modal-cancel" id="logoutCancel">Batal</button>
                <button class="modal-pay danger" id="confirmLogout">
                    <i class="fas fa-sign-out-alt"></i>
                    Keluar
                </button>
            </div>
        </div>
    </div>

    <!-- ===== SUCCESS MODAL ===== -->
    <div class="modal-overlay" id="successModal">
        <div class="modal-box success-modal">
            <div class="success-animation">
                <div class="success-circle">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h3>Pembayaran Berhasil!</h3>
            <p id="successMessage">Tagihan SPP Semester Ganjil 2026 telah berhasil dibayar.</p>
            <div class="success-detail">
                <div class="sd-item">
                    <span>No. Transaksi</span>
                    <span id="successTrxId">TRX-2024110015</span>
                </div>
                <div class="sd-item">
                    <span>Metode</span>
                    <span id="successMethod">QRIS</span>
                </div>
                <div class="sd-item">
                    <span>Total</span>
                    <span id="successAmount">Rp 250.000</span>
                </div>
                <div class="sd-item">
                    <span>Status</span>
                    <span class="sd-status">Lunas</span>
                </div>
            </div>
            <button class="modal-pay" id="successClose">
                <i class="fas fa-home"></i>
                Kembali ke Dashboard
            </button>
        </div>
    </div>

    <!-- ===== EDIT PROFILE MODAL ===== -->
    <div class="modal-overlay" id="editProfileModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-edit"></i>
                    Edit Profil
                </h3>
                <button class="modal-close" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editProfileForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editProfileName">Nama Lengkap</label>
                        <input type="text" id="editProfileName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="editProfileEmail">Email</label>
                        <input type="email" id="editProfileEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="editProfileUsername">Username</label>
                        <input type="text" id="editProfileUsername" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="editProfileAlamat">Alamat</label>
                        <textarea id="editProfileAlamat" name="alamat" rows="3"></textarea>
                    </div>
                    <p class="form-feedback" id="editProfileFeedback"></p>
                </div>
                <div class="modal-footer">
                    <button class="modal-cancel" type="button">Batal</button>
                    <button class="modal-pay" type="submit">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== CHANGE PASSWORD MODAL ===== -->
    <div class="modal-overlay" id="changePasswordModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-lock"></i>
                    Ganti Password
                </h3>
                <button class="modal-close" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="oldPassword">Password Lama</label>
                        <input type="password" id="oldPassword" name="old_password" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">Password Baru</label>
                        <input type="password" id="newPassword" name="new_password" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <label for="newPasswordConfirmation">Konfirmasi Password Baru</label>
                        <input type="password" id="newPasswordConfirmation" name="new_password_confirmation" minlength="8" required>
                    </div>
                    <p class="form-feedback" id="changePasswordFeedback"></p>
                </div>
                <div class="modal-footer">
                    <button class="modal-cancel" type="button">Batal</button>
                    <button class="modal-pay" type="submit">
                        <i class="fas fa-key"></i>
                        Ubah Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== QUICK CHATBOT ===== -->
    <div class="chatbot-shell" id="chatbotShell">
        <div class="chatbot-panel" id="chatbotPanel" aria-live="polite">
            <div class="chatbot-header">
                <div>
                    <span class="chatbot-eyebrow">Asisten SyaJagad</span>
                    <strong>Butuh cek tagihan?</strong>
                </div>
                <div class="chatbot-header-actions">
                    <button class="chatbot-clear" id="chatbotClear" type="button" aria-label="Hapus riwayat chat">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="chatbot-close" id="chatbotClose" type="button" aria-label="Tutup asisten">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="chatbot-body" id="chatbotMessages">
                <div class="chatbot-message bot">
                    <span>Saya bisa bantu cek tagihan, denda, status pembayaran, dan cara bayar. Tulis pertanyaan bebas atau pilih menu cepat di bawah. Kalau di luar konteks aplikasi, saya akan arahkan kembali.</span>
                    <small class="chatbot-time">Baru saja</small>
                </div>
            </div>
            <form class="chatbot-form" id="chatbotForm">
                <input type="text" id="chatbotInput" maxlength="500" placeholder="Tanya tagihan, denda, status, cara bayar...">
                <button type="submit" aria-label="Kirim pertanyaan">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
            <div class="chatbot-options" id="chatbotOptions">
                <button type="button" data-intent="total_tagihan">Total tagihan</button>
                <button type="button" data-intent="tagihan_aktif">Belum lunas</button>
                <button type="button" data-intent="denda">Denda</button>
                <button type="button" data-intent="cara_bayar_qris">Bayar QRIS</button>
                <button type="button" data-intent="cara_bayar_va">Bayar VA</button>
                <button type="button" data-intent="status_terakhir">Status terakhir</button>
                <button type="button" data-intent="rekomendasi">Rekomendasi</button>
                <button type="button" data-intent="kontak_admin">Kontak admin</button>
            </div>
        </div>
        <button class="chatbot-toggle" id="chatbotToggle" type="button" aria-label="Buka Asisten SyaJagad">
            <i class="fas fa-comments"></i>
            <span>Asisten</span>
        </button>
    </div>

    <form id="logoutForm" method="POST" action="{{ route('logout') }}" style="display: none;">
        @csrf
    </form>

    <script>
        // Pass data user dari Laravel ke JavaScript
        window.userData = {
            name: "{{ Auth::user()->name }}",
            nis: "{{ Auth::user()->nis }}",
            email: "{{ Auth::user()->email }}",
            username: "{{ Auth::user()->username }}",
            tgl_lahir: "{{ Auth::user()->tgl_lahir }}",
            alamat: "{{ Auth::user()->alamat }}",
            role: "{{ Auth::user()->role }}"
        };
        window.paymentData = @json($paymentData ?? []);
    </script>
    <script
        src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
        data-client-key="{{ config('services.midtrans.client_key') }}">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="{{ asset('js/dbSantri.js') }}"></script>
</body>
</html>
