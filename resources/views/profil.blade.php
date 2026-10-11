@extends('layouts.app')
@section('title', 'Profil Sekolah - SMKS Islam Bustanul Ulum')
@section('content')
@use('App\Models\Pengaturan', 'P')
@php
    $kepsek = P::ambil('profil_kepsek');
    $fotoKepsek = P::ambil('profil_foto_kepsek');
    $sambutan = P::ambil('profil_sambutan');
    $sejarah = P::ambil('profil_sejarah');
    $visi = P::ambil('profil_visi');
    $misi = P::baris('profil_misi');
    $identitas = array_filter([
        'NPSN' => [P::ambil('profil_npsn'), 'bi-hash'],
        'Akreditasi' => [P::ambil('profil_akreditasi'), 'bi-patch-check-fill'],
        'Tahun berdiri' => [P::ambil('profil_tahun'), 'bi-calendar-event'],
        'Alamat' => [P::ambil('alamat'), 'bi-geo-alt-fill'],
        'Telepon' => [P::ambil('telepon'), 'bi-telephone-fill'],
        'Email' => [P::ambil('email'), 'bi-envelope-fill'],
    ], fn ($d) => filled($d[0]));

    $menu = [];
    if ($sambutan) $menu['sambutan'] = 'Sambutan';
    if ($sejarah) $menu['sejarah'] = 'Sejarah';
    if ($visi || $misi) $menu['visi-misi'] = 'Visi & Misi';
    if ($identitas) $menu['identitas'] = 'Identitas';
    if ($staf->isNotEmpty()) $menu['staf'] = 'Guru & Staf';
@endphp
@include('partials.kepala-halaman', ['judul' => 'Profil Sekolah', 'sub' => 'SMKS Islam Bustanul Ulum Pakusari - Jember', 'ikon' => 'bi-buildings-fill', 'menu' => $menu])

<div class="container isi-hal">
    @if ($sambutan)
        <section class="blok" id="sambutan">
            <div class="sambutan">
                <div class="sambutan__tokoh">
                    <div class="sambutan__avatar" @if ($fotoKepsek) style="background-image:url('{{ Storage::disk('public')->url($fotoKepsek) }}')" role="img" aria-label="Foto {{ $kepsek ?: 'Kepala Sekolah' }}" @endif>
                        @unless ($fotoKepsek){{ $kepsek ? mb_strtoupper(mb_substr($kepsek, 0, 1)) : 'K' }}@endunless
                    </div>
                    <div>
                        @if ($kepsek)<b>{{ $kepsek }}</b>@endif
                        <small>Kepala Sekolah</small>
                    </div>
                </div>
                <div class="sambutan__teks">
                    <i class="bi bi-quote"></i>
                    <h2 class="sr-only">Sambutan Kepala Sekolah</h2>
                    <p>{!! nl2br(e($sambutan)) !!}</p>
                </div>
            </div>
        </section>
    @endif

    @if ($sejarah)
        <section class="blok" id="sejarah">
            <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-clock-history"></i></span><h2>Sejarah Singkat</h2></div>
            <div class="kartu-polos teks"><p>{!! nl2br(e($sejarah)) !!}</p></div>
        </section>
    @endif

    @if ($visi || $misi)
        <section class="blok dua-kartu" id="visi-misi">
            @if ($visi)
                <div class="visi">
                    <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-eye-fill"></i></span><h2>Visi</h2></div>
                    <p>{!! nl2br(e($visi)) !!}</p>
                </div>
            @endif
            @if ($misi)
                <div class="kartu-polos">
                    <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-bullseye"></i></span><h2>Misi</h2></div>
                    <ol class="misi">@foreach ($misi as $m)<li>{{ $m }}</li>@endforeach</ol>
                </div>
            @endif
        </section>
    @endif

    @if ($identitas)
        <section class="blok" id="identitas">
            <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-building"></i></span><h2>Identitas Sekolah</h2></div>
            <ul class="identitas">
                @foreach ($identitas as $label => [$nilai, $ikon])
                    <li><i class="bi {{ $ikon }}"></i><span><small>{{ $label }}</small><b>{{ $nilai }}</b></span></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($staf->isNotEmpty())
        <section class="blok" id="staf">
            <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-people-fill"></i></span><h2>Guru &amp; Staf</h2></div>
            <div class="staf">
                @foreach ($staf as $s)
                    <figure class="staf__kartu">
                        <div class="staf__foto" @if ($s->foto) style="background-image:url('{{ Storage::disk('public')->url($s->foto) }}')" @endif>
                            @unless ($s->foto){{ mb_strtoupper(mb_substr($s->nama, 0, 1)) }}@endunless
                        </div>
                        <figcaption><b>{{ $s->nama }}</b><small>{{ $s->jabatan }}</small></figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    @if (! $menu)
        <p class="kosong-hal">Informasi profil sekolah sedang disiapkan.</p>
    @endif
</div>
@endsection
