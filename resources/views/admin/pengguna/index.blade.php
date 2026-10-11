@extends('admin.layout')
@section('title', 'Pengguna Admin')
@section('content')
<div class="kepala">
    <div><h1>Pengguna Admin</h1><p>{{ $items->total() }} akun dengan akses penuh ke panel.</p></div>
    <div class="kepala__aksi">
        <a href="{{ route('admin.pengguna.create') }}" class="tombol"><i class="bi bi-person-plus-fill"></i> Tambah akun</a>
    </div>
</div>

@if ($errors->any())
    <div class="notif notif--err" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><div>@foreach ($errors->all() as $e){{ $e }}@endforeach</div></div>
@endif

<div class="tabel-bungkus">
<table class="tabel">
    <thead><tr><th scope="col">Nama</th><th scope="col">Email</th><th scope="col">Dibuat</th><th scope="col">Aksi</th></tr></thead>
    <tbody>
    @foreach ($items as $u)
        <tr>
            <td class="judul">{{ $u->name }} @if ($u->is(auth()->user()))<span class="pil pil--ya">Anda</span>@endif</td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->created_at?->translatedFormat('d M Y') }}</td>
            <td>
                <div class="aksi">
                    <a href="{{ route('admin.pengguna.edit', $u) }}" class="ikon-tbl"><i class="bi bi-pencil-square"></i> Edit</a>
                    @unless ($u->is(auth()->user()))
                        <form method="POST" action="{{ route('admin.pengguna.destroy', $u) }}" data-konfirmasi="{{ 'Hapus akun ' . $u->email . '?' }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="ikon-tbl hapus"><i class="bi bi-trash3"></i> Hapus</button>
                        </form>
                    @endunless
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="halaman">{{ $items->links() }}</div>
@endsection
