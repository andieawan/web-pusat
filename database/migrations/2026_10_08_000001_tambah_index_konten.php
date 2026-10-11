<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Index untuk kolom yang dipakai menyaring/mengurutkan di halaman publik dan admin. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita', fn (Blueprint $t) => $t->index(['terbit', 'tanggal'], 'berita_terbit_tanggal_idx'));
        Schema::table('pengumuman', fn (Blueprint $t) => $t->index(['kategori', 'tanggal'], 'pengumuman_kategori_tanggal_idx'));
        Schema::table('agenda', fn (Blueprint $t) => $t->index('tanggal', 'agenda_tanggal_idx'));
        Schema::table('galeri', fn (Blueprint $t) => $t->index('tanggal', 'galeri_tanggal_idx'));
        Schema::table('slide', fn (Blueprint $t) => $t->index(['aktif', 'urutan'], 'slide_aktif_urutan_idx'));
    }

    public function down(): void
    {
        Schema::table('berita', fn (Blueprint $t) => $t->dropIndex('berita_terbit_tanggal_idx'));
        Schema::table('pengumuman', fn (Blueprint $t) => $t->dropIndex('pengumuman_kategori_tanggal_idx'));
        Schema::table('agenda', fn (Blueprint $t) => $t->dropIndex('agenda_tanggal_idx'));
        Schema::table('galeri', fn (Blueprint $t) => $t->dropIndex('galeri_tanggal_idx'));
        Schema::table('slide', fn (Blueprint $t) => $t->dropIndex('slide_aktif_urutan_idx'));
    }
};
