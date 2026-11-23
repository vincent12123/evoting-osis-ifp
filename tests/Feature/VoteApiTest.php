<?php

// Checkpoint 3 — SPEC REQ-09, REQ-11, REQ-13, REQ-18–REQ-21 · acceptance-criteria.md AC-30–AC-37.

use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\Vote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->election = Election::factory()->active()->create();
    $this->candidate = Candidate::factory()->for($this->election)->number(1)->create();
    $this->election->update(['status' => 'open']); // paslon terkunci setelah ini (REQ-19)
    $this->station = PollingStation::factory()->create();
    $this->token = connectDevice($this->station);
});

function castVote($test, array $payload = [], ?string $token = null)
{
    return $test->postJson('/api/kiosk/votes', [
        'candidate_id' => $test->candidate->id,
        'client_uuid' => (string) Str::uuid(),
        ...$payload,
    ], kioskHeaders($token ?? $test->token));
}

it('AC-30 suara sah tersimpan sebagai suara resmi dan dibalas 201', function () {
    castVote($this)->assertCreated()->assertJson(['ok' => true, 'vote_count' => 1]);

    $vote = Vote::sole();
    expect($vote->is_trial)->toBeFalse()
        ->and($vote->candidate_id)->toBe($this->candidate->id)
        ->and($vote->polling_station_id)->toBe($this->station->id)
        ->and($vote->election_id)->toBe($this->election->id);
});

it('AC-31 kiriman ulang dengan client_uuid yang sama tidak menambah suara', function () {
    $uuid = (string) Str::uuid();

    castVote($this, ['client_uuid' => $uuid])->assertCreated();

    // Kiriman ulang langsung (di bawah 2 detik) tetap dianggap sukses, bukan ditolak rem.
    foreach (range(1, 5) as $_) {
        castVote($this, ['client_uuid' => $uuid])->assertOk()->assertJson(['ok' => true, 'vote_count' => 1]);
    }

    expect(Vote::count())->toBe(1);
});

it('AC-31 kiriman ulang tetap sukses walau pemungutan baru saja ditutup', function () {
    $uuid = (string) Str::uuid();
    castVote($this, ['client_uuid' => $uuid])->assertCreated();
    $this->election->update(['status' => 'closed']);

    castVote($this, ['client_uuid' => $uuid])->assertOk();
    expect(Vote::count())->toBe(1);
});

it('AC-32 suara ditolak saat status Persiapan atau Ditutup', function (string $status) {
    $this->election->update(['status' => $status]);

    castVote($this)
        ->assertStatus(409)
        ->assertJson(['code' => 'election_not_open', 'message' => 'Pemungutan suara sudah ditutup. Suara ini tidak tercatat.']);

    expect(Vote::count())->toBe(0);
})->with(['preparation', 'closed']);

it('AC-32 suara ditolak bila tidak ada pemilihan aktif', function () {
    $this->election->update(['is_active' => false]);

    castVote($this)->assertStatus(409)->assertJsonPath('code', 'election_not_open');
});

it('AC-33 TPS nonaktif ditolak 403', function () {
    $this->station->update(['is_active' => false]);

    castVote($this)->assertForbidden()->assertJsonPath('code', 'station_inactive');
    expect(Vote::count())->toBe(0);
});

it('AC-33 paslon milik pemilihan lain ditolak 422', function () {
    $foreign = Candidate::factory()->for(Election::factory())->number(1)->create();

    castVote($this, ['candidate_id' => $foreign->id])->assertStatus(422)->assertJsonPath('code', 'invalid_candidate');
    expect(Vote::count())->toBe(0);
});

it('AC-33 payload tidak lengkap atau UUID salah ditolak 422', function (array $payload) {
    $this->postJson('/api/kiosk/votes', $payload, kioskHeaders($this->token))->assertStatus(422);
    expect(Vote::count())->toBe(0);
})->with([
    'tanpa paslon' => [['client_uuid' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d']],
    'tanpa uuid' => [['candidate_id' => 1]],
    'uuid salah' => [['candidate_id' => 1, 'client_uuid' => 'bukan-uuid']],
]);

it('AC-34 suara saat Uji coba ditandai is_trial dan dihitung terpisah', function () {
    $this->election->update(['status' => 'trial']);

    castVote($this)->assertCreated()->assertJsonPath('vote_count', 1);

    expect(Vote::sole()->is_trial)->toBeTrue();
});

it('AC-35 suara kedua dari TPS yang sama < 2 detik ditolak; TPS lain tetap diterima', function () {
    Carbon::setTestNow(Carbon::parse('2026-12-01 03:00:00.000'));
    castVote($this)->assertCreated();

    Carbon::setTestNow(Carbon::parse('2026-12-01 03:00:01.500'));
    castVote($this)
        ->assertStatus(429)
        ->assertJson(['code' => 'too_fast', 'message' => 'Tunggu sebentar, lalu pilih lagi.']);

    $otherToken = connectDevice(PollingStation::factory()->create());
    castVote($this, [], $otherToken)->assertCreated();

    Carbon::setTestNow(Carbon::parse('2026-12-01 03:00:02.100'));
    castVote($this)->assertCreated();

    expect(Vote::count())->toBe(3);
});

it('AC-36 tabel votes dan log tidak menyimpan IP, user-agent, atau token', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '10.11.12.13'])
        ->withHeader('User-Agent', 'IFP-Hisense-Uji')
        ->postJson('/api/kiosk/votes', [
            'candidate_id' => $this->candidate->id,
            'client_uuid' => (string) Str::uuid(),
        ], kioskHeaders($this->token))
        ->assertCreated();

    expect(Schema::getColumnListing('votes'))->toEqualCanonicalizing([
        'id', 'election_id', 'candidate_id', 'polling_station_id', 'is_trial', 'client_uuid', 'created_at',
    ]);

    $dump = json_encode([DB::table('votes')->get(), DB::table('activity_logs')->get(), DB::table('polling_stations')->get()]);
    expect($dump)->not->toContain('10.11.12.13')
        ->not->toContain('IFP-Hisense-Uji')
        ->not->toContain($this->token);

    $log = storage_path('logs/laravel.log');
    if (file_exists($log)) {
        expect(file_get_contents($log))->not->toContain('10.11.12.13');
    }
});

it('AC-37 tidak ada rute untuk mengubah atau menghapus suara, dan model menolaknya', function () {
    $routes = collect(Route::getRoutes()->getRoutes())->map(fn ($r) => $r->uri());
    expect($routes->filter(fn (string $uri) => str_contains($uri, 'admin') && str_contains($uri, 'vote')))->toBeEmpty();

    castVote($this)->assertCreated();
    $vote = Vote::sole();

    expect(fn () => $vote->update(['is_trial' => true]))->toThrow(LogicException::class, 'Suara tidak bisa diubah.')
        ->and(fn () => $vote->delete())->toThrow(LogicException::class, 'Suara sah tidak bisa dihapus.');

    expect(Vote::count())->toBe(1);
});

it('suara tidak menulis log aktivitas (kerahasiaan, tidak ada jejak per suara)', function () {
    $before = ActivityLog::count();
    castVote($this)->assertCreated();

    expect(ActivityLog::count())->toBe($before);
});
