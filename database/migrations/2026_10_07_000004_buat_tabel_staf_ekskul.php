<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staf', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('jabatan');
            $t->string('foto')->nullable();
            $t->unsignedSmallInteger('urutan')->default(0);
            $t->timestamps();
        });

        Schema::create('ekstrakurikuler', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('ikon', 50)->default('bi-people-fill');
            $t->string('pembina')->nullable();
            $t->string('jadwal', 100)->nullable();
            $t->text('deskripsi')->nullable();
            $t->unsignedSmallInteger('urutan')->default(0);
            $t->timestamps();
        });

        Schema::table('program_keahlian', function (Blueprint $t) {
            $t->text('deskripsi')->nullable()->after('ikon');
        });
    }

    public function down(): void
    {
        Schema::table('program_keahlian', fn (Blueprint $t) => $t->dropColumn('deskripsi'));
        Schema::dropIfExists('ekstrakurikuler');
        Schema::dropIfExists('staf');
    }
};
