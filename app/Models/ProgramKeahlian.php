<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramKeahlian extends Model
{
    protected $table = 'program_keahlian';
    protected $guarded = [];

    protected $casts = ['aktif' => 'boolean'];

    /** Program yang tampil di website publik (jurusan nonaktif/dihapus di Pusat disembunyikan). */
    public function scopeTampil($q)
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('program_keahlian', 'aktif') ? $q->where('aktif', true) : $q;
    }
}
