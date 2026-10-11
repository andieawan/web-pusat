<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Kelola akun admin: tambah, ubah (termasuk reset password), dan hapus. */
class PenggunaController extends Controller
{
    private function pesan(): array
    {
        return [
            'email.unique' => 'Email itu sudah dipakai akun lain.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.letters' => 'Password harus mengandung huruf.',
            'password.numbers' => 'Password harus mengandung angka.',
        ];
    }

    private function aturan(?User $user): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    private function admin(User $pengguna): User
    {
        abort_unless($pengguna->is_admin, 404);

        return $pengguna;
    }

    public function index()
    {
        return view('admin.pengguna.index', [
            'items' => User::where('is_admin', true)->orderBy('name')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('admin.pengguna.form', ['item' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan(null), $this->pesan());

        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->forceFill(['is_admin' => true])->save();

        return redirect()->route('admin.pengguna.index')->with('ok', "Akun {$user->email} ditambahkan.");
    }

    public function edit(User $pengguna)
    {
        return view('admin.pengguna.form', ['item' => $this->admin($pengguna)]);
    }

    public function update(Request $request, User $pengguna)
    {
        $this->admin($pengguna);
        $data = $request->validate($this->aturan($pengguna), $this->pesan());

        $pengguna->fill(['name' => $data['name'], 'email' => $data['email']]);
        if (filled($data['password'] ?? null)) {
            $pengguna->password = $data['password'];
        }
        $pengguna->save();

        return redirect()->route('admin.pengguna.index')->with('ok', 'Akun diperbarui.');
    }

    public function destroy(Request $request, User $pengguna)
    {
        $this->admin($pengguna);

        // Mencegah admin menghapus dirinya sendiri, sehingga selalu ada minimal satu admin.
        if ($pengguna->is($request->user())) {
            return back()->withErrors(['hapus' => 'Anda tidak bisa menghapus akun yang sedang dipakai.']);
        }

        $pengguna->delete();

        return back()->with('ok', 'Akun dihapus.');
    }
}
