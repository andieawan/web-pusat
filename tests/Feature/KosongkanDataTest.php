<?php

namespace Tests\Feature;

use App\Models\Staf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KosongkanDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_staf_dari_pusat_yang_dihapus(): void
    {
        Staf::create(['nama' => 'Dari Pusat', 'jabatan' => 'Guru', 'urutan' => 1, 'pusat_uuid' => 'p-1', 'aktif' => true]);
        Staf::create(['nama' => 'Manual', 'jabatan' => 'Guru', 'urutan' => 2, 'aktif' => true]);

        $this->artisan('web:kosongkan-data')->expectsQuestion('Ketik HAPUS SEMUA untuk melanjutkan', 'x')->assertExitCode(1);
        $this->artisan('web:kosongkan-data', ['--yakin' => true, '--tanpa-cadangan' => true])->assertExitCode(0);
        $this->assertSame(['Manual'], Staf::pluck('nama')->all());
        $this->artisan('web:kosongkan-data', ['--yakin' => true, '--tanpa-cadangan' => true, '--semua-staf' => true])->assertExitCode(0);
        $this->assertSame(0, Staf::count());
    }
}
