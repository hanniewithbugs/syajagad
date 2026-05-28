#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${1:-}"
DB_NAME="${2:-syajagad}"
DB_USER="${3:-syajagad_user}"
DB_PASS="${4:-}"
PROJECT_DIR="${5:-/var/www/syajagad/backend}"

if [[ -z "$DOMAIN" || -z "$DB_PASS" ]]; then
  echo "Usage: sudo bash deploy/ubuntu-setup.sh domain.com syajagad syajagad_user 'password-kuat' /var/www/syajagad/backend"
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive

apt update
apt install -y software-properties-common ca-certificates lsb-release apt-transport-https curl unzip git nginx mysql-server
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd

if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer
fi

if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
  apt install -y nodejs
fi

mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

if [[ ! -d "$PROJECT_DIR" ]]; then
  echo "Project directory not found: $PROJECT_DIR"
  echo "Upload or git clone the project first, then rerun this script."
  exit 1
fi

cd "$PROJECT_DIR"
composer install --no-dev --optimize-autoloader
npm install
npm run build

php artisan key:generate --force || true
php artisan migrate --force
php artisan db:seed --class=InvoiceSeeder --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data "$PROJECT_DIR"
chmod -R 775 "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"

cat > /etc/nginx/sites-available/syajagad <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} www.${DOMAIN};

    root ${PROJECT_DIR}/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/syajagad /etc/nginx/sites-enabled/syajagad
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

echo "Server base setup complete."
echo "Next: install SSL with:"
echo "sudo apt install -y certbot python3-certbot-nginx"
echo "sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
