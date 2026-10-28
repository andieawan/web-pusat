<?php

namespace App\Console\Commands;

use App\Support\CadanganDb;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kosongkan data website yang berasal dari Pusat (halaman Guru & Staf) untuk memulai ulang bersama Pusat.
 * Konten website (berita, pengumuman, agenda, galeri, slide, program keahlian, pengaturan) TIDAK disentuh.
 * Foto staf dipindah ke storage/app/cadangan, bukan dihapus.
 */
class KosongkanData extends Command
{
    protected $signature = 'web:kosongkan-data
        {--semua-staf : Hapus juga Guru & Staf yang ditambahkan manual di website (bukan dari Pusat)}
        {--yakin : Lewati pertanyaan konfirmasi (untuk skrip)}
        {--tanpa-cadangan : Jangan buat cadangan database dulu (tidak disarankan)}';

    protected $description = 'Hapus Guru & Staf yang berasal dari Pusat (konten website lain tetap) — cadangan dibuat otomatis lebih dulu';

    public function handle(): int
    {
        $q = fn () => DB::table('staf')->when(! $this->option('semua-staf') && Schema::hasColumn('staf', 'pusat_uuid'), fn ($q) => $q->whereNotNull('pusat_uuid'));
        $n = $q()->count();
        $this->warn("Akan DIHAPUS PERMANEN dari website: $n Guru & Staf".($this->option('semua-staf') ? ' (semua)' : ' (yang berasal dari Pusat)').'.');
        $this->line('Berita, pengumuman, agenda, galeri, slide, program keahlian, dan pengaturan tidak disentuh.');

        if (! $this->option('yakin') && $this->ask('Ketik HAPUS SEMUA untuk melanjutkan') !== 'HAPUS SEMUA') {
            $this->info('Dibatalkan. Tidak ada yang dihapus.');

            return self::FAILURE;
        }
        if (! $this->option('tanpa-cadangan')) {
            try {
                $this->info('Cadangan dibuat: '.CadanganDb::buat('web-sebelum-kosongkan'));
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
                $this->line('Tidak ada yang dihapus. Perbaiki masalah cadangan, atau jalankan ulang dengan --tanpa-cadangan bila sudah punya cadangan lain.');

                return self::FAILURE;
            }
        }

        $foto = $q()->whereNotNull('foto')->pluck('foto')->filter()->all();
        $q()->delete();
        if ($foto) {
            $tujuan = storage_path('app/cadangan/foto-staf-'.now()->format('Ymd-His'));
            @mkdir($tujuan, 0750, true);
            foreach ($foto as $f) {
                $asal = storage_path('app/public/'.ltrim($f, '/'));
                if (is_file($asal)) {
                    @rename($asal, $tujuan.'/'.basename($f));
                }
            }
            $this->info('Foto staf lama dipindah ke: '.$tujuan);
        }

        $this->info("Selesai. $n Guru & Staf dihapus; daftar baru masuk otomatis saat Pusat mengirim data guru (atau tekan Sinkron di Admin → Integrasi).");

        return self::SUCCESS;
    }
}
