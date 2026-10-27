<?php

namespace App\Services;

use App\Models\Staf;
use Illuminate\Support\Facades\Schema;

/**
 * Guru & staf di Pusat → halaman Guru & Staf website. Nama selalu mengikuti Pusat; jabatan mengikuti Pusat
 * hanya bila jabatan di Pusat berubah (jabatan yang diperhalus admin di website tidak tertimpa tiap sinkron).
 * Foto dan urutan diatur di website. Pegawai nonaktif/dihapus di Pusat disembunyikan.
 */
class StafSync
{
    public static function siap(): bool
    {
        return Schema::hasColumn('staf', 'pusat_uuid');
    }

    /** @return array{baru:int,ubah:int,sembunyi:int} */
    public static function terapkan(array $pegawai): array
    {
        $h = ['baru' => 0, 'ubah' => 0, 'sembunyi' => 0];
        if (! self::siap()) {
            return $h;
        }
        foreach ($pegawai as $p) {
            if ($r = self::satu((array) $p)) {
                $h[$r]++;
            }
        }

        return $h;
    }

    /** @return 'baru'|'ubah'|'sembunyi'|null */
    public static function satu(array $p): ?string
    {
        $uuid = (string) ($p['uuid'] ?? '');
        $nama = trim((string) ($p['nama'] ?? ''));
        if ($uuid === '' || $nama === '' || ! self::siap()) {
            return null;
        }
        $aktif = ($p['aktif'] ?? true) && empty($p['dihapus']);
        $jabPusat = trim((string) ($p['jabatan'] ?? ''));
        $jabBawaan = $jabPusat !== '' ? $jabPusat : (($p['jenis'] ?? 'guru') === 'staf' ? 'Staf' : 'Guru');

        $s = Staf::where('pusat_uuid', $uuid)->first()
            ?? Staf::whereNull('pusat_uuid')->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first();
        if (! $s) {
            if (! $aktif) {
                return null;
            }
            Staf::create(['pusat_uuid' => $uuid, 'nama' => $nama, 'jabatan' => mb_substr($jabBawaan, 0, 255), 'jabatan_pusat' => $jabPusat ?: null,
                'aktif' => true, 'urutan' => (int) Staf::max('urutan') + 1]);

            return 'baru';
        }
        $s->fill(['pusat_uuid' => $uuid, 'nama' => $nama, 'aktif' => $aktif]);
        if ($jabPusat !== '' && $jabPusat !== (string) $s->jabatan_pusat) {
            $s->fill(['jabatan' => mb_substr($jabPusat, 0, 255), 'jabatan_pusat' => $jabPusat]);
        }
        if (! $s->isDirty()) {
            return null;
        }
        $sembunyi = $s->isDirty('aktif') && ! $aktif;
        $s->save();

        return $sembunyi ? 'sembunyi' : 'ubah';
    }
}
