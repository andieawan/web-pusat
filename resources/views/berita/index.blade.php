@extends('layouts.app')
@section('title', 'Berita - SMKS Islam Bustanul Ulum')
@section('content')
<section class="container halaman-publik">
    <h1 class="judul-halaman">Berita</h1>
    @if ($q !== '')
        <p class="hasil-cari">Hasil pencarian untuk <strong>“{{ $q }}”</strong> — <a href="{{ route('berita') }}">tampilkan semua</a></p>
    @endif
    <div class="berita berita--grid">
        @forelse ($berita as $b)
            <article class="kartu">
                <div class="kartu__gambar" @if ($b->gambar) style="background-image:url('{{ Storage::disk('public')->url($b->gambar) }}')" @endif></div>
                <div class="kartu__isi">
                    <div class="kartu__meta">
                        <span><i class="bi bi-calendar3"></i> {{ $b->tanggal->translatedFormat('d M Y') }}</span>
                        <span class="label label--{{ $b->kategori === 'Prestasi' ? 'green' : 'blue' }}">{{ $b->kategori }}</span>
                    </div>
                    <h3>{{ $b->judul }}</h3>
                    <p>{{ $b->ringkas }}</p>
                    <a href="{{ route('berita.show', $b->slug) }}" class="selengkapnya">Selengkapnya <i class="bi bi-arrow-right"></i></a>
                </div>
            </article>
        @empty
            <p>{{ $q !== '' ? 'Tidak ada berita yang cocok.' : 'Belum ada berita.' }}</p>
        @endforelse
    </div>
    <div class="halaman">{{ $berita->links() }}</div>
</section>
@endsection
