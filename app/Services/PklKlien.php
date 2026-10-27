<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** Daftar mitra DUDI dari aplikasi PKL. Aman-gagal: bila PKL mati, dipakai salinan terakhir (maks. 7 hari) atau tidak ditampilkan. */
class PklKlien
{
    public static function aktif(): bool
    {
        return config('pkl.url') !== '';
    }

    /** @return array{periode_aktif:?string, mitra:array, total_siswa_periode_ini:int}|null */
    public static function mitra(bool $segarkan = false): ?array
    {
        if (! self::aktif()) {
            return null;
        }
        if ($segarkan) {
            Cache::forget('pkl.mitra');
        }
        if (Cache::has('pkl.mitra')) {
            return Cache::get('pkl.mitra');
        }
        try {
            $res = Http::acceptJson()->timeout(config('pkl.timeout'))->connectTimeout(config('pkl.timeout'))->get(config('pkl.url').'/api/publik/mitra');
            $data = $res->successful() ? $res->json('data') : null;
        } catch (\Throwable) {
            $data = null;
        }
        if (is_array($data) && isset($data['mitra']) && is_array($data['mitra'])) {
            $data['mitra'] = array_values(array_filter(array_map(fn ($m) => is_array($m) && isset($m['nama'])
                ? ['nama' => mb_substr((string) $m['nama'], 0, 120), 'bidang' => mb_substr((string) ($m['bidang'] ?? ''), 0, 120),
                    'siswa_periode_ini' => (int) ($m['siswa_periode_ini'] ?? 0), 'siswa_total' => (int) ($m['siswa_total'] ?? 0)] : null, $data['mitra'])));
            Cache::put('pkl.mitra', $data, config('pkl.cache_detik'));
            Cache::put('pkl.mitra.basi', $data, now()->addDays(7));
            Cache::put('pkl.mitra.waktu', now('UTC')->format('Y-m-d H:i:s'), now()->addDays(30));

            return $data;
        }
        $lama = Cache::get('pkl.mitra.basi');
        Cache::put('pkl.mitra', $lama, 120); // jangan memanggil PKL tiap kunjungan saat PKL mati

        return $lama;
    }
}
