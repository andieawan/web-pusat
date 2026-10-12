# Mencoba Website SMK di komputer pribadi (Windows)

Pasang **Pusat** lebih dulu dan siapkan alat (Laragon, Git) mengikuti `docs/PENYIAPAN-LOKAL.md` di repo `pusat-sekolah`. Aplikasi ini dijalankan di **port 8002**; semua perintah di Terminal Laragon, satu per satu.
Selalu pakai alamat `http://127.0.0.1:8002` (bukan `localhost`).

## 1. Ambil kode
```bash
cd C:\laragon\www
git clone https://github.com/andieawan/web-pusat.git
cd web-pusat
composer install
```
(Repo privat: *Password* = token GitHub, lihat panduan VM.)

## 2. Database & `.env`
```bash
mysql -u root -e "CREATE DATABASE smk_ibu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
copy .env.example .env
php artisan key:generate
notepad .env
```
Ubah:
```ini
APP_URL=http://127.0.0.1:8002
DB_DATABASE=smk_ibu
DB_USERNAME=root
DB_PASSWORD=
ADMIN_EMAIL=admin@smkibu.sch.id
ADMIN_PASSWORD=GantiDenganPasswordKuat
SESSION_SECURE_COOKIE=false
SESSION_COOKIE=web_session
TRUSTED_PROXIES=
```
`SESSION_COOKIE` harus beda dari aplikasi lain (cookie tidak membedakan port).

## 3. Tabel & akun admin
```bash
php artisan migrate --seed
php artisan storage:link
php artisan db:seed --class=DemoSeeder   # opsional: berita/agenda contoh
```

## 4. Jalankan
```bash
php artisan serve --port=8002
```
Buka http://127.0.0.1:8002/login (admin) atau http://127.0.0.1:8002/ (beranda)

## 5. Sambungkan ke Pusat (opsional)
1. Di folder Pusat: `php artisan pusat:aplikasi-baru web --base-url=http://127.0.0.1:8002 --redirect=http://127.0.0.1:8002/pusat/callback --webhook=http://127.0.0.1:8002/pusat/webhook`
2. Isi `.env` Website, lalu `php artisan migrate` dan `php artisan config:clear`:
```ini
PUSAT_ENABLED=true
PUSAT_URL=http://127.0.0.1:8000
PUSAT_CLIENT_ID=...
PUSAT_CLIENT_SECRET=...
PUSAT_WEBHOOK_SECRET=...
```
3. Email admin di Pusat harus sama dengan `ADMIN_EMAIL` (penautan pertama lewat email) dan akun itu harus punya peran **admin** untuk aplikasi "web". Uji tombol "Masuk lewat Pusat" di `/login`.

Masalah umum (database, cookie, redirect_uri, port): lihat bagian terakhir `docs/PENYIAPAN-LOKAL.md` di repo `pusat-sekolah`.
