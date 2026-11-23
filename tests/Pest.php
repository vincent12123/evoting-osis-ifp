<?php

use App\Actions\ActivateDevice;
use App\Actions\GenerateActivationCode;
use App\Models\PollingStation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Semua feature test memakai SQLite in-memory (phpunit.xml) dan database segar per test.
| Konvensi: satu file per kelompok acceptance criteria; nama test diawali ID kriteria (mis. "AC-01 …").
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Hubungkan IFP ke TPS lewat alur sebenarnya (kode → token). Mengembalikan token mentah.
 */
function connectDevice(PollingStation $station): string
{
    $code = app(GenerateActivationCode::class)->handle($station);
    [, $token] = app(ActivateDevice::class)->handle($code);

    return $token;
}

/** Header Bearer untuk API kiosk. */
function kioskHeaders(string $token): array
{
    return ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
}
