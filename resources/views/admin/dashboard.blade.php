@extends('admin.layout')
@section('title', 'Dashboard')
@section('content')
@php
    $jam = (int) now()->format('G');
    $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
    $selesai = collect($kelengkapan)->where(1, true)->count();
    $persen = (int) round($selesai / max(1, count($kelengkapan)) * 100);
@endphp
<section class="salam">
    <div>
        <h1>{{ $salam }}, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</h1>
        <p>{{ now()->translatedFormat('l, d F Y') }} &middot; Kelola seluruh konten website dari sini.</p>
    </div>
    <div class="salam__aksi">
        <a href="{{ route('admin.berita.create') }}" class="tombol"><i class="bi bi-plus-lg"></i> Tulis berita</a>
        <a href="{{ route('admin.pengumuman.create') }}" class="tombol tombol--garis"><i class="bi bi-megaphone"></i> Pengumuman</a>
        <a href="{{ route('admin.galeri.create') }}" class="tombol tombol--garis"><i class="bi bi-camera"></i> Foto</a>
    </div>
</section>

@if (! empty($jurusanBelumAda))
    <section class="kartu">
        <div class="kartu__kepala"><h2>Jurusan di Pusat belum ada di website</h2></div>
        <p>Belum ada Program Keahlian dengan nama: <b>{{ implode(', ', $jurusanBelumAda) }}</b>.
            <a href="{{ route('admin.program.create') }}" class="sub">Tambah program keahlian</a>
            <small>(Nama dicocokkan persis; abaikan bila penulisannya memang berbeda.)</small></p>
    </section>
@endif

<div class="ringkas">
    @foreach ($kartu as $k)
        <a href="{{ route($k['rute']) }}" class="ringkas__kartu warna-{{ $k['warna'] }}">
            <span class="ringkas__ikon"><i class="bi {{ $k['ikon'] }}"></i></span>
            <div><b>{{ $k['jumlah'] }}</b><span>{{ $k['nama'] }}</span></div>
        </a>
    @endforeach
</div>

<div class="dasbor">
    <div class="dasbor__kolom">
        <section class="kartu">
            <div class="kartu__kepala"><h2>Berita terbaru</h2><a href="{{ route('admin.berita.index') }}">Lihat semua</a></div>
            <ul class="daftar">
                @forelse ($beritaTerbaru as $b)
                    <li>
                        <div class="daftar__utama">
                            <a href="{{ route('admin.berita.edit', $b->id) }}">{{ $b->judul }}</a>
                            <small>{{ $b->tanggal->translatedFormat('d M Y') }}</small>
                        </div>
                        <span class="pil pil--{{ $b->kategori }}">{{ $b->kategori }}</span>
                        <span class="pil {{ $b->terbit ? 'pil--ya' : 'pil--tidak' }}">{{ $b->terbit ? 'Terbit' : 'Draf' }}</span>
                    </li>
                @empty
                    <li class="daftar__kosong">Belum ada berita. <a href="{{ route('admin.berita.create') }}" class="sub">Tulis yang pertama</a></li>
                @endforelse
            </ul>
        </section>

        <section class="kartu">
            <div class="kartu__kepala"><h2>Pengumuman terbaru</h2><a href="{{ route('admin.pengumuman.index') }}">Lihat semua</a></div>
            <ul class="daftar">
                @forelse ($pengumumanTerbaru as $p)
                    <li>
                        <div class="daftar__utama">
                            <a href="{{ route('admin.pengumuman.edit', $p->id) }}">{{ $p->judul }}</a>
                            <small>{{ $p->tanggal->translatedFormat('d M Y') }}</small>
                        </div>
                        <span class="pil">{{ $p->kategori }}</span>
                    </li>
                @empty
                    <li class="daftar__kosong">Belum ada pengumuman.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <div class="dasbor__kolom">
        <section class="kartu">
            <div class="kartu__kepala"><h2>Kelengkapan website</h2><span class="pil {{ $persen === 100 ? 'pil--ya' : '' }}">{{ $persen }}%</span></div>
            <div class="progres__ket">{{ $selesai }} dari {{ count($kelengkapan) }} langkah selesai</div>
            <div class="progres" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100"><i style="width:{{ $persen }}%"></i></div>
            <ul class="daftar ceklis">
                @foreach ($kelengkapan as [$nama, $beres, $url])
                    <li class="{{ $beres ? 'ok' : 'belum' }}">
                        <a href="{{ $url }}"><i class="bi {{ $beres ? 'bi-check-circle-fill' : 'bi-circle' }}"></i> {{ $nama }}</a>
                        @unless ($beres)<span class="ceklis__ajak">Isi &rarr;</span>@endunless
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="kartu">
            <div class="kartu__kepala"><h2>Agenda mendatang</h2><a href="{{ route('admin.agenda.index') }}">Lihat semua</a></div>
            <ul class="daftar">
                @forelse ($agendaMendatang as $a)
                    <li>
                        <div class="tgl"><b>{{ $a->tanggal->format('d') }}</b><span>{{ $a->tanggal->translatedFormat('M') }}</span></div>
                        <div class="daftar__utama">
                            <a href="{{ route('admin.agenda.edit', $a->id) }}">{{ $a->judul }}</a>
                            @if ($a->waktu)<small>{{ $a->waktu }}</small>@endif
                        </div>
                    </li>
                @empty
                    <li class="daftar__kosong">Tidak ada agenda mendatang.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
