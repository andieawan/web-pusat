<?php

namespace App\Http\Controllers\Admin;

use App\Models\Ekstrakurikuler;

class EkstrakurikulerController extends CrudController
{
    protected function model(): string { return Ekstrakurikuler::class; }
    protected function judul(): string { return 'Ekstrakurikuler'; }
    protected function rute(): string { return 'ekstrakurikuler'; }

    protected function urutan(): array
    {
        return ['urutan', 'asc'];
    }

    protected function fields(): array
    {
        return [
            'nama' => ['label' => 'Nama kegiatan', 'type' => 'text', 'rules' => 'required|max:255'],
            'ikon' => ['label' => 'Ikon (nama dari icons.getbootstrap.com, mis. bi-people-fill)', 'type' => 'text', 'rules' => ['required', 'max:50', 'regex:/^bi-[a-z0-9-]+$/']],
            'pembina' => ['label' => 'Pembina', 'type' => 'text', 'rules' => 'nullable|max:255'],
            'jadwal' => ['label' => 'Jadwal (mis. Jumat, 14.00 WIB)', 'type' => 'text', 'rules' => 'nullable|max:100'],
            'deskripsi' => ['label' => 'Deskripsi singkat', 'type' => 'textarea', 'rules' => 'nullable|string|max:1000'],
            'urutan' => ['label' => 'Urutan tampil', 'type' => 'number', 'rules' => 'required|integer|min:0'],
        ];
    }

    protected function kolom(): array
    {
        return ['Kegiatan' => 'nama', 'Pembina' => 'pembina', 'Jadwal' => 'jadwal', 'Urutan' => 'urutan'];
    }
}
