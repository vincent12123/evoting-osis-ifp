<?php

// Regresi: kolom unggah Filament macet "Memuat" saat admin membuka alamat yang berbeda dari APP_URL
// (mis. 127.0.0.1 vs localhost) karena URL foto absolut diblokir CORS. URL berkas publik harus relatif.

use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use Illuminate\Support\Facades\Storage;

it('URL berkas publik (foto paslon, logo) relatif terhadap alamat yang sedang dibuka', function () {
    config(['app.url' => 'http://localhost:8000']);

    expect(Storage::disk('public')->url('candidates/a.webp'))->toBe('/storage/candidates/a.webp')
        ->and(Storage::disk('public')->url('logos/b.webp'))->toBe('/storage/logos/b.webp');
});

it('API kiosk mengirim URL foto & logo relatif', function () {
    $election = Election::factory()->active()->create(['logo_path' => 'logos/sekolah.webp']);
    Candidate::factory()->for($election)->number(1)->create(['photo_path' => 'candidates/foto.webp']);
    $token = connectDevice(PollingStation::factory()->create());

    $this->getJson('/api/kiosk/status', kioskHeaders($token))->assertJsonPath('election.logo_url', '/storage/logos/sekolah.webp');
    $this->getJson('/api/kiosk/candidates', kioskHeaders($token))->assertJsonPath('candidates.0.photo_url', '/storage/candidates/foto.webp');
});
