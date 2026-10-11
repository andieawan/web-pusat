<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita', function (Blueprint $t) {
            $t->id();
            $t->string('judul');
            $t->string('slug')->unique();
            $t->string('kategori', 30)->default('Informasi'); // Kegiatan / Prestasi / Informasi
            $t->text('ringkas');
            $t->longText('isi');
            $t->string('gambar')->nullable();
            $t->date('tanggal');
            $t->boolean('terbit')->default(true);
            $t->timestamps();
        });

        Schema::create('pengumuman', function (Blueprint $t) {
            $t->id();
            $t->string('judul');
            $t->string('kategori', 30)->default('Akademik');
            $t->date('tanggal');
            $t->timestamps();
        });

        Schema::create('agenda', function (Blueprint $t) {
            $t->id();
            $t->string('judul');
            $t->date('tanggal');
            $t->string('waktu', 50)->nullable();
            $t->timestamps();
        });

        Schema::create('galeri', function (Blueprint $t) {
            $t->id();
            $t->string('judul');
            $t->string('gambar')->nullable();
            $t->date('tanggal');
            $t->timestamps();
        });

        Schema::create('program_keahlian', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('ikon', 50)->default('bi-book'); // nama ikon Bootstrap Icons
            $t->unsignedSmallInteger('urutan')->default(0);
            $t->timestamps();
        });

        Schema::create('pengaturan', function (Blueprint $t) {
            $t->id();
            $t->string('kunci')->unique();
            $t->text('nilai')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['pengaturan', 'program_keahlian', 'galeri', 'agenda', 'pengumuman', 'berita'] as $tabel) {
            Schema::dropIfExists($tabel);
        }
    }
};
