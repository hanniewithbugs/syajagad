<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SyaJagad</title>
    <link rel="stylesheet" href="{{asset('css/style.css')}}">
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
                <h1>Selamat Datang Kembali</h1>
                <p>Sistem pembayaran SPP santri & administrasi Pondok Pesantren Mahasiswa Jagad 'Alimussirry</p>
                <div class="floating-cards">
                </div>
            </div>
        </div>

        <!-- Right Form -->
        <div class="auth-form">
            <div class="form-header">
                <a href="{{ url('/') }}" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2>Masuk ke Sistem</h2>
                <p>Silahkan login dengan akun santri atau admin</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="login-form">
                @csrf
                @if(session('error'))
                    <div class="alert-error" style="margin-bottom:12px;color:#b91c1c;background:#fee2e2;padding:10px 12px;border-radius:8px;">
                        {{ session('error') }}
                    </div>
                @endif
                <!-- Username/Email -->
                <div class="input-group">
                    <label>NIS / Email *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="email" placeholder="Masukkan NIS atau Email" required>
                    </div>
                </div>


                <!-- Role Selector -->
                <div class="input-group">
                    <label>Pilih Peran *</label>
                    <div class="input-wrapper select-wrapper">
                        <i class="fas fa-user-tag"></i>
                        <select id="loginRole" name="role" required>
                            <option value="">-- Pilih Peran --</option>
                            <option value="santri">Santri</option>
                            <option value="admin">Admin</option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>

                <!-- Password -->
                <div class="input-group">
                    <label>Password *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="loginPassword" name="password" placeholder="Masukkan password" required>
                        <i class="fas fa-eye toggle-password" data-target="loginPassword"></i>
                    </div>
                </div>

                <!-- Forgot Password -->
                <div class="forgot-password">
                    <a href="#" id="forgotPassword">Lupa Password?</a>
                </div>

                <!-- Login Button -->
                <button type="submit" class="auth-btn primary">
                    <span class="btn-text">Masuk</span>
                    <div class="btn-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </button>
            </form>

            <!-- Register Link -->
            <div class="auth-footer">
                <p>Belum punya akun?</p>
                <a href="{{ route('register') }}" class="register-link">
                    Daftar Sekarang
                </a>
            </div>

            <!-- Google Login (Optional) -->
            <div class="social-login">
                <p>atau login dengan</p>
                <button type="button" class="google-btn">
                    <i class="fab fa-google"></i>
                    Google
                </button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/script.js') }}"></script>
</body>
</html>