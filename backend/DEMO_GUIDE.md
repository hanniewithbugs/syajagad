# Panduan Demo Lomba SyaJagad

## Alur Demo 5-7 Menit

1. Buka landing page di `http://127.0.0.1:8000`.
2. Login sebagai admin:
   - Email: `admin@syajagad.local`
   - Password: `AdminJagad2026!`
   - Role: Admin
3. Tunjukkan dashboard admin:
   - total santri
   - pembayaran terbaru
   - insight risiko pembayaran
4. Buka menu Data Santri:
   - cari santri berdasarkan nama/NIS
   - buka detail invoice
   - buat tagihan baru
5. Buka Pengaturan:
   - permission admin
   - audit log
6. Buka Laporan:
   - klik CSV atau Excel
   - klik PDF untuk membuka halaman cetak/simpan PDF
7. Logout lalu login sebagai santri:
   - NIS/NIP: `24 000 001`
   - Password: `Jagad001`
   - Role: Santri
8. Tunjukkan dashboard santri:
   - tagihan aktif
   - prediksi AI tagihan
   - notifikasi
   - popup pembayaran Midtrans

## Narasi Singkat

SyaJagad adalah sistem pembayaran SPP santri multi-user berbasis Laravel. Admin bisa mengelola santri dan tagihan, santri bisa login memakai NIS/NIP, melakukan pembayaran digital melalui Midtrans, dan menerima notifikasi. Sistem dilengkapi audit log, permission admin, export laporan, serta insight AI untuk prediksi risiko pembayaran.

## Poin Teknis Untuk Juri

- Backend Laravel 13 dengan Blade dan JavaScript.
- Database relasional melalui migration Laravel.
- Integrasi Midtrans Snap untuk QRIS dan Virtual Account.
- OpenAI Responses API untuk insight tagihan, dengan fallback lokal.
- Role-based access dan permission admin.
- Audit log untuk aktivitas penting.
- Feature test otomatis menjaga alur utama tetap stabil.

## Checklist Sebelum Demo

- Jalankan `php artisan optimize:clear`.
- Pastikan server aktif di `http://127.0.0.1:8000`.
- Pastikan `.env` berisi Midtrans sandbox key.
- Isi `OPENAI_API_KEY` jika ingin demo insight dari OpenAI asli.
- Siapkan file kredensial santri jika juri ingin melihat data login.
