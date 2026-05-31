# Deploy SyaJagad ke Vercel + Neon

Panduan ini untuk Tahap 1: Laravel full-stack tetap berjalan sebagai satu aplikasi di Vercel, database memakai Neon PostgreSQL.

## 1. Buat Database Neon

1. Buat project baru di Neon.
2. Ambil connection string PostgreSQL, gunakan yang **pooled** jika tersedia.
3. Simpan connection string itu untuk env `DATABASE_URL`.

## 2. Buat APP_KEY

Di lokal:

```bash
php artisan key:generate --show
```

Salin hasil `base64:...` ke env `APP_KEY` di Vercel.

## 3. Import Project ke Vercel

1. Pilih repo GitHub.
2. Set **Root Directory** ke:

```text
backend
```

3. Framework preset: `Other`.
4. Build Command: kosongkan/default. Script Composer `vercel` akan menjalankan `npm install` dan `npm run build`.
5. Deploy akan memakai `backend/vercel.json`.

> Penting: jangan pilih root repo sebagai Root Directory. Root repo berisi file lama/duplikat dan `package.json` Next.js, sedangkan aplikasi production ada di folder `backend`.

## 4. Environment Variables Vercel

Gunakan isi contoh dari:

```text
deploy/env.vercel-neon.example
```

Minimal wajib:

```env
APP_NAME=SyaJagad
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://nama-project.vercel.app

DB_CONNECTION=pgsql
DATABASE_URL=postgresql://...
DB_SSLMODE=require

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync

LOG_CHANNEL=stderr
VIEW_COMPILED_PATH=/tmp/syajagad/framework/views
```

Untuk Midtrans sandbox:

```env
MIDTRANS_SERVER_KEY=...
MIDTRANS_CLIENT_KEY=...
MIDTRANS_IS_PRODUCTION=false
```

## 5. Jalankan Migration ke Neon

Cara paling aman untuk tahap gratis: jalankan migration dari lokal dengan env Neon.

PowerShell:

```powershell
$env:APP_ENV="production"
$env:DB_CONNECTION="pgsql"
$env:DATABASE_URL="postgresql://..."
$env:DB_SSLMODE="require"
php artisan migrate --force
php artisan db:seed --force
```

Jika `php` tidak ada di PATH Laragon:

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan migrate --force
```

Untuk proyek ini di Windows Laragon, command validasi yang sudah dipakai:

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe vendor\bin\phpunit --colors=never
npm run build
```

## 6. Push ke GitHub

Pastikan yang masuk GitHub adalah root repo ini, tetapi Vercel tetap diarahkan ke folder `backend`.

```powershell
git status
git add .gitignore backend/api/index.php backend/.env.example backend/deploy/env.vercel-neon.example backend/DEPLOY_VERCEL_NEON.md
git commit -m "Prepare Laravel backend for Vercel deployment"
git push origin main
```

## 7. Setelah Deploy

Tes URL:

```text
https://nama-project.vercel.app
https://nama-project.vercel.app/login
https://nama-project.vercel.app/register
```

Midtrans notification URL:

```text
https://nama-project.vercel.app/payment/notification
```

## Catatan Batasan

- Vercel untuk Laravel memakai community PHP runtime, cukup untuk demo/portfolio/tahap awal.
- File upload permanen jangan simpan ke storage lokal Vercel. Gunakan Cloudinary/S3/Supabase Storage jika nanti butuh upload.
- Session dan cache sudah diarahkan ke database supaya cocok dengan serverless.
- Jika deploy gagal karena database, cek lagi `DATABASE_URL`, `DB_CONNECTION=pgsql`, `DB_SSLMODE=require`, dan pastikan migration Neon sudah dijalankan.
