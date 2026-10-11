<?php

namespace Tests\Feature;

use App\Models\Pengaturan;
use App\Models\ProgramKeahlian;
use App\Models\User;
use App\Support\PusatTanda;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Penyambungan ke Pusat Sekolah: login admin, statistik beranda, webhook pencabutan akses. */
class PusatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pusat.enabled' => true, 'pusat.url' => 'https://pusat.test', 'pusat.client_id' => 'app_web',
            'pusat.client_secret' => 'sec', 'pusat.webhook_secret' => 'whsec']);
        Cache::flush();
        Pengaturan::lupakan();
    }

    private function info(array $o = []): array
    {
        return array_merge(['sub' => 'u-1', 'username' => 'admin.web', 'nama' => 'Admin Web', 'email' => 'Admin@Sekolah.Test', 'peran' => ['admin']], $o);
    }

    private function loginViaPusat(array $info)
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pusat.test/api/sso/token' => Http::response(['access_token' => 't', 'user' => $info])]);
        $this->get('/pusat/masuk');

        return $this->get('/pusat/callback?code=abc&state='.session('pusat_sso.state'));
    }

    // ---- Login ----

    public function test_tombol_hanya_tampil_saat_aktif(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk lewat Pusat');
        config(['pusat.enabled' => false]);
        $this->get('/login')->assertOk()->assertDontSee('Masuk lewat Pusat');
        $this->get('/pusat/masuk')->assertRedirect('/login');
    }

    public function test_mulai_mengarah_ke_pusat_dengan_pkce_dan_redirect_uri(): void
    {
        $loc = $this->get('/pusat/masuk')->headers->get('Location');
        $this->assertStringStartsWith('https://pusat.test/sso/authorize?', $loc);
        parse_str(parse_url($loc, PHP_URL_QUERY), $q);
        $this->assertSame('app_web', $q['client_id']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertSame(route('pusat.callback'), $q['redirect_uri']);
        $this->assertSame(session('pusat_sso.state'), $q['state']);
    }

    public function test_admin_pusat_membuat_akun_baru_dan_bisa_membuka_panel(): void
    {
        $this->loginViaPusat($this->info())->assertRedirect(route('admin.dashboard'));

        $u = User::where('pusat_uuid', 'u-1')->firstOrFail();
        $this->assertTrue($u->is_admin);
        $this->assertSame('admin@sekolah.test', $u->email);
        $this->get('/admin')->assertOk();
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/api/sso/token') && $r['redirect_uri'] === route('pusat.callback') && $r['code_verifier'] !== '');
    }

    public function test_akun_lokal_dengan_email_sama_ditautkan_bukan_digandakan(): void
    {
        $lokal = User::factory()->create(['email' => 'admin@sekolah.test']);
        $this->loginViaPusat($this->info())->assertRedirect(route('admin.dashboard'));

        $this->assertSame(1, User::count());
        $this->assertSame('u-1', $lokal->fresh()->pusat_uuid);
        $this->assertTrue($lokal->fresh()->is_admin);
    }

    public function test_tanpa_peran_admin_atau_tanpa_email_ditolak(): void
    {
        $this->loginViaPusat($this->info(['peran' => ['editor']]))->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->loginViaPusat($this->info(['sub' => 'u-2', 'email' => null]))->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_state_salah_ditolak_dan_email_tertaut_akun_lain_ditolak(): void
    {
        $this->get('/pusat/masuk');
        $this->get('/pusat/callback?code=abc&state=salah')->assertRedirect('/login');
        $this->assertGuest();

        User::factory()->create(['email' => 'admin@sekolah.test', 'pusat_uuid' => 'lain']);
        $this->loginViaPusat($this->info())->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_keluar_dari_sesi_pusat_juga_keluar_dari_pusat(): void
    {
        $this->loginViaPusat($this->info());
        $loc = $this->post('/logout')->headers->get('Location');
        $this->assertStringStartsWith('https://pusat.test/sso/logout?', $loc);
    }

    // ---- Statistik ----

    public function test_statistik_beranda_dari_pusat_dan_cadangan_saat_pusat_mati(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);
        Pengaturan::lupakan();

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pusat.test/api/v1/ringkasan' => Http::response(['data' => ['jumlah_siswa_aktif' => 777, 'jumlah_guru' => 40, 'jumlah_staf' => 9]])]);
        $this->get('/')->assertOk()->assertSee('777')->assertSee('49'); // guru + staf

        // Pusat mati & belum ada salinan: angka manual dari Pengaturan (demo: 682 / 48).
        Cache::flush();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pusat.test/*' => Http::response('error', 500)]);
        $this->get('/')->assertOk()->assertSee('682')->assertSee('48');
    }

    public function test_pusat_mati_memakai_salinan_terakhir_yang_berhasil(): void
    {
        $this->seed(DatabaseSeeder::class);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pusat.test/api/v1/ringkasan' => Http::response(['data' => ['jumlah_siswa_aktif' => 555, 'jumlah_guru' => 1, 'jumlah_staf' => 1]])]);
        $this->get('/')->assertSee('555');

        Cache::forget('pusat.ringkasan'); // cache segar habis, salinan lama (basi) masih ada
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pusat.test/*' => Http::response('error', 500)]);
        $this->get('/')->assertOk()->assertSee('555');
    }

    public function test_tanpa_integrasi_beranda_memakai_angka_manual_dan_tidak_memanggil_pusat(): void
    {
        config(['pusat.enabled' => false]);
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);
        Pengaturan::lupakan();
        Http::fake();
        $this->get('/')->assertOk()->assertSee('682');
        Http::assertNothingSent();
    }

    public function test_dasbor_menampilkan_jurusan_pusat_yang_belum_ada_di_website(): void
    {
        ProgramKeahlian::create(['nama' => 'Desain Komunikasi Visual', 'ikon' => 'bi-brush', 'urutan' => 1]);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pusat.test/api/v1/master/jurusan*' => Http::response(['data' => [
            ['kode' => 'DKV', 'nama' => 'desain komunikasi visual', 'aktif' => true],
            ['kode' => 'AK', 'nama' => 'Akuntansi', 'aktif' => true],
        ]])]);

        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()->assertSee('Akuntansi')->assertDontSee('Belum ada Program Keahlian dengan nama: <b>desain');
    }

    // ---- Webhook ----

    private function webhook(string $event, array $data, string $rahasia = 'whsec')
    {
        $badan = json_encode(['event' => $event, 'data' => $data]);
        $waktu = time();

        return $this->call('POST', '/pusat/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_PUSAT_EVENT' => $event,
            'HTTP_X_PUSAT_TIMESTAMP' => (string) $waktu, 'HTTP_X_PUSAT_SIGNATURE' => PusatTanda::buat($rahasia, $waktu, $badan),
        ], $badan);
    }

    public function test_webhook_tanda_tangan_salah_ditolak(): void
    {
        $this->webhook('ping', [], 'lain')->assertStatus(401);
        $this->post('/pusat/webhook')->assertStatus(401);
    }

    public function test_webhook_mencabut_admin_dan_sesi_langsung_tertolak(): void
    {
        $u = User::factory()->admin()->create(['pusat_uuid' => 'u-1']);
        $lain = User::factory()->admin()->create(); // tidak tertaut: tidak disentuh

        $this->webhook('akun.diperbarui', ['uuid' => 'u-1', 'aktif' => true, 'peran' => ['admin']])->assertOk();
        $this->assertTrue($u->fresh()->is_admin);

        $this->actingAs($u)->get('/admin')->assertOk();
        $this->webhook('akses.diperbarui', ['uuid' => 'u-1', 'aktif' => true, 'peran' => []])->assertOk();
        $this->assertFalse($u->fresh()->is_admin);
        $this->actingAs($u->fresh())->get('/admin')->assertForbidden();
        $this->assertTrue($lain->fresh()->is_admin);

        $dua = User::factory()->admin()->create(['pusat_uuid' => 'u-3']);
        $this->webhook('akun.diperbarui', ['uuid' => 'u-3', 'aktif' => false, 'peran' => ['admin']])->assertOk();
        $this->assertFalse($dua->fresh()->is_admin);
    }

    public function test_webhook_mati_bila_integrasi_nonaktif(): void
    {
        config(['pusat.enabled' => false]);
        $this->webhook('ping', [])->assertNotFound();
    }
}
