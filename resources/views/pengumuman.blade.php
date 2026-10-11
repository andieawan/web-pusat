@extends('layouts.app')
@section('title', 'Pengumuman - SMKS Islam Bustanul Ulum')
@section('content')
<section class="container halaman-publik">
    <h1 class="judul-halaman">Pengumuman</h1>
    <div class="daftar">
        @forelse ($pengumuman as $p)
            <div class="item-tgl item-tgl--garis">
                <div class="tgl"><b>{{ $p->tanggal->format('d') }}</b><span>{{ $p->tanggal->translatedFormat('M Y') }}</span></div>
                <div><p>{{ $p->judul }}</p><small>{{ $p->kategori }}</small></div>
            </div>
        @empty
            <p>Belum ada pengumuman.</p>
        @endforelse
    </div>
    <div class="halaman">{{ $pengumuman->links() }}</div>
</section>
@endsection
