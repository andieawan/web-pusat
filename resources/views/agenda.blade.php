@extends('layouts.app')
@section('title', 'Agenda Sekolah - SMKS Islam Bustanul Ulum')
@section('content')
<section class="container halaman-publik">
    <h1 class="judul-halaman">Agenda Sekolah</h1>
    <div class="daftar">
        @forelse ($agenda as $a)
            <div class="item-tgl item-tgl--garis">
                <div class="tgl tgl--putih"><b>{{ $a->tanggal->format('d') }}</b><span>{{ $a->tanggal->translatedFormat('M Y') }}</span></div>
                <div><p>{{ $a->judul }}</p><small>{{ $a->waktu }}</small></div>
            </div>
        @empty
            <p>Belum ada agenda.</p>
        @endforelse
    </div>
    <div class="halaman">{{ $agenda->links() }}</div>
</section>
@endsection
