<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slide', function (Blueprint $t) {
            $t->id();
            $t->string('judul')->nullable(); // catatan untuk admin saja
            $t->string('gambar');
            $t->unsignedSmallInteger('urutan')->default(0);
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slide');
    }
};
