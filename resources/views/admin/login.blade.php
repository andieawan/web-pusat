<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Login Admin - SMKS Islam Bustanul Ulum</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="{{ \App\Support\Aset::url('css/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ \App\Support\Aset::url('css/admin.css') }}" rel="stylesheet">
    <script src="{{ \App\Support\Aset::url('js/admin.js') }}" defer></script>
</head>
<body class="login">
<form method="POST" action="{{ route('login.proses') }}" class="login__kotak">
    @csrf
    <img src="{{ \App\Models\Pengaturan::logoUrl() }}" alt="" class="login__logo" data-sembunyi-galat>
    <h1>Login Admin</h1>
    <p>SMKS Islam Bustanul Ulum Pakusari</p>
    @if ($errors->any())
        <div class="galat" role="alert">{{ $errors->first() }}</div>
    @endif
    <label>Email
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
    </label>
    <label>Password
        <span class="kolom-sandi">
            <input type="password" name="password" required autocomplete="current-password">
            <button type="button" data-lihat-sandi aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
        </span>
    </label>
    <label class="cek"><input type="checkbox" name="ingat" value="1"> Ingat saya di perangkat ini</label>
    <button type="submit" class="tombol">Masuk</button>
    @if (\App\Services\PusatKlien::aktif())
        <a href="{{ route('pusat.masuk') }}" class="tombol tombol--garis" style="display:block;text-align:center;margin-top:10px">Masuk lewat Pusat</a>
    @endif
    @if (\App\Services\PusatKlien::aktif())
        <p class="petunjuk-login">Akun admin dari Pusat bisa memakai email &amp; password Pusat di form ini bila Pusat sedang tidak bisa dihubungi.</p>
    @endif
    <a href="{{ route('beranda') }}" class="kembali">← Kembali ke website</a>
</form>
</body>
</html>
