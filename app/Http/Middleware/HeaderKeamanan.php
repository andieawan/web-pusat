<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan tambahan di tingkat aplikasi.
 * Halaman admin & login: CSP ketat (tanpa skrip inline) dan tidak boleh disimpan di cache browser,
 * supaya tombol "Back" setelah keluar tidak menampilkan isi panel.
 */
class HeaderKeamanan
{
    public function handle(Request $request, Closure $next): Response
    {
        $respons = $next($request);

        $respons->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $respons->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->secure()) {
            $respons->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        if ($request->is('admin', 'admin/*', 'login')) {
            $respons->headers->set('Content-Security-Policy',
                "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; "
                . "font-src 'self'; form-action 'self'; frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
            $respons->headers->set('Cache-Control', 'no-store, private');
        }

        return $respons;
    }
}
