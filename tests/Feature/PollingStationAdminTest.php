<?php

// Checkpoint 3 — SPEC REQ-04, REQ-22, REQ-23 · acceptance-criteria.md AC-20, AC-25–AC-27, UX-08, UX-10.

use App\Actions\GenerateActivationCode;
use App\Filament\Resources\PollingStations\Pages\ListPollingStations;
use App\Livewire\ActivationCodeStatus;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\User;
use App\Models\Vote;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

it('menambah TPS lewat dialog dan menolak nama ganda', function () {
    Livewire::test(ListPollingStations::class)
        ->callAction('create', ['name' => 'TPS Aula', 'location' => 'Aula lantai 1'])
        ->assertHasNoFormErrors()
        ->assertNotified('TPS TPS Aula tersimpan.');

    Livewire::test(ListPollingStations::class)
        ->callAction('create', ['name' => 'TPS Aula'])
        ->assertHasFormErrors(['name' => 'Nama TPS sudah dipakai.']);

    expect(PollingStation::count())->toBe(1)
        ->and(ActivityLog::where('action', 'polling_station.created')->count())->toBe(1);
});

it('AC-20 "Buat kode aktivasi" menghasilkan 6 angka yang berlaku 30 menit', function () {
    Carbon::setTestNow(Carbon::parse('2026-12-01 01:00:00'));
    $station = PollingStation::factory()->create();

    Livewire::test(ListPollingStations::class)
        ->mountAction(TestAction::make('generateCode')->table($station))
        ->assertMountedActionModalSee('Berlaku sampai 08.30 WIB (30 menit)');

    $station->refresh();
    expect($station->activation_code)->toMatch('/^\d{6}$/')
        ->and($station->activation_code_expires_at->equalTo(now()->addMinutes(30)))->toBeTrue();
});

it('AC-20 "Buat kode baru" mengganti kode lama', function () {
    $station = PollingStation::factory()->create();

    $component = Livewire::test(ListPollingStations::class)
        ->mountAction(TestAction::make('generateCode')->table($station));
    $first = $station->fresh()->activation_code;

    $component->callMountedAction();
    $second = $station->fresh()->activation_code;

    expect($second)->toMatch('/^\d{6}$/')->not->toBe($first);
});

it('UX-08 dialog kode berubah menjadi "IFP terhubung ke {TPS}." setelah kode dipakai', function () {
    $station = PollingStation::factory()->create(['name' => 'TPS Lab']);
    $issuedAt = now()->subSecond()->toDateTimeString();
    app(GenerateActivationCode::class)->handle($station);

    $component = Livewire::test(ActivationCodeStatus::class, ['stationId' => $station->id, 'issuedAt' => $issuedAt])
        ->assertSee('Kode aktivasi untuk')
        ->assertDontSee('IFP terhubung ke TPS Lab.');

    $this->postJson('/api/kiosk/activate', ['code' => $station->fresh()->activation_code])->assertCreated();

    $component->call('$refresh')->assertSee('IFP terhubung ke TPS Lab.');
});

it('AC-25 "Lepas IFP" menghapus token, mencatat log, dan memakai dialog bernama', function () {
    $station = PollingStation::factory()->create(['name' => 'TPS Aula']);
    $token = connectDevice($station);

    Livewire::test(ListPollingStations::class)
        ->mountAction(TestAction::make('release')->table($station))
        ->assertMountedActionModalSee('Lepas IFP dari TPS Aula?')
        ->assertMountedActionModalSee('IFP itu akan kembali ke layar aktivasi.')
        ->callMountedAction()
        ->assertNotified('IFP dilepas dari TPS Aula.');

    expect($station->fresh()->device_token_hash)->toBeNull();
    $this->getJson('/api/kiosk/status', kioskHeaders($token))->assertUnauthorized();

    $log = ActivityLog::where('action', 'device.released')->sole();
    expect($log->user_id)->toBe($this->admin->id);
});

it('AC-25 "Lepas IFP" tidak tampil untuk TPS tanpa IFP', function () {
    $station = PollingStation::factory()->create();

    Livewire::test(ListPollingStations::class)
        ->assertActionHidden(TestAction::make('release')->table($station));
});

it('AC-26 IFP terlihat 20 detik lalu = Daring, 40 detik lalu = Luring, tanpa IFP = Belum terhubung', function () {
    $online = PollingStation::factory()->create(['name' => 'TPS A']);
    $offline = PollingStation::factory()->create(['name' => 'TPS B']);
    $none = PollingStation::factory()->create(['name' => 'TPS C']);
    connectDevice($online);
    connectDevice($offline);
    $online->forceFill(['device_last_seen_at' => now()->subSeconds(20)])->saveQuietly();
    $offline->forceFill(['device_last_seen_at' => now()->subSeconds(40)])->saveQuietly();

    expect($online->fresh()->deviceStatus())->toBe('online')
        ->and($offline->fresh()->deviceStatus())->toBe('offline')
        ->and($none->fresh()->deviceStatus())->toBe('none');

    Livewire::test(ListPollingStations::class)
        ->assertSee('Daring')
        ->assertSee('Luring')
        ->assertSee('Belum terhubung');
});

it('AC-27 TPS yang punya suara sah tidak bisa dihapus', function () {
    $election = Election::factory()->active()->create();
    $candidate = Candidate::factory()->for($election)->number(1)->create();
    $election->update(['status' => 'open']);
    $station = PollingStation::factory()->create();
    Vote::create(['election_id' => $election->id, 'candidate_id' => $candidate->id, 'polling_station_id' => $station->id, 'client_uuid' => (string) Str::uuid()]);

    Livewire::test(ListPollingStations::class)
        ->callAction(TestAction::make('delete')->table($station))
        ->assertNotified('TPS yang sudah punya suara tidak bisa dihapus. Nonaktifkan saja.');

    expect(PollingStation::whereKey($station->id)->exists())->toBeTrue();
    expect(fn () => $station->delete())->toThrow(LogicException::class, 'TPS yang sudah punya suara tidak bisa dihapus.');
});

it('AC-27 TPS tanpa suara bisa dihapus dengan dialog bernama', function () {
    $station = PollingStation::factory()->create(['name' => 'TPS Cadangan']);

    Livewire::test(ListPollingStations::class)
        ->mountAction(TestAction::make('delete')->table($station))
        ->assertMountedActionModalSee('Hapus TPS Cadangan?')
        ->callMountedAction()
        ->assertNotified('TPS TPS Cadangan dihapus.');

    expect(PollingStation::count())->toBe(0);
});

it('menonaktifkan TPS tercatat di log dan membuat IFP-nya menolak suara', function () {
    $station = PollingStation::factory()->create(['name' => 'TPS Aula']);

    Livewire::test(ListPollingStations::class)
        ->callAction(TestAction::make('toggleActive')->table($station));

    expect($station->fresh()->is_active)->toBeFalse()
        ->and(ActivityLog::where('action', 'polling_station.updated')->sole()->summary())->toContain('aktif: ya → tidak');
});

it('daftar TPS kosong menampilkan teks state kosong dari kartu', function () {
    Livewire::test(ListPollingStations::class)
        ->assertSee('Belum ada TPS. Tambahkan TPS untuk setiap IFP yang dipakai.');
});
