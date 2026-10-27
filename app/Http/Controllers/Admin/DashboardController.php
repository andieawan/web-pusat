<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Berita;
use App\Models\Pengaturan;
use App\Models\Pengumuman;
use App\Models\ProgramKeahlian;
use App\Models\Slide;
use App\Services\PusatKlien;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /** nama tabel => [label, rute, ikon, warna] */
    private const KARTU = [
        'berita' => ['Berita', 'admin.berita.index', 'bi-newspaper', 'biru'],
        'pengumuman' => ['Pengumuman', 'admin.pengumuman.index', 'bi-megaphone-fill', 'oranye'],
        'agenda' => ['Agenda', 'admin.agenda.index', 'bi-calendar-week-fill', 'ungu'],
        'galeri' => ['Foto Galeri', 'admin.galeri.index', 'bi-camera-fill', 'hijau'],
        'program_keahlian' => ['Program Keahlian', 'admin.program.index', 'bi-mortarboard-fill', 'biru'],
        'ekstrakurikuler' => ['Ekstrakurikuler', 'admin.ekstrakurikuler.index', 'bi-people-fill', 'oranye'],
        'staf' => ['Guru & Staf', 'admin.staf.index', 'bi-person-badge-fill', 'ungu'],
        'slide' => ['Banner Hero', 'admin.slide.index', 'bi-images', 'hijau'],
    ];

    public function index()
    {
        // Satu kueri untuk semua hitungan (nama tabel berasal dari konstanta, bukan input pengguna).
        $sub = collect(array_keys(self::KARTU))->map(fn ($t) => "(select count(*) from `{$t}`) as `{$t}`")->implode(', ');
        $hitung = (array) DB::selectOne("select {$sub}");

        $kartu = [];
        foreach (self::KARTU as $tabel => [$nama, $rute, $ikon, $warna]) {
            $kartu[] = ['nama' => $nama, 'jumlah' => (int) ($hitung[$tabel] ?? 0), 'rute' => $rute, 'ikon' => $ikon, 'warna' => $warna];
        }

        // Daftar kelengkapan: hal-hal yang sebaiknya diisi agar website tampil utuh.
        $kelengkapan = [
            ['Logo sekolah', (bool) Pengaturan::ambil('logo'), route('admin.pengaturan') . '#logo'],
            ['Foto kepala sekolah', (bool) Pengaturan::ambil('profil_foto_kepsek'), route('admin.pengaturan') . '#foto-kepsek'],
            ['Alamat & kontak sekolah', (bool) (Pengaturan::ambil('alamat') && Pengaturan::ambil('telepon')), route('admin.pengaturan')],
            ['Sambutan kepala sekolah', (bool) Pengaturan::ambil('profil_sambutan'), route('admin.pengaturan')],
            ['Banner beranda', ($hitung['slide'] ?? 0) > 0, route('admin.slide.index')],
            ['Minimal 3 berita', ($hitung['berita'] ?? 0) >= 3, route('admin.berita.create')],
        ];

        // Jurusan di Pusat yang belum punya halaman Program Keahlian (dicocokkan lewat nama, tanpa membedakan huruf).
        $jurusanBelumAda = [];
        PusatKlien::pegawai(); // menyalin guru & staf baru dari Pusat (memakai cache; tidak memanggil Pusat tiap kali)
        if ($jurusan = PusatKlien::jurusan()) {
            $ada = ProgramKeahlian::pluck('nama')->map(fn ($n) => mb_strtolower(trim($n)))->all();
            $jurusanBelumAda = collect($jurusan)->pluck('nama')->filter(fn ($n) => ! in_array(mb_strtolower(trim($n)), $ada, true))->values()->all();
        }

        return view('admin.dashboard', [
            'jurusanBelumAda' => $jurusanBelumAda,
            'kartu' => $kartu,
            'kelengkapan' => $kelengkapan,
            'beritaTerbaru' => Berita::orderByDesc('tanggal')->orderByDesc('id')->take(5)->get(['id', 'judul', 'kategori', 'tanggal', 'terbit']),
            'agendaMendatang' => Agenda::where('tanggal', '>=', now()->toDateString())->orderBy('tanggal')->take(5)->get(['id', 'judul', 'tanggal', 'waktu']),
            'pengumumanTerbaru' => Pengumuman::orderByDesc('tanggal')->take(3)->get(['id', 'judul', 'kategori', 'tanggal']),
        ]);
    }
}
