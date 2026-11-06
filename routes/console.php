<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Akun admin & salinan password (login cadangan) dari Pusat tiap jam; webhook akun memperbarui lebih cepat.
\Illuminate\Support\Facades\Schedule::call(fn () => \App\Services\PusatKlien::sinkronAkun())->name('pusat-akun')->hourly()->withoutOverlapping()
    ->when(fn () => \App\Services\PusatKlien::aktif() && \App\Services\PusatKlien::siap());
