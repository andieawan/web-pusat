<?php

namespace App\Http\Controllers\Admin;

use App\Models\ProgramKeahlian;


class ProgramKeahlianController extends CrudController
{
    protected function model(): string { return ProgramKeahlian::class; }
    protected function judul(): string { return 'Program Keahlian'; }
    protected function rute(): string { return 'program'; }

    protected function urutan(): array
    {
        return ['urutan', 'asc'];
    }

    protected function fields(): array
    {
        return [
            'nama' => ['label' => 'Nama program', 'type' => 'text', 'rules' => 'required|max:255'],
            'ikon' => ['label' => 'Ikon (nama dari icons.getbootstrap.com, mis. bi-pc-display)', 'type' => 'text', 'rules' => ['required', 'max:50', 'regex:/^bi-[a-z0-9-]+$/']],
            'deskripsi' => ['label' => 'Deskripsi singkat (tampil di halaman Akademik)', 'type' => 'textarea', 'rules' => 'nullable|string|max:1000'],
            'urutan' => ['label' => 'Urutan tampil', 'type' => 'number', 'rules' => 'required|integer|min:0'],
        ];
    }

    protected function kolom(): array
    {
        return ['Program' => 'nama', 'Ikon' => 'ikon', 'Urutan' => 'urutan'];
    }
}
