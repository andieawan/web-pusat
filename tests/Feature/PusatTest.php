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
        Http::fake(['pusat.test/api/sso/token' => Http::response(['access_token' => 't', 'user' => $info]), 'pusat.test/api/v1/ping' => Http::response(['ok' => true])]);
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
        Http::fake(['pusat.test/api/v1/ping' => Http::response(['ok' => true])]);
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

    public function test_jurusan_pusat_otomatis_menjadi_program_keahlian(): void
    {
        ProgramKeahlian::create(['nama' => 'Desain Komunikasi Visual', 'ikon' => 'bi-brush', 'deskripsi' => 'Buatan admin', 'urutan' => 1]);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pusat.test/api/v1/master/jurusan*' => Http::response(['data' => [
            ['uuid' => 'j-dkv', 'kode' => 'DKV', 'nama' => 'desain komunikasi visual', 'aktif' => true],
            ['uuid' => 'j-ak', 'kode' => 'AK', 'nama' => 'Akuntansi dan Keuangan Lembaga', 'aktif' => true],
            ['uuid' => 'j-lama', 'kode' => 'X', 'nama' => 'Jurusan Lama', 'aktif' => false],
        ]]), 'pusat.test/*' => Http::response(['data' => []])]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post('/admin/integrasi/sinkron')->assertSessionHas('ok');
        $this->assertSame(2, ProgramKeahlian::count());
        $dkv = ProgramKeahlian::where('pusat_uuid', 'j-dkv')->first();
        $this->assertSame('Buatan admin', $dkv->deskripsi); // isi buatan admin tidak ditimpa
        $this->assertSame('bi-brush', $dkv->ikon);
        $this->assertSame('bi-calculator', ProgramKeahlian::where('kode', 'AK')->value('ikon'));
        $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSee('Jurusan di Pusat belum ada di website');
        $this->get('/akademik')->assertOk()->assertSee('Akuntansi dan Keuangan Lembaga');

        // Dinonaktifkan di Pusat (webhook) → disembunyikan dari website publik
        $this->webhook('jurusan.diperbarui', ['uuid' => 'j-ak', 'kode' => 'AK', 'nama' => 'Akuntansi dan Keuangan Lembaga', 'aktif' => false])->assertOk();
        $this->assertFalse(ProgramKeahlian::where('pusat_uuid', 'j-ak')->value('aktif'));
        $this->get('/akademik')->assertOk()->assertDontSee('Akuntansi dan Keuangan Lembaga');
        $this->webhook('jurusan.dibuat', ['uuid' => 'j-bd', 'kode' => 'BD', 'nama' => 'Bisnis Digital', 'aktif' => true])->assertOk();
        $this->assertSame('bi-shop', ProgramKeahlian::where('pusat_uuid', 'j-bd')->value('ikon'));
    }

    public function test_guru_staf_dari_pusat_tampil_dan_jabatan_admin_tidak_tertimpa(): void
    {
        \App\Models\Staf::create(['nama' => 'Siti Aminah', 'jabatan' => 'Guru Matematika & Wakasek Kurikulum', 'urutan' => 1]);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pusat.test/api/v1/master/pegawai*' => Http::response(['data' => [
            ['uuid' => 'p1', 'nip' => '1', 'nama' => 'Siti Aminah', 'jenis' => 'guru', 'jabatan' => null, 'aktif' => true],
            ['uuid' => 'p2', 'nip' => '2', 'nama' => 'Ahmad Fauzi', 'jenis' => 'staf', 'jabatan' => 'Kepala TU', 'aktif' => true],
            ['uuid' => 'p3', 'nip' => '3', 'nama' => 'Pensiun', 'jenis' => 'guru', 'aktif' => false],
        ]]), 'pusat.test/*' => Http::response(['data' => []])]);

        $this->actingAs(User::factory()->admin()->create())->post('/admin/integrasi/sinkron')->assertSessionHas('ok');
        $this->assertSame(2, \App\Models\Staf::count());
        $this->assertSame('Guru Matematika & Wakasek Kurikulum', \App\Models\Staf::where('pusat_uuid', 'p1')->value('jabatan'));
        $this->assertSame('Kepala TU', \App\Models\Staf::where('pusat_uuid', 'p2')->value('jabatan'));
        $this->get('/profil')->assertOk()->assertSee('Ahmad Fauzi');

        // Jabatan berubah di Pusat → ikut; pegawai dinonaktifkan → disembunyikan
        $this->webhook('pegawai.diperbarui', ['uuid' => 'p1', 'nama' => 'Siti Aminah', 'jenis' => 'guru', 'jabatan' => 'Kepala Sekolah', 'aktif' => true])->assertOk();
        $this->assertSame('Kepala Sekolah', \App\Models\Staf::where('pusat_uuid', 'p1')->value('jabatan'));
        $this->webhook('pegawai.diperbarui', ['uuid' => 'p2', 'nama' => 'Ahmad Fauzi', 'jenis' => 'staf', 'jabatan' => 'Kepala TU', 'aktif' => false])->assertOk();
        $this->get('/profil')->assertOk()->assertDontSee('Ahmad Fauzi');
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

    public function test_halaman_integrasi_pusat_sama_dengan_aplikasi_lain(): void
    {
        Http::fake([
            'pusat.test/api/v1/ringkasan' => Http::response(['data' => ['tahun_ajaran' => '2026/2027', 'jumlah_siswa_aktif' => 72, 'jumlah_guru' => 9, 'jumlah_staf' => 2, 'jumlah_kelas' => 9]]),
            'pusat.test/api/v1/master/jurusan*' => Http::response(['data' => [['kode' => 'AK', 'nama' => 'Akuntansi'], ['kode' => 'DKV', 'nama' => 'Desain Komunikasi Visual']]]),
            'pusat.test/api/v1/ping' => Http::response(['data' => ['ok' => true]]),
        ]);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/integrasi')->assertOk()
            ->assertSee('Redirect URI')->assertSee('URL webhook')->assertSee('2026/2027')->assertSee('72 siswa aktif')->assertSee('Sinkron sekarang');
        $this->post('/admin/integrasi/uji-pusat')->assertSessionHas('ok');
        $this->post('/admin/integrasi/sinkron')->assertSessionHas('ok');
    }

    public function test_login_cadangan_admin_dari_pusat_saat_pusat_mati(): void
    {
        $this->webhook('akses.diperbarui', ['uuid' => 'u-9', 'email' => 'Kepala@Sekolah.Test', 'nama' => 'Kepala', 'aktif' => true, 'peran' => ['admin'],
            'password_hash' => password_hash('SandiPusat123', PASSWORD_BCRYPT)])->assertOk();
        $u = \App\Models\User::where('pusat_uuid', 'u-9')->firstOrFail();
        $this->assertTrue((bool) $u->is_admin);

        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('mati'));
        $this->get('/pusat/masuk')->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'kepala@sekolah.test', 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'kepala@sekolah.test', 'password' => 'SandiPusat123'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($u);

        // akses admin dicabut di Pusat → login cadangan ikut tertutup
        $this->post('/logout');
        $this->webhook('akses.diperbarui', ['uuid' => 'u-9', 'aktif' => true, 'peran' => []])->assertOk();
        $this->post('/login', ['email' => 'kepala@sekolah.test', 'password' => 'SandiPusat123'])->assertSessionHasErrors('email');
    }
}
