<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SyaJagad</title>
    <link rel="stylesheet" href="{{ asset('css/dbAdmin.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <img src="{{ asset('images/logo.jpg') }}" alt="SyaJagad Ponpes" class="logo-img">
                </div>
                <span class="logo-text">SyaJagad</span>
            </div>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Admin Profile Card -->
        <div class="sidebar-profile">
            <div class="profile-avatar">
                <div class="avatar-img">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="avatar-status online"></div>
            </div>
            <div class="profile-info">
                <h4>Admin Ponpes JA</h4>
                <span class="profile-role">Admin 1</span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section-label">Menu Utama</div>
            <ul class="nav-list">
                <li class="nav-item active" data-page="dashboard">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item" data-page="santri">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span>Data Santri</span>
                    </a>
                </li>
                <li class="nav-item" data-page="pembayaran">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <span>Pembayaran SPP</span>
                    </a>
                </li>
                <li class="nav-item" data-page="laporan">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span>Laporan</span>
                    </a>
                </li>
                <li class="nav-item" data-page="pengaturan">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span>Pengaturan</span>
                    </a>
                </li>
            </ul>
            <div class="nav-section-label">Akun</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="#" class="nav-link logout-link" id="logoutLink">
                        <div class="nav-icon">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
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
                    <div class="notif-badge" id="notifBadge">3</div>
                </button>
                <button class="topbar-message" id="messageBtn">
                    <i class="fas fa-envelope"></i>
                    <div class="notif-badge">2</div>
                </button>
                <div class="topbar-profile">
                    <div class="tp-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="tp-info">
                        <span class="tp-name">Admin Ponpes JA</span>
                        <span class="tp-role">Admin 1</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- ===== PAGE: DASHBOARD ===== -->
        <div class="page active" id="page-dashboard">
            <div class="page-content dashboard-modified">
                <!-- Page Header -->
 <div class="welcome-banner">
                    <div class="welcome-left">
                        <div class="welcome-greeting">
                            <span class="greeting-time" id="greetingTime">Selamat Pagi</span>
                            <span class="greeting-emoji">👨‍💼</span>
                        </div>
                        <h2>Selamat Datang, Admin!</h2>
                        <p>Sistem manajemen santri dan pembayaran SyaJagad berjalan optimal. Pantau performa dan kelola data secara real-time.</p>
                    </div>
                    <div class="welcome-right">
                        <div class="welcome-illustration">
                            <div class="wi-dashboard">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="wi-users">
                                <i class="fas fa-users"></i>
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
                <div class="dashboard-summary-cards">
                    <div class="summary-card blue">
                        <i class="fas fa-users icon"></i>
                        <div class="content">
                            <div class="label">Total Santri</div>
                            <div class="value" id="totalSantri">120</div>
                            <div class="sub">Santri Aktif</div>
                        </div>
                    </div>
                    <div class="summary-card green-dark">
                        <i class="fas fa-check-circle icon"></i>
                        <div class="content">
                            <div class="label">Sudah Bayar</div>
                            <div class="value" id="totalPaid">85</div>
                            <div class="sub">Santri</div>
                        </div>
                    </div>
                    <div class="summary-card red">
                        <i class="fas fa-clock icon"></i>
                        <div class="content">
                            <div class="label">Belum Bayar</div>
                            <div class="value" id="totalUnpaid">35</div>
                            <div class="sub">Santri</div>
                        </div>
                    </div>
                    <div class="summary-card yellow">
                        <i class="fas fa-coins icon"></i>
                        <div class="content">
                            <div class="label">Total Pemasukan</div>
                            <div class="value" id="totalPemasukan">Rp 42.5M</div>
                            <div class="sub">Semester ini</div>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Grid -->
                <div class="dashboard-main-grid">
                    <!-- Pembayaran Terbaru -->
                    <div class="payment-latest">
                        <h2><i class="fas fa-history"></i> Pembayaran Terbaru</h2>
                        <table class="payment-table">
                            <thead>
                                <tr>
                                    <th>Nama Santri</th>
                                    <th>Bulan</th>
                                    <th>Tanggal Bayar</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="pembayaranTerbaruList">
                                <!-- Dynamic content -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Grafik Pembayaran -->
                    <div class="payment-chart-section">
                        <h2><i class="fas fa-chart-bar"></i> Grafik Pembayaran</h2>
                        <canvas id="paymentChart" width="400" height="200"></canvas>
                        <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--gray-600);">
                            <div>Juli 2024 • Januari 2025 • Juli 2025 • Januari 2026</div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Tagihan -->
                <div class="tagihan-summary-cards">
                    <div class="tagihan-card total-tagihan">
                        <i class="fas fa-file-invoice-dollar icon"></i>
                        <div class="content">
                            <div class="label">Total Tagihan</div>
                            <div class="value" id="totalTagihan">Rp 50.000.000</div>
                        </div>
                    </div>
                    <div class="tagihan-card paid-tagihan">
                        <i class="fas fa-check-circle icon"></i>
                        <div class="content">
                            <div class="label">Sudah Dibayar</div>
                            <div class="value" id="totalBayar">Rp 42.500.000</div>
                        </div>
                    </div>
                    <div class="tagihan-card sisa-tagihan">
                        <i class="fas fa-exclamation-triangle icon"></i>
                        <div class="content">
                            <div class="label">Sisa Tagihan</div>
                            <div class="value" id="sisaTagihan">Rp 7.500.000</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== PAGE: DATA SANTRI ===== -->
        <div class="page" id="page-santri">
            <div class="page-content">
                <div class="page-header">
                    <h2><i class="fas fa-users"></i> Data Santri</h2>
                    <p>Kelola data santri pondok pesantren secara lengkap</p>
                </div>

                <!-- Admin Actions -->
                <div class="admin-actions">
                    <div class="search-filter">
                        <div class="filter-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchSantri" placeholder="Cari nama, NIS, atau angkatan...">
                        </div>
                        <select id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="cuti">Cuti</option>
                            <option value="alumni">Alumni</option>
                        </select>
                    </div>
                    <button class="btn-primary" id="addSantriBtn">
                        <i class="fas fa-plus"></i>
                        Tambah Santri
                    </button>
                </div>

                <!-- Data Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox"></th>
                                <th>Nama</th>
                                <th>No Induk</th>
                                <th>Jenis Kelamin</th>
                                <th>Angkatan</th>
                                <th>Status Santri</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="santriTableBody">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                    <div class="table-pagination">
                        <div class="pagination-info" id="santriPaginationInfo"></div>
                        <div class="pagination-nav" id="santriPagination"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== PAGE: PEMBAYARAN SPP ===== -->
        <div class="page" id="page-pembayaran">
            <div class="page-content">
                <div class="page-header">
                    <h2><i class="fas fa-file-invoice-dollar"></i> Pembayaran SPP</h2>
                    <p>Manajemen pembayaran SPP santri per semester</p>
                </div>

                <!-- Summary Cards -->
                <div class="summary-grid">
                    <div class="summary-card card-warning">
                        <div class="sc-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="sc-info">
                            <span class="sc-label">Total Menunggak</span>
                            <span class="sc-value">35 Santri</span>
                            <span class="sc-sub">Rp 8.750.000</span>
                        </div>
                    </div>
                    <div class="summary-card card-success">
                        <div class="sc-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="sc-info">
                            <span class="sc-label">Bayar Semester Ini</span>
                            <span class="sc-value">85 Santri</span>
                            <span class="sc-sub">Rp 42.500.000</span>
                        </div>
                    </div>
                </div>

                <!-- Search -->
                <div class="admin-actions">
                    <div class="search-filter">
                        <div class="filter-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchPembayaran" placeholder="Cari santri berdasarkan nama, NIS, atau angkatan...">
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox"></th>
                                <th>Nama</th>
                                <th>No Induk</th>
                                <th>Jenis Kelamin</th>
                                <th>Angkatan</th>
                                <th>Status Santri</th>
                                <th>Bulan Tagihan</th>
                                <th>Status Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody id="pembayaranTableBody">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                    <div class="table-pagination">
                        <div class="pagination-info" id="pembayaranPaginationInfo"></div>
                        <div class="pagination-nav" id="pembayaranPagination"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== PAGE: LAPORAN ===== -->
        <div class="page" id="page-laporan">
            <div class="page-content">
                <div class="page-header">
                    <h2><i class="fas fa-chart-bar"></i> Laporan</h2>
                    <p>Analisis lengkap pembayaran dan performa keuangan ponpes</p>
                </div>

                <!-- Report Filters -->
                <div class="report-filters">
                    <div class="filter-group">
                        <select>
                            <option>Jenis Laporan</option>
                            <option>Pembayaran Bulanan</option>
                            <option>Pendapatan Tahunan</option>
                            <option>Tunggakan SPP</option>
                        </select>
                        <select>
                            <option>Semua Angkatan</option>
                            <option>2024</option>
                            <option>2023</option>
                        </select>
                        <select>
                            <option>Metode Pembayaran</option>
                            <option>VA BCA</option>
                            <option>VA Mandiri</option>
                            <option>Cash</option>
                        </select>
                        <input type="date">
                        <span> _ </span>
                        <input type="date">
                    </div>
                    <div class="export-buttons">
                        <button class="btn-export" data-format="pdf">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="btn-export" data-format="excel">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="btn-export" data-format="csv">
                            <i class="fas fa-file-csv"></i> CSV
                        </button>
                    </div>
                </div>

                <!-- Report Summary -->
                <div class="report-summary">
                    <div class="summary-item">
                        <div class="label">Total Pendapatan Terverifikasi</div>
                        <div class="value">Rp 42.500.000</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Total Tunggakan SPP</div>
                        <div class="value">35 Santri</div>
                        <div class="sub-value">Rp 7.500.000</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Tingkat Kolektibilitas</div>
                        <div class="value">71%</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Menunggu Verifikasi</div>
                        <div class="value">12 Transaksi</div>
                    </div>
                </div>

                <!-- Charts Grid -->
                <div class="report-grid">
                    <div class="content-card full-width">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Pertumbuhan Pendapatan</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" width="800" height="300"></canvas>
                        </div>
                    </div>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-pie"></i> Demografi Pembayaran</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="demographyChart" width="300" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== PAGE: PENGATURAN ===== -->
        <div class="page" id="page-pengaturan">
            <div class="page-content">
                <div class="page-header">
                    <h2><i class="fas fa-cog"></i> Pengaturan</h2>
                    <p>Konfigurasi sistem pembayaran dan profil instansi</p>
                </div>

                <div class="settings-grid">
                    <!-- Profil Instansi -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-building"></i> Profil Instansi</h3>
                        </div>
                        <div class="card-body">
                            <div class="profile-field">
                                <label>Nama Institusi</label>
                                <input type="text" value="Ponpes Jendela Alam" class="form-input">
                            </div>
                            <div class="profile-field">
                                <label>Alamat</label>
                                <input type="text" value="Jl. Pesantren No. 123, Jakarta" class="form-input">
                            </div>
                            <div class="profile-field">
                                <label>Email</label>
                                <input type="email" value="admin@ponpesja.ac.id" class="form-input">
                            </div>
                            <div class="profile-field">
                                <label>Telepon</label>
                                <input type="tel" value="(021) 12345678" class="form-input">
                            </div>
                            <button class="btn-primary" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>

                    <!-- Keamanan Sistem -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-shield-alt"></i> Keamanan Sistem</h3>
                        </div>
                        <div class="card-body">
                            <div class="profile-field">
                                <label>Kata Sandi Lama</label>
                                <input type="password" placeholder="Masukkan kata sandi lama" class="form-input">
                            </div>
                            <div class="profile-field">
                                <label>Kata Sandi Baru</label>
                                <input type="password" placeholder="Masukkan kata sandi baru" class="form-input">
                            </div>
                            <div class="profile-field">
                                <label>Konfirmasi Kata Sandi</label>
                                <input type="password" placeholder="Konfirmasi kata sandi baru" class="form-input">
                            </div>
                            <button class="btn-primary" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-lock"></i> Ubah Kata Sandi
                            </button>
                            <div style="margin-top: 1.5rem;">
                                <label class="toggle-item">
                                    <input type="checkbox" id="twoFactorAuth">
                                    <span class="toggle-switch"></span>
                                    Autentikasi Dua Faktor
                                </label>
                                <button class="btn-primary" style="width: 100%; margin-top: 1rem;">
                                    <i class="fas fa-users-cog"></i> Kelola Peran
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rincian Pembayaran -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-money-bill-wave"></i> Rincian Pembayaran</h3>
                    </div>
                    <div class="card-body">
                        <div class="payment-config-list">
                            <div class="payment-config-item">
                                <div class="pci-info">
                                    <div class="pci-name">SPP Bulanan</div>
                                    <div class="pci-amount">Rp 250.000</div>
                                </div>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                            </div>
                            <div class="payment-config-item">
                                <div class="pci-info">
                                    <div class="pci-name">SPP Semesteran</div>
                                    <div class="pci-amount">Rp 1.200.000</div>
                                </div>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                            </div>
                        </div>
                        <div class="payment-stats">
                            <div class="stat-item">
                                <strong>Total Pembayaran: Rp 50.000.000</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Integrasi & Periode -->
                <div class="admin-section-grid">
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-plug"></i> Integrasi Bank</h3>
                        </div>
                        <div class="card-body toggle-group">
                            <label class="toggle-item">
                                <input type="checkbox" checked>
                                <span class="toggle-switch"></span>
                                Bank Mandiri VA
                            </label>
                            <label class="toggle-item">
                                <input type="checkbox" checked>
                                <span class="toggle-switch"></span>
                                Bank BCA VA
                            </label>
                        </div>
                    </div>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-alt"></i> Periode Pembayaran</h3>
                        </div>
                        <div class="card-body period-controls">
                            <label>
                                Mulai
                                <input type="date" value="2024-07-01">
                            </label>
                            <label>
                                Selesai
                                <input type="date" value="2025-06-30">
                            </label>
                            <button class="btn-primary">
                                <i class="fas fa-edit"></i> Edit Periode
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

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

    <script src="{{ asset('js/dbAdmin.js') }}"></script>
</body>
</html>