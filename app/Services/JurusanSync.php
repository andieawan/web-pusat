<?php

namespace App\Services;

use App\Models\ProgramKeahlian;
use Illuminate\Support\Facades\Schema;

/**
 * Jurusan di Pusat → Program Keahlian di website. Yang belum ada dibuat otomatis (ikon & deskripsi bisa
 * dilengkapi admin); yang sudah ada hanya diperbarui nama/kodenya; jurusan nonaktif/dihapus di Pusat disembunyikan.
 * Program Keahlian buatan admin yang tidak ada di Pusat dibiarkan.
 */
class JurusanSync
{
    private const IKON = ['AK' => 'bi-calculator', 'AKL' => 'bi-calculator', 'BD' => 'bi-shop', 'BDP' => 'bi-shop', 'DKV' => 'bi-palette',
        'TKJ' => 'bi-pc-display', 'TJKT' => 'bi-pc-display', 'RPL' => 'bi-code-slash', 'PPLG' => 'bi-code-slash', 'MP' => 'bi-briefcase',
        'OTKP' => 'bi-briefcase', 'TKR' => 'bi-car-front', 'TO' => 'bi-car-front', 'TSM' => 'bi-bicycle'];

    public static function siap(): bool
    {
        return Schema::hasColumn('program_keahlian', 'pusat_uuid');
    }

    /** @return array{baru:int,ubah:int,sembunyi:int} */
    public static function terapkan(array $jurusan): array
    {
        $h = ['baru' => 0, 'ubah' => 0, 'sembunyi' => 0];
        if (! self::siap()) {
            return $h;
        }
        foreach ($jurusan as $j) {
            $r = self::satu((array) $j);
            if ($r) {
                $h[$r]++;
            }
        }

        return $h;
    }

    /** Terapkan satu jurusan (juga dipakai webhook jurusan.*). @return 'baru'|'ubah'|'sembunyi'|null */
    public static function satu(array $j): ?string
    {
        $uuid = (string) ($j['uuid'] ?? '');
        $nama = trim((string) ($j['nama'] ?? ''));
        if ($uuid === '' || $nama === '' || ! self::siap()) {
            return null;
        }
        $kode = mb_substr(trim((string) ($j['kode'] ?? '')), 0, 20);
        $aktif = ($j['aktif'] ?? true) && empty($j['dihapus']);

        $p = ProgramKeahlian::where('pusat_uuid', $uuid)->first()
            ?? ProgramKeahlian::whereNull('pusat_uuid')->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first();
        if (! $p) {
            if (! $aktif) {
                return null;
            }
            ProgramKeahlian::create(['pusat_uuid' => $uuid, 'kode' => $kode, 'nama' => $nama, 'aktif' => true,
                'ikon' => self::IKON[strtoupper($kode)] ?? 'bi-book', 'urutan' => (int) ProgramKeahlian::max('urutan') + 1]);

            return 'baru';
        }
        $p->fill(['pusat_uuid' => $uuid, 'kode' => $kode, 'nama' => $nama, 'aktif' => $aktif]);
        if (! $p->isDirty()) {
            return null;
        }
        $sembunyi = $p->isDirty('aktif') && ! $aktif;
        $p->save();

        return $sembunyi ? 'sembunyi' : 'ubah';
    }
}
