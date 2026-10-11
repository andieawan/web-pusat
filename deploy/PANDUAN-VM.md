# Panduan Instalasi di VM Proxmox

Target: VM **Ubuntu Server 24.04 LTS** (atau Debian 13) di Proxmox, diakses publik lewat
reverse proxy NPMplus. Skrip `deploy/install.sh` mengerjakan hampir semuanya (Nginx, PHP 8.3,
MariaDB, Composer, database, `.env`, migrasi, konfigurasi Nginx).

## 1. Buat VM di Proxmox

1. Unggah ISO *Ubuntu Server 24.04 LTS* ke storage ISO (mis. HDD-BACKUP) lewat **Storage → ISO Images**.
2. **Create VM**: OS = ISO tadi, Machine `q35`, centang **Qemu Agent**, disk **20 GB** di SSD-PVE,
   **2 vCPU**, **2 GB RAM** (cukup untuk website ini), network `vmbr0`.
3. Jalankan VM, pasang Ubuntu. Centang **Install OpenSSH server**. Saran nama host: `web-smkibu`.
4. Setelah selesai, login dan pasang agent + IP tetap:
   ```bash
   sudo apt update && sudo apt install -y qemu-guest-agent && sudo systemctl enable --now qemu-guest-agent
   ```
   IP statis (contoh `192.168.10.20`; sesuaikan), edit `/etc/netplan/50-cloud-init.yaml`
   (nama file bisa berbeda; `ls /etc/netplan`):
   ```yaml
   network:
     version: 2
     ethernets:
       ens18:                 # cek nama dengan: ip a
         dhcp4: false
         addresses: [192.168.10.20/24]
         routes:
           - to: default
             via: 192.168.10.254
         nameservers:
           addresses: [192.168.10.254, 1.1.1.1]
   ```
   lalu `sudo netplan apply`.

## 2. Jalankan skrip instalasi

Di VM (Ubuntu 26.04 memakai `sudo-rs`, jadi masuk dulu ke shell root agar variabel terbaca):

```bash
sudo -i
curl -fsSL https://raw.githubusercontent.com/andieawan/web-smkibu-laravel/main/deploy/install.sh -o install.sh
DOMAIN=smkibupakusari.sch.id APP_URL=https://smkibupakusari.sch.id PROXY_IP=192.168.10.10 bash install.sh
```

- Ganti `DOMAIN`/`APP_URL` dengan alamat yang akan dipakai website ini (kalau server menjalankan beberapa
  aplikasi, tiap aplikasi sebaiknya punya domain/subdomain sendiri).
- `PROXY_IP` = IP VM/container NPMplus (satu IP persis, bukan seluruh LAN).
- Mau coba dulu lewat IP tanpa domain? Jalankan `sudo bash install.sh` saja, lalu buka `http://<IP-VM>/`.
  Kalau nanti pindah ke domain, ubah `APP_URL` di `/var/www/web-smkibu-laravel/.env`, lalu
  `sudo -u www-data php artisan config:cache`, dan ubah `server_name` di `/etc/nginx/sites-available/smkibu`.
- **Catat email dan password admin** yang dicetak di akhir (hanya tampil sekali).
  Skrip aman dijalankan ulang: database dan `.env` yang sudah ada tidak ditimpa.

## 3. Unggah gambar tetap

Dari komputer Anda (Windows PowerShell / terminal):

```bash
scp logo.png gedung.jpg hero.jpg <user>@<IP-VM>:/tmp/
ssh <user>@<IP-VM> "sudo mv /tmp/{logo.png,gedung.jpg,hero.jpg} /var/www/web-smkibu-laravel/public/images/"
```

(Foto berita, galeri, dan banner diunggah dari panel admin.)

## 4. Publikasikan lewat NPMplus + Cloudflare

1. **MikroTik**: pastikan port 80 dan 443 dari IP publik (`45.158.10.5`) diteruskan (dst-nat) ke NPMplus.
2. **Cloudflare DNS**: record `A` → `45.158.10.5` untuk `smkibupakusari.sch.id` (dan `www` bila perlu).
   Awalnya set **DNS only** (awan abu-abu) supaya sertifikat Let's Encrypt mudah terbit.
3. **NPMplus → Proxy Hosts → Add**:
   - Domain Names: `smkibupakusari.sch.id`
   - Scheme `http`, Forward Hostname/IP = IP VM (mis. `192.168.10.20`), Port `80`
   - Aktifkan *Block Common Exploits*
   - Tab **SSL**: Request a new certificate, aktifkan *Force SSL* dan *HTTP/2*
4. Buka `https://smkibupakusari.sch.id`. Kalau nanti memakai proxy Cloudflare (awan oranye), set SSL/TLS mode
   ke **Full (strict)**.

## 5. Setelah terpasang

- Login `https://<domain>/login`, buka **Pengaturan Situs** (alamat, telepon, link media sosial, angka statistik)
  dan **Banner Hero**, lalu tambah berita/pengumuman/agenda.
- Ganti password admin kapan saja:
  ```bash
  cd /var/www/web-smkibu-laravel
  sudo -u www-data php artisan tinker
  >>> App\Models\User::where('is_admin', true)->first()->update(['password' => 'PASSWORD_BARU']);
  ```

## 6. Pembaruan dan cadangan

```bash
# Perbarui ke versi terbaru GitHub (otomatis maintenance mode, migrasi, cache)
sudo bash /var/www/web-smkibu-laravel/deploy/update.sh

# Cadangan harian pukul 01:30 (database + foto), simpan 14 hari di /var/backups/smkibu
echo '30 1 * * * root /var/www/web-smkibu-laravel/deploy/backup.sh' | sudo tee /etc/cron.d/smkibu-backup
```

Selain itu, buat jadwal **Datacenter → Backup** di Proxmox untuk VM ini (storage HDD-BACKUP, harian,
simpan beberapa versi). Pulihkan database dengan:
`gunzip < db_TANGGAL.sql.gz | mysql -u smk_ibu -p smk_ibu` (password ada di `.env`).

## Pemecahan masalah

| Gejala | Periksa |
|---|---|
| Halaman 500 | `sudo tail -50 /var/www/web-smkibu-laravel/storage/logs/laravel.log` dan `sudo tail /var/log/nginx/error.log` |
| 502 Bad Gateway | `systemctl status php8.5-fpm` (Ubuntu 26.04 memakai PHP 8.5; Ubuntu 24.04 memakai 8.3) |
| Foto/ikon tidak tampil | `APP_URL` di `.env` harus sama dengan alamat yang dibuka; `ls -l public/storage` (symlink); setelah ubah `.env` jalankan `php artisan config:cache` |
| Unggah foto gagal (413) | batas unggah: `/etc/php/*/fpm/conf.d/99-smkibu.ini` dan `client_max_body_size` di Nginx (sudah 10 MB) |
| Setelah login pindah ke `http://` | pastikan NPMplus meneruskan header `X-Forwarded-Proto` (bawaan aktif) dan `PROXY_IP` benar |
| Sertifikat Let's Encrypt gagal | DNS sudah menunjuk ke IP publik, port 80 terbuka dan sampai ke NPMplus, Cloudflare dalam mode DNS only |
