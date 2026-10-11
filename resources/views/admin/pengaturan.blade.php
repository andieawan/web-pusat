@extends('admin.layout')
@section('title', 'Pengaturan Situs')
@section('content')
<div class="kepala"><div><h1>Pengaturan Situs</h1><p>Identitas, kontak, dan teks halaman profil, akademik, serta kesiswaan.</p></div></div>

@if ($errors->any())
    <div class="notif notif--err" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><div>Periksa kembali isian Anda:<ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
@endif

<form method="POST" action="{{ route('admin.pengaturan.update') }}" class="form" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="form__badan">
    <h2 class="form__grup" id="logo">Logo Sekolah</h2>
    <label>Unggah logo (PNG/JPG/WebP, maks. 2 MB; latar transparan lebih bagus)
        @if (! empty($nilai['logo']))<img src="{{ Storage::disk('public')->url($nilai['logo']) }}" class="pratinjau" alt="Logo saat ini">@endif
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp">
    </label>
    @if (! empty($nilai['logo']))
        <label class="cek"><input type="checkbox" name="hapus_logo" value="1"> Hapus logo ini (kembali ke bawaan)</label>
    @endif

    <h2 class="form__grup" id="foto-kepsek">Foto Kepala Sekolah (tampil di halaman Profil)</h2>
    <label>Unggah foto (JPG/PNG/WebP, maks. 8 MB; otomatis diperkecil. Foto setengah badan, rasio persegi paling pas)
        @if (! empty($nilai['profil_foto_kepsek']))<img src="{{ Storage::disk('public')->url($nilai['profil_foto_kepsek']) }}" class="pratinjau" alt="Foto kepala sekolah saat ini">@endif
        <input type="file" name="foto_kepsek" accept="image/png,image/jpeg,image/webp">
    </label>
    @if (! empty($nilai['profil_foto_kepsek']))
        <label class="cek"><input type="checkbox" name="hapus_foto_kepsek" value="1"> Hapus foto ini (kembali ke huruf inisial)</label>
    @endif

    @foreach ($grup as $namaGrup => $daftar)
        <h2 class="form__grup">{{ $namaGrup }}</h2>
        @foreach ($daftar as $kunci => [$label, $tipe])
            <label>{{ $label }}
                @if ($tipe === 'textarea')
                    <textarea name="{{ $kunci }}" rows="5">{{ old($kunci, $nilai[$kunci] ?? '') }}</textarea>
                @else
                    <input type="text" name="{{ $kunci }}" value="{{ old($kunci, $nilai[$kunci] ?? '') }}">
                @endif
            </label>
        @endforeach
    @endforeach
    </div>
    <div class="form__aksi"><button type="submit" class="tombol"><i class="bi bi-check-lg"></i> Simpan pengaturan</button></div>
</form>
@endsection
