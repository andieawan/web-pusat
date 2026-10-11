<?php

return [
    // Akun admin awal yang dibuat oleh DatabaseSeeder.
    // Kalau ADMIN_PASSWORD kosong, password acak dibuat dan ditampilkan sekali saat seeding.
    'admin_email' => env('ADMIN_EMAIL', 'admin@smkibu.sch.id'),
    'admin_password' => env('ADMIN_PASSWORD'),

    // Batas sisi terpanjang (piksel) foto yang diunggah lewat admin.
    'foto_maks_piksel' => 1600,
];
