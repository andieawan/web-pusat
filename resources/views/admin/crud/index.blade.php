@extends('admin.layout')
@section('title', $judul)
@section('content')
@php $jenisKolom = fn ($a) => $fields[$a]['type'] ?? null; @endphp
<div class="kepala">
    <div>
        <h1>{{ $judul }}</h1>
        <p>{{ $items->total() }} data</p>
    </div>
    <div class="kepala__aksi">
        <a href="{{ route('admin.' . $rute . '.create') }}" class="tombol"><i class="bi bi-plus-lg"></i> Tambah {{ $judul }}</a>
    </div>
</div>

@if ($items->count() > 5)
<div class="alat">
    <label class="cari"><span class="sr">Cari di halaman ini</span><i class="bi bi-search"></i>
        <input type="search" id="cari-tabel" placeholder="Cari di halaman ini..." autocomplete="off"></label>
    <span class="jumlah" id="hasil-cari" aria-live="polite"></span>
</div>
@endif

<div class="tabel-bungkus">
<table class="tabel">
    <thead><tr>
        @foreach ($kolom as $label => $atribut)<th scope="col">{{ $label }}</th>@endforeach
        <th scope="col">Aksi</th>
    </tr></thead>
    <tbody>
    @forelse ($items as $item)
        <tr data-cari="{{ mb_strtolower(collect($kolom)->map(fn ($a) => is_scalar($item->{$a}) ? (string) $item->{$a} : '')->implode(' ')) }}">
            @foreach ($kolom as $atribut)
                @php $v = $item->{$atribut}; $jenis = $jenisKolom($atribut); @endphp
                <td @class(['judul' => $loop->first])>
                    @if ($jenis === 'image')
                        @if ($v)<img src="{{ Storage::disk('public')->url($v) }}" alt="" class="mini" loading="lazy">@else<span class="sub">-</span>@endif
                    @elseif ($jenis === 'bool' || is_bool($v))
                        <span class="pil {{ $v ? 'pil--ya' : 'pil--tidak' }}">{{ $v ? 'Ya' : 'Tidak' }}</span>
                    @elseif ($atribut === 'kategori' && $v)
                        <span class="pil pil--{{ $v }}">{{ $v }}</span>
                    @elseif ($v instanceof \Carbon\Carbon) {{ $v->translatedFormat('d M Y') }}
                    @else {{ \Illuminate\Support\Str::limit((string) $v, 70) }}
                    @endif
                </td>
            @endforeach
            <td>
                <div class="aksi">
                    <a href="{{ route('admin.' . $rute . '.edit', $item->id) }}" class="ikon-tbl"><i class="bi bi-pencil-square"></i> Edit</a>
                    <form method="POST" action="{{ route('admin.' . $rute . '.destroy', $item->id) }}" data-konfirmasi="Hapus data ini? Tindakan ini tidak bisa dibatalkan.">
                        @csrf @method('DELETE')
                        <button type="submit" class="ikon-tbl hapus"><i class="bi bi-trash3"></i> Hapus</button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="{{ count($kolom) + 1 }}" class="kosong">
            <i class="bi bi-inbox"></i>Belum ada data {{ mb_strtolower($judul) }}.<br>
            <a href="{{ route('admin.' . $rute . '.create') }}" class="tombol tombol--kecil"><i class="bi bi-plus-lg"></i> Tambah sekarang</a>
        </td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="halaman">{{ $items->links() }}</div>
@endsection
