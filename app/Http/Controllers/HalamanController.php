<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Berita;
use App\Models\Ekstrakurikuler;
use App\Models\Galeri;
use App\Models\Pengumuman;
use App\Models\ProgramKeahlian;
use App\Models\Staf;

/** Halaman publik yang datanya dari database. */
class HalamanController extends Controller
{
    public function berita()
    {
        $q = trim((string) request('q', ''));

        $berita = Berita::where('terbit', true)
            ->when($q !== '', function ($query) use ($q) {
                // Karakter khusus LIKE di-escape supaya dicari apa adanya.
                $kata = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
                $query->where(fn ($w) => $w->where('judul', 'like', $kata)->orWhere('ringkas', 'like', $kata));
            })
            ->orderByDesc('tanggal')
            ->paginate(9)
            ->withQueryString();

        return view('berita.index', ['berita' => $berita, 'q' => $q]);
    }

    public function pengumuman()
    {
        return view('pengumuman', ['pengumuman' => Pengumuman::orderByDesc('tanggal')->paginate(15)]);
    }

    public function agenda()
    {
        return view('agenda', ['agenda' => Agenda::orderByDesc('tanggal')->paginate(15)]);
    }

    public function beritaShow(string $slug)
    {
        $berita = Berita::where('terbit', true)->where('slug', $slug)->firstOrFail();

        return view('berita.show', [
            'berita' => $berita,
            'lainnya' => Berita::where('terbit', true)->where('id', '!=', $berita->id)->orderByDesc('tanggal')->take(4)->get(),
        ]);
    }

    public function galeri()
    {
        return view('galeri', [
            'galeri' => Galeri::orderByDesc('tanggal')->paginate(12),
        ]);
    }

    public function profil()
    {
        return view('profil', ['staf' => Staf::orderBy('urutan')->orderBy('id')->get()]);
    }

    public function akademik()
    {
        return view('akademik', [
            'program' => ProgramKeahlian::orderBy('urutan')->get(),
            'agenda' => Agenda::where('tanggal', '>=', now()->toDateString())->orderBy('tanggal')->take(5)->get(),
            'pengumuman' => Pengumuman::where('kategori', 'Akademik')->orderByDesc('tanggal')->take(5)->get(),
        ]);
    }

    public function kesiswaan()
    {
        return view('kesiswaan', [
            'ekskul' => Ekstrakurikuler::orderBy('urutan')->orderBy('id')->get(),
            'prestasi' => Berita::where('terbit', true)->where('kategori', 'Prestasi')->orderByDesc('tanggal')->take(4)->get(),
            'pengumuman' => Pengumuman::where('kategori', 'Kesiswaan')->orderByDesc('tanggal')->take(5)->get(),
        ]);
    }
}
