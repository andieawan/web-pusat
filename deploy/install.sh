#!/usr/bin/env bash
# Instalasi website SMKS Islam Bustanul Ulum di VM Ubuntu Server 24.04 / Debian 13 (jalankan sebagai root).
#
#   curl -fsSL https://raw.githubusercontent.com/andieawan/web-smkibu-laravel/main/deploy/install.sh -o install.sh
#   sudo DOMAIN=smkibupakusari.sch.id bash install.sh
#
# Variabel opsional (awali perintah dengan NAMA=nilai):
#   DOMAIN         nama domain website (default: _ = melayani akses lewat IP juga)
#   APP_URL        alamat publik lengkap, mis. https://smkibupakusari.sch.id (default: http://$DOMAIN, atau http://IP-VM)
#   PROXY_IP       IP reverse proxy (NPMplus) yang dipercaya; sebaiknya satu IP persis (default 192.168.10.0/24)
#   APP_DIR        folder instalasi (default /var/www/web-smkibu-laravel)
#   REPO           URL git (default repo resmi)
#   SKIP_DB_INSTALL=1  jangan pasang MariaDB (pakai server MySQL/MariaDB yang sudah ada)
#   DB_HOST/DB_NAME/DB_USER  (default 127.0.0.1 / smk_ibu / smk_ibu)
#   DB_ADMIN_CMD   perintah klien SQL berhak admin (default: mysql)
set -euo pipefail

DOMAIN="${DOMAIN:-_}"
IP="$(hostname -I 2>/dev/null | awk '{print $1}')"
if [ "$DOMAIN" = "_" ]; then APP_URL="${APP_URL:-http://${IP:-localhost}}"; else APP_URL="${APP_URL:-http://$DOMAIN}"; fi
PROXY_IP="${PROXY_IP:-192.168.10.0/24}"
APP_DIR="${APP_DIR:-/var/www/web-smkibu-laravel}"
REPO="${REPO:-https://github.com/andieawan/web-smkibu-laravel.git}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_NAME="${DB_NAME:-smk_ibu}"
DB_USER="${DB_USER:-smk_ibu}"
DB_ADMIN_CMD="${DB_ADMIN_CMD:-mysql}"
SKIP_DB_INSTALL="${SKIP_DB_INSTALL:-0}"

info() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
gagal() { printf '\n\033[1;31mGAGAL: %s\033[0m\n' "$*" >&2; exit 1; }
sandi() { openssl rand -hex $(( ${1:-20} / 2 )); }

[ "$(id -u)" -eq 0 ] || gagal "Jalankan sebagai root (sudo bash install.sh)."
command -v apt-get >/dev/null || gagal "Skrip ini untuk Debian/Ubuntu (butuh apt)."
export DEBIAN_FRONTEND=noninteractive

# ---------------------------------------------------------------- 1. Paket
info "Memasang paket (nginx, PHP, git, composer)"
apt-get update -y
PAKET=(nginx git unzip curl openssl ca-certificates composer
       php-fpm php-cli php-mysql php-mbstring php-xml php-curl php-zip php-gd php-bcmath php-intl)
[ "$SKIP_DB_INSTALL" = "1" ] || PAKET+=(mariadb-server)
apt-get install -y "${PAKET[@]}"

PHPV="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' \
    || gagal "PHP $PHPV terlalu lama; Laravel 13 butuh PHP 8.3+. Pakai Ubuntu 24.04 / Debian 13, atau pasang PHP 8.3 dari repo ondrej/sury."
SOCK="/run/php/php${PHPV}-fpm.sock"
info "PHP $PHPV terdeteksi"

# ---------------------------------------------------------------- 2. PHP ini
info "Mengatur batas unggah foto (10 MB)"
cat > "/etc/php/${PHPV}/fpm/conf.d/99-smkibu.ini" <<INI
upload_max_filesize = 10M
post_max_size = 12M
expose_php = Off
INI
systemctl enable --now "php${PHPV}-fpm"
systemctl restart "php${PHPV}-fpm"

# ---------------------------------------------------------------- 3. Kode
info "Mengambil kode dari GitHub"
if [ -d "$APP_DIR/.git" ]; then
    git -C "$APP_DIR" pull --ff-only
else
    [ ! -e "$APP_DIR" ] || [ -z "$(ls -A "$APP_DIR")" ] || gagal "$APP_DIR sudah ada dan bukan repo git."
    mkdir -p "$(dirname "$APP_DIR")"
    git clone "$REPO" "$APP_DIR"
fi
cd "$APP_DIR"

info "Memasang dependensi Composer (tanpa paket dev)"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

# Hanya storage & bootstrap/cache yang boleh ditulis web server; kode tetap milik root.
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

# ---------------------------------------------------------------- 4. Database + .env
if [ -f .env ]; then
    info ".env sudah ada: database dan .env tidak diubah"
    NEW_INSTALL=0
else
    NEW_INSTALL=1
    DB_PASS="$(sandi 24)"
    ADMIN_PASS="$(sandi 16)"

    info "Membuat database '$DB_NAME' dan user '$DB_USER'"
    $DB_ADMIN_CMD <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

    info "Menulis .env"
    cp .env.example .env
    sed -i \
        -e "s|^APP_URL=.*|APP_URL=${APP_URL}|" \
        -e "s|^DB_HOST=.*|DB_HOST=${DB_HOST}|" \
        -e "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" \
        -e "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" \
        -e "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" \
        -e "s|^ADMIN_PASSWORD=.*|ADMIN_PASSWORD=|" .env
    case "$APP_URL" in https://*) sed -i 's|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|' .env ;; esac
    php artisan key:generate --force
    chown root:www-data .env
    chmod 640 .env
fi

artisan() { runuser -u www-data -- php "$APP_DIR/artisan" "$@"; }

if [ "$NEW_INSTALL" = "1" ]; then
    info "Migrasi database + data awal"
    # Password admin acak akan dicetak sekali oleh seeder (ADMIN_PASSWORD di .env sengaja kosong).
    SEED_LOG="$(mktemp)"
    artisan migrate --seed --force 2>&1 | tee "$SEED_LOG"
else
    artisan migrate --force
fi
# Symlink dibuat sebagai root (public/ milik root; www-data tidak boleh menulis di sana).
ln -sfn "$APP_DIR/storage/app/public" "$APP_DIR/public/storage"

info "Cache konfigurasi"
artisan config:cache
artisan route:cache
artisan view:cache

# ---------------------------------------------------------------- 5. Nginx
info "Menyiapkan Nginx"
cat > /etc/nginx/conf.d/smkibu-map.conf <<'CONF'
# Teruskan status HTTPS dari reverse proxy (NPMplus) ke PHP.
map $http_x_forwarded_proto $smkibu_https { default off; https on; }
CONF

LISTEN="listen 80;"
[ "$DOMAIN" = "_" ] && LISTEN="listen 80 default_server;"

cat > /etc/nginx/sites-available/smkibu <<CONF
server {
    $LISTEN
    server_name $DOMAIN;
    root $APP_DIR/public;
    index index.php;
    charset utf-8;
    client_max_body_size 10M;

    # Alamat asli pengunjung dari reverse proxy (hanya dipercaya dari PROXY_IP).
    set_real_ip_from $PROXY_IP;
    real_ip_header X-Forwarded-For;
    real_ip_recursive on;

    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }

    location ~ ^/index\.php(/|\$) {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$SOCK;
        fastcgi_param HTTPS \$smkibu_https;
    }
    location ~ \.php\$ { return 404; }

    location ~ /\.(?!well-known).* { deny all; }
    location ~* ^/(storage|vendor/bootstrap-icons|vendor/fonts|css|images)/.*\.(jpg|jpeg|png|webp|gif|css|woff2?|svg|ico)\$ {
        expires 30d;
        access_log off;
    }
}
CONF
ln -sf /etc/nginx/sites-available/smkibu /etc/nginx/sites-enabled/smkibu
[ "$DOMAIN" = "_" ] && rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable --now nginx
systemctl reload nginx

# ---------------------------------------------------------------- 6. Selesai
chown -R www-data:www-data storage bootstrap/cache
info "Selesai"
cat <<RINGKAS

  Website  : http://${IP}/   (atau ${APP_URL} setelah reverse proxy & DNS siap)
  Admin    : ${APP_URL}/login
  Folder   : ${APP_DIR}
RINGKAS
if [ "${NEW_INSTALL}" = "1" ]; then
    printf '  Database : %s / user %s (password ada di %s/.env)\n' "$DB_NAME" "$DB_USER" "$APP_DIR"
    printf '\n  Kredensial admin (dicetak seeder di atas; CATAT SEKARANG, tidak tampil lagi):\n'
    grep -E 'Email:|Password:' "$SEED_LOG" | sed 's/^/    /' || true
    rm -f "$SEED_LOG"
fi
cat <<LANJUT

  Berikutnya:
   1. Unggah logo.png, gedung.jpg, hero.jpg ke ${APP_DIR}/public/images/
   2. Atur reverse proxy (NPMplus) + DNS Cloudflare  -> lihat deploy/PANDUAN-VM.md
   3. Login admin, lalu isi Pengaturan Situs & Banner Hero
LANJUT
