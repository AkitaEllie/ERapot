<?php

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

Route::livewire('/penempatan', 'pages::penempatan')
    ->name('penempatan')
    ->middleware(['auth', 'hak:penempatan']);

// Kepala Sekolah
Route::livewire('/dashboard/progres', 'pages::dashboard-kepsekol')
    ->name('dashboard.kepsekol')
    ->middleware(['auth', 'hak:dashboard.progres']);

Route::livewire('/review-rapor', 'pages::review-rapor')
    ->name('review-rapor')
    ->middleware(['auth', 'hak:review']);

// Guru
Route::livewire('/indikator', 'pages::indikator')
    ->name('indikator')
    ->middleware(['auth', 'hak:indikator']);

Route::livewire('/rapor', 'pages::rapor-list')
    ->name('rapor.index')
    ->middleware(['auth', 'hak:rapor']);

// Declared before the catch-all below, otherwise {mapel} would swallow "pratinjau".
Route::livewire('/rapor/{rapor}/pratinjau', 'pages::rapor-preview')
    ->name('rapor.pratinjau')
    ->middleware(['auth', 'hak:ekspor']);

Route::livewire('/rapor/{rapor}/{mapel}', 'pages::rapor-input')
    ->name('rapor.input')
    ->middleware(['auth', 'hak:rapor']);

Route::post('/logout', function (Request $request) {
    $request->user()->logout();

    return redirect()->route('login');
})->name('logout')->middleware('auth');
