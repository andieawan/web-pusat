<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Proxy tepercaya dari config (lihat config/app.php: trusted_proxies).
        if ($proxi = trim((string) config('app.trusted_proxies'))) {
            \Illuminate\Http\Middleware\TrustProxies::at($proxi === '*' ? '*' : array_map('trim', explode(',', $proxi)));
        }
        // Tampilan pagination sederhana yang cocok dengan CSS website & admin.
        Paginator::defaultView('pagination.site');
        Paginator::defaultSimpleView('pagination.site');

        // Login: dibatasi per kombinasi email+IP (menahan tebak-password satu akun)
        // dan per IP (menahan penyerangan ke banyak akun).
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $r->input('email')) . '|' . $r->ip()),
            Limit::perMinute(20)->by($r->ip()),
        ]);
    }
}
