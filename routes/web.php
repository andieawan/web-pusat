<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\HalamanController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// ---------- Publik ----------
Route::get('/', [HomeController::class, 'index'])->name('beranda');
Route::get('/berita', [HalamanController::class, 'berita'])->name('berita');
Route::get('/berita/{slug}', [HalamanController::class, 'beritaShow'])->name('berita.show');
Route::get('/galeri', [HalamanController::class, 'galeri'])->name('galeri');
Route::get('/pengumuman', [HalamanController::class, 'pengumuman'])->name('pengumuman');
Route::get('/agenda', [HalamanController::class, 'agenda'])->name('agenda');
Route::get('/profil', [HalamanController::class, 'profil'])->name('profil');
Route::get('/akademik', [HalamanController::class, 'akademik'])->name('akademik');
Route::get('/kesiswaan', [HalamanController::class, 'kesiswaan'])->name('kesiswaan');

// Halaman yang belum dibuat (placeholder) agar menu tidak error.
foreach (['ppdb'] as $halaman) {
    Route::view('/' . $halaman, 'placeholder', ['judul' => ucfirst($halaman)])->name($halaman);
}

// ---------- Pusat Sekolah (login admin lewat Pusat; bawaan mati, lihat docs/PUSAT.md) ----------
Route::get('/pusat/masuk', [Admin\PusatController::class, 'mulai'])->middleware('throttle:20,1')->name('pusat.masuk');
Route::get('/pusat/callback', [Admin\PusatController::class, 'callback'])->middleware('throttle:20,1')->name('pusat.callback');
Route::post('/pusat/webhook', [Admin\PusatController::class, 'webhook'])->middleware('throttle:120,1')->name('pusat.webhook');

// ---------- Login / logout ----------
Route::get('/login', [Admin\AuthController::class, 'form'])->name('login');
Route::post('/login', [Admin\AuthController::class, 'masuk'])->middleware('throttle:login')->name('login.proses');
Route::post('/logout', [Admin\AuthController::class, 'keluar'])->name('logout');

// ---------- Panel admin ----------
Route::prefix('admin')->name('admin.')->middleware(['auth', 'auth.session', 'admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('berita', Admin\BeritaController::class)->except('show');
    Route::resource('pengumuman', Admin\PengumumanController::class)->except('show');
    Route::resource('agenda', Admin\AgendaController::class)->except('show');
    Route::resource('galeri', Admin\GaleriController::class)->except('show');
    Route::resource('program', Admin\ProgramKeahlianController::class)->except('show');
    Route::resource('ekstrakurikuler', Admin\EkstrakurikulerController::class)->except('show');
    Route::resource('staf', Admin\StafController::class)->except('show');
    Route::resource('slide', Admin\SlideController::class)->except('show');

    Route::get('akun', [Admin\AkunController::class, 'edit'])->name('akun');
    Route::put('akun', [Admin\AkunController::class, 'profil'])->name('akun.profil');
    Route::put('akun/password', [Admin\AkunController::class, 'password'])->middleware('throttle:6,1')->name('akun.password');
    Route::resource('pengguna', Admin\PenggunaController::class)->except('show');

    Route::get('integrasi', [Admin\IntegrasiController::class, 'index'])->name('integrasi');
    Route::post('integrasi/uji-pusat', [Admin\IntegrasiController::class, 'ujiPusat'])->middleware('throttle:10,1')->name('integrasi.pusat');
    Route::post('integrasi/sinkron', [Admin\IntegrasiController::class, 'sinkron'])->middleware('throttle:5,1')->name('integrasi.sinkron');

    Route::get('pengaturan', [Admin\PengaturanController::class, 'edit'])->name('pengaturan');
    Route::put('pengaturan', [Admin\PengaturanController::class, 'update'])->name('pengaturan.update');
});
