@extends('layouts.app')

@section('content')

{{-- Hero --}}
@php $daftarSlide = count($slides) ? $slides : [asset('images/hero.jpg')]; @endphp
<section class="hero" data-slider>
    @foreach ($daftarSlide as $i => $src)
        <div class="hero__bg {{ $i === 0 ? 'on' : '' }}" style="background-image:url('{{ $src }}')"></div>
    @endforeach
    <div class="container hero__inner">
        <div class="hero__teks">
            <span class="hero__kecil">SELAMAT DATANG DI</span>
            <h1>SMKS ISLAM<br><em>BUSTANUL ULUM</em><br><span>PAKUSARI - JEMBER</span></h1>
            <p class="hero__motto">“Mencetak Generasi Unggul, Berakhlak Mulia<br>dan Siap Kerja di Era Global”</p>
            <a href="{{ route('profil') }}" class="btn btn--primary">Kenali Sekolah Kami <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
    @if (count($daftarSlide) > 1)
        <button type="button" class="hero__nav hero__nav--kiri" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></button>
        <button type="button" class="hero__nav hero__nav--kanan" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></button>
    @endif
</section>

{{-- Statistik --}}
<section class="container statistik">
    @foreach ($statistik as $s)
        <div class="stat">
            <div class="stat__ikon"><i class="bi {{ $s['ikon'] }}"></i></div>
            <div>
                <small>{{ $s['judul'] }}</small>
                <b>{{ $s['nilai'] }}</b>
                <span>{{ $s['ket'] }}</span>
            </div>
        </div>
    @endforeach
</section>

{{-- Berita + Pengumuman --}}
<section class="container dua-kolom">
    <div>
        <div class="judul-seksi">
            <h2>Berita Terbaru</h2>
            <a href="{{ route('berita') }}" class="lihat">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="berita">
            @foreach ($berita as $b)
                <article class="kartu">
                    <div class="kartu__gambar" @if ($b['gambar']) style="background-image:url('{{ $b['gambar'] }}')" @endif></div>
                    <div class="kartu__isi">
                        <div class="kartu__meta">
                            <span><i class="bi bi-calendar3"></i> {{ $b['tanggal'] }}</span>
                            <span class="label label--{{ $b['warna'] }}">{{ $b['label'] }}</span>
                        </div>
                        <h3>{{ $b['judul'] }}</h3>
                        <p>{{ $b['ringkas'] }}</p>
                        <a href="{{ $b['url'] }}" class="selengkapnya">Selengkapnya <i class="bi bi-arrow-right"></i></a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>

    <aside class="panel pengumuman">
        <div class="judul-seksi">
            <h2><i class="bi bi-megaphone-fill"></i> Pengumuman</h2>
            <a href="{{ route('pengumuman') }}" class="lihat">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        </div>
        @foreach ($pengumuman as $p)
            <div class="item-tgl">
                <div class="tgl"><b>{{ $p['tgl'] }}</b><span>{{ $p['bln'] }}</span></div>
                <div><p>{{ $p['judul'] }}</p><small>{{ $p['kategori'] }}</small></div>
            </div>
        @endforeach
    </aside>
</section>

{{-- Tentang + Program Keahlian --}}
<section class="container dua-kolom dua-kolom--tentang">
    <div class="tentang">
        <div class="tentang__gambar" style="background-image:url('{{ asset('images/gedung.jpg') }}')"></div>
        <div>
            <small class="tentang__label">Tentang Kami</small>
            <h2>SMK Islam Bustanul Ulum<br>Pakusari Jember</h2>
            <p>{{ $tentang }}</p>
            <a href="{{ route('profil') }}" class="btn btn--primary">Selengkapnya <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>

    <div class="keahlian">
        <div class="judul-seksi">
            <h2 class="h2-kecil"><i class="bi bi-megaphone-fill"></i> Program Keahlian</h2>
            <a href="{{ route('akademik') }}" class="lihat">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="keahlian__grid">
            @foreach ($program as $pr)
                <div class="keahlian__item">
                    <i class="bi {{ $pr['ikon'] }}"></i>
                    <span>{{ $pr['nama'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Banner kutipan --}}
<section class="banner">
    <div class="container banner__inner">
        <i class="bi bi-mortarboard-fill banner__ikon"></i>
        <div class="banner__teks">
            <h3>Berilmu, Berakhlak, dan Siap Berkarya</h3>
            <p>Bersama SMKS Islam Bustanul Ulum, wujudkan masa depan yang lebih baik.</p>
        </div>
        <blockquote>
            “Pendidikan adalah investasi terbaik untuk masa depan.”
            <cite>— SMKS Islam Bustanul Ulum</cite>
        </blockquote>
    </div>
</section>

{{-- Galeri + Agenda --}}
<section class="container dua-kolom dua-kolom--galeri">
    <div>
        <div class="judul-seksi">
            <h2 class="h2-kecil"><i class="bi bi-camera-fill"></i> Galeri Kegiatan</h2>
            <a href="{{ route('galeri') }}" class="lihat">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="galeri">
            @foreach ($galeri as $g)
                <figure>
                    <div class="galeri__foto" @if ($g['gambar']) style="background-image:url('{{ $g['gambar'] }}')" @endif></div>
                    <figcaption>{{ $g['judul'] }}<small><i class="bi bi-calendar3"></i> {{ $g['tanggal'] }}</small></figcaption>
                </figure>
            @endforeach
        </div>
    </div>

    <aside>
        <div class="judul-seksi">
            <h2 class="h2-kecil"><i class="bi bi-calendar-week-fill"></i> Agenda Sekolah</h2>
            <a href="{{ route('agenda') }}" class="lihat">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        </div>
        @foreach ($agenda as $a)
            <div class="item-tgl item-tgl--garis">
                <div class="tgl tgl--putih"><b>{{ $a['tgl'] }}</b><span>{{ $a['bln'] }}</span></div>
                <div><p>{{ $a['judul'] }}</p><small>{{ $a['waktu'] }}</small></div>
            </div>
        @endforeach
    </aside>
</section>

<script>
(function () {
    var hero = document.querySelector('[data-slider]');
    if (!hero) return;
    var slide = hero.querySelectorAll('.hero__bg');
    if (slide.length < 2) return;
    var i = 0, timer;
    function pindah(n) {
        slide[i].classList.remove('on');
        i = (n + slide.length) % slide.length;
        slide[i].classList.add('on');
    }
    function otomatis() {
        clearInterval(timer);
        timer = setInterval(function () { pindah(i + 1); }, 6000);
    }
    hero.querySelector('.hero__nav--kiri').addEventListener('click', function () { pindah(i - 1); otomatis(); });
    hero.querySelector('.hero__nav--kanan').addEventListener('click', function () { pindah(i + 1); otomatis(); });
    otomatis();
})();
</script>

@endsection
