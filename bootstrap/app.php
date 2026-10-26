<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\HeaderKeamanan;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => EnsureAdmin::class]);
        // Proxy tepercaya (TRUSTED_PROXIES) dipasang di AppServiceProvider dari config: callback ini berjalan
        // sebelum .env dimuat, sehingga env() di sini selalu kosong.
        $middleware->validateCsrfTokens(except: ['pusat/webhook']); // dipanggil server Pusat; diamankan tanda tangan HMAC
        $middleware->appendToGroup('web', HeaderKeamanan::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
