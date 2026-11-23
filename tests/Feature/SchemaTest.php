<?php

// Checkpoint 1 — skema SPEC §4 terbentuk sesuai aturan yang dijaga database.

use App\Enums\ElectionStatus;
use App\Enums\TouchMode;
use App\Models\Election;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('membuat semua tabel domain SPEC §4', function () {
    foreach (['users', 'elections', 'candidates', 'polling_stations', 'votes', 'activity_logs'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("tabel {$table} belum ada");
    }
});

it('pemilihan baru memakai nilai bawaan SPEC §4.2', function () {
    $election = Election::create(['name' => 'Pemilihan Ketua & Wakil OSIS', 'school_year' => '2026/2027'])->fresh();

    expect($election->status)->toBe(ElectionStatus::Preparation)
        ->and($election->touch_mode)->toBe(TouchMode::Confirm)
        ->and($election->reset_delay_seconds)->toBe(3)
        ->and($election->settings_version)->toBe(1);
});

it('pemilihan pertama otomatis aktif, pemilihan berikutnya tidak', function () {
    $first = Election::create(['name' => 'A', 'school_year' => '2025/2026']);
    $second = Election::create(['name' => 'B', 'school_year' => '2026/2027']);

    expect($first->fresh()->is_active)->toBeTrue()
        ->and($second->fresh()->is_active)->toBeFalse();
});

it('database menolak dua pemilihan aktif sekaligus', function () {
    Election::create(['name' => 'A', 'school_year' => '2025/2026', 'is_active' => true]);
    Election::create(['name' => 'B', 'school_year' => '2026/2027', 'is_active' => true]);
})->throws(QueryException::class);

it('tabel votes tidak punya kolom identitas pemilih maupun updated_at', function () {
    expect(Schema::getColumnListing('votes'))->toEqualCanonicalizing([
        'id', 'election_id', 'candidate_id', 'polling_station_id', 'is_trial', 'client_uuid', 'created_at',
    ]);
});
