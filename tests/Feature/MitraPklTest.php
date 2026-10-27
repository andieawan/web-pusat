<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Halaman Akademik menampilkan mitra DUDI dari aplikasi PKL; aman bila PKL mati. */
class MitraPklTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['pkl.url' => 'https://pkl.test']);
    }

    public function test_mitra_tampil_di_akademik_dan_salinan_dipakai_saat_pkl_mati(): void
    {
        Http::fake(['pkl.test/api/publik/mitra' => Http::response(['data' => ['periode_aktif' => 'PKL Gel 1', 'total_siswa_periode_ini' => 12,
            'mitra' => [['nama' => 'CV Jaringan Nusantara', 'bidang' => 'Jaringan komputer', 'siswa_periode_ini' => 4, 'siswa_total' => 9]]]])]);
        $this->get('/akademik')->assertOk()->assertSee('Mitra Industri')->assertSee('CV Jaringan Nusantara')->assertSee('12 siswa PKL pada PKL Gel 1');

        Cache::forget('pkl.mitra'); // salinan segar habis, PKL mati → pakai salinan lama
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pkl.test/*' => Http::response('', 500)]);
        $this->get('/akademik')->assertOk()->assertSee('CV Jaringan Nusantara');
    }

    public function test_tanpa_pkl_halaman_tetap_tampil(): void
    {
        config(['pkl.url' => '']);
        $this->get('/akademik')->assertOk()->assertDontSee('Mitra Industri');
    }

    public function test_menu_aplikasi_lain_di_panel_admin(): void
    {
        config(['pusat.url' => 'https://pusat.test']);
        Http::fake(['*' => Http::response(['data' => []])]);
        $this->actingAs(User::factory()->admin()->create())->withSession(['aplikasi_lain' => [['nama' => 'Absensi Siswa', 'url' => 'https://pusat.test/buka/absensi']]])
            ->get('/admin')->assertOk()->assertSee('Absensi Siswa')->assertSee('https://pusat.test/buka/absensi');
    }
}
