<?php

return [
    // Akun admin awal yang dibuat oleh DatabaseSeeder.
    // Kalau ADMIN_PASSWORD kosong, password acak dibuat dan ditampilkan sekali saat seeding.
    'admin_email' => env('ADMIN_EMAIL', 'admin@smkibu.sch.id'),
    'admin_password' => env('ADMIN_PASSWORD'),
    // false = tanpa admin lokal: semua admin dibuat di Pusat (login cadangan memakai password Pusat). install.sh mengisi false.
    'admin_lokal' => (bool) env('ADMIN_LOKAL', true),

    // Batas sisi terpanjang (piksel) foto yang diunggah lewat admin.
    'foto_maks_piksel' => 1600,
];
