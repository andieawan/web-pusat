<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Berita extends Model
{
    protected $table = 'berita';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'terbit' => 'boolean'];
    }
}
