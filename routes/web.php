<?php

use App\Http\Controllers\BeritaAcaraController;
use App\Http\Controllers\KioskPageController;
use Illuminate\Support\Facades\Route;

// Halaman depan tidak dipakai: panitia ke /admin, IFP ke /kiosk.
Route::redirect('/', '/admin');

// Layar IFP (SCR-K1–K7) — tanpa login; IFP dikenali lewat token perangkat di API.
Route::get('/kiosk', [KioskPageController::class, 'show'])->name('kiosk');

// Pratinjau layar IFP untuk admin (TSK-05).
Route::get('/kiosk/pratinjau', [KioskPageController::class, 'preview'])
    ->middleware('auth')
    ->name('kiosk.preview');

// Kartu SCR-A7 — berita acara cetak (admin saja).
Route::get('/berita-acara', BeritaAcaraController::class)
    ->middleware('auth')
    ->name('berita-acara');
