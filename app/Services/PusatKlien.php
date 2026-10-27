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
                Sambungan::tarikAman(); // Website tidak menerima webhook: ambil sambungan paling lama tiap jam
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
    public static function segarkan(): bool
    {
        foreach (['ringkasan', 'jurusan'] as $n) {
            Cache::forget("pusat.$n");
        }
        $ok = self::ringkasan() !== null && self::jurusan() !== null;

        return $ok && Cache::get('pusat.sinkron_terakhir') !== null;
    }
}
