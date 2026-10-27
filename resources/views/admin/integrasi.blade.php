@extends('admin.layout')
@section('title', 'Integrasi Pusat')
@section('content')
{{-- Susunan sama dengan halaman Integrasi di Absensi, BK, TU, dan PKL (gaya mengikuti panel website). --}}
@php
    $terakhirTeks = '-';
    if (! empty($p['terakhir'])) {
        $t = \Carbon\CarbonImmutable::parse($p['terakhir'], 'UTC')->setTimezone('Asia/Jakarta');
        $terakhirTeks = $t->format('d-m-Y H:i').' ('.$t->locale('id')->diffForHumans().')';
    }
@endphp
<div class="kepala"><div><h1>Integrasi Pusat</h1><p>Sambungan ke Pusat Sekolah: login admin (SSO), ringkasan jumlah siswa/guru, dan daftar jurusan.</p></div></div>

<div class="kartu">
    <div class="kartu__kepala"><h2>Pusat Sekolah</h2>
        @if ($p['aktif'] && $p['siap'])<span class="pil pil--ya">Aktif</span>@elseif ($p['aktif'])<span class="pil">Aktif, database belum diperbarui</span>@else<span class="pil pil--tidak">Nonaktif</span>@endif
    </div>
    <div class="tabel-bungkus" style="box-shadow:none;border:0;border-radius:0">
    <table class="tabel">
        <tr><td style="width:200px;color:var(--abu)">Alamat Pusat</td><td>{{ $p['url'] ?: '-' }}</td></tr>
        <tr><td style="color:var(--abu)">Redirect URI</td><td><code>{{ $p['callback'] }}</code></td></tr>
        <tr><td style="color:var(--abu)">URL webhook</td><td><code>{{ $p['webhook'] }}</code></td></tr>
        <tr><td style="color:var(--abu)">Tahun ajaran aktif</td><td>{{ $p['tahun'] ?: '-' }}</td></tr>
        <tr><td style="color:var(--abu)">Sinkron terakhir</td><td>{{ $terakhirTeks }}</td></tr>
        <tr><td style="color:var(--abu)">Data dari Pusat</td><td>{{ $p['jumlah'] ? collect($p['jumlah'])->map(fn ($n, $l) => $n.' '.$l)->implode(' · ') : '-' }}</td></tr>
        <tr><td style="color:var(--abu)">Sinkron otomatis</td><td>Ringkasan & jurusan diambil ulang otomatis secara berkala; bila Pusat mati, website memakai data terakhir.</td></tr>
    </table>
    </div>
    @if ($p['aktif'])
    <div style="padding:14px 20px;display:flex;gap:8px;flex-wrap:wrap">
        <form method="POST" action="{{ route('admin.integrasi.pusat') }}">@csrf<button class="tombol tombol--garis">Uji koneksi</button></form>
        <form method="POST" action="{{ route('admin.integrasi.sinkron') }}">@csrf<button class="tombol">Sinkron sekarang</button></form>
    </div>
    @else
    <p style="padding:14px 20px">Isi <code>PUSAT_ENABLED=true</code>, <code>PUSAT_URL</code>, <code>PUSAT_CLIENT_ID</code>, <code>PUSAT_CLIENT_SECRET</code>, <code>PUSAT_WEBHOOK_SECRET</code> di <code>.env</code>.</p>
    @endif
</div>

<div class="kartu" style="margin-top:18px">
    <div class="kartu__kepala"><h2>Aplikasi PKL (mitra industri)</h2>
        @if ($p['pklUrl'])<span class="pil pil--ya">Tersambung</span>@else<span class="pil pil--tidak">Belum diatur</span>@endif
    </div>
    <div class="tabel-bungkus" style="box-shadow:none;border:0;border-radius:0">
    <table class="tabel">
        <tr><td style="width:200px;color:var(--abu)">Alamat PKL</td><td>{{ $p['pklUrl'] ?: '- (sambungkan "PKL → Website" di Pusat → Administrasi → Sambungan)' }}</td></tr>
        <tr><td style="color:var(--abu)">Ditampilkan</td><td>Halaman Akademik → "Mitra Industri (DUDI)": nama & bidang DUDI dan jumlah siswa PKL. Tanpa data siswa.</td></tr>
        <tr><td style="color:var(--abu)">Data terakhir</td><td>{{ $p['mitra'] ? count($p['mitra']['mitra'] ?? []).' mitra' : '-' }}{{ $p['mitraWaktu'] ? ' · diambil '.\Carbon\CarbonImmutable::parse($p['mitraWaktu'], 'UTC')->setTimezone('Asia/Jakarta')->format('d-m-Y H:i') : '' }}</td></tr>
    </table>
    </div>
</div>

@if ($p['jurusanBelumAda'])
<div class="notif" style="margin-top:18px;background:var(--biru-bg);border-color:var(--garis)"><i class="bi bi-info-circle-fill"></i><div>Jurusan di Pusat yang belum punya halaman Program Keahlian: <b>{{ implode(', ', $p['jurusanBelumAda']) }}</b>. <a href="{{ route('admin.program.create') }}">Tambahkan</a>.</div></div>
@endif
@endsection
