@extends('layouts.app')
@section('title', 'Kesiswaan - SMKS Islam Bustanul Ulum')
@section('content')
@use('App\Models\Pengaturan', 'P')
@php
    $osis = P::ambil('kesiswaan_osis');
    $tertib = P::baris('kesiswaan_tatatertib');
    $beasiswa = P::ambil('kesiswaan_beasiswa');

    $menu = [];
    if ($osis) $menu['osis'] = 'OSIS';
    $menu['ekskul'] = 'Ekstrakurikuler';
    if ($tertib || $beasiswa) $menu['aturan'] = 'Tata Tertib & Beasiswa';
    if ($prestasi->isNotEmpty()) $menu['prestasi'] = 'Prestasi';
    if ($pengumuman->isNotEmpty()) $menu['info'] = 'Pengumuman';
@endphp
@include('partials.kepala-halaman', ['judul' => 'Kesiswaan', 'sub' => 'Pembinaan karakter, organisasi, dan prestasi siswa', 'ikon' => 'bi-people-fill', 'menu' => $menu])

<div class="container isi-hal">
    @if ($osis)
        <section class="blok" id="osis">
            <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-people-fill"></i></span><h2>OSIS &amp; Pembinaan Siswa</h2></div>
            <div class="kartu-polos teks"><p>{!! nl2br(e($osis)) !!}</p></div>
        </section>
    @endif

    <section class="blok" id="ekskul">
        <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-trophy-fill"></i></span><h2>Ekstrakurikuler</h2></div>
        <div class="program">
            @forelse ($ekskul as $e)
                <div class="program__kartu">
                    <span class="program__ikon"><i class="bi {{ $e->ikon }}"></i></span>
                    <h3>{{ $e->nama }}</h3>
                    @if ($e->deskripsi)<p>{{ $e->deskripsi }}</p>@endif
                    @if ($e->pembina || $e->jadwal)
                        <ul class="meta">
                            @if ($e->pembina)<li><i class="bi bi-person-fill"></i> {{ $e->pembina }}</li>@endif
                            @if ($e->jadwal)<li><i class="bi bi-clock"></i> {{ $e->jadwal }}</li>@endif
                        </ul>
                    @endif
                </div>
            @empty
                <p class="kosong-hal">Daftar ekstrakurikuler belum diisi.</p>
            @endforelse
        </div>
    </section>

    @if ($tertib || $beasiswa)
        <section class="blok dua-kartu" id="aturan">
            @if ($tertib)
                <div class="kartu-polos">
                    <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-shield-check"></i></span><h2>Tata Tertib</h2></div>
                    <ul class="butir butir--cek">@foreach ($tertib as $t)<li><span>{{ $t }}</span></li>@endforeach</ul>
                </div>
            @endif
            @if ($beasiswa)
                <div class="kartu-polos">
                    <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-award-fill"></i></span><h2>Beasiswa &amp; Bantuan</h2></div>
                    <p>{!! nl2br(e($beasiswa)) !!}</p>
                </div>
            @endif
        </section>
    @endif

    @if ($prestasi->isNotEmpty())
        <section class="blok" id="prestasi">
            <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-stars"></i></span><h2>Prestasi Terbaru</h2><a href="{{ route('berita') }}" class="lihat">Semua berita <i class="bi bi-arrow-right"></i></a></div>
            <div class="berita berita--grid">
                @foreach ($prestasi as $b)
                    <article class="kartu">
                        <div class="kartu__gambar" @if ($b->gambar) style="background-image:url('{{ Storage::disk('public')->url($b->gambar) }}')" @endif></div>
                        <div class="kartu__isi">
                            <div class="kartu__meta"><span><i class="bi bi-calendar3"></i> {{ $b->tanggal->translatedFormat('j M Y') }}</span><span class="label label--green">Prestasi</span></div>
                            <h3>{{ $b->judul }}</h3>
                            <a href="{{ route('berita.show', $b->slug) }}" class="selengkapnya">Selengkapnya <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($pengumuman->isNotEmpty())
        <section class="blok" id="info">
            <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-megaphone-fill"></i></span><h2>Pengumuman Kesiswaan</h2><a href="{{ route('pengumuman') }}" class="lihat">Semua <i class="bi bi-arrow-right"></i></a></div>
            <div class="kartu-polos">
                @foreach ($pengumuman as $p)
                    <div class="item-tgl">
                        <div class="tgl"><b>{{ $p->tanggal->format('d') }}</b><span>{{ $p->tanggal->translatedFormat('M') }}</span></div>
                        <div><p>{{ $p->judul }}</p></div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
