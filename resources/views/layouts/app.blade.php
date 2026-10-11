<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SMKS Islam Bustanul Ulum Pakusari - Jember')</title>
    <link href="{{ \App\Support\Aset::url('css/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="icon" href="{{ \App\Models\Pengaturan::logoUrl() }}">
    <link href="{{ \App\Support\Aset::url('css/site.css') }}" rel="stylesheet">
</head>
<body>

{{-- Topbar --}}
<div class="topbar">
    <div class="container topbar__inner">
        <div class="topbar__info">
            <span><i class="bi bi-geo-alt-fill"></i> {{ \App\Models\Pengaturan::ambil('alamat', 'Jl. Raya Pakusari No. 45, Pakusari - Jember') }}</span>
            <span><i class="bi bi-telephone-fill"></i> {{ \App\Models\Pengaturan::ambil('telepon', '(0331) 593XXX') }}</span>
            <span><i class="bi bi-envelope-fill"></i> {{ \App\Models\Pengaturan::ambil('email', 'smksibp@gmail.com') }}</span>
        </div>
        <div class="topbar__sosmed">
            @include('partials.sosmed')
        </div>
    </div>
</div>

{{-- Navbar --}}
<header class="navbar">
    <div class="container navbar__inner">
        <a href="{{ route('beranda') }}" class="brand">
            <img src="{{ \App\Models\Pengaturan::logoUrl() }}" alt="Logo" class="brand__logo" onerror="this.style.display='none'">
            <span class="brand__text">
                <strong>SMKS ISLAM<br>BUSTANUL ULUM</strong>
                <small>PAKUSARI - JEMBER</small>
            </span>
        </a>

        <button class="navbar__toggle" onclick="document.getElementById('menu').classList.toggle('open')" aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>

        <nav id="menu" class="menu">
            <a href="{{ route('beranda') }}" class="{{ request()->routeIs('beranda') ? 'active' : '' }}">Beranda</a>
            <a href="{{ route('profil') }}" class="{{ request()->routeIs('profil*') ? 'active' : '' }}">Profil</a>
            <a href="{{ route('akademik') }}" class="{{ request()->routeIs('akademik*') ? 'active' : '' }}">Akademik</a>
            <a href="{{ route('kesiswaan') }}" class="{{ request()->routeIs('kesiswaan*') ? 'active' : '' }}">Kesiswaan</a>
            <a href="{{ route('berita') }}" class="{{ request()->routeIs('berita*') ? 'active' : '' }}">Berita</a>
            <a href="{{ route('galeri') }}" class="{{ request()->routeIs('galeri*') ? 'active' : '' }}">Galeri</a>
            <a href="{{ route('ppdb') }}" class="{{ request()->routeIs('ppdb*') ? 'active' : '' }}">PPDB</a>
        </nav>

        <div class="navbar__aksi">
            <button type="button" class="btn-cari" aria-label="Cari berita" aria-controls="cari"
                    onclick="var f=document.getElementById('cari');f.classList.toggle('open');if(f.classList.contains('open'))f.querySelector('input').focus()"><i class="bi bi-search"></i></button>
            @if (auth()->user()?->is_admin)
                <a href="{{ route('admin.dashboard') }}" class="btn btn--primary"><strong>Admin</strong> <i class="bi bi-person-fill"></i></a>
            @else
                <a href="{{ route('login') }}" class="btn btn--primary"><strong>Login</strong> <i class="bi bi-person-fill"></i></a>
            @endif
        </div>
    </div>
</header>

<form id="cari" class="cari {{ request('q') ? 'open' : '' }}" action="{{ route('berita') }}" method="get" role="search">
    <div class="container cari__isi">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari berita..." maxlength="100" aria-label="Kata kunci">
        <button type="submit" class="btn btn--primary">Cari</button>
    </div>
</form>

<main>
    @yield('content')
</main>

{{-- Footer --}}
<footer class="footer">
    <div class="container footer__grid">
        <div>
            <a href="{{ route('beranda') }}" class="brand brand--light">
                <img src="{{ \App\Models\Pengaturan::logoUrl() }}" alt="Logo" class="brand__logo" onerror="this.style.display='none'">
                <span class="brand__text"><strong>SMKS ISLAM<br>BUSTANUL ULUM</strong><small>PAKUSARI - JEMBER</small></span>
            </a>
            <p class="footer__desc">SMKS Islam Bustanul Ulum Jember berkomitmen mencetak lulusan yang kompeten, berakhlak mulia, dan siap kerja.</p>
        </div>
        <div>
            <h4>Tautan Cepat</h4>
            <div class="footer__links">
                <a href="{{ route('beranda') }}">Beranda</a><a href="{{ route('berita') }}">Berita</a>
                <a href="{{ route('profil') }}">Profil Sekolah</a><a href="{{ route('galeri') }}">Galeri</a>
                <a href="{{ route('akademik') }}">Akademik</a><a href="{{ route('ppdb') }}">PPDB</a>
                <a href="{{ route('kesiswaan') }}">Kesiswaan</a>
            </div>
        </div>
        <div>
            <h4 id="kontak">Kontak Kami</h4>
            <ul class="footer__kontak">
                <li><i class="bi bi-geo-alt-fill"></i> {{ \App\Models\Pengaturan::ambil('alamat', 'Jl. Raya Pakusari No. 45, Pakusari - Jember') }}</li>
                <li><i class="bi bi-telephone-fill"></i> {{ \App\Models\Pengaturan::ambil('telepon', '(0331) 593XXX') }}</li>
                <li><i class="bi bi-envelope-fill"></i> {{ \App\Models\Pengaturan::ambil('email', 'smksibp@gmail.com') }}</li>
            </ul>
        </div>
        <div>
            <h4>Ikuti Kami</h4>
            <div class="footer__sosmed">
                @include('partials.sosmed')
            </div>
        </div>
        <a href="{{ route('ppdb') }}" class="footer__ppdb">
            <i class="bi bi-laptop"></i>
            <span><strong>PPDB Online</strong><small>Daftar sekarang untuk masa depanmu</small></span>
            <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="footer__bawah">
        <div class="container">
            <span>© {{ date('Y') }} SMKS Islam Bustanul Ulum Pakusari Jember. All rights reserved.</span>
            <span class="footer__bawah-nav"><a href="{{ route('beranda') }}">Beranda</a> | <a href="{{ route('profil') }}">Profil</a> | <a href="#kontak">Kontak</a></span>
        </div>
    </div>
</footer>

</body>
</html>
