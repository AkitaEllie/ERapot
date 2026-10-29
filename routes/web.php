<?php

use App\Http\Controllers\RaporDownloadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(Auth::check() ? 'dashboard' : 'login'));

Route::livewire('/login', 'pages::login')->name('login')->middleware('guest');

// Dispatches to the dashboard that matches the signed-in role.
Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard')->middleware('auth');

// Tata Usaha
Route::livewire('/dashboard/tata-usaha', 'pages::dashboard-tata-usaha')
    ->name('dashboard.tata-usaha')
    ->middleware(['auth', 'hak:dashboard.tata-usaha']);

Route::livewire('/siswa', 'pages::siswa')
    ->name('siswa.index')
    ->middleware(['auth', 'hak:siswa']);

Route::livewire('/siswa/tambah', 'pages::siswa-form')
    ->name('siswa.tambah')
    ->middleware(['auth', 'hak:siswa']);

Route::livewire('/siswa/{siswa}/ubah', 'pages::siswa-form')
    ->name('siswa.ubah')
    ->middleware(['auth', 'hak:siswa']);

Route::livewire('/penempatan', 'pages::penempatan')
    ->name('penempatan')
    ->middleware(['auth', 'hak:penempatan']);

// Kepala Sekolah
Route::livewire('/dashboard/progres', 'pages::dashboard-kepsekol')
    ->name('dashboard.kepsekol')
    ->middleware(['auth', 'hak:dashboard.progres']);

Route::livewire('/dashboard/progres/{kelas}', 'pages::progres-kelas')
    ->name('dashboard.kepsekol.kelas')
    ->middleware(['auth', 'hak:dashboard.progres']);

Route::livewire('/review-rapor', 'pages::review-rapor')
    ->name('review-rapor')
    ->middleware(['auth', 'hak:review']);

Route::livewire('/persetujuan-rapor', 'pages::persetujuan-rapor')
    ->name('persetujuan-rapor')
    ->middleware(['auth', 'hak:persetujuan']);

// Guru
Route::livewire('/indikator', 'pages::indikator')
    ->name('indikator')
    ->middleware(['auth', 'hak:indikator']);

Route::livewire('/rapor', 'pages::rapor-list')
    ->name('rapor.index')
    ->middleware(['auth', 'hak:rapor']);

// Declared before the catch-all below, otherwise {mapel} would swallow "pratinjau".
// The principal reads approved reports here too, from Persetujuan Rapor.
Route::livewire('/rapor/{rapor}/pratinjau', 'pages::rapor-preview')
    ->name('rapor.pratinjau')
    ->middleware(['auth', 'hak:ekspor,persetujuan']);

// Also before the catch-all: the homeroom teacher's growth and attendance tab.
Route::livewire('/rapor/{rapor}/data-fisik', 'pages::rapor-data-fisik')
    ->name('rapor.data-fisik')
    ->middleware(['auth', 'hak:rapor']);

// Also before the catch-all. Streams the exported file, exporting on demand when
// the stored copy is missing so a download link never 404s.
Route::get('/rapor/{rapor}/unduh', RaporDownloadController::class)
    ->name('rapor.unduh')
    ->middleware(['auth', 'hak:ekspor']);

Route::livewire('/rapor/{rapor}/{mapel}', 'pages::rapor-input')
    ->name('rapor.input')
    ->middleware(['auth', 'hak:rapor']);

// Screens added from the supplementary wireframe (TU-01 to TU-07, G-04).
Route::livewire('/ekspor-rapor', 'pages::ekspor-rapor')
    ->name('ekspor-rapor')
    ->middleware(['auth', 'hak:ekspor']);

Route::livewire('/tahun-ajaran', 'pages::tahun-ajaran')
    ->name('tahun-ajaran')
    ->middleware(['auth', 'hak:tahun-ajaran']);

Route::livewire('/pengguna', 'pages::pengguna')
    ->name('pengguna')
    ->middleware(['auth', 'hak:pengguna']);

Route::livewire('/kelas', 'pages::kelas')
    ->name('kelas')
    ->middleware(['auth', 'hak:kelas']);

Route::livewire('/mapel', 'pages::mapel')
    ->name('mapel')
    ->middleware(['auth', 'hak:mapel']);

Route::livewire('/penugasan-mengajar', 'pages::penugasan-mengajar')
    ->name('penugasan-mengajar')
    ->middleware(['auth', 'hak:penugasan']);

// Available to every signed-in role: a user changes only their own password.
Route::livewire('/profil', 'pages::profil')
    ->name('profil')
    ->middleware('auth');

Route::post('/logout', function (Request $request) {
    $request->user()->logout();

    return redirect()->route('login');
})->name('logout')->middleware('auth');
