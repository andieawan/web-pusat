<?php

namespace App\Http\Controllers\Admin;

use App\Models\Galeri;


class GaleriController extends CrudController
{
    protected function model(): string { return Galeri::class; }
    protected function judul(): string { return 'Foto Galeri'; }
    protected function rute(): string { return 'galeri'; }

    protected function urutan(): array
    {
        return ['tanggal', 'desc'];
    }

    protected function fields(): array
    {
        return [
            'judul' => ['label' => 'Keterangan foto', 'type' => 'text', 'rules' => 'required|max:255'],
            'tanggal' => ['label' => 'Tanggal kegiatan', 'type' => 'date', 'rules' => 'required|date'],
            'gambar' => ['label' => 'Foto (maks. 8 MB, otomatis diperkecil)', 'type' => 'image'],
        ];
    }

    protected function kolom(): array
    {
        return ['Keterangan' => 'judul', 'Tanggal' => 'tanggal'];
    }
}
