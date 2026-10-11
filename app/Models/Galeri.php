<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Galeri extends Model
{
    protected $table = 'galeri';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }
}
