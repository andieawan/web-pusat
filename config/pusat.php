<?php

// Penyambungan ke aplikasi Pusat Sekolah (login admin + statistik). Mati bila PUSAT_ENABLED=false.
return [
    'enabled' => (bool) env('PUSAT_ENABLED', false),
    'url' => rtrim((string) env('PUSAT_URL', ''), '/'),
    'client_id' => env('PUSAT_CLIENT_ID', ''),
    'client_secret' => env('PUSAT_CLIENT_SECRET', ''),
    'webhook_secret' => env('PUSAT_WEBHOOK_SECRET', ''),
    'timeout' => (int) env('PUSAT_TIMEOUT', 5), // singkat: beranda tidak boleh menunggu Pusat

    // Pemeriksaan sertifikat HTTPS Pusat. Hanya untuk uji lokal dengan sertifikat buatan sendiri:
    // PUSAT_VERIFY_SSL=false mematikannya (JANGAN di server sungguhan). Lebih baik: PUSAT_CA_BUNDLE=path
    // berkas .pem berisi CA lokal Anda (mis. rootCA.pem dari mkcert/envkit), pemeriksaan tetap aktif.
    'verify_ssl' => filter_var(env('PUSAT_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    'ca_bundle' => env('PUSAT_CA_BUNDLE'),
    'cache_detik' => (int) env('PUSAT_CACHE_DETIK', 300),
];
