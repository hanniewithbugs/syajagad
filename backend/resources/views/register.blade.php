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
                <p>Daftar untuk mengelola SPP atau administrasi secara online</p>
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
                            <input type="text" id="nis" name="nis" placeholder="123456" maxlength="10" required>
                        </div>
                    </div>
                    <div class="input-group half">
                        <label>Nama Lengkap *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nama" name="name" placeholder="Ahmad Santoso" required>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Tanggal Lahir + Alamat -->
                <div class="input-row">
                    <div class="input-group half">
                        <label>Tanggal Lahir *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-calendar"></i>
                            <input type="date" id="tglLahir" name="tgl_lahir" required>
                        </div>
                    </div>
                    <div class="input-group half">
                        <label>Alamat *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" id="alamat" name="alamat" placeholder="Jl. Pondok Pesantren No. 123" required>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Peran/Role -->
                <div class="input-group">
                    <label>Pilih Peran *</label>
                    <div class="input-wrapper select-wrapper">
                        <i class="fas fa-user-tag"></i>
                        <select id="role" name="role" required>
                            <option value="">-- Pilih Peran --</option>
                            <option value="santri">Santri</option>
                            <option value="admin">Admin</option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>

                <!-- Row 4: Email + Username -->
                <div class="input-row">
                    <div class="input-group half">
                        <label>Email / Gmail *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="santri@pondok.ac.id" required>
                        </div>
                    </div>
                    <div class="input-group half">
                        <label>Username *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-at"></i>
                            <input type="text" id="username" name="username" placeholder="santri123" required>
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