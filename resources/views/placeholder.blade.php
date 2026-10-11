@extends('layouts.app')

@section('title', $judul . ' - SMKS Islam Bustanul Ulum')

@section('content')
<section class="container" style="padding:80px 0;text-align:center">
    <h1 style="color:var(--biru-tua)">{{ $judul }}</h1>
    <p style="color:var(--abu)">Halaman ini sedang disiapkan.</p>
    <a href="{{ route('beranda') }}" class="btn btn--primary">Kembali ke Beranda</a>
</section>
@endsection
