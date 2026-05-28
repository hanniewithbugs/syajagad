# SyaJagad

SyaJagad adalah aplikasi web pembayaran SPP santri dan administrasi Pondok Pesantren Mahasiswa Jagad 'Alimussirry. Aplikasi ini dibuat sebagai proyek UAS/lomba dengan fokus pada alur multi-user, pembayaran digital, audit administrasi, notifikasi, dan insight AI.

## Teknologi

- Backend: PHP, Laravel 13
- Frontend: Blade, HTML, CSS custom, JavaScript
- Database lokal: mengikuti `.env`, direkomendasikan MySQL/MariaDB Laragon
- Payment gateway: Midtrans Snap sandbox/production
- AI: OpenAI Responses API dengan fallback analisis lokal
- Testing: PHPUnit / Laravel Feature Test

## Fitur

- Login admin dan santri
- Login santri memakai email atau NIS/NIP, dengan atau tanpa spasi
- Dashboard admin dan dashboard santri
- Import data santri dari Excel/CSV
- Data santri lengkap: nama, NIS/NIP, gender, tanggal lahir, alamat, email, username
- Angkatan otomatis dari 2 digit depan NIS/NIP
- Password santri otomatis: `Jagad` + 3 digit akhir NIS/NIP
- Kelola santri, tagihan per santri, dan tagihan massal
- Pembayaran Midtrans: QRIS, BCA VA, Mandiri VA
- Webhook Midtrans untuk validasi status pembayaran
- Notifikasi santri untuk tagihan dan pembayaran berhasil
- Permission admin dan audit log
- Insight risiko pembayaran dan prediksi AI tagihan
- Export laporan: CSV, Excel, dan halaman cetak/simpan PDF
- Responsive untuk desktop dan mobile

## Menjalankan Lokal

```bash
composer install
npm install
npm run build
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Buka:

```text
http://127.0.0.1:8000
```

## Deploy Online

Panduan deploy Ubuntu VPS tersedia di:

```text
DEPLOY_UBUNTU.md
```

File bantu deployment tersedia di folder:

```text
deploy/
```

## Akun Demo

Admin:

```text
Email: admin@syajagad.local
Password: AdminJagad2026!
Role: Admin
```

Santri demo:

```text
NIS/NIP: 24 000 001
Password: Jagad001
Role: Santri
```

Data santri hasil import tersedia di:

```text
storage/app/santri-credentials-20260518-035709.csv
```

## Konfigurasi `.env`

Midtrans:

```env
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

OpenAI:

```env
OPENAI_API_KEY=
OPENAI_MODEL=gpt-5.4-mini
OPENAI_TIMEOUT=12
```

Jika `OPENAI_API_KEY` kosong, aplikasi tetap berjalan memakai analisis lokal.

## Import Santri

File Excel dikonversi ke CSV, lalu diimport dengan:

```bash
php artisan santri:import-rekap storage\app\imports\rekap_santri.csv
```

Gunakan preview tanpa menyimpan:

```bash
php artisan santri:import-rekap storage\app\imports\rekap_santri.csv --dry-run
```

## Testing

```bash
php artisan test tests\Feature
```

Status terakhir: 27 feature test lulus.

## Catatan Presentasi

- Tekankan bahwa status lunas tidak ditentukan frontend, tetapi diverifikasi backend lewat Midtrans.
- Tekankan keamanan: password hash, role middleware, permission admin, audit log, dan API key tidak bocor ke frontend.
- Tekankan nilai tambah: AI insight tetap berjalan walau API eksternal belum aktif karena ada fallback lokal.
