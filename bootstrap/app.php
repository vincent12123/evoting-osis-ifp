<?php

use App\Exceptions\KioskException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Rute admin di luar panel Filament (mis. berita acara) mengarahkan tamu ke login panel.
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));

        // Produksi di balik Cloudflare Tunnel: cloudflared → nginx di mesin yang sama. Hanya proxy lokal yang
        // dipercaya, sehingga skema HTTPS & IP asli (untuk batas percobaan kode aktivasi) benar, tetapi orang
        // yang mengakses nginx langsung dari internet tidak bisa memalsukan X-Forwarded-For.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Penolakan kiosk (kode salah, rem 2 detik, pemungutan ditutup) adalah alur normal, bukan error.
        $exceptions->dontReport(KioskException::class);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
