<?php

// Penyambungan ke aplikasi Pusat Sekolah (login admin + statistik). Mati bila PUSAT_ENABLED=false.
return [
    'enabled' => (bool) env('PUSAT_ENABLED', false),
    'url' => rtrim((string) env('PUSAT_URL', ''), '/'),
    'client_id' => env('PUSAT_CLIENT_ID', ''),
    'client_secret' => env('PUSAT_CLIENT_SECRET', ''),
    'webhook_secret' => env('PUSAT_WEBHOOK_SECRET', ''),
    'timeout' => (int) env('PUSAT_TIMEOUT', 5), // singkat: beranda tidak boleh menunggu Pusat
    'cache_detik' => (int) env('PUSAT_CACHE_DETIK', 300),
];
