<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PusatAkun;
use App\Services\PusatKlien;
use App\Support\PusatTanda;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

/** Login admin lewat Pusat (authorization code + PKCE) dan penerima webhook pencabutan akses. */
class PusatController extends Controller
{
    public function mulai(Request $request)
    {
        if (! PusatKlien::aktif() || ! PusatKlien::siap()) {
            return redirect()->route('login')->withErrors(['email' => 'Login lewat Pusat belum diaktifkan.']);
        }
        $state = Str::random(40);
        $verifier = Str::random(64);
        $request->session()->put('pusat_sso', ['state' => $state, 'verifier' => $verifier, 'waktu' => time()]);
        $tantangan = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return redirect()->away(config('pusat.url').'/sso/authorize?'.http_build_query([
            'client_id' => config('pusat.client_id'),
            'redirect_uri' => route('pusat.callback'),
            'response_type' => 'code',
            'state' => $state,
            'code_challenge' => $tantangan,
            'code_challenge_method' => 'S256',
        ]));
    }

    public function callback(Request $request)
    {
        if (! PusatKlien::aktif() || ! PusatKlien::siap()) {
            return redirect()->route('login');
        }
        $sesi = $request->session()->pull('pusat_sso');
        $kode = (string) $request->query('code', '');
        if (! $sesi || $kode === '' || time() - ($sesi['waktu'] ?? 0) > 600
            || ! hash_equals((string) $sesi['state'], (string) $request->query('state', ''))) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi login lewat Pusat tidak valid atau kedaluwarsa. Coba lagi.']);
        }

        try {
            $res = PusatKlien::http()->asForm()->post('/api/sso/token', [
                'grant_type' => 'authorization_code',
                'code' => $kode,
                'redirect_uri' => route('pusat.callback'),
                'code_verifier' => $sesi['verifier'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors(['email' => 'Pusat tidak dapat dihubungi. Gunakan login email untuk sementara.']);
        }
        if (! $res->ok() || ! is_array($res->json('user'))) {
            return redirect()->route('login')->withErrors(['email' => 'Pusat menolak login Anda. Coba lagi atau hubungi administrator.']);
        }

        [$user, $galat] = PusatAkun::masuk($res->json('user'));
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => $galat]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('via_pusat', true);
        // Daftar aplikasi lain milik akun ini (menu "Pindah ke aplikasi lain"); hanya nama & tautan lewat Pusat.
        $request->session()->put('aplikasi_lain', collect((array) ($res->json('user.aplikasi_lain') ?? []))
            ->filter(fn ($a) => is_array($a) && isset($a['nama'], $a['url']))->map(fn ($a) => ['nama' => (string) $a['nama'], 'url' => (string) $a['url']])->take(20)->values()->all());

        return redirect()->intended(route('admin.dashboard'));
    }

    /** Aman: HMAC + batas waktu 5 menit. Hanya mencabut akses (idempoten); event lain diabaikan. */
    public function webhook(Request $request)
    {
        if (! PusatKlien::aktif() || ! PusatKlien::siap() || config('pusat.webhook_secret') === '') {
            return response()->json(['ok' => false], 404);
        }
        $waktu = (int) $request->header('X-Pusat-Timestamp');
        if (! PusatTanda::verifikasi((string) config('pusat.webhook_secret'), $waktu, $request->getContent(), (string) $request->header('X-Pusat-Signature'))) {
            return response()->json(['ok' => false, 'error' => 'tanda_tangan_salah'], 401);
        }
        $event = (string) $request->header('X-Pusat-Event');
        $data = (array) $request->json('data', []);
        if (in_array($event, ['akun.diperbarui', 'akses.diperbarui'], true)) {
            PusatAkun::cabutBilaPerlu($data);
        } elseif (str_starts_with($event, 'jurusan.')) {
            \App\Services\JurusanSync::satu($data);
            \Illuminate\Support\Facades\Cache::forget('pusat.jurusan');
        } elseif ($event === 'sambungan.diperbarui') {
            \App\Services\Sambungan::tarik();
        }

        return response()->json(['ok' => true]);
    }
}
