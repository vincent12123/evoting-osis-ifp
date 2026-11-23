<?php

// Checkpoint 2 — SPEC REQ-02, REQ-08, REQ-17, REQ-23 · acceptance-criteria.md AC-10–AC-12, AC-19.

use App\Enums\ElectionStatus;
use App\Enums\TouchMode;
use App\Filament\Resources\Elections\Pages\CreateElection;
use App\Filament\Resources\Elections\Pages\EditElection;
use App\Filament\Resources\Elections\Pages\ListElections;
use App\Models\ActivityLog;
use App\Models\Election;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

it('AC-10 pemilihan baru berstatus Persiapan, 2x sentuh, jeda 3 detik', function () {
    Livewire::test(CreateElection::class)
        ->fillForm(['name' => 'Pemilihan Ketua & Wakil OSIS', 'school_year' => '2026/2027'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Pemilihan dibuat.');

    $election = Election::sole();
    expect($election->status)->toBe(ElectionStatus::Preparation)
        ->and($election->touch_mode)->toBe(TouchMode::Confirm)
        ->and($election->reset_delay_seconds)->toBe(3)
        ->and($election->sound_enabled)->toBeTrue()
        ->and($election->confetti_enabled)->toBeTrue();

    expect(ActivityLog::where('action', 'election.created')->where('user_id', $this->admin->id)->exists())->toBeTrue();
});

it('AC-11 mengaktifkan pemilihan B membuat hanya B yang aktif', function () {
    $a = Election::factory()->create(['name' => 'Pemilihan 2025/2026', 'school_year' => '2025/2026']);
    $b = Election::factory()->create(['name' => 'Pemilihan 2026/2027']);
    expect($a->fresh()->is_active)->toBeTrue()->and($b->fresh()->is_active)->toBeFalse();

    Livewire::test(ListElections::class)
        ->assertActionHidden(TestAction::make('activate')->table($a))
        ->callAction(TestAction::make('activate')->table($b))
        ->assertNotified('Pemilihan 2026/2027 sekarang pemilihan aktif.');

    expect($a->fresh()->is_active)->toBeFalse()
        ->and($b->fresh()->is_active)->toBeTrue()
        ->and(Election::where('is_active', true)->count())->toBe(1);

    $log = ActivityLog::where('action', 'election.activated')->sole();
    expect($log->properties['new']['active_election'])->toBe('Pemilihan 2026/2027');
});

it('AC-12 tahun ajaran yang salah format ditolak dengan pesan dari kartu', function (string $value) {
    Livewire::test(CreateElection::class)
        ->fillForm(['name' => 'Pemilihan', 'school_year' => $value])
        ->call('create')
        ->assertHasFormErrors(['school_year' => 'Tahun ajaran harus berformat 2026/2027.']);

    expect(Election::count())->toBe(0);
})->with(['2026-2027', '2026/2028', '26/27', 'dua ribu']);

it('AC-12 nama pemilihan kosong ditolak dengan pesan Bahasa Indonesia', function () {
    Livewire::test(CreateElection::class)
        ->fillForm(['name' => '', 'school_year' => '2026/2027'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'Nama pemilihan wajib diisi.']);
});

it('AC-19 mengubah pengaturan IFP menaikkan settings_version tepat 1 dan tercatat di log', function () {
    $election = Election::factory()->create();

    Livewire::test(EditElection::class, ['record' => $election->getRouteKey()])
        ->fillForm([
            'touch_mode' => TouchMode::Instant->value,
            'reset_delay_seconds' => 5,
            'sound_enabled' => false,
            'confetti_enabled' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Pengaturan tersimpan. IFP akan memakai pengaturan baru dalam 10 detik.');

    $election->refresh();
    expect($election->touch_mode)->toBe(TouchMode::Instant)
        ->and($election->reset_delay_seconds)->toBe(5)
        ->and($election->sound_enabled)->toBeFalse()
        ->and($election->confetti_enabled)->toBeFalse()
        ->and($election->settings_version)->toBe(2);

    $log = ActivityLog::where('action', 'election.updated')->sole();
    expect($log->user_id)->toBe($this->admin->id)
        ->and($log->properties['old']['touch_mode'])->toBe('confirm')
        ->and($log->properties['new']['touch_mode'])->toBe('instant')
        ->and($log->summary())->toContain('mode sentuh: confirm → instant');
});

it('AC-19 mengubah tahun ajaran saja tidak menaikkan settings_version', function () {
    $election = Election::factory()->create(['school_year' => '2025/2026']);

    Livewire::test(EditElection::class, ['record' => $election->getRouteKey()])
        ->fillForm(['school_year' => '2026/2027'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($election->fresh()->settings_version)->toBe(1);
});

it('AC-19 mode 1x sentuh menampilkan peringatan dari kartu SCR-A5', function () {
    $election = Election::factory()->create();

    Livewire::test(EditElection::class, ['record' => $election->getRouteKey()])
        ->assertDontSee('sentuhan tidak sengaja langsung menjadi suara')
        ->fillForm(['touch_mode' => TouchMode::Instant->value])
        ->assertSee('Pada mode 1x sentuh, sentuhan tidak sengaja langsung menjadi suara dan tidak bisa dibatalkan.');
});

it('pemilihan tidak bisa dihapus (diarsipkan saja)', function () {
    $election = Election::factory()->create();

    expect($this->admin->can('delete', $election))->toBeFalse();
});
