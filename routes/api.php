<?php

use App\Http\Controllers\KioskApiController;
use App\Http\Middleware\AuthenticateKioskDevice;
use Illuminate\Support\Facades\Route;

// API layar IFP — SPEC §5.4–§5.5. Prefix /api dari bootstrap/app.php.
Route::prefix('kiosk')->name('kiosk.api.')->group(function () {
    Route::post('activate', [KioskApiController::class, 'activate'])->name('activate');

    Route::middleware(AuthenticateKioskDevice::class)->group(function () {
        Route::get('status', [KioskApiController::class, 'status'])->name('status');
        Route::get('candidates', [KioskApiController::class, 'candidates'])->name('candidates');
        Route::post('votes', [KioskApiController::class, 'vote'])->name('votes');
    });
});
