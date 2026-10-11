<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }
}
