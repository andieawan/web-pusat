<?php

namespace App\Http\Controllers\Admin;

use App\Models\Agenda;


class AgendaController extends CrudController
{
    protected function model(): string { return Agenda::class; }
    protected function judul(): string { return 'Agenda'; }
    protected function rute(): string { return 'agenda'; }

    protected function urutan(): array
    {
        return ['tanggal', 'desc'];
    }

    protected function fields(): array
    {
        return [
            'judul' => ['label' => 'Nama kegiatan', 'type' => 'text', 'rules' => 'required|max:255'],
            'tanggal' => ['label' => 'Tanggal', 'type' => 'date', 'rules' => 'required|date'],
            'waktu' => ['label' => 'Waktu (mis. 07.00 - Selesai)', 'type' => 'text', 'rules' => 'nullable|max:50'],
        ];
    }

    protected function kolom(): array
    {
        return ['Kegiatan' => 'judul', 'Tanggal' => 'tanggal', 'Waktu' => 'waktu'];
    }
}
