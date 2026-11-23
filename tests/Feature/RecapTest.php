<?php

// Checkpoint 5 — SPEC REQ-15, REQ-16 (v1.1) · acceptance-criteria.md AC-50–AC-54, UX-09.

use App\Actions\ElectionLifecycle;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Recap;
use App\Filament\Resources\Elections\Pages\EditElection;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\User;
use App\Models\Vote;
use App\Support\ElectionRecap;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->election = Election::factory()->active()->create(['name' => 'Pemilihan OSIS 2026/2027']);
    $this->c1 = Candidate::factory()->for($this->election)->number(1)->create(['chair_name' => 'Arka', 'vice_name' => 'Naila']);
    $this->c2 = Candidate::factory()->for($this->election)->number(2)->create(['chair_name' => 'Dimas', 'vice_name' => 'Citra']);
    $this->c3 = Candidate::factory()->for($this->election)->number(3)->create(['chair_name' => 'Rian', 'vice_name' => 'Alisa']);
    $this->aula = PollingStation::factory()->create(['name' => 'TPS Aula']);
    $this->lab = PollingStation::factory()->create(['name' => 'TPS Lab']);
});

function addVotes(Election $e, Candidate $c, PollingStation $s, int $n, bool $trial = false): void
{
    foreach (range(1, $n) as $_) {
        Vote::create(['election_id' => $e->id, 'candidate_id' => $c->id, 'polling_station_id' => $s->id, 'is_trial' => $trial, 'client_uuid' => (string) Str::uuid()]);
    }
}

/** 10 suara sah (01: 5, 02: 3, 03: 2) di 2 TPS + 4 suara uji coba lama. */
function scenarioAc50($t): void
{
    addVotes($t->election, $t->c1, $t->aula, 3);
    addVotes($t->election, $t->c1, $t->lab, 2);
    addVotes($t->election, $t->c2, $t->aula, 3);
    addVotes($t->election, $t->c3, $t->lab, 2);
    addVotes($t->election, $t->c2, $t->lab, 4, trial: true);
}

it('AC-50 rekap menghitung total, persentase, dan tabel TPS × paslon tanpa suara uji coba', function () {
    scenarioAc50($this);

    $recap = new ElectionRecap($this->election);
    expect($recap->total)->toBe(10)
        ->and($recap->candidates->pluck('votes')->all())->toBe([5, 3, 2])
        ->and($recap->candidates->pluck('percent')->all())->toBe([50.0, 30.0, 20.0]);

    $aula = $recap->stations->firstWhere('station.name', 'TPS Aula');
    $lab = $recap->stations->firstWhere('station.name', 'TPS Lab');
    expect($aula['votes'])->toBe([$this->c1->id => 3, $this->c2->id => 3])
        ->and($aula['total'])->toBe(6)
        ->and($lab['votes'])->toBe([$this->c1->id => 2, $this->c3->id => 2])
        ->and($lab['total'])->toBe(4);

    // Cocok dengan isi DB.
    expect($recap->total)->toBe(Vote::where('election_id', $this->election->id)->where('is_trial', false)->count());

    Livewire::test(Recap::class)
        ->assertSee('50,0%')->assertSee('30,0%')->assertSee('20,0%')
        ->assertSee('Terbanyak')
        ->assertSee('Hasil sementara — pemungutan belum ditutup.');
});

it('AC-51 dua paslon teratas seri ditandai "Suara sama", bukan "Terbanyak"', function () {
    addVotes($this->election, $this->c1, $this->aula, 4);
    addVotes($this->election, $this->c2, $this->aula, 4);
    addVotes($this->election, $this->c3, $this->aula, 1);

    $recap = new ElectionRecap($this->election);
    expect($recap->topLabel())->toBe('Suara sama')
        ->and($recap->candidates->where('is_top', true)->count())->toBe(2);

    Livewire::test(Recap::class)->assertSee('Suara sama')->assertDontSee('Terbanyak');
});

it('AC-52 / UX-09 tanpa suara menampilkan "Belum ada suara masuk."', function () {
    Livewire::test(Recap::class)->assertSee('Belum ada suara masuk.');
});

it('saat Uji coba ada tab "Uji coba" terpisah yang tidak bercampur dengan rekap resmi', function () {
    $this->election->update(['status' => 'trial']);
    addVotes($this->election, $this->c1, $this->aula, 3, trial: true);

    Livewire::test(Recap::class)
        ->assertSee('Belum ada suara masuk.')
        ->set('mode', 'trial')
        ->assertSee('UJI COBA — suara ini tidak masuk rekap resmi.')
        ->assertSee('100,0%');
});

it('AC-53 berita acara setelah Ditutup memuat kop, judul, waktu WIB, perolehan, tabel TPS, kolom absen manual, dan tanda tangan', function () {
    $this->election->update([
        'school_name' => 'SMP Negeri 2 Sintang',
        'school_address' => 'Jl. Contoh No. 1, Sintang',
        'signing_place' => 'Sintang',
        'signatories' => [
            ['role' => 'Ketua Panitia', 'name' => 'Budi Santoso'],
            ['role' => 'Pembina OSIS', 'name' => null],
            ['role' => 'Kepala Sekolah', 'name' => 'Dra. Siti Aminah'],
        ],
    ]);
    connectDevice($this->aula);
    Carbon::setTestNow('2026-12-01 00:30:00'); // 07.30 WIB
    app(ElectionLifecycle::class)->open($this->election);
    scenarioAc50($this);
    Carbon::setTestNow('2026-12-01 05:00:00'); // 12.00 WIB
    app(ElectionLifecycle::class)->close($this->election->fresh());

    $this->get(route('berita-acara'))
        ->assertOk()
        ->assertSee('SMP Negeri 2 Sintang')
        ->assertSee('Jl. Contoh No. 1, Sintang')
        ->assertSee('Berita Acara Penghitungan Suara', false)
        ->assertSee('Pemilihan OSIS 2026/2027')
        ->assertSee('pukul 07.30 WIB')
        ->assertSee('pukul 12.00 WIB')
        ->assertSee('Arka &amp; Naila', false)
        ->assertSee('50,0%')
        ->assertSee('Jumlah pemilih menurut catatan pengawas')
        ->assertSee('Sintang, 01 Desember 2026')
        ->assertSee('Ketua Panitia')->assertSee('Budi Santoso')
        ->assertSee('Pembina OSIS')
        ->assertSee('Dra. Siti Aminah')
        ->assertSee('Saksi Paslon 01')->assertSee('Saksi Paslon 02')->assertSee('Saksi Paslon 03')
        ->assertDontSee('SEMENTARA – PEMUNGUTAN BELUM DITUTUP');
});

it('AC-53 kolom saksi bisa dimatikan dan penandatangan bisa diubah di Pengaturan', function () {
    $undo = Repeater::fake();
    $versionBefore = $this->election->fresh()->settings_version;

    Livewire::test(EditElection::class, ['record' => $this->election->getRouteKey()])
        ->fillForm([
            'school_name' => 'SMP Negeri 2 Sintang',
            'signatories' => [['role' => 'Ketua Panitia', 'name' => 'Budi'], ['role' => 'Bendahara OSIS', 'name' => null]],
            'witness_signatures' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();
    $undo();

    $this->election->refresh();
    expect($this->election->signatories)->toHaveCount(2)
        ->and($this->election->witness_signatures)->toBeFalse()
        ->and($this->election->settings_version)->toBe($versionBefore); // bukan pengaturan IFP

    $this->get(route('berita-acara'))
        ->assertSee('Bendahara OSIS')
        ->assertDontSee('Kepala Sekolah')
        ->assertDontSee('Saksi Paslon');
});

it('AC-53 nama penandatangan kosong dicetak sebagai garis isian', function () {
    $this->get(route('berita-acara'))
        ->assertSee('Ketua Panitia')->assertSee('Pembina OSIS')->assertSee('Kepala Sekolah')
        ->assertSee('..............................');
});

it('AC-54 berita acara sebelum Ditutup diberi watermark SEMENTARA', function () {
    $this->election->update(['status' => 'open']);

    $this->get(route('berita-acara'))->assertOk()->assertSee('SEMENTARA – PEMUNGUTAN BELUM DITUTUP');
});

it('berita acara hanya memuat suara resmi', function () {
    scenarioAc50($this);

    $this->get(route('berita-acara'))
        ->assertSee('Hanya memuat suara resmi')
        ->assertSeeInOrder(['Jumlah suara sah', '10']);
});

it('AC-02 halaman rekap dan berita acara butuh login', function () {
    auth()->logout();

    $this->get('/admin/recap')->assertRedirect('/admin/login');
    $this->get('/berita-acara')->assertRedirect('/admin/login');
});

it('setelah ditutup, dasbor menampilkan "Lihat rekap" dan Tutup mengarah ke halaman Rekap', function () {
    connectDevice($this->aula);
    app(ElectionLifecycle::class)->open($this->election);

    Livewire::test(Dashboard::class)
        ->callAction('close')
        ->assertRedirect(Recap::getUrl());

    Livewire::test(Dashboard::class)->assertActionVisible('recap');
});
