# Deploy SyaJagad ke Ubuntu VPS

Dokumen ini dipakai untuk membuat SyaJagad online di internet dengan Nginx, PHP 8.3, MySQL/MariaDB, Midtrans, dan OpenAI.

## 1. Kebutuhan

- VPS Ubuntu 22.04/24.04
- Domain/subdomain yang sudah mengarah ke IP VPS
- Akses SSH root atau sudo
- Repository/ZIP project SyaJagad
- Midtrans sandbox key
- OpenAI API key jika ingin AI eksternal aktif

## 2. Upload Project

Contoh via Git:

```bash
cd /var/www
git clone URL_REPO_KAMU syajagad
cd /var/www/syajagad/backend
```

Contoh via ZIP:

```bash
cd /var/www
unzip syajagad.zip -d syajagad
cd /var/www/syajagad/backend
```

## 3. Siapkan `.env`

```bash
cp deploy/env.production.example .env
nano .env
```

Ubah minimal:

```env
APP_URL=https://domain-kamu.com
DB_DATABASE=syajagad
DB_USERNAME=syajagad_user
DB_PASSWORD=password_database_yang_kuat
MIDTRANS_SERVER_KEY=...
MIDTRANS_CLIENT_KEY=...
OPENAI_API_KEY=...
```

## 4. Jalankan Setup Server

```bash
sudo bash deploy/ubuntu-setup.sh domain-kamu.com syajagad syajagad_user 'password_database_yang_kuat' /var/www/syajagad/backend
```

Script akan menginstall Nginx, MySQL, PHP 8.3, Composer, Node.js, dependency Laravel, build frontend, migrate database, seed akun demo, dan membuat config Nginx.

## 5. SSL HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d domain-kamu.com -d www.domain-kamu.com
```

## 6. Midtrans Webhook

Di dashboard Midtrans, set Payment Notification URL:

```text
https://domain-kamu.com/payment/notification
```

Untuk demo/lomba gunakan sandbox:

```env
MIDTRANS_IS_PRODUCTION=false
```

## 7. Akun Demo

Admin:

```text
Email: admin@syajagad.local
Password: AdminJagad2026!
Role: Admin
```

Santri:

```text
NIS/NIP: 24 000 001
Password: Jagad001
Role: Santri
```

## 8. Checklist Setelah Deploy

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan test tests/Feature --stop-on-failure
```

Cek di browser:

- `https://domain-kamu.com`
- Login admin
- Login santri
- Export laporan
- AI insight
- Midtrans sandbox checkout

## 9. Troubleshooting

Permission error:

```bash
sudo chown -R www-data:www-data /var/www/syajagad/backend
sudo chmod -R 775 /var/www/syajagad/backend/storage /var/www/syajagad/backend/bootstrap/cache
```

Nginx error:

```bash
sudo nginx -t
sudo systemctl reload nginx
sudo tail -f /var/log/nginx/error.log
```

Laravel error:

```bash
tail -f storage/logs/laravel.log
```
