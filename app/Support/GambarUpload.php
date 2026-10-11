<?php

namespace App\Support;

/**
 * Memperkecil foto unggahan dengan GD (tanpa paket tambahan).
 * Mengembalikan null kalau tidak perlu / tidak bisa diproses; pemanggil lalu menyimpan berkas apa adanya.
 */
class GambarUpload
{
    /** Di atas jumlah piksel ini GD berisiko kehabisan memori; simpan apa adanya. */
    private const MAKS_PIKSEL = 30_000_000;

    /**
     * @return array{0:string,1:string}|null [isi biner, ekstensi] atau null
     */
    public static function perkecil(string $berkas, int $maks = 1600): ?array
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $info = @getimagesize($berkas);
        if (! $info) {
            return null;
        }
        [$w, $h, $tipe] = $info;

        // GIF (bisa beranimasi) dan gambar raksasa tidak diproses.
        if ($tipe === IMAGETYPE_GIF || $w * $h > self::MAKS_PIKSEL) {
            return null;
        }

        $img = match ($tipe) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($berkas),
            IMAGETYPE_PNG => @imagecreatefrompng($berkas),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($berkas) : false,
            default => false,
        };
        if (! $img) {
            return null;
        }

        $punyaAlpha = $tipe !== IMAGETYPE_JPEG;
        if ($punyaAlpha) {
            imagepalettetotruecolor($img);
        }

        // Foto dari ponsel sering menyimpan rotasi di EXIF.
        if ($tipe === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($berkas);
            $sudut = match ($exif['Orientation'] ?? 1) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
            if ($sudut !== 0 && ($putar = imagerotate($img, $sudut, 0))) {
                $img = $putar;
            }
        }

        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) > $maks) {
            $skala = $maks / max($w, $h);
            $lw = max(1, (int) round($w * $skala));
            $lh = max(1, (int) round($h * $skala));
            $baru = imagecreatetruecolor($lw, $lh);
            if ($punyaAlpha) {
                imagealphablending($baru, false);
                imagesavealpha($baru, true);
                imagefill($baru, 0, 0, imagecolorallocatealpha($baru, 0, 0, 0, 127));
            }
            imagecopyresampled($baru, $img, 0, 0, 0, 0, $lw, $lh, $w, $h);
            $img = $baru;
        }

        if ($punyaAlpha) {
            imagesavealpha($img, true);
        }

        ob_start();
        switch ($tipe) {
            case IMAGETYPE_PNG:
                imagepng($img, null, 6);
                $ext = 'png';
                break;
            case IMAGETYPE_WEBP:
                imagewebp($img, null, 82);
                $ext = 'webp';
                break;
            default:
                imagejpeg($img, null, 82);
                $ext = 'jpg';
        }
        $isi = ob_get_clean();

        return $isi === false || $isi === '' ? null : [$isi, $ext];
    }
}
