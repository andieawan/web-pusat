@extends('layouts.app')
@section('title', 'Akademik - SMKS Islam Bustanul Ulum')
@section('content')
@use('App\Models\Pengaturan', 'P')
@php
    $kurikulum = P::ambil('akademik_kurikulum');
    $jam = P::baris('akademik_jam');
    $pkl = P::ambil('akademik_pkl');

    $menu = ['program' => 'Program Keahlian'];
    if ($kurikulum || $jam) $menu['kurikulum'] = 'Kurikulum & Jam Belajar';
    if ($pkl) $menu['pkl'] = 'PKL & Industri';
    if ($agenda->isNotEmpty() || $pengumuman->isNotEmpty()) $menu['kegiatan'] = 'Agenda & Pengumuman';
@endphp
@include('partials.kepala-halaman', ['judul' => 'Akademik', 'sub' => 'Program keahlian, kurikulum, dan kegiatan belajar', 'ikon' => 'bi-mortarboard-fill', 'menu' => $menu])

<div class="container isi-hal">
    <section class="blok" id="program">
        <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-mortarboard-fill"></i></span><h2>Program Keahlian</h2></div>
        <div class="program">
            @forelse ($program as $pr)
                <div class="program__kartu">
                    <span class="program__ikon"><i class="bi {{ $pr->ikon }}"></i></span>
                    <h3>{{ $pr->nama }}</h3>
                    @if ($pr->deskripsi)<p>{{ $pr->deskripsi }}</p>@endif
                </div>
            @empty
                <p class="kosong-hal">Program keahlian belum diisi.</p>
            @endforelse
        </div>
    </section>

    @if ($kurikulum || $jam)
        <section class="blok dua-kartu" id="kurikulum">
            @if ($kurikulum)
                <div class="kartu-polos">
                    <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-journal-bookmark-fill"></i></span><h2>Kurikulum</h2></div>
                    <p>{!! nl2br(e($kurikulum)) !!}</p>
                </div>
            @endif
            @if ($jam)
                <div class="kartu-polos">
                    <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-clock-fill"></i></span><h2>Jam Belajar</h2></div>
                    <ul class="butir jam">@foreach ($jam as $j)<li><span>{{ $j }}</span></li>@endforeach</ul>
                </div>
            @endif
        </section>
    @endif

    @if ($pkl)
        <section class="blok" id="pkl">
            <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-briefcase-fill"></i></span><h2>Praktik Kerja Lapangan &amp; Kerja Sama Industri</h2></div>
            <div class="kartu-polos teks"><p>{!! nl2br(e($pkl)) !!}</p></div>
        </section>
    @endif

    @if ($agenda->isNotEmpty() || $pengumuman->isNotEmpty())
        <section class="blok dua-kartu" id="kegiatan">
            @if ($agenda->isNotEmpty())
                <div class="kartu-polos">
                    <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-calendar-week-fill"></i></span><h2>Agenda Mendatang</h2><a href="{{ route('agenda') }}" class="lihat">Semua <i class="bi bi-arrow-right"></i></a></div>
                    @foreach ($agenda as $a)
                        <div class="item-tgl item-tgl--garis">
                            <div class="tgl tgl--putih"><b>{{ $a->tanggal->format('d') }}</b><span>{{ $a->tanggal->translatedFormat('M') }}</span></div>
                            <div><p>{{ $a->judul }}</p><small>{{ $a->waktu }}</small></div>
                        </div>
                    @endforeach
                </div>
            @endif
            @if ($pengumuman->isNotEmpty())
                <div class="kartu-polos">
                    <div class="judul-blok"><span class="judul-blok__ikon"><i class="bi bi-megaphone-fill"></i></span><h2>Pengumuman Akademik</h2><a href="{{ route('pengumuman') }}" class="lihat">Semua <i class="bi bi-arrow-right"></i></a></div>
                    @foreach ($pengumuman as $p)
                        <div class="item-tgl">
                            <div class="tgl"><b>{{ $p->tanggal->format('d') }}</b><span>{{ $p->tanggal->translatedFormat('M') }}</span></div>
                            <div><p>{{ $p->judul }}</p></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
