# Integrasi dengan Pusat Sekolah

Website ini bisa tersambung ke aplikasi **Pusat Sekolah**. Semua fitur **mati secara bawaan** (`PUSAT_ENABLED=false`); website tetap berjalan normal tanpa Pusat.

## Yang didapat
- **Statistik beranda otomatis**: jumlah siswa aktif, guru + staf dari Pusat. Bila Pusat tidak terjangkau, dipakai salinan terakhir (maks. 7 hari), lalu angka manual di Pengaturan.
- **Login admin lewat Pusat (SSO)**: hanya akun dengan peran `admin` di aplikasi "web" yang menjadi admin website. Login lokal tetap ada.
- **Pencabutan cepat**: webhook `akun.diperbarui` / `akses.diperbarui` mencabut `is_admin` bila akun dinonaktifkan atau peran admin dicabut.
- **Dashboard**: kartu peringatan bila ada jurusan di Pusat yang belum ada di Program Keahlian (dicocokkan lewat nama).
- Halaman Guru & Staf tetap lokal (tidak diubah).

## Pemasangan
1. Di server Pusat:
   `php artisan pusat:aplikasi-baru web --base-url=https://WEBSITE --redirect=https://WEBSITE/pusat/callback --webhook=https://WEBSITE/pusat/webhook`
   (catat client_id, client_secret, webhook secret).
2. Di website: `php artisan migrate`.
3. Isi `.env`: `PUSAT_ENABLED=true`, `PUSAT_URL`, `PUSAT_CLIENT_ID`, `PUSAT_CLIENT_SECRET`, `PUSAT_WEBHOOK_SECRET`; bila di belakang proxy isi `TRUSTED_PROXIES`.
4. `php artisan config:clear` lalu coba tombol "Masuk lewat Pusat" di halaman login admin.

## Penautan akun
Login pertama ditautkan lewat **email** (harus sama dengan email admin lokal); setelah itu lewat `pusat_uuid`. Email yang sudah terikat ke akun Pusat lain ditolak.
