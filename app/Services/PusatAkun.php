<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/** Mencocokkan profil dari Pusat dengan akun admin website. Hanya peran Pusat "admin" yang boleh masuk panel. */
class PusatAkun
{
    /** @return array{0:?User,1:?string} [akun, pesan galat] */
    public static function masuk(array $info): array
    {
        $uuid = (string) ($info['sub'] ?? '');
        $email = mb_strtolower(trim((string) ($info['email'] ?? '')));
        if ($uuid === '') {
            return [null, 'Data akun dari Pusat tidak lengkap.'];
        }
        if (! in_array('admin', (array) ($info['peran'] ?? []), true)) {
            return [null, 'Akun Anda tidak punya peran admin untuk website. Hubungi administrator Pusat.'];
        }

        $user = User::where('pusat_uuid', $uuid)->first();
        if (! $user) {
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [null, 'Akun Anda di Pusat belum punya email yang sah. Isi dulu email di Pusat.'];
            }
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first(); // penautan pertama lewat email yang sama
            if ($user && $user->pusat_uuid !== null) {
                return [null, 'Email ini sudah tertaut ke akun Pusat lain. Hubungi administrator.'];
            }
        }

        if (! $user) {
            $user = new User(['name' => (string) ($info['nama'] ?? $email), 'email' => $email, 'password' => Str::random(48)]); // password tak diketahui siapa pun
        }
        $user->pusat_uuid = $uuid;
        $user->is_admin = true;
        if (! empty($info['nama'])) {
            $user->name = (string) $info['nama'];
        }
        $user->save();

        return [$user, null];
    }

    /** Webhook: akses admin dicabut di Pusat (akun nonaktif / peran admin hilang) -> is_admin dimatikan seketika. */
    public static function cabutBilaPerlu(array $data): bool
    {
        $user = User::where('pusat_uuid', (string) ($data['uuid'] ?? ''))->first();
        if (! $user || ! $user->is_admin) {
            return false;
        }
        $dicabut = (array_key_exists('aktif', $data) && $data['aktif'] === false)
            || (array_key_exists('peran', $data) && ! in_array('admin', (array) $data['peran'], true));
        if (! $dicabut) {
            return false;
        }
        $user->is_admin = false; // EnsureAdmin memeriksa tiap request, jadi sesi aktif langsung tertolak
        $user->save();

        return true;
    }
}
