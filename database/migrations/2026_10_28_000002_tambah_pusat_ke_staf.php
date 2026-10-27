<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guru & Staf disalin dari data pegawai Pusat; foto & urutan tetap diatur di website.
        Schema::table('staf', function (Blueprint $t) {
            $t->uuid('pusat_uuid')->nullable()->unique()->after('id');
            $t->string('jabatan_pusat')->nullable()->after('jabatan'); // jabatan terakhir dari Pusat (untuk mengenali perubahan)
            $t->boolean('aktif')->default(true)->after('urutan');
        });
    }

    public function down(): void
    {
        Schema::table('staf', function (Blueprint $t) {
            $t->dropUnique(['pusat_uuid']);
            $t->dropColumn(['pusat_uuid', 'jabatan_pusat', 'aktif']);
        });
    }
};
