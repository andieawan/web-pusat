<?php

namespace Tests\Feature;

use App\Models\Pengaturan;
use App\Models\User;
use App\Services\ProfilPusat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Alamat, kontak, kepala sekolah, NPSN, dan logo website mengikuti Profil Sekolah di Pusat. */
class ProfilPusatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        @unlink(ProfilPusat::berkas());
        @unlink(ProfilPusat::logoBerkas());
        config(['pusat.enabled' => true, 'pusat.url' => 'https://pusat.test', 'pusat.client_id' => 'c', 'pusat.client_secret' => 's', 'pusat.webhook_secret' => 'wh']);
    }

    protected function tearDown(): void
    {
        @unlink(ProfilPusat::berkas());
        @unlink(ProfilPusat::logoBerkas());
        parent::tearDown();
    }

    public function test_profil_mengisi_dan_mengunci_pengaturan(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        Http::fake([
            'pusat.test/api/v1/profil' => Http::response(['data' => ['diisi' => true, 'nama' => 'SMK', 'alamat' => 'Jl. Himalaya 17', 'telepon' => '0331-7255753',
                'kepsek' => 'Kepala Baru', 'npsn' => '20570966', 'email' => null, 'logo_url' => 'https://pusat.test/logo-sekolah', 'logo_versi' => '7']]),
            'pusat.test/logo-sekolah' => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);
        Pengaturan::simpan('email', 'lama@sekolah.sch.id');
        ProfilPusat::tarik();
        Pengaturan::lupakan();
        $this->assertSame('Jl. Himalaya 17', Pengaturan::ambil('alamat'));
        $this->assertSame('Kepala Baru', Pengaturan::ambil('profil_kepsek'));
        $this->assertSame('lama@sekolah.sch.id', Pengaturan::ambil('email')); // kosong di Pusat: tetap
        $this->assertSame('pusat/logo-7.png', Pengaturan::ambil('logo'));
        Storage::disk('public')->assertExists('pusat/logo-7.png');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/pengaturan')->assertOk()->assertSee('dari Profil Sekolah di Pusat')->assertSee('Logo mengikuti');
        $this->actingAs($admin)->put('/admin/pengaturan', ['alamat' => 'Diubah', 'email' => 'baru@sekolah.sch.id']);
        Pengaturan::lupakan();
        $this->assertSame('Jl. Himalaya 17', Pengaturan::ambil('alamat'));
        $this->assertSame('baru@sekolah.sch.id', Pengaturan::ambil('email'));
    }
}
