<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Pengaturan;
use App\Models\Pengumuman;
use App\Models\ProgramKeahlian;
use App\Models\Slide;
use App\Services\PusatKlien;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index()
    {
        $url = fn (?string $path) => $path ? Storage::disk('public')->url($path) : null;

        // Angka siswa & guru otomatis dari Pusat bila tersambung; bila tidak (atau Pusat mati) memakai isian manual di Pengaturan.
        $ringkas = PusatKlien::ringkasan();
        $statSiswa = isset($ringkas['jumlah_siswa_aktif']) ? (string) $ringkas['jumlah_siswa_aktif'] : Pengaturan::ambil('stat_siswa', '0');
        $statGuru = isset($ringkas['jumlah_guru']) ? (string) ((int) $ringkas['jumlah_guru'] + (int) ($ringkas['jumlah_staf'] ?? 0)) : Pengaturan::ambil('stat_guru', '0');

        return view('home', [
            'slides' => Slide::where('aktif', true)->orderBy('urutan')->get()
                ->map(fn ($sl) => Storage::disk('public')->url($sl->gambar))->all(),
            'statistik' => [
                ['ikon' => 'bi-mortarboard-fill', 'judul' => 'Siswa Aktif', 'nilai' => $statSiswa, 'ket' => 'Peserta didik'],
                ['ikon' => 'bi-people-fill', 'judul' => 'Guru & Staff', 'nilai' => $statGuru, 'ket' => 'Pendidik dan tenaga kependidikan'],
                ['ikon' => 'bi-trophy-fill', 'judul' => 'Prestasi', 'nilai' => Pengaturan::ambil('stat_prestasi', '0'), 'ket' => 'Penghargaan tingkat kabupaten hingga nasional'],
                ['ikon' => 'bi-book-half', 'judul' => 'Program Keahlian', 'nilai' => (string) ProgramKeahlian::tampil()->count(), 'ket' => 'Kompetensi keahlian unggulan'],
            ],
            'berita' => Berita::where('terbit', true)->orderByDesc('tanggal')->take(3)->get()->map(fn ($b) => [
                'tanggal' => $b->tanggal->translatedFormat('d M Y'),
                'label' => $b->kategori,
                'warna' => $b->kategori === 'Prestasi' ? 'green' : 'blue',
                'judul' => $b->judul,
                'ringkas' => $b->ringkas,
                'gambar' => $url($b->gambar),
                'url' => route('berita.show', $b->slug),
            ])->all(),
            'pengumuman' => Pengumuman::orderByDesc('tanggal')->take(4)->get()->map(fn ($p) => [
                'tgl' => $p->tanggal->format('d'), 'bln' => $p->tanggal->translatedFormat('M'),
                'judul' => $p->judul, 'kategori' => $p->kategori,
            ])->all(),
            'program' => ProgramKeahlian::tampil()->orderBy('urutan')->get()->map(fn ($p) => [
                'ikon' => $p->ikon, 'nama' => $p->nama,
            ])->all(),
            'galeri' => Galeri::orderByDesc('tanggal')->take(4)->get()->map(fn ($g) => [
                'judul' => $g->judul, 'tanggal' => $g->tanggal->translatedFormat('j M Y'), 'gambar' => $url($g->gambar),
            ])->all(),
            'agenda' => Agenda::whereDate('tanggal', '>=', today())->orderBy('tanggal')->take(4)->get()
                ->whenEmpty(fn () => Agenda::orderByDesc('tanggal')->take(4)->get())
                ->map(fn ($a) => [
                    'tgl' => $a->tanggal->format('d'), 'bln' => $a->tanggal->translatedFormat('M'),
                    'judul' => $a->judul, 'waktu' => $a->waktu,
                ])->all(),
            'tentang' => Pengaturan::ambil('tentang', ''),
        ]);
    }
}
