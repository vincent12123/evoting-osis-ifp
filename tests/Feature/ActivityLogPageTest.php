<?php

// Checkpoint 2 — SCR-A8 · acceptance-criteria.md UX-09, NF-20 (sebagian), NF-03 (sebagian).

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['name' => 'Bu Rina']);
    $this->actingAs($this->admin);
});

it('UX-09 log kosong menampilkan teks state kosong dari kartu', function () {
    Livewire::test(ListActivityLogs::class)
        ->assertSee('Belum ada aktivitas tercatat.');
});

it('menampilkan admin, label aksi, keterangan, dan waktu dalam WIB', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-23 01:30:00', 'UTC')); // = 08.30 WIB
    ActivityLog::record('election.updated', $this->admin, null, [
        'old' => ['touch_mode' => 'confirm'],
        'new' => ['touch_mode' => 'instant'],
    ]);

    Livewire::test(ListActivityLogs::class)
        ->assertSee('Bu Rina')
        ->assertSee('Ubah pengaturan pemilihan')
        ->assertSee('mode sentuh: confirm → instant')
        ->assertSee('23 Nov 2026, 08.30.00');
});

it('log tidak bisa dibuat, diubah, atau dihapus dari panel', function () {
    $log = ActivityLog::record('auth.login', $this->admin);

    expect($this->admin->can('create', ActivityLog::class))->toBeFalse()
        ->and($this->admin->can('update', $log))->toBeFalse()
        ->and($this->admin->can('delete', $log))->toBeFalse();
});
