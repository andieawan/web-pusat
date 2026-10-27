<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staf extends Model
{
    protected $table = 'staf';
    protected $guarded = [];

    protected $casts = ['aktif' => 'boolean'];

    /** Yang tampil di website publik (pegawai nonaktif di Pusat disembunyikan). */
    public function scopeTampil($q)
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('staf', 'aktif') ? $q->where('aktif', true) : $q;
    }
}
