<?php

namespace Tests\Feature;

use App\Services\Sambungan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Sambungan antar-aplikasi diatur dari Pusat (Administrasi → Sambungan): diambil lewat API, disimpan terenkripsi, menimpa .env. */
class SambunganPusatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        @unlink(Sambungan::berkas());
        config(['pusat.enabled' => true, 'pusat.url' => 'https://p.test', 'pusat.client_id' => 'c', 'pusat.client_secret' => 's', 'pusat.webhook_secret' => 'wh']);
    }

    protected function tearDown(): void
    {
        @unlink(Sambungan::berkas());
        parent::tearDown();
    }

    private function pusat(array $data, int $status = 200): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory); // ganti balasan palsu sebelumnya
        Http::fake(['p.test/api/v1/sambungan' => Http::response(['data' => $data], $status)]);
    }

    private function s(string $jenis, bool $aktif, string $url = '', string $kunci = ''): array
    {
        return ['jenis' => $jenis, 'aktif' => $aktif, 'peran' => 'x', 'mitra' => 'm', 'url' => $url, 'kunci' => $kunci];
    }

    public function test_sinkron_mengambil_alamat_pkl(): void
    {
        config(['pkl.url' => '']);
        $this->pusat([$this->s('pkl-web', true, 'https://pkl.test')]);
        Sambungan::tarik();
        $this->assertSame('https://pkl.test', config('pkl.url'));
        $this->assertTrue(\App\Services\PklKlien::aktif());
        config(['pkl.url' => '']);
        Sambungan::terapkan();
        $this->assertSame('https://pkl.test', config('pkl.url'));

        $this->pusat([$this->s('pkl-web', false)]);
        Sambungan::tarik();
        $this->assertSame('', config('pkl.url'));
    }

    public function test_pusat_lama_tanpa_menu_sambungan_tidak_mengubah_apa_pun(): void
    {
        config(['pkl.url' => 'https://pkl-env.test']);
        $this->pusat([], 404);
        Sambungan::tarik();
        $this->assertSame('https://pkl-env.test', config('pkl.url'));
        $this->assertFileDoesNotExist(Sambungan::berkas());
    }
}
