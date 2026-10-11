<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    protected $table = 'slide';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}
