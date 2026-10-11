<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Ekstrakurikuler;
use App\Models\Pengaturan;
use App\Models\Slide;
use App\Models\Staf;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Pengaturan::lupakan();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);
        Pengaturan::lupakan();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ---------- Publik ----------

    public function test_beranda_tampil_dengan_data_demo(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('BUSTANUL ULUM')
            ->assertSee('Berita Terbaru')
            ->assertSee('Program Keahlian')
            ->assertSee('images/hero.jpg'); // cadangan saat belum ada banner
    }

    public function test_banner_aktif_tampil_dan_nonaktif_tidak(): void
    {
        Slide::create(['gambar' => 'konten/aktif.jpg', 'urutan' => 1, 'aktif' => true]);
        Slide::create(['gambar' => 'konten/mati.jpg', 'urutan' => 2, 'aktif' => false]);

        $this->get('/')->assertOk()->assertSee('konten/aktif.jpg')->assertDontSee('konten/mati.jpg');
    }

    public function test_berita_daftar_detail_dan_pencarian(): void
    {
        $this->get('/berita')->assertOk();

        $berita = Berita::firstOrFail();
        $this->get('/berita/' . $berita->slug)->assertOk()->assertSee($berita->judul);

        $this->get('/berita?q=Juara')->assertOk()->assertSee('Juara 2')->assertDontSee('Halal bi Halal');
        // Karakter khusus LIKE dicari apa adanya, bukan sebagai wildcard.
        $this->get('/berita?q=%25')->assertOk()->assertSee('Tidak ada berita yang cocok');
    }

    public function test_berita_tidak_terbit_tidak_bisa_dibuka(): void
    {
        $berita = Berita::firstOrFail();
        $berita->update(['terbit' => false]);

        $this->get('/berita/' . $berita->slug)->assertNotFound();
    }

    public function test_halaman_pengumuman_agenda_dan_galeri(): void
    {
        $this->get('/pengumuman')->assertOk()->assertSee('Jadwal Ujian Tengah Semester');
        $this->get('/agenda')->assertOk()->assertSee('Rapat Wali Murid');
        $this->get('/galeri')->assertOk();
    }

    public function test_tautan_sosmed_hanya_tampil_bila_diisi(): void
    {
        $this->get('/')->assertDontSee('bi-facebook');

        Pengaturan::simpan('sosmed_facebook', 'https://facebook.com/smkibu');
        $this->get('/')->assertSee('https://facebook.com/smkibu', false)->assertSee('bi-facebook');
    }

    // ---------- Seeder ----------

    public function test_seeder_membuat_satu_admin_dan_tidak_menimpa_password(): void
    {
        $this->assertSame(1, User::where('is_admin', true)->count());

        $hashLama = User::where('is_admin', true)->value('password');
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('is_admin', true)->count());
        $this->assertSame($hashLama, User::where('is_admin', true)->value('password'));
    }

    // ---------- Akses admin ----------

    public function test_admin_wajib_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/berita')->assertRedirect('/login');
    }

    public function test_user_biasa_tidak_boleh_masuk_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_user_biasa_tidak_bisa_login_lewat_form_admin(): void
    {
        $user = User::factory()->create(['password' => 'rahasia-123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_admin_dan_buka_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'rahasia-123']);

        $this->post('/login', ['email' => $admin->email, 'password' => 'rahasia-123'])->assertRedirect('/admin');
        $this->get('/admin')->assertOk()->assertSee('Dashboard');
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'rahasia-123']);

        $this->post('/login', ['email' => $admin->email, 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ---------- CRUD admin ----------

    public function test_admin_bisa_menambah_pengumuman_dan_kategori_divalidasi(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/pengumuman', [
            'judul' => 'Tes Pengumuman', 'kategori' => 'Umum', 'tanggal' => '2026-10-07',
        ])->assertRedirect('/admin/pengumuman');
        $this->assertDatabaseHas('pengumuman', ['judul' => 'Tes Pengumuman']);

        $this->actingAs($admin)->post('/admin/pengumuman', [
            'judul' => 'Kategori Ngawur', 'kategori' => 'Bebas', 'tanggal' => '2026-10-07',
        ])->assertSessionHasErrors('kategori');
        $this->assertDatabaseMissing('pengumuman', ['judul' => 'Kategori Ngawur']);
    }

    public function test_berita_bisa_disembunyikan_lewat_kotak_centang(): void
    {
        $berita = Berita::firstOrFail();

        $this->actingAs($this->admin())->put('/admin/berita/' . $berita->id, [
            'judul' => $berita->judul, 'kategori' => $berita->kategori,
            'tanggal' => $berita->tanggal->toDateString(), 'ringkas' => $berita->ringkas,
            'isi' => $berita->isi, 'terbit' => '0', // nilai hidden saat kotak tidak dicentang
        ])->assertRedirect('/admin/berita');

        $this->assertFalse($berita->fresh()->terbit);
    }

    public function test_foto_besar_otomatis_diperkecil(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post('/admin/berita', [
            'judul' => 'Berita Bergambar', 'kategori' => 'Kegiatan', 'tanggal' => '2026-10-07',
            'ringkas' => 'Ringkas', 'isi' => 'Isi berita', 'terbit' => '1',
            'gambar' => UploadedFile::fake()->image('besar.jpg', 3200, 2400),
        ])->assertRedirect('/admin/berita');

        $berita = Berita::where('judul', 'Berita Bergambar')->firstOrFail();
        Storage::disk('public')->assertExists($berita->gambar);

        [$lebar] = getimagesizefromstring(Storage::disk('public')->get($berita->gambar));
        $this->assertLessThanOrEqual(1600, $lebar);
    }

    public function test_banner_wajib_punya_foto(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/slide', ['urutan' => 1, 'aktif' => '1'])
            ->assertSessionHasErrors('gambar');

        $this->actingAs($admin)->post('/admin/slide', [
            'urutan' => 1, 'aktif' => '1', 'gambar' => UploadedFile::fake()->image('hero.jpg', 1800, 700),
        ])->assertRedirect('/admin/slide');
        $this->assertSame(1, Slide::count());
    }

    public function test_pengaturan_menolak_tautan_berbahaya(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/pengaturan', ['sosmed_facebook' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('sosmed_facebook');

        $this->actingAs($admin)->put('/admin/pengaturan', ['sosmed_facebook' => 'https://facebook.com/smkibu'])
            ->assertSessionHasNoErrors();
        $this->assertSame('https://facebook.com/smkibu', Pengaturan::ambil('sosmed_facebook'));
    }

    public function test_ikon_program_keahlian_harus_berformat_bootstrap_icons(): void
    {
        $this->actingAs($this->admin())->post('/admin/program', [
            'nama' => 'Program Uji', 'ikon' => '"><script>', 'urutan' => 9,
        ])->assertSessionHasErrors('ikon');
    }

    // ---------- Profil, Akademik, Kesiswaan ----------

    public function test_halaman_profil_menampilkan_isian_admin_dan_menyembunyikan_yang_kosong(): void
    {
        Pengaturan::simpan('profil_sejarah', 'Berdiri atas inisiatif masyarakat.');
        Pengaturan::simpan('profil_misi', "Misi satu\n\nMisi dua");
        Staf::create(['nama' => 'Budi, S.Pd.', 'jabatan' => 'Guru TKJ', 'urutan' => 1]);

        $this->get('/profil')
            ->assertOk()
            ->assertSee('Berdiri atas inisiatif masyarakat.')
            ->assertSee('Misi dua')
            ->assertSee('Budi, S.Pd.')
            ->assertSee('Mencetak Generasi Unggul')   // visi bawaan seeder
            ->assertDontSee('NPSN');                    // belum diisi, jadi barisnya tidak tampil
    }

    public function test_teks_halaman_di_escape_dari_html(): void
    {
        Pengaturan::simpan('profil_sejarah', '<script>alert(1)</script>');

        $this->get('/profil')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_halaman_akademik_dan_kesiswaan_tampil(): void
    {
        $this->get('/akademik')->assertOk()->assertSee('Program Keahlian')->assertSee('Teknik Komputer dan Jaringan');
        $this->get('/kesiswaan')->assertOk()->assertSee('Ekstrakurikuler')->assertSee('Pramuka');
    }

    public function test_admin_mengelola_ekskul_dan_staf_dan_ikon_divalidasi(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/ekstrakurikuler', [
            'nama' => 'Futsal', 'ikon' => 'bi-trophy', 'urutan' => 5,
        ])->assertRedirect('/admin/ekstrakurikuler');
        $this->assertDatabaseHas('ekstrakurikuler', ['nama' => 'Futsal']);

        $this->actingAs($admin)->post('/admin/ekstrakurikuler', [
            'nama' => 'Ngawur', 'ikon' => '"><script>', 'urutan' => 5,
        ])->assertSessionHasErrors('ikon');

        $this->actingAs($admin)->post('/admin/staf', [
            'nama' => 'Siti, S.Kom.', 'jabatan' => 'Guru RPL', 'urutan' => 2,
        ])->assertRedirect('/admin/staf');
        $this->assertSame(1, Staf::count());

        $this->get('/kesiswaan')->assertSee('Futsal');
        $this->get('/profil')->assertSee('Siti, S.Kom.');
    }

    public function test_halaman_konten_baru_hanya_untuk_admin(): void
    {
        $this->get('/admin/staf')->assertRedirect('/login');
        $this->get('/admin/ekstrakurikuler')->assertRedirect('/login');
    }

    public function test_pengaturan_halaman_disimpan_dan_dibatasi_panjangnya(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/pengaturan', ['akademik_kurikulum' => 'Kurikulum Merdeka'])
            ->assertSessionHasNoErrors();
        $this->get('/akademik')->assertSee('Kurikulum Merdeka');

        $this->actingAs($admin)->put('/admin/pengaturan', ['profil_sejarah' => str_repeat('a', 5001)])
            ->assertSessionHasErrors('profil_sejarah');
    }

    public function test_admin_bisa_mengunggah_dan_menghapus_logo(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/pengaturan', ['logo' => UploadedFile::fake()->image('logo.png', 300, 300)])
            ->assertSessionHasNoErrors();
        $logo = Pengaturan::ambil('logo');
        $this->assertNotEmpty($logo);
        Storage::disk('public')->assertExists($logo);
        $this->get('/')->assertSee($logo);

        $this->actingAs($admin)->put('/admin/pengaturan', ['hapus_logo' => '1'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($logo);
        $this->assertEmpty(Pengaturan::ambil('logo'));

        $this->actingAs($admin)->put('/admin/pengaturan', ['logo' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml')])
            ->assertSessionHasErrors('logo');
    }

    public function test_css_diberi_versi_agar_cache_ikut_diperbarui(): void
    {
        $this->get('/')->assertOk()->assertSee('css/site.css?v=', false);
        $this->get('/login')->assertOk()->assertSee('css/admin.css?v=', false);
    }

    public function test_admin_bisa_mengunggah_foto_kepala_sekolah_dan_tampil_di_profil(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        Pengaturan::simpan('profil_sambutan', 'Selamat datang.');

        $this->actingAs($admin)->put('/admin/pengaturan', ['foto_kepsek' => UploadedFile::fake()->image('kepsek.jpg', 2400, 1800)])
            ->assertSessionHasNoErrors();
        $foto = Pengaturan::ambil('profil_foto_kepsek');
        $this->assertNotEmpty($foto);
        Storage::disk('public')->assertExists($foto);
        [$lebar] = getimagesizefromstring(Storage::disk('public')->get($foto));
        $this->assertLessThanOrEqual(800, $lebar);

        $this->get('/profil')->assertOk()->assertSee(basename($foto));

        $this->actingAs($admin)->put('/admin/pengaturan', ['hapus_foto_kepsek' => '1'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($foto);
        $this->assertEmpty(Pengaturan::ambil('profil_foto_kepsek'));
    }

    public function test_admin_bisa_ganti_password_sendiri(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'lama-12345']);

        $this->actingAs($admin)->get('/admin/akun')->assertOk();

        $this->actingAs($admin)->put('/admin/akun/password', [
            'password_lama' => 'salah-12345', 'password' => 'baru-12345', 'password_confirmation' => 'baru-12345',
        ])->assertSessionHasErrors('password_lama');

        $this->actingAs($admin)->put('/admin/akun/password', [
            'password_lama' => 'lama-12345', 'password' => 'baru-12345', 'password_confirmation' => 'beda-12345',
        ])->assertSessionHasErrors('password');

        $this->actingAs($admin)->put('/admin/akun/password', [
            'password_lama' => 'lama-12345', 'password' => 'baru-12345', 'password_confirmation' => 'baru-12345',
        ])->assertSessionHasNoErrors();

        auth()->logout();
        $this->post('/login', ['email' => $admin->email, 'password' => 'baru-12345'])->assertRedirect();
        $this->assertAuthenticatedAs($admin->fresh());
    }

    public function test_admin_bisa_menambah_akun_admin_lain_yang_bisa_login(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/pengguna')->assertOk();
        $this->actingAs($admin)->post('/admin/pengguna', [
            'name' => 'Operator Dua', 'email' => 'dua@contoh.sch.id',
            'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123',
        ])->assertSessionHasNoErrors();

        $baru = User::where('email', 'dua@contoh.sch.id')->firstOrFail();
        $this->assertTrue($baru->is_admin);

        $this->actingAs($admin)->post('/admin/pengguna', [
            'name' => 'Dobel', 'email' => 'dua@contoh.sch.id',
            'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123',
        ])->assertSessionHasErrors('email');

        $this->actingAs($admin)->put("/admin/pengguna/{$baru->id}", [
            'name' => 'Operator Dua', 'email' => 'dua@contoh.sch.id',
            'password' => 'diganti-123', 'password_confirmation' => 'diganti-123',
        ])->assertSessionHasNoErrors();

        auth()->logout();
        $this->post('/login', ['email' => 'dua@contoh.sch.id', 'password' => 'diganti-123'])->assertRedirect();
        $this->assertAuthenticatedAs($baru->fresh());
    }

    public function test_admin_tidak_bisa_menghapus_akun_sendiri_tapi_bisa_menghapus_lain(): void
    {
        $admin = $this->admin();
        $lain = User::factory()->admin()->create();

        $this->actingAs($admin)->delete("/admin/pengguna/{$admin->id}")->assertSessionHasErrors('hapus');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->actingAs($admin)->delete("/admin/pengguna/{$lain->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('users', ['id' => $lain->id]);
    }

    public function test_user_biasa_tidak_bisa_membuka_manajemen_pengguna(): void
    {
        $biasa = User::factory()->create();
        $this->actingAs($biasa)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($biasa)->get('/admin/akun')->assertForbidden();
    }

    public function test_dashboard_tampil_dengan_ringkasan_dan_kelengkapan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('Kelengkapan website')
            ->assertSee('Berita terbaru')
            ->assertSee('js/admin.js?v=', false);
    }

    public function test_halaman_admin_memakai_csp_ketat_dan_tidak_di_cache(): void
    {
        $admin = $this->admin();

        $respons = $this->actingAs($admin)->get('/admin/pengguna')->assertOk();
        $this->assertStringContainsString("script-src 'self'", $respons->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('no-store', $respons->headers->get('Cache-Control'));
        $respons->assertDontSee('onsubmit=', false)->assertSee('data-konfirmasi', false);

        $login = $this->get('/login')->assertOk();
        $this->assertStringContainsString("script-src 'self'", $login->headers->get('Content-Security-Policy'));

        $this->get('/')->assertOk()->assertHeader('Permissions-Policy');
    }

    public function test_email_berkutip_tidak_bisa_menyuntik_js_di_dialog_hapus(): void
    {
        $admin = $this->admin();
        User::factory()->admin()->create(['email' => "x'y@contoh.id"]);

        $html = $this->actingAs($admin)->get('/admin/pengguna')->getContent();
        $this->assertStringNotContainsString('onsubmit', $html);
        $this->assertStringContainsString('data-konfirmasi="Hapus akun x&#039;y@contoh.id?"', $html);
    }

    public function test_login_dibatasi_per_email_setelah_5_percobaan_gagal(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'rahasia-123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $admin->email, 'password' => 'salah'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $admin->email, 'password' => 'rahasia-123'])->assertStatus(429);

        // Email lain dari IP yang sama tidak ikut terkunci.
        $this->post('/login', ['email' => 'lain@contoh.id', 'password' => 'salah'])->assertSessionHasErrors('email');
    }
}
