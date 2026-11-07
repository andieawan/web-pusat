<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/** Akses ke Pusat Sekolah. Semua pembacaan data publik aman-gagal: website tetap tampil bila Pusat mati. */
class PusatKlien
{
    public static function aktif(): bool
    {
        return config('pusat.enabled') && config('pusat.url') !== '' && config('pusat.client_id') !== '' && config('pusat.client_secret') !== '';
    }

    /** Migrasi kolom penaut sudah dijalankan. */
    public static function siap(): bool
    {
        return Schema::hasColumn('users', 'pusat_uuid');
    }

    /** Cek cepat Pusat hidup (dipakai sebelum mengarahkan ke login Pusat). */
    public static function hidup(): bool
    {
        try {
            return self::http()->timeout(4)->connectTimeout(3)->get('/api/v1/ping')->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /** Akun admin website dari Pusat (izin "akun") + salinan hash untuk login cadangan (izin "akun_sandi"). */
    public static function sinkronAkun(): void
    {
        try {
            $res = self::http()->get('/api/v1/akun', ['per_page' => 500]);
            if ($res->successful()) {
                foreach ((array) $res->json('data', []) as $a) {
                    PusatAkun::terapkanDariPusat((array) $a);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function http(): PendingRequest
    {
        return Http::baseUrl(config('pusat.url'))
            ->withOptions(['verify' => config('pusat.ca_bundle') ?: (bool) config('pusat.verify_ssl')])
            ->withBasicAuth(config('pusat.client_id'), config('pusat.client_secret'))
            ->acceptJson()
            ->connectTimeout(config('pusat.timeout'))
            ->timeout(config('pusat.timeout'));
    }

    /**
     * Angka agregat (tanpa data pribadi). Di-cache; bila Pusat gagal dipakai salinan terakhir yang berhasil
     * (maks. 7 hari), dan bila belum pernah berhasil: null (pemanggil memakai angka manual).
     */
    public static function ringkasan(): ?array
    {
        return self::ambil('ringkasan', '/api/v1/ringkasan');
    }

    /** @return array<int,array{kode:string,nama:string}>|null */
    public static function jurusan(): ?array
    {
        $baris = self::ambil('jurusan', '/api/v1/master/jurusan?per_page=200');

        return $baris === null ? null : array_values(array_filter($baris, fn ($j) => empty($j['dihapus']) && ($j['aktif'] ?? true)));
    }

    /** Guru & staf (cakupan pegawai_dasar: tanpa HP/email). Salinan baru langsung diterapkan ke halaman Guru & Staf. */
    public static function pegawai(): ?array
    {
        return self::ambil('pegawai', '/api/v1/master/pegawai?per_page=500');
    }

    private static function ambil(string $nama, string $path): ?array
    {
        if (! self::aktif()) {
            return null;
        }
        $segar = "pusat.$nama";
        $basi = "pusat.$nama.basi";
        if (Cache::has($segar)) {
            return Cache::get($segar);
        }
        try {
            $res = self::http()->get($path);
            $data = $res->successful() ? $res->json('data') : null;
        } catch (\Throwable) {
            $data = null;
        }
        if (is_array($data)) {
            if (Cache::add('pusat.sambungan_ditarik', 1, 3600)) {
                Sambungan::tarikAman(); // cadangan bila webhook terlewat: paling lama tiap jam
                ProfilPusat::tarikAman();
            }
            try {
                match ($nama) {
                    'jurusan' => JurusanSync::terapkan($data), // jurusan baru di Pusat langsung jadi Program Keahlian
                    'pegawai' => StafSync::terapkan($data),    // guru & staf baru langsung tampil di halaman Guru & Staf
                    default => null,
                };
            } catch (\Throwable $e) {
                report($e);
            }
            Cache::put($segar, $data, config('pusat.cache_detik'));
            Cache::put($basi, $data, now()->addDays(7));
            Cache::put('pusat.sinkron_terakhir', now('UTC')->format('Y-m-d H:i:s'), now()->addDays(30));

            return $data;
        }
        // Gagal: jangan mencoba lagi tiap request (beranda jadi lambat) — simpan salinan lama sebentar.
        $lama = Cache::get($basi);
        Cache::put($segar, $lama, 30);

        return $lama;
    }

    /** Buang salinan sementara lalu ambil ulang ringkasan & jurusan dari Pusat (tombol "Sinkron sekarang"). */
    public static function segarkan(?string $pemicu = null): bool
    {
        foreach (['ringkasan', 'jurusan', 'pegawai'] as $n) {
            Cache::forget("pusat.$n");
        }
        $ok = self::ringkasan() !== null && self::jurusan() !== null;
        self::pegawai(); // opsional: butuh izin pegawai_dasar di Pusat
        self::sinkronAkun(); // akun admin & login cadangan

        $ok = $ok && Cache::get('pusat.sinkron_terakhir') !== null;
        self::lapor($ok, [], $ok ? '' : 'Ringkasan/jurusan dari Pusat tidak bisa diambil.', $pemicu);

        return $ok;
    }

    /**
     * Lapor hasil sinkron ke Pusat (kolom "Sinkron terakhir" di menu Aplikasi Pusat + notifikasi admin).
     * Tidak pernah menggagalkan sinkron; Pusat versi lama yang belum punya jalurnya cukup membalas 404.
     */
    public static function lapor(bool $ok, array $hasil = [], string $galat = '', ?string $pemicu = null): void
    {
        try {
            self::http()->timeout(5)->post('/api/v1/sinkron/laporan', [
                'ok' => $ok,
                'pemicu' => $pemicu ?? (app()->runningInConsole() ? 'otomatis' : 'manual'),
                'hasil' => $hasil,
                'galat' => mb_substr($galat, 0, 1000),
            ]);
        } catch (\Throwable) {
        }
    }
}
