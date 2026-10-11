<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';
    protected $guarded = [];

    protected static ?array $cache = null;

    /** Ambil satu pengaturan (dimuat sekali per request). */
    public static function ambil(string $kunci, ?string $default = null): ?string
    {
        static::$cache ??= static::pluck('nilai', 'kunci')->all();

        return static::$cache[$kunci] ?? $default;
    }

    /** Logo sekolah: unggahan admin, atau berkas bawaan public/images/logo.png. */
    public static function logoUrl(): string
    {
        $logo = static::ambil('logo');

        return $logo ? Storage::disk('public')->url($logo) : asset('images/logo.png');
    }

    /** Teks multibaris → daftar baris yang tidak kosong (untuk butir misi, tata tertib, dsb.). */
    public static function baris(string $kunci): array
    {
        $teks = (string) static::ambil($kunci, '');

        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $teks) ?: []), fn ($b) => $b !== ''));
    }

    /** Kosongkan cache (dipakai tes dan setelah pengaturan diubah). */
    public static function lupakan(): void
    {
        static::$cache = null;
    }

    public static function simpan(string $kunci, ?string $nilai): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        static::lupakan();
    }
}
