<?php

namespace App\Http\Controllers\Admin;

use App\Models\Staf;

class StafController extends CrudController
{
    protected function model(): string { return Staf::class; }
    protected function judul(): string { return 'Guru & Staf'; }
    protected function rute(): string { return 'staf'; }

    protected function urutan(): array
    {
        return ['urutan', 'asc'];
    }

    protected function fields(): array
    {
        return [
            'nama' => ['label' => 'Nama (beserta gelar)', 'type' => 'text', 'rules' => 'required|max:255'],
            'jabatan' => ['label' => 'Jabatan / mapel (mis. Kepala Sekolah, Guru TKJ)', 'type' => 'text', 'rules' => 'required|max:255'],
            'foto' => ['label' => 'Foto (opsional, maks. 8 MB)', 'type' => 'image'],
            'urutan' => ['label' => 'Urutan tampil (angka kecil tampil lebih dulu)', 'type' => 'number', 'rules' => 'required|integer|min:0'],
        ];
    }

    protected function kolom(): array
    {
        return ['Nama' => 'nama', 'Jabatan' => 'jabatan', 'Urutan' => 'urutan'];
    }
}
