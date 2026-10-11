<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'pusat_uuid')) {
            Schema::table('users', function (Blueprint $t) {
                $t->string('pusat_uuid', 36)->nullable()->unique(); // penaut ke akun di Pusat Sekolah
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'pusat_uuid')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('pusat_uuid'));
        }
    }
};
