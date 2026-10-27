<?php

// Aplikasi PKL: daftar mitra DUDI (publik, tanpa data siswa) untuk halaman Akademik.
return [
    'url' => (fn (string $u) => $u === '' || preg_match('#^https?://#i', $u) ? $u : 'https://'.$u)(rtrim(trim((string) env('PKL_URL', ''), " \t\"'"), '/')),
    'cache_detik' => (int) env('PKL_CACHE_DETIK', 1800),
    'timeout' => (int) env('PKL_TIMEOUT', 5),
];
