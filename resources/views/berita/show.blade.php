@extends('layouts.app')
@section('title', $berita->judul . ' - SMKS Islam Bustanul Ulum')
@section('content')
<section class="container halaman-publik">
    <a href="{{ route('berita') }}" class="lihat">← Semua berita</a>
    <article class="baca">
        <div class="kartu__meta" style="justify-content:flex-start;gap:12px">
            <span><i class="bi bi-calendar3"></i> {{ $berita->tanggal->translatedFormat('d F Y') }}</span>
            <span class="label label--{{ $berita->kategori === 'Prestasi' ? 'green' : 'blue' }}">{{ $berita->kategori }}</span>
        </div>
        <h1>{{ $berita->judul }}</h1>
        @if ($berita->gambar)<img src="{{ Storage::disk('public')->url($berita->gambar) }}" alt="" class="baca__gambar">@endif
        <div class="baca__isi">{!! nl2br(e($berita->isi)) !!}</div>
    </article>
    @if ($lainnya->isNotEmpty())
        <h2 class="judul-halaman" style="font-size:18px;margin-top:36px">Berita Lainnya</h2>
        <ul class="lainnya">
            @foreach ($lainnya as $l)
                <li><a href="{{ route('berita.show', $l->slug) }}">{{ $l->judul }}</a> <small>{{ $l->tanggal->translatedFormat('d M Y') }}</small></li>
            @endforeach
        </ul>
    @endif
</section>
@endsection
