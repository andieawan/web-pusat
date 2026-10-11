<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') - Panel SMKS IBU</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="{{ \App\Support\Aset::url('css/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ \App\Support\Aset::url('css/admin.css') }}" rel="stylesheet">
    <script src="{{ \App\Support\Aset::url('js/admin.js') }}" defer></script>
</head>
<body class="admin">
@php
    $menu = [
        'Utama' => [
            ['Dashboard', 'admin.dashboard', 'bi-speedometer2', 'admin.dashboard'],
        ],
        'Konten website' => [
            ['Berita', 'admin.berita.index', 'bi-newspaper', 'admin.berita.*'],
            ['Pengumuman', 'admin.pengumuman.index', 'bi-megaphone-fill', 'admin.pengumuman.*'],
            ['Agenda', 'admin.agenda.index', 'bi-calendar-week-fill', 'admin.agenda.*'],
            ['Galeri', 'admin.galeri.index', 'bi-camera-fill', 'admin.galeri.*'],
            ['Banner Hero', 'admin.slide.index', 'bi-images', 'admin.slide.*'],
        ],
        'Data sekolah' => [
            ['Program Keahlian', 'admin.program.index', 'bi-mortarboard-fill', 'admin.program.*'],
            ['Ekstrakurikuler', 'admin.ekstrakurikuler.index', 'bi-people-fill', 'admin.ekstrakurikuler.*'],
            ['Guru & Staf', 'admin.staf.index', 'bi-person-badge-fill', 'admin.staf.*'],
        ],
        'Sistem' => [
            ['Pengaturan Situs', 'admin.pengaturan', 'bi-gear-fill', 'admin.pengaturan*'],
            ['Pengguna Admin', 'admin.pengguna.index', 'bi-shield-lock-fill', 'admin.pengguna.*'],
            ['Akun Saya', 'admin.akun', 'bi-person-circle', 'admin.akun*'],
        ],
    ];
    $user = auth()->user();
@endphp
<aside class="side" id="menu-samping">
    <div class="side__brand">
        <img class="side__logo" src="{{ \App\Models\Pengaturan::logoUrl() }}" alt="" width="40" height="40" data-sembunyi-galat>
        <div class="side__nama">SMKS IBU<small>Panel Admin</small></div>
    </div>
    <nav aria-label="Menu admin">
        @foreach ($menu as $grup => $butir)
            <div class="side__grup">{{ $grup }}</div>
            @foreach ($butir as [$nama, $rute, $ikon, $pola])
                <a href="{{ route($rute) }}" class="{{ request()->routeIs($pola) ? 'on' : '' }}" @if (request()->routeIs($pola)) aria-current="page" @endif><i class="bi {{ $ikon }}"></i> {{ $nama }}</a>
            @endforeach
        @endforeach
    </nav>
    <div class="side__bawah">
        <div class="side__user">
            <span class="avatar">{{ \Illuminate\Support\Str::substr($user->name, 0, 1) }}</span>
            <div><b>{{ $user->name }}</b><small>{{ $user->email }}</small></div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" title="Keluar" aria-label="Keluar"><i class="bi bi-box-arrow-left"></i></button>
        </form>
    </div>
</aside>
<div class="side-lapis" data-menu="tutup"></div>
<div class="utama">
    <header class="atas">
        <button type="button" class="atas__menu" data-menu="buka" aria-label="Buka menu" aria-controls="menu-samping"><i class="bi bi-list"></i></button>
        <div class="atas__judul">@yield('title', 'Admin')</div>
        <a href="{{ route('beranda') }}" target="_blank" rel="noopener" class="atas__tautan"><i class="bi bi-box-arrow-up-right"></i><span>Lihat website</span></a>
    </header>
    <main class="isi">
        @if (session('ok'))<div class="notif notif--ok" role="status"><i class="bi bi-check-circle-fill"></i><div>{{ session('ok') }}</div><button type="button" class="notif__tutup" data-tutup aria-label="Tutup">&times;</button></div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>
