<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - SyaJagad</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <!-- Left Illustration -->
        <div class="auth-illustration">
            <div class="illustration-content">
                <div class="logo-large">
                    <i class="fas fa-mosque"></i>
                    <span>SyaJagad</span>
                </div>
                <h1>Bergabung dengan Komunitas</h1>
                <p>Halo, calon pengguna baru. Yuk mulai dengan langkah yang sederhana dan nyaman.</p>
                <div class="floating-cards">
                    <div class="auth-visual">
                        <div class="auth-preview-card auth-welcome-card">
                            <div class="auth-visual-top">
                                <div class="auth-visual-icon">
                                    <i class="fas fa-seedling"></i>
                                </div>
                                <div>
                                    <span>Halo baru</span>
                                    <strong>Selamat bergabung</strong>
                                </div>
                                <span class="auth-status-pill">Mulai</span>
                            </div>

                            <div class="auth-greeting-portrait">
                                <div class="auth-user-bubble auth-user-main">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="auth-user-bubble auth-user-small">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="auth-user-ring"></div>
                            </div>

                            <div class="auth-welcome-lines">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>

                        <div class="auth-floating-card auth-float-one">
                            <i class="fas fa-hand-peace"></i>
                            <span>Halo baru</span>
                        </div>
                        <div class="auth-floating-card auth-float-two">
                            <i class="fas fa-star"></i>
                            <span>Mulai nyaman</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Form -->
        <div class="auth-form">
            <div class="form-header">
                <a href="{{ url('/') }}" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2>Buat Akun Baru</h2>
                <p>Isi data diri dengan lengkap</p>
            </div>

            <form class="register-form" id="registerForm" method="POST" action="{{ route('register') }}">
                @csrf
                @if(session('error'))
                    <div class="alert-error" style="margin-bottom:12px;color:#b91c1c;background:#fee2e2;padding:10px 12px;border-radius:8px;">
                        {{ session('error') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert-error" style="margin-bottom:12px;color:#b91c1c;background:#fee2e2;padding:10px 12px;border-radius:8px;">
                        {{ $errors->first() }}
                    </div>
                @endif
                <!-- Row 1: NIS + Nama -->
                <div class="input-row">
                    <div class="input-group half">
                        <label>NIS (Nomor Induk Santri) *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-id-card"></i>
                            <input type="text" id="nis" name="nis" value="{{ old('nis') }}" placeholder="123456" maxlength="20" required autofocus>
                        </div>
                    </div>
                    <div class="input-group half">
                        <label>Nama Lengkap *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nama" name="name" value="{{ old('name') }}" placeholder="Ahmad Santoso" required>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Tanggal Lahir + Alamat -->
                <div class="input-row">
                    <div class="input-group half">
                        <label>Tanggal Lahir *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-calendar"></i>
                            <input type="date" id="tglLahir" name="tgl_lahir" value="{{ old('tgl_lahir') }}" required>
                        </div>
                    </div>
                    <div class="input-group half">
                        <label>Alamat *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" id="alamat" name="alamat" value="{{ old('alamat') }}" placeholder="Jl. Pondok Pesantren No. 123" required>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="role" name="role" value="santri">

                <!-- Row 4: Email + Username -->
                <div class="input-row">
                    <div class="input-group half">
                        <label>Email / Gmail *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="santri@pondok.ac.id" required>
                        </div>
                    </div>
                    <div class="input-group half">
                        <label>Username *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-at"></i>
                            <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="santri123" required>
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div class="input-group">
                    <label>Password *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" minlength="8" required>
                        <i class="fas fa-eye toggle-password" data-target="password"></i>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="input-group">
                    <label>Konfirmasi Password *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirmPassword" name="password_confirmation" placeholder="Ulangi password" minlength="8" required>
                        <i class="fas fa-eye toggle-password" data-target="confirmPassword"></i>
                    </div>
                </div>

                <!-- Register Button -->
                <button type="submit" class="auth-btn primary">
                    <span class="btn-text">Daftar Akun</span>
                    <div class="btn-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </button>
            </form>

            <!-- Login Link -->
            <div class="auth-footer">
                <p>Sudah punya akun?</p>
                <a href="{{ route('login') }}" class="login-link">
                    Masuk Sekarang
                </a>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/script.js') }}"></script>
</body>
</html>
