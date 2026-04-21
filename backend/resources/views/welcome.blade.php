<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SyaJagad - Smart Payment Santri</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <div class="logo-icon">
                    <img src="{{ asset('images/logo.jpg') }}" alt="SyaJagad" class="logo-img">
                </div>
                <span>SyaJagad</span>
            </div>
            <a href="{{ route('login') }}" class="nav-signin">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-bg"></div>
        <div class="hero-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div> 
            <div class="shape shape-3"></div>
          
        </div>
        
        <div class="hero-container">
            <!-- Left Content -->
            <div class="hero-content">
                <div class="badge">Sistem Internal Pondok</div>
                <h1 class="hero-title">
                    Bayar SPP Santri <br>
                    <span class="gradient-text">Lebih Mudah</span>
                </h1>
                <p class="hero-subtitle">
                    Dengan aplikasi ini, pembayaran menjadi lebih mudah
                </p>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number" data-target="5000">0</div>
                        <div class="stat-label">Santri Terdaftar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" data-target="25000">0</div>
                        <div class="stat-label">Transaksi Berhasil</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" data-target="99">0</div>
                        <span class="stat-percent">%</span>
                        <div class="stat-label">Uptime</div>
                    </div>
                </div>

                <div class="hero-buttons">
                    <a href="{{ route('login') }}" class="cta-button primary">
                        <i class="fas fa-rocket"></i>
                        Mulai Bayar Sekarang
                    </a>
                </div>

                <!-- Features Quick View -->
                <div class="features-preview">
                    <div class="feature-item">
                        <div class="feature-icon bg-gradient-primary">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <span>QRIS & Transfer</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon bg-gradient-success">
                            <i class="fas fa-brain"></i>
                        </div>
                        <span>Prediksi AI</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon bg-gradient-warning">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <span>100% Aman</span>
                    </div>
                </div>
            </div>
            
 <!-- Right Illustration (Mobile App Style) - FIXED -->
<div class="hero-illustration">
    <div class="app-preview">
        <!-- Phone Frame -->
        <div class="phone-frame">
            <!-- Notch/Speaker -->
            <div class="phone-notch"></div>
            
            <!-- Screen Content -->
            <div class="phone-screen">
                <!-- Status Bar -->
                <div class="status-bar">
                    <span id="wib-time">--:--</span>
                    <div class="signal-bars">
                        <span></span><span></span><span></span>
                    </div>
                </div>
                
                <!-- Payment Timeline -->
                <div class="payment-success payment-timeline-card">
                    <div class="payment-header">
                        <span class="payment-method-badge"><i class="fas fa-qrcode"></i> QRIS</span>
                        <span class="payment-ref">TRX#A7K29</span>
                    </div>

                    <div class="timeline-title">Status Transaksi</div>
                    <div class="timeline-subtitle">SPP Januari 2026</div>

                    <div class="payment-timeline">
                        <div class="timeline-step done">
                            <div class="step-icon"><i class="fas fa-receipt"></i></div>
                            <div class="step-content">
                                <div class="step-title">Inisiasi Pembayaran</div>
                                <div class="step-meta">09:40 WIB</div>
                            </div>
                        </div>

                        <div class="timeline-step done">
                            <div class="step-icon"><i class="fas fa-shield-check"></i></div>
                            <div class="step-content">
                                <div class="step-title">Verifikasi Sistem</div>
                                <div class="step-meta">09:41 WIB</div>
                            </div>
                        </div>

                        <div class="timeline-step done">
                            <div class="step-icon"><i class="fas fa-circle-check"></i></div>
                            <div class="step-content">
                                <div class="step-title">Sukses Tercatat</div>
                                <div class="step-meta">Nominal Disembunyikan</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions - FIXED SPACING -->
                <div class="quick-actions">
                    <button class="action-btn" type="button" aria-label="QRIS">
                        <i class="fas fa-qrcode"></i>
                        <span>QRIS</span>
                    </button>
                    <button class="action-btn active" type="button" aria-label="Transfer">
                        <i class="fas fa-university"></i>
                        <span>Transfer</span>
                    </button>
                    <button class="action-btn" type="button" aria-label="Riwayat">
                        <i class="fas fa-history"></i>
                        <span>Riwayat</span>
                    </button>
                </div>
            </div>
            
            <!-- Home Indicator -->
            <div class="home-indicator"></div>
        </div>
    
                    
                    <!-- Floating Elements -->
                    <div class="floating-elements">
                        <div class="floating-qr">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="floating-card">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="floating-coin">
                            <span>Rp</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <h2 class="section-title">Apa itu SyaJagad?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-large primary">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Payment Gateway</h3>
                    <p>Aplikasi pembayaran online yang aman dan mudah digunakan.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-large success">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>AI Prediction</h3>
                    <p>Memiliki kemampuan prediksi berdasarkan data dan pola yang ada.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-large warning">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Multi User</h3>
                    <p>Menyediakan pengalaman pengguna yang optimal untuk berbagai jenis pengguna.</p>
                </div>
            </div>

            <div class="contact-info-card">
                <div class="contact-header">
                    <h3>Informasi Kontak Pondok</h3>
                    <p>Hubungi kami untuk informasi pendaftaran, administrasi, dan pembayaran.</p>
                </div>

                <div class="contact-grid">
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="contact-content">
                            <span class="contact-label">Alamat</span>
                            <span class="contact-value">Jl. Jetis Kulon VI No.16A, Wonokromo, Kec. Wonokromo, Surabaya, Jawa Timur 60231</span>
                        </div>
                    </div>

                    <a class="contact-item contact-link" href="https://instagram.com/ponpes_ja" target="_blank" rel="noopener noreferrer">
                        <div class="contact-icon"><i class="fab fa-instagram"></i></div>
                        <div class="contact-content">
                            <span class="contact-label">Instagram</span>
                            <span class="contact-value">@ponpes_ja</span>
                        </div>
                    </a>

                    <a class="contact-item contact-link" href="mailto:jagad_alimussirry99@yahoo.co.id">
                        <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                        <div class="contact-content">
                            <span class="contact-label">Email</span>
                            <span class="contact-value">jagad_alimussirry99@yahoo.co.id</span>
                        </div>
                    </a>

                    <a class="contact-item contact-link" href="tel:+6282136212570">
                        <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="contact-content">
                            <span class="contact-label">Telepon</span>
                            <span class="contact-value">+62 821-3621-2570</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script src="{{ asset('js/script.js') }}"></script>
    <script>
        (function () {
            const wibTimeEl = document.getElementById('wib-time');
            if (!wibTimeEl) return;

            const wibFormatter = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });

            function updateWibTime() {
                wibTimeEl.textContent = wibFormatter.format(new Date()).replace('.', ':');
            }

            updateWibTime();
            setInterval(updateWibTime, 1000 * 60);
        })();
    </script>
</body>
</html>
