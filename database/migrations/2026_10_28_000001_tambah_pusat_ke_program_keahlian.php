<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Program Keahlian disalin dari jurusan di Pusat (penaut uuid); jurusan nonaktif/dihapus di Pusat disembunyikan.
        Schema::table('program_keahlian', function (Blueprint $t) {
            $t->uuid('pusat_uuid')->nullable()->unique()->after('id');
            $t->string('kode', 20)->nullable()->after('pusat_uuid');
            $t->boolean('aktif')->default(true)->after('urutan');
        });
    }

    public function down(): void
    {
        Schema::table('program_keahlian', function (Blueprint $t) {
            $t->dropUnique(['pusat_uuid']);
            $t->dropColumn(['pusat_uuid', 'kode', 'aktif']);
        });
    }
};
