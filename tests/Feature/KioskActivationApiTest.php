<?php

// Checkpoint 3 — SPEC REQ-05, REQ-13, REQ-14, REQ-22 · acceptance-criteria.md AC-21–AC-25, AC-28, AC-29.

use App\Actions\ActivateDevice;
use App\Actions\GenerateActivationCode;
use App\Actions\ReleaseDevice;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\Vote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

it('AC-21 kode sah ditukar dengan token; DB hanya menyimpan hash dan kode terhapus', function () {
    $station = PollingStation::factory()->create(['name' => 'TPS Aula']);
    $code = app(GenerateActivationCode::class)->handle($station);

    $response = $this->postJson('/api/kiosk/activate', ['code' => $code])
        ->assertCreated()
        ->assertJsonPath('station.name', 'TPS Aula');

    $token = $response->json('token');
    expect(strlen($token))->toBeGreaterThanOrEqual(40);

    $station->refresh();
    expect($station->device_token_hash)->toBe(hash('sha256', $token))
        ->and($station->device_token_hash)->not->toBe($token)
        ->and($station->activation_code)->toBeNull()
        ->and($station->activation_code_expires_at)->toBeNull();

    expect(ActivityLog::where('action', 'device.activated')->exists())->toBeTrue();
});

it('AC-22 kode yang sudah dipakai ditolak', function () {
    $station = PollingStation::factory()->create();
    $code = app(GenerateActivationCode::class)->handle($station);
    $this->postJson('/api/kiosk/activate', ['code' => $code])->assertCreated();

    $this->postJson('/api/kiosk/activate', ['code' => $code])
        ->assertStatus(422)
        ->assertJson([
            'code' => 'invalid_code',
            'message' => 'Kode tidak dikenal atau sudah kedaluwarsa. Minta kode baru ke panitia.',
        ]);
});

it('AC-22 kode berumur lebih dari 30 menit ditolak', function () {
    $station = PollingStation::factory()->create();
    $code = app(GenerateActivationCode::class)->handle($station);

    Carbon::setTestNow(now()->addMinutes(31));

    $this->postJson('/api/kiosk/activate', ['code' => $code])->assertStatus(422)->assertJsonPath('code', 'invalid_code');
    expect($station->fresh()->device_token_hash)->toBeNull();
});

it('AC-22 kode berformat salah ditolak tanpa query', function (string $code) {
    $this->postJson('/api/kiosk/activate', ['code' => $code])->assertStatus(422)->assertJsonPath('code', 'invalid_code');
})->with(['', '12345', '1234567', 'abcdef']);

it('AC-23 percobaan ke-11 dari IP yang sama dalam 10 menit ditolak 429', function () {
    foreach (range(1, 10) as $_) {
        $this->postJson('/api/kiosk/activate', ['code' => '000000'])->assertStatus(422);
    }

    // Kode sah pun ditolak setelah batas tercapai.
    $code = app(GenerateActivationCode::class)->handle(PollingStation::factory()->create());

    $this->postJson('/api/kiosk/activate', ['code' => $code])
        ->assertStatus(429)
        ->assertJson(['code' => 'too_many_attempts', 'message' => 'Terlalu banyak percobaan. Coba lagi dalam 10 menit.']);
});

it('AC-24 mengaktifkan IFP kedua ke TPS yang sama membatalkan token IFP pertama', function () {
    $station = PollingStation::factory()->create();
    $first = connectDevice($station);
    $second = connectDevice($station);

    $this->getJson('/api/kiosk/status', kioskHeaders($first))
        ->assertUnauthorized()
        ->assertJson(['code' => 'device_unknown', 'message' => 'IFP ini sudah dilepas dari TPS. Masukkan kode aktivasi baru.']);

    $this->getJson('/api/kiosk/status', kioskHeaders($second))->assertOk();
});

it('AC-25 IFP yang dilepas mendapat 401 pada request berikutnya', function () {
    $station = PollingStation::factory()->create();
    $token = connectDevice($station);

    app(ReleaseDevice::class)->handle($station);

    expect($station->fresh()->device_token_hash)->toBeNull();
    $this->getJson('/api/kiosk/status', kioskHeaders($token))->assertUnauthorized();
});

it('NF-01 API kiosk tanpa token atau dengan token palsu ditolak', function (array $headers) {
    $this->getJson('/api/kiosk/status', $headers)->assertUnauthorized();
    $this->getJson('/api/kiosk/candidates', $headers)->assertUnauthorized();
    $this->postJson('/api/kiosk/votes', [], $headers)->assertUnauthorized();
})->with([
    'tanpa token' => [['Accept' => 'application/json']],
    'token palsu' => [['Authorization' => 'Bearer '.str_repeat('x', 48), 'Accept' => 'application/json']],
]);

it('AC-28 status berisi pemilihan, TPS, jumlah suara TPS, pengaturan; terakhir terlihat diperbarui', function () {
    $election = Election::factory()->active()->create([
        'name' => 'Pemilihan OSIS', 'touch_mode' => 'instant', 'reset_delay_seconds' => 5,
    ]);
    $candidate = Candidate::factory()->for($election)->number(1)->create();
    $election->update(['status' => 'open']);
    $station = PollingStation::factory()->create(['name' => 'TPS Lab']);
    $other = PollingStation::factory()->create();
    $token = connectDevice($station);

    foreach ([$station, $station, $other] as $s) {
        Vote::create(['election_id' => $election->id, 'candidate_id' => $candidate->id, 'polling_station_id' => $s->id, 'client_uuid' => (string) Str::uuid()]);
    }

    Carbon::setTestNow(now()->addMinutes(5));

    $this->getJson('/api/kiosk/status', kioskHeaders($token))
        ->assertOk()
        ->assertJsonPath('station.name', 'TPS Lab')
        ->assertJsonPath('station.is_active', true)
        ->assertJsonPath('station.vote_count', 2)
        ->assertJsonPath('election.status', 'open')
        ->assertJsonPath('election.touch_mode', 'instant')
        ->assertJsonPath('election.reset_delay_seconds', 5)
        ->assertJsonPath('election.sound_enabled', true)
        ->assertJsonPath('election.settings_version', $election->fresh()->settings_version);

    expect(abs($station->fresh()->device_last_seen_at->diffInSeconds(now())))->toBeLessThan(1.0);
});

it('AC-28 tanpa pemilihan aktif, status mengembalikan election null', function () {
    $token = connectDevice(PollingStation::factory()->create());

    $this->getJson('/api/kiosk/status', kioskHeaders($token))->assertOk()->assertJsonPath('election', null);
});

it('AC-29 status dan daftar paslon tidak memuat perolehan per paslon', function () {
    $election = Election::factory()->active()->create();
    $candidate = Candidate::factory()->for($election)->number(1)->create();
    $election->update(['status' => 'open']);
    $station = PollingStation::factory()->create();
    $token = connectDevice($station);
    Vote::create(['election_id' => $election->id, 'candidate_id' => $candidate->id, 'polling_station_id' => $station->id, 'client_uuid' => (string) Str::uuid()]);

    $status = $this->getJson('/api/kiosk/status', kioskHeaders($token))->assertOk()->json();
    $list = $this->getJson('/api/kiosk/candidates', kioskHeaders($token))->assertOk()->json();

    expect($list['candidates'])->toHaveCount(1)
        ->and(array_keys($list['candidates'][0]))->not->toContain('votes', 'votes_count', 'vote_count')
        ->and(json_encode($list))->not->toContain('vote')
        ->and(array_keys($status['election']))->not->toContain('votes', 'results');

    expect($list['candidates'][0])->toMatchArray([
        'number' => 1,
        'number_label' => '01',
        'accent' => 'paslon-1',
    ])->and($list['candidates'][0]['photo_url'])->toContain('/storage/candidates/');
});

it('token perangkat disimpan sebagai hash, bukan teks asli', function () {
    $station = PollingStation::factory()->create();
    $token = connectDevice($station);

    expect(PollingStation::where('device_token_hash', $token)->exists())->toBeFalse()
        ->and(PollingStation::where('device_token_hash', ActivateDevice::hash($token))->exists())->toBeTrue();
});
