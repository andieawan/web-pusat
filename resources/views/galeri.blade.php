@extends('layouts.app')
@section('title', 'Galeri - SMKS Islam Bustanul Ulum')
@section('content')
<section class="container halaman-publik">
    <h1 class="judul-halaman">Galeri Kegiatan</h1>
    <div class="galeri galeri--penuh">
        @forelse ($galeri as $g)
            <figure>
                <div class="galeri__foto" @if ($g->gambar) style="background-image:url('{{ Storage::disk('public')->url($g->gambar) }}')" @endif></div>
                <figcaption>{{ $g->judul }}<small><i class="bi bi-calendar3"></i> {{ $g->tanggal->translatedFormat('j M Y') }}</small></figcaption>
            </figure>
        @empty
            <p>Belum ada foto.</p>
        @endforelse
    </div>
    <div class="halaman">{{ $galeri->links() }}</div>
</section>
@endsection
