<?php

use App\Http\Controllers\BeritaAcaraController;
use Illuminate\Support\Facades\Route;

// Halaman depan tidak dipakai: panitia ke /admin, IFP ke /kiosk (Checkpoint 4).
Route::redirect('/', '/admin');

// Kartu SCR-A7 — berita acara cetak (admin saja).
Route::get('/berita-acara', BeritaAcaraController::class)
    ->middleware('auth')
    ->name('berita-acara');
