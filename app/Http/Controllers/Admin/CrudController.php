<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\GambarUpload;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dasar CRUD admin. Controller turunan cukup mendefinisikan
 * model, judul, rute, field form, dan kolom tabel.
 *
 * Tipe field: text, textarea, date, number, select (butuh 'opsi'), image ('wajib' => true bila harus ada), bool.
 */
abstract class CrudController extends Controller
{
    abstract protected function model(): string;
    abstract protected function judul(): string;
    abstract protected function rute(): string;
    /** @return array<string, array{label:string,type:string,rules?:string,opsi?:array,bantuan?:string}> */
    abstract protected function fields(): array;
    /** @return array<string,string> label => atribut */
    abstract protected function kolom(): array;

    protected function urutan(): array
    {
        return ['id', 'desc'];
    }

    /** Kait untuk menambah data sebelum disimpan (mis. slug). */
    protected function sebelumSimpan(array $data, ?object $item): array
    {
        return $data;
    }

    private function tampil(string $view, array $extra = [])
    {
        return view($view, array_merge([
            'judul' => $this->judul(), 'rute' => $this->rute(),
            'fields' => $this->fields(), 'kolom' => $this->kolom(),
        ], $extra));
    }

    public function index()
    {
        [$kolom, $arah] = $this->urutan();
        $items = ($this->model())::orderBy($kolom, $arah)->paginate(15);

        return $this->tampil('admin.crud.index', ['items' => $items]);
    }

    public function create()
    {
        return $this->tampil('admin.crud.form', ['item' => null]);
    }

    public function store(Request $request)
    {
        ($this->model())::create($this->ambilData($request, null));

        return redirect()->route('admin.' . $this->rute() . '.index')->with('ok', $this->judul() . ' berhasil ditambahkan.');
    }

    public function edit($id)
    {
        return $this->tampil('admin.crud.form', ['item' => ($this->model())::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $item = ($this->model())::findOrFail($id);
        $item->update($this->ambilData($request, $item));

        return redirect()->route('admin.' . $this->rute() . '.index')->with('ok', $this->judul() . ' berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = ($this->model())::findOrFail($id);
        foreach ($this->fields() as $nama => $f) {
            if ($f['type'] === 'image' && $item->{$nama}) {
                Storage::disk('public')->delete($item->{$nama});
            }
        }
        $item->delete();

        return back()->with('ok', $this->judul() . ' berhasil dihapus.');
    }

    private function ambilData(Request $request, ?object $item): array
    {
        $aturan = [];
        foreach ($this->fields() as $nama => $f) {
            if ($f['type'] === 'image') {
                $wajib = ($f['wajib'] ?? false) && ! ($item && $item->{$nama});
                $aturan[$nama] = ($wajib ? 'required' : 'nullable') . '|image|mimes:jpg,jpeg,png,webp,gif|max:8192';
            } elseif ($f['type'] === 'select') {
                // Nilai harus salah satu pilihan yang ada.
                $aturan[$nama] = ($f['rules'] ?? 'nullable') . '|in:' . implode(',', $f['opsi']);
            } else {
                $aturan[$nama] = $f['rules'] ?? 'nullable';
            }
        }
        $request->validate($aturan);

        $data = [];
        foreach ($this->fields() as $nama => $f) {
            if ($f['type'] === 'image') {
                if ($request->hasFile($nama)) {
                    if ($item && $item->{$nama}) {
                        Storage::disk('public')->delete($item->{$nama});
                    }
                    $data[$nama] = $this->simpanGambar($request->file($nama));
                }
            } elseif ($f['type'] === 'bool') {
                $data[$nama] = $request->boolean($nama);
            } else {
                $data[$nama] = $request->input($nama);
            }
        }

        return $this->sebelumSimpan($data, $item);
    }

    /** Simpan foto di disk public; foto besar otomatis diperkecil. */
    private function simpanGambar(UploadedFile $berkas): string
    {
        $hasil = GambarUpload::perkecil($berkas->getRealPath(), (int) config('smk.foto_maks_piksel', 1600));
        if ($hasil === null) {
            return $berkas->store('konten', 'public');
        }

        [$isi, $ext] = $hasil;
        $nama = 'konten/' . Str::random(40) . '.' . $ext;
        Storage::disk('public')->put($nama, $isi);

        return $nama;
    }
}
