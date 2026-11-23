<?php

// Checkpoint 3 — SPEC REQ-02, REQ-13, REQ-20, REQ-23 · acceptance-criteria.md AC-38, AC-39, AC-55, AC-56, UX-06, UX-10.

use App\Actions\ElectionLifecycle;
use App\Enums\ElectionStatus;
use App\Exceptions\ElectionTransitionException;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ElectionStatsWidget;
use App\Filament\Widgets\PollingStationsWidget;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
    $this->election = Election::factory()->active()->create(['name' => 'Pemilihan OSIS 2026/2027']);
});

function readyToOpen(Election $election): PollingStation
{
    Candidate::factory()->for($election)->number(1)->create();
    Candidate::factory()->for($election)->number(2)->create();
    $station = PollingStation::factory()->create();
    connectDevice($station);

    return $station;
}

function trialVotes(Election $election, PollingStation $station, int $n): void
{
    $candidate = $election->candidates()->first();
    foreach (range(1, $n) as $_) {
        Vote::create(['election_id' => $election->id, 'candidate_id' => $candidate->id, 'polling_station_id' => $station->id, 'is_trial' => true, 'client_uuid' => (string) Str::uuid()]);
    }
}

it('AC-38 pemungutan tidak bisa dibuka dengan paslon kurang dari 2', function () {
    Candidate::factory()->for($this->election)->number(1)->create();
    connectDevice(PollingStation::factory()->create());

    expect(fn () => app(ElectionLifecycle::class)->open($this->election))
        ->toThrow(ElectionTransitionException::class, 'Tambahkan minimal 2 paslon lengkap sebelum membuka pemungutan.');

    expect($this->election->fresh()->status)->toBe(ElectionStatus::Preparation);

    Livewire::test(Dashboard::class)
        ->assertActionDisabled('open')
        ->assertSee('Tambahkan minimal 2 paslon lengkap sebelum membuka pemungutan.');
});

it('AC-38 pemungutan tidak bisa dibuka tanpa IFP terhubung', function () {
    Candidate::factory()->for($this->election)->number(1)->create();
    Candidate::factory()->for($this->election)->number(2)->create();
    PollingStation::factory()->create(); // TPS ada, IFP belum

    expect(fn () => app(ElectionLifecycle::class)->open($this->election))
        ->toThrow(ElectionTransitionException::class, 'Belum ada IFP yang terhubung ke TPS mana pun.');

    Livewire::test(Dashboard::class)
        ->assertActionDisabled('open')
        ->assertSee('Belum ada IFP yang terhubung ke TPS mana pun.');
});

it('AC-39 Buka dari Uji coba mengosongkan suara uji coba, mengisi opened_at, dan mencatat log', function () {
    $station = readyToOpen($this->election);
    app(ElectionLifecycle::class)->startTrial($this->election, $this->admin);
    trialVotes($this->election, $station, 12);

    Livewire::test(Dashboard::class)
        ->mountAction('open')
        ->assertMountedActionModalSee('Buka pemungutan suara "Pemilihan OSIS 2026/2027"?')
        ->assertMountedActionModalSee('Suara uji coba (12) akan dikosongkan.')
        ->callMountedAction()
        ->assertNotified('Pemungutan suara dibuka.');

    $this->election->refresh();
    expect($this->election->status)->toBe(ElectionStatus::Open)
        ->and($this->election->opened_at)->not->toBeNull()
        ->and(Vote::count())->toBe(0);

    expect(ActivityLog::where('action', 'election.trial_cleared')->sole()->properties['old']['suara uji coba'])->toBe(12)
        ->and(ActivityLog::where('action', 'election.status_changed')->latest('id')->first()->summary())->toContain('status: Uji coba → Dibuka');
});

it('AC-39 Buka langsung dari Persiapan (tanpa uji coba) diperbolehkan', function () {
    readyToOpen($this->election);

    app(ElectionLifecycle::class)->open($this->election, $this->admin);

    expect($this->election->fresh()->status)->toBe(ElectionStatus::Open);
});

it('AC-55 Tutup pemungutan memakai dialog, mengisi closed_at, dan tercatat di log', function () {
    readyToOpen($this->election);
    app(ElectionLifecycle::class)->open($this->election);

    Livewire::test(Dashboard::class)
        ->mountAction('close')
        ->assertMountedActionModalSee('Tutup pemungutan?')
        ->assertMountedActionModalSee('Setelah ditutup, IFP tidak menerima suara lagi.')
        ->callMountedAction()
        ->assertNotified('Pemungutan suara ditutup.');

    $this->election->refresh();
    expect($this->election->status)->toBe(ElectionStatus::Closed)
        ->and($this->election->closed_at)->not->toBeNull();
});

it('AC-55 status Ditutup final: tidak ada aksi untuk membuka kembali', function () {
    readyToOpen($this->election);
    app(ElectionLifecycle::class)->open($this->election);
    app(ElectionLifecycle::class)->close($this->election);

    Livewire::test(Dashboard::class)
        ->assertActionHidden('open')
        ->assertActionHidden('startTrial')
        ->assertActionHidden('close');

    expect(fn () => app(ElectionLifecycle::class)->open($this->election->fresh()))
        ->toThrow(ElectionTransitionException::class, 'Aksi ini tidak bisa dilakukan saat status pemilihan Ditutup.');
});

it('AC-56 simulasi & kosongkan suara uji coba hanya bisa saat Uji coba', function (string $status) {
    readyToOpen($this->election);
    $this->election->update(['status' => $status]);

    Livewire::test(Dashboard::class)
        ->assertActionHidden('simulate')
        ->assertActionHidden('clearTrial');

    expect(fn () => app(ElectionLifecycle::class)->addSimulatedVotes($this->election))->toThrow(ElectionTransitionException::class)
        ->and(fn () => app(ElectionLifecycle::class)->clearTrialVotes($this->election))->toThrow(ElectionTransitionException::class);
})->with(['open', 'closed', 'preparation']);

it('saat Uji coba: tambah 10 suara simulasi lalu kosongkan', function () {
    readyToOpen($this->election);
    app(ElectionLifecycle::class)->startTrial($this->election);

    Livewire::test(Dashboard::class)
        ->callAction('simulate')
        ->assertNotified('10 suara simulasi ditambahkan.');

    expect(Vote::where('is_trial', true)->count())->toBe(10);

    Livewire::test(Dashboard::class)
        ->mountAction('clearTrial')
        ->assertMountedActionModalSee('Kosongkan suara uji coba (10 suara)?')
        ->callMountedAction()
        ->assertNotified('Suara uji coba dikosongkan (10 suara).');

    expect(Vote::count())->toBe(0);
});

it('UX-06 dasbor tanpa TPS menampilkan teks state kosong dari kartu', function () {
    Livewire::test(PollingStationsWidget::class)
        ->assertSee('Belum ada TPS. Tambahkan TPS dan hubungkan IFP.')
        ->assertSee('Kelola TPS');
});

it('dasbor tanpa pemilihan menampilkan ajakan membuat pemilihan', function () {
    $this->election->delete();

    Livewire::test(Dashboard::class)
        ->assertSee('Belum ada pemilihan. Buat pemilihan untuk mulai.')
        ->assertActionVisible('createElection');
});

it('dasbor menampilkan status IFP dan jumlah suara per TPS tanpa perolehan per paslon', function () {
    $station = readyToOpen($this->election);
    $station->update(['name' => 'TPS Aula']);
    app(ElectionLifecycle::class)->open($this->election);
    $this->postJson('/api/kiosk/votes', [
        'candidate_id' => $this->election->candidates()->first()->id,
        'client_uuid' => (string) Str::uuid(),
    ], kioskHeaders(connectDevice($station)))->assertCreated();

    Livewire::test(PollingStationsWidget::class)
        ->assertSee('TPS Aula')
        ->assertSee('Daring');

    Livewire::test(ElectionStatsWidget::class)
        ->assertSee('Total suara')
        ->assertSee('1 dari 1')
        ->assertDontSee('Paslon 01');
});
