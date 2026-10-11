<?php

namespace App\Support;

/** URL aset statis dengan versi (waktu ubah berkas) agar cache browser/Cloudflare ikut diperbarui saat berkas berubah. */
class Aset
{
    public static function url(string $path): string
    {
        $berkas = public_path($path);

        return asset($path) . (is_file($berkas) ? '?v=' . filemtime($berkas) : '');
    }
}
