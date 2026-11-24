<?php

// Produksi di balik Cloudflare Tunnel (cloudflared → nginx di mesin yang sama).

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_uji-proxy', fn () => response()->json([
        'secure' => request()->isSecure(),
        'ip' => request()->ip(),
        'url' => url('/kiosk'),
    ]));
});

it('header proxy dari cloudflared lokal (127.0.0.1) dipercaya: HTTPS & IP asli', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '36.70.1.2', 'X-Forwarded-Host' => 'evoting.smpn2sintang.sch.id', 'X-Forwarded-Port' => '443'])
        ->getJson('/_uji-proxy')
        ->assertJson(['secure' => true, 'ip' => '36.70.1.2', 'url' => 'https://evoting.smpn2sintang.sch.id/kiosk']);
});

it('header proxy dari IP luar diabaikan (tidak bisa memalsukan IP untuk lolos batas percobaan)', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '1.2.3.4'])
        ->getJson('/_uji-proxy')
        ->assertJson(['secure' => false, 'ip' => '203.0.113.9']);
});
