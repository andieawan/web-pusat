# Website SMKS Islam Bustanul Ulum Pakusari - Jember

Laravel 13 + MySQL. Beranda (dengan banner geser), berita + pencarian, pengumuman, agenda, galeri,
dan panel admin untuk mengelola semuanya (berita, pengumuman, agenda, galeri, program keahlian,
banner hero, pengaturan situs). Font dan ikon sudah lokal, jadi tidak butuh internet saat dibuka.

Butuh: **PHP 8.3+** (ext: mbstring, xml, curl, zip, mysql/pdo_mysql, gd, bcmath, intl, fileinfo), Composer 2, MySQL/MariaDB.
Node.js **tidak diperlukan** (tidak ada build frontend).

## Instalasi (VM / server)

> Sambung ke Pusat Sekolah (opsional): lihat [docs/PUSAT.md](docs/PUSAT.md) dan langkah 5b di PANDUAN-VM.

> **Cara cepat untuk VM Ubuntu/Debian:** jalankan `deploy/install.sh` (otomatis). Panduan lengkap
> Proxmox + NPMplus + Cloudflare ada di [`deploy/PANDUAN-VM.md`](deploy/PANDUAN-VM.md).
> Langkah manual di bawah ini untuk yang ingin memasang sendiri.

```bash
git clone https://github.com/andieawan/web-pusat.git
cd web-pusat

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Buat database dan user MySQL:

```sql
CREATE DATABASE smk_ibu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smk_ibu'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON smk_ibu.* TO 'smk_ibu'@'localhost';
FLUSH PRIVILEGES;
```

Edit `.env`: isi `DB_PASSWORD`, set `APP_URL` ke alamat sebenarnya. `.env.example` sudah
berisi `APP_ENV=production` dan `APP_DEBUG=false` (jangan diubah di server). Isi juga
`ADMIN_EMAIL` dan `ADMIN_PASSWORD` untuk akun admin pertama; kalau `ADMIN_PASSWORD` dikosongkan,
password acak dibuat dan **ditampilkan sekali** di layar saat seeding (catat!). Lalu:

```bash
php artisan migrate --seed --force   # admin + pengaturan awal + program keahlian (TANPA data contoh)
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Data contoh (berita, pengumuman, agenda palsu) hanya untuk pengembangan/demo:
`php artisan db:seed --class=DemoSeeder`. Jangan dijalankan di server sungguhan.

Izin folder (user web server, mis. `www-data`):

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rw storage bootstrap/cache
```

## Web server

Arahkan *document root* ke folder `public/`. Contoh Nginx:

```nginx
server {
    listen 80;
    server_name smkibu.example.sch.id;
    root /var/www/web-pusat/public;
    index index.php;
    client_max_body_size 10M;      # unggah foto sampai 8 MB

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;   # sesuaikan: ls /run/php/
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```

(Apache: aktifkan `mod_rewrite`; `public/.htaccess` sudah ada.)

Batas unggah PHP bawaan hanya 2 MB, sedangkan admin menerima foto sampai 8 MB (otomatis diperkecil
ke maks. 1600 px). Naikkan di `php.ini` (mis. `/etc/php/8.5/fpm/php.ini`), lalu restart php-fpm:

```ini
upload_max_filesize = 10M
post_max_size = 12M
```

## Admin

- Login: `/login`
- Akun awal: email dari `ADMIN_EMAIL`, password dari `ADMIN_PASSWORD` atau yang ditampilkan saat seeding.
- Hanya akun dengan `is_admin = true` yang bisa login dan membuka `/admin`.
- Ganti password kapan saja:
  ```bash
  php artisan tinker
  >>> App\Models\User::where('email', 'admin@smkibu.sch.id')->first()->update(['password' => 'PASSWORD_BARU_YANG_KUAT']);
  ```
  (password di-hash otomatis oleh model.)
- Kalau website diakses lewat HTTPS, set `SESSION_SECURE_COOKIE=true` di `.env`.

## Gambar

> `APP_URL` di `.env` harus sama dengan alamat yang dipakai membuka website (URL foto dibuat dari nilai ini).

Taruh di `public/images/`: `logo.png` (logo sekolah) dan `gedung.jpg` (foto "Tentang Kami"), serta
`hero.jpg` sebagai banner cadangan. Banner beranda sebenarnya diatur dari admin (**Banner Hero**,
bisa lebih dari satu, bergeser otomatis). Foto berita dan galeri juga diunggah dari admin
(disimpan di `storage/app/public/konten`).

## Pengembangan lokal

> Coba di Windows (Laragon) bersama Pusat: [docs/PENYIAPAN-LOKAL.md](docs/PENYIAPAN-LOKAL.md).

```bash
composer install
cp .env.example .env && php artisan key:generate
# di .env untuk lokal: APP_ENV=local, APP_DEBUG=true, isi DB_*, ADMIN_PASSWORD=<bebas>
php artisan migrate --seed
php artisan db:seed --class=DemoSeeder   # data contoh (opsional)
php artisan serve
php artisan test      # tes memakai SQLite in-memory
```

## Update di server

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## Belum dibuat

Halaman Profil, Akademik, Kesiswaan, dan PPDB (masih placeholder "sedang disiapkan").

## Integrasi Pusat Sekolah
Opsional (statistik otomatis + login admin SSO). Lihat [docs/PUSAT.md](docs/PUSAT.md).
