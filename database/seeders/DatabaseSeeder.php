<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use App\Models\ProgramKeahlian;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder PRODUKSI: hanya akun admin, pengaturan awal, dan program keahlian.
 * Data contoh (berita, pengumuman, agenda) ada di DemoSeeder:
 *     php artisan db:seed --class=DemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->buatAdmin();
        $this->buatPengaturan();
        $this->buatProgramKeahlian();
    }

    private function buatAdmin(): void
    {
        $email = config('smk.admin_email');
        $admin = User::firstOrNew(['email' => $email]);

        if (! $admin->exists) {
            $dariEnv = config('smk.admin_password');
            $password = $dariEnv ?: Str::random(16);

            $admin->forceFill(['name' => 'Administrator', 'password' => $password, 'is_admin' => true])->save();

            if (! $dariEnv) {
                $this->command?->warn("Akun admin dibuat. Email: {$email}  Password: {$password}");
                $this->command?->warn('Catat password ini sekarang; tidak akan ditampilkan lagi.');
            }
        } elseif (! $admin->is_admin) {
            $admin->forceFill(['is_admin' => true])->save();
        }
    }

    private function buatPengaturan(): void
    {
        $awal = [
            'stat_siswa' => '0', 'stat_guru' => '0', 'stat_prestasi' => '0',
            'alamat' => 'Jl. Raya Pakusari No. 45, Pakusari - Jember',
            'telepon' => '(0331) 593XXX', 'email' => 'smksibp@gmail.com',
            'sosmed_facebook' => '', 'sosmed_instagram' => '', 'sosmed_youtube' => '',
            'profil_visi' => 'Mencetak Generasi Unggul, Berakhlak Mulia dan Siap Kerja di Era Global',
            'tentang' => 'SMKS Islam Bustanul Ulum Pakusari Jember adalah sekolah menengah kejuruan yang berlandaskan nilai-nilai Islam dan berkomitmen untuk mencetak lulusan yang kompeten, berakhlak mulia, serta siap menghadapi dunia kerja dan melanjutkan pendidikan ke jenjang yang lebih tinggi.',
        ];
        foreach ($awal as $kunci => $nilai) {
            Pengaturan::firstOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }
        Pengaturan::lupakan();
    }

    private function buatProgramKeahlian(): void
    {
        if (ProgramKeahlian::count() > 0) {
            return;
        }
        foreach ([
            ['Teknik Komputer dan Jaringan (TKJ)', 'bi-pc-display'],
            ['Multimedia (MM)', 'bi-code-square'],
            ['Akuntansi (AK)', 'bi-calculator'],
            ['Otomatisasi Tata Kelola Perkantoran (OTKP)', 'bi-file-earmark-text'],
            ['Rekayasa Perangkat Lunak (RPL)', 'bi-code-slash'],
        ] as $i => [$nama, $ikon]) {
            ProgramKeahlian::create(['nama' => $nama, 'ikon' => $ikon, 'urutan' => $i + 1]);
        }
    }
}
