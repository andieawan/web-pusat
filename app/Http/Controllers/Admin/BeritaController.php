<?php

namespace App\Http\Controllers\Admin;

use App\Models\Berita;
use Illuminate\Support\Str;

class BeritaController extends CrudController
{
    protected function model(): string { return Berita::class; }
    protected function judul(): string { return 'Berita'; }
    protected function rute(): string { return 'berita'; }

    protected function urutan(): array
    {
        return ['tanggal', 'desc'];
    }

    protected function fields(): array
    {
        return [
            'judul' => ['label' => 'Judul', 'type' => 'text', 'rules' => 'required|max:255'],
            'kategori' => ['label' => 'Kategori', 'type' => 'select', 'opsi' => ['Kegiatan', 'Prestasi', 'Informasi'], 'rules' => 'required'],
            'tanggal' => ['label' => 'Tanggal', 'type' => 'date', 'rules' => 'required|date'],
            'ringkas' => ['label' => 'Ringkasan (tampil di kartu beranda)', 'type' => 'textarea', 'rules' => 'required|max:400'],
            'isi' => ['label' => 'Isi berita', 'type' => 'textarea', 'rules' => 'required', 'baris' => 10],
            'gambar' => ['label' => 'Gambar (maks. 8 MB, otomatis diperkecil)', 'type' => 'image'],
            'terbit' => ['label' => 'Tampilkan di website', 'type' => 'bool'],
        ];
    }

    protected function kolom(): array
    {
        return ['Judul' => 'judul', 'Kategori' => 'kategori', 'Tanggal' => 'tanggal', 'Terbit' => 'terbit'];
    }

    protected function sebelumSimpan(array $data, ?object $item): array
    {
        // Slug dibuat sekali saat berita baru; tidak berubah saat judul diedit.
        if (! $item) {
            $data['slug'] = Str::slug($data['judul']) . '-' . Str::lower(Str::random(4));
        }

        return $data;
    }
}
