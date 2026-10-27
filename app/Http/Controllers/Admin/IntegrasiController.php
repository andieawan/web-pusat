<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramKeahlian;
use App\Services\PusatKlien;
use Illuminate\Support\Facades\Cache;

/** Admin → Integrasi Pusat: susunan sama dengan aplikasi lain (status, alamat, data dari Pusat, uji koneksi, sinkron). */
class IntegrasiController extends Controller
{
    public function index()
    {
        $siap = PusatKlien::siap();
        $aktif = PusatKlien::aktif();
        $r = $aktif ? PusatKlien::ringkasan() : null;
        $jurusan = $aktif ? PusatKlien::jurusan() : null;

        return view('admin.integrasi', ['p' => [
            'aktif' => $aktif, 'siap' => $siap, 'url' => config('pusat.url'),
            'callback' => route('pusat.callback'), 'webhook' => route('pusat.webhook'),
            'tahun' => $r['tahun_ajaran'] ?? null,
            'terakhir' => Cache::get('pusat.sinkron_terakhir'),
            'jumlah' => $r === null ? [] : [
                'siswa aktif' => (int) ($r['jumlah_siswa_aktif'] ?? 0), 'guru' => (int) ($r['jumlah_guru'] ?? 0),
                'staf' => (int) ($r['jumlah_staf'] ?? 0), 'kelas' => (int) ($r['jumlah_kelas'] ?? 0), 'jurusan' => count($jurusan ?? []),
            ],
            'jurusanBelumAda' => $jurusan === null ? [] : collect($jurusan)->pluck('nama')
                ->reject(fn ($n) => ProgramKeahlian::whereRaw('LOWER(nama) = ?', [mb_strtolower(trim($n))])->exists())->values()->all(),
        ]]);
    }

    public function ujiPusat()
    {
        if (! PusatKlien::aktif()) {
            return back()->with('err', 'Integrasi Pusat belum aktif (isi PUSAT_* di .env).');
        }
        try {
            $res = PusatKlien::http()->get('/api/v1/ping');
        } catch (\Throwable $e) {
            return back()->with('err', 'Pusat tidak dapat dihubungi: '.mb_substr($e->getMessage(), 0, 200));
        }
        if ($res->status() === 401) {
            return back()->with('err', 'Pusat menolak kredensial (client_id/secret salah atau aplikasi nonaktif).');
        }

        return $res->ok() ? back()->with('ok', 'Terhubung ke Pusat. Kredensial benar.') : back()->with('err', 'Pusat membalas HTTP '.$res->status().'.');
    }

    public function sinkron()
    {
        if (! PusatKlien::aktif()) {
            return back()->with('err', 'Integrasi Pusat belum aktif (isi PUSAT_* di .env).');
        }

        return PusatKlien::segarkan()
            ? back()->with('ok', 'Data dari Pusat diperbarui (ringkasan sekolah & jurusan).')
            : back()->with('err', 'Pusat tidak dapat dihubungi; website tetap memakai data terakhir yang tersimpan.');
    }
}
