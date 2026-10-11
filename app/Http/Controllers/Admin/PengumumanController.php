<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pengumuman;


class PengumumanController extends CrudController
{
    protected function model(): string { return Pengumuman::class; }
    protected function judul(): string { return 'Pengumuman'; }
    protected function rute(): string { return 'pengumuman'; }

    protected function urutan(): array
    {
        return ['tanggal', 'desc'];
    }

    protected function fields(): array
    {
        return [
            'judul' => ['label' => 'Judul pengumuman', 'type' => 'text', 'rules' => 'required|max:255'],
            'kategori' => ['label' => 'Kategori', 'type' => 'select', 'opsi' => ['Akademik', 'Kesiswaan', 'Umum'], 'rules' => 'required'],
            'tanggal' => ['label' => 'Tanggal', 'type' => 'date', 'rules' => 'required|date'],
        ];
    }

    protected function kolom(): array
    {
        return ['Judul' => 'judul', 'Kategori' => 'kategori', 'Tanggal' => 'tanggal'];
    }
}
