@extends('admin.layout')
@section('title', ($item ? 'Edit ' : 'Tambah ') . $judul)
@section('content')
<div class="kepala">
    <div>
        <h1>{{ $item ? 'Edit' : 'Tambah' }} {{ $judul }}</h1>
        <p><a href="{{ route('admin.' . $rute . '.index') }}" class="sub"><i class="bi bi-arrow-left"></i> Kembali ke daftar</a></p>
    </div>
</div>

@if ($errors->any())
    <div class="notif notif--err" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><div>Periksa kembali isian Anda:<ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
@endif

<form method="POST" enctype="multipart/form-data" class="form"
      action="{{ $item ? route('admin.' . $rute . '.update', $item->id) : route('admin.' . $rute . '.store') }}">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="form__badan">
    @foreach ($fields as $nama => $f)
        @php $nilai = old($nama, $item?->{$nama}); @endphp
        @if ($nilai instanceof \Carbon\Carbon) @php $nilai = $nilai->format('Y-m-d'); @endphp @endif

        @if ($f['type'] === 'bool')
            <input type="hidden" name="{{ $nama }}" value="0">
            <label class="cek"><input type="checkbox" name="{{ $nama }}" value="1" {{ old($nama, $item ? $item->{$nama} : true) ? 'checked' : '' }}> {{ $f['label'] }}</label>
        @else
            <label>{{ $f['label'] }}
            @if ($f['type'] === 'textarea')
                <textarea name="{{ $nama }}" rows="{{ $f['baris'] ?? 3 }}">{{ $nilai }}</textarea>
            @elseif ($f['type'] === 'select')
                <select name="{{ $nama }}">
                    @foreach ($f['opsi'] as $o)<option value="{{ $o }}" @selected($nilai === $o)>{{ $o }}</option>@endforeach
                </select>
            @elseif ($f['type'] === 'image')
                @if ($item && $item->{$nama})<img src="{{ Storage::disk('public')->url($item->{$nama}) }}" class="pratinjau" alt="Gambar saat ini">@endif
                <input type="file" name="{{ $nama }}" accept="image/*">
            @else
                <input type="{{ $f['type'] }}" name="{{ $nama }}" value="{{ $nilai }}">
            @endif
            @isset($f['bantuan'])<small>{{ $f['bantuan'] }}</small>@endisset
            </label>
        @endif
    @endforeach
    </div>

    <div class="form__aksi">
        <button type="submit" class="tombol"><i class="bi bi-check-lg"></i> Simpan</button>
        <a href="{{ route('admin.' . $rute . '.index') }}" class="batal">Batal</a>
    </div>
</form>
@endsection
