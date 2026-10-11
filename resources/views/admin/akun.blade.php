@extends('admin.layout')
@section('title', 'Akun Saya')
@section('content')
<div class="kepala"><div><h1>Akun Saya</h1><p>Kelola data login dan keamanan akun Anda.</p></div></div>

@if ($errors->any())
    <div class="notif notif--err" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><div>Periksa kembali isian Anda:<ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
@endif

<div class="form-susun">
<form method="POST" action="{{ route('admin.akun.profil') }}" class="form">
    @csrf @method('PUT')
    <div class="form__badan">
        <h2 class="form__grup"><i class="bi bi-person-circle"></i> Data akun</h2>
        <label>Nama
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="100" autocomplete="name">
        </label>
        <label>Email (dipakai untuk login)
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="username">
        </label>
    </div>
    <div class="form__aksi"><button type="submit" class="tombol"><i class="bi bi-check-lg"></i> Simpan data</button></div>
</form>

<form method="POST" action="{{ route('admin.akun.password') }}" class="form" autocomplete="off">
    @csrf @method('PUT')
    <div class="form__badan">
        <h2 class="form__grup"><i class="bi bi-key-fill"></i> Ganti password</h2>
        <label>Password saat ini
            <input type="password" name="password_lama" required autocomplete="current-password">
        </label>
        <label>Password baru
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
            <small>Minimal 8 karakter, kombinasi huruf dan angka.</small>
        </label>
        <label>Ulangi password baru
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </label>
        <p class="bantuan"><i class="bi bi-info-circle"></i> Setelah password diganti, sesi login Anda di perangkat lain otomatis keluar.</p>
    </div>
    <div class="form__aksi"><button type="submit" class="tombol"><i class="bi bi-shield-check"></i> Ganti password</button></div>
</form>
</div>
@endsection
