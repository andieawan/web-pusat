<?php

namespace App\Http\Controllers\Admin;

use App\Models\Slide;

class SlideController extends CrudController
{
    protected function model(): string { return Slide::class; }
    protected function judul(): string { return 'Banner Hero'; }
    protected function rute(): string { return 'slide'; }

    protected function urutan(): array
    {
        return ['urutan', 'asc'];
    }

    protected function fields(): array
    {
        return [
            'gambar' => ['label' => 'Foto banner (disarankan landscape, min. 1600 px lebar)', 'type' => 'image', 'wajib' => true],
            'judul' => ['label' => 'Catatan (hanya terlihat admin)', 'type' => 'text', 'rules' => 'nullable|max:255'],
            'urutan' => ['label' => 'Urutan tampil', 'type' => 'number', 'rules' => 'required|integer|min:0'],
            'aktif' => ['label' => 'Tampilkan di beranda', 'type' => 'bool'],
        ];
    }

    protected function kolom(): array
    {
        return ['Catatan' => 'judul', 'Foto' => 'gambar', 'Urutan' => 'urutan', 'Aktif' => 'aktif'];
    }
}
