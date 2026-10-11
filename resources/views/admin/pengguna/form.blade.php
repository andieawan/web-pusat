@extends('admin.layout')
@section('title', $item ? 'Edit Akun' : 'Tambah Akun')
@section('content')
<div class="kepala">
    <div>
        <h1>{{ $item ? 'Edit' : 'Tambah' }} Akun Admin</h1>
        <p><a href="{{ route('admin.pengguna.index') }}" class="sub"><i class="bi bi-arrow-left"></i> Kembali ke daftar</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="notif notif--err" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><div>Periksa kembali isian Anda:<ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
@endif

<form method="POST" class="form" autocomplete="off"
      action="{{ $item ? route('admin.pengguna.update', $item) : route('admin.pengguna.store') }}">
    @csrf
    @if ($item) @method('PUT') @endif
    <div class="form__badan">
        <label>Nama
            <input type="text" name="name" value="{{ old('name', $item?->name) }}" required maxlength="100">
        </label>
        <label>Email (dipakai untuk login)
            <input type="email" name="email" value="{{ old('email', $item?->email) }}" required maxlength="255">
        </label>
        <label>{{ $item ? 'Password baru' : 'Password' }}
            <input type="password" name="password" minlength="8" autocomplete="new-password" @required(! $item)>
            <small>{{ $item ? 'Kosongkan bila tidak ingin mengubah. ' : '' }}Minimal 8 karakter, kombinasi huruf dan angka.</small>
        </label>
        <label>Ulangi password
            <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" @required(! $item)>
        </label>
        <p class="bantuan"><i class="bi bi-info-circle"></i> Semua akun di halaman ini memiliki akses penuh ke panel admin.</p>
    </div>
    <div class="form__aksi">
        <button type="submit" class="tombol"><i class="bi bi-check-lg"></i> Simpan</button>
        <a href="{{ route('admin.pengguna.index') }}" class="batal">Batal</a>
    </div>
</form>
@endsection
