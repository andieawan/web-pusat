<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Akun admin yang sedang login: ubah nama/email dan ganti password sendiri. */
class AkunController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.akun', ['user' => $request->user()]);
    }

    public function profil(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], ['email.unique' => 'Email itu sudah dipakai akun lain.']);

        $user->update($data);

        return back()->with('ok', 'Data akun disimpan.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'password_lama' => 'required|current_password',
            'password' => ['required', 'confirmed', 'different:password_lama', Password::min(8)->letters()->numbers()],
        ], [
            'password_lama.current_password' => 'Password saat ini salah.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
            'password.different' => 'Password baru harus berbeda dari yang lama.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.letters' => 'Password baru harus mengandung huruf.',
            'password.numbers' => 'Password baru harus mengandung angka.',
        ]);

        // Sesi di perangkat lain otomatis keluar (middleware auth.session); sesi ini tetap aktif.
        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('ok', 'Password berhasil diganti.');
    }
}
