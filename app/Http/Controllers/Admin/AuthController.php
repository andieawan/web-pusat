<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function form()
    {
        return Auth::check() ? redirect()->route('admin.dashboard') : view('admin.login');
    }

    public function masuk(Request $request)
    {
        $kredensial = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Hanya akun admin yang boleh masuk lewat form ini.
        if (! Auth::attempt($kredensial + ['is_admin' => true], $request->boolean('ingat'))) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function keluar(Request $request)
    {
        $viaPusat = (bool) $request->session()->get('via_pusat');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($viaPusat && \App\Services\PusatKlien::aktif()) {
            // Keluar juga dari Pusat (mencabut token), lalu kembali ke beranda website.
            return redirect()->away(config('pusat.url').'/sso/logout?'.http_build_query([
                'client_id' => config('pusat.client_id'),
                'post_logout_redirect_uri' => route('beranda'),
            ]));
        }

        return redirect()->route('beranda');
    }
}
