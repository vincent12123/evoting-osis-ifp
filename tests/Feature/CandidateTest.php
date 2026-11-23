<?php

// Checkpoint 2 — SPEC REQ-03, REQ-19, REQ-23 · acceptance-criteria.md AC-13–AC-18, UX-07, UX-10.

use App\Exceptions\CandidatesLockedException;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Filament\Resources\Candidates\Pages\CreateCandidate;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->undoRepeaterFake = Repeater::fake();
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
    $this->election = Election::factory()->active()->create();
    // Foto bawaan CandidateFactory harus ada agar form edit bisa memuatnya.
    Storage::disk('public')->put('candidates/contoh.webp', 'x');
});

/** State repeater "simple" di form berbentuk [['text' => …]]; yang tersimpan tetap daftar teks biasa. */
function missionsState(array $items): array
{
    return array_map(fn (string $text) => ['text' => $text], $items);
}

function candidateData(array $overrides = []): array
{
    if (isset($overrides['missions'])) {
        $overrides['missions'] = missionsState($overrides['missions']);
    }

    return [
        'number' => 1,
        'accent' => 'paslon-1',
        'chair_name' => 'Arka Ramadhan',
        'vice_name' => 'Naila Putri',
        'tagline' => 'Inovatif, Kolaboratif, dan Berkarakter Juara',
        'vision' => 'Mewujudkan OSIS sebagai wadah aspirasi siswa.',
        'missions' => missionsState(['Kotak aspirasi digital tiap bulan.', 'Pekan kompetisi antarkelas.']),
        'photo_path' => UploadedFile::fake()->image('foto.jpg', 2400, 2000)->size(2000),
        ...$overrides,
    ];
}

it('AC-13 paslon lengkap tersimpan dan fotonya disimpan sebagai WebP 4:3 ≤ 1600px', function () {
    Livewire::test(CreateCandidate::class)
        ->fillForm(candidateData())
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Paslon 01 tersimpan.');

    $candidate = Candidate::sole();
    expect($candidate->election_id)->toBe($this->election->id)
        ->and($candidate->missions)->toBe(['Kotak aspirasi digital tiap bulan.', 'Pekan kompetisi antarkelas.'])
        ->and($candidate->photo_path)->toEndWith('.webp');

    Storage::disk('public')->assertExists($candidate->photo_path);
    $bytes = Storage::disk('public')->get($candidate->photo_path);
    [$width, $height, $type] = getimagesizefromstring($bytes);

    expect($type)->toBe(IMAGETYPE_WEBP)
        ->and($width)->toBe(1600)
        ->and($height)->toBe(1200)
        ->and(strlen($bytes))->toBeLessThanOrEqual(300 * 1024);

    expect(ActivityLog::where('action', 'candidate.created')->where('user_id', $this->admin->id)->exists())->toBeTrue()
        ->and($this->election->fresh()->settings_version)->toBe(2);
});

it('AC-14 nomor urut ganda ditolak dengan pesan dari kartu', function () {
    Candidate::factory()->for($this->election)->number(2)->create();

    Livewire::test(CreateCandidate::class)
        ->fillForm(candidateData(['number' => 2, 'accent' => 'paslon-3']))
        ->call('create')
        ->assertHasFormErrors(['number' => 'Nomor urut 02 sudah dipakai.'])
        ->assertNotified('Paslon gagal disimpan. Periksa isian yang ditandai merah.');

    expect(Candidate::count())->toBe(1);
});

it('AC-14 nomor urut yang sama boleh dipakai di pemilihan lain', function () {
    $old = Election::factory()->create();
    Candidate::factory()->for($old)->number(1)->create();

    Livewire::test(CreateCandidate::class)
        ->fillForm(candidateData())
        ->call('create')
        ->assertHasNoFormErrors();
});

it('AC-14 warna aksen ganda ditolak', function () {
    Candidate::factory()->for($this->election)->number(1)->create();

    Livewire::test(CreateCandidate::class)
        ->fillForm(candidateData(['number' => 2, 'accent' => 'paslon-1']))
        ->call('create')
        ->assertHasFormErrors(['accent' => 'Warna aksen ini sudah dipakai paslon lain.']);
});

it('AC-15 foto bukan gambar atau lebih dari 3 MB ditolak', function (UploadedFile $file) {
    Livewire::test(CreateCandidate::class)
        ->fillForm(candidateData(['photo_path' => $file]))
        ->call('create')
        ->assertHasFormErrors(['photo_path' => 'Foto harus JPG, PNG, atau WebP, maksimal 3 MB.']);

    expect(Candidate::count())->toBe(0);
})->with([
    'PDF' => fn () => UploadedFile::fake()->create('visi.pdf', 100, 'application/pdf'),
    'JPG 4 MB' => fn () => UploadedFile::fake()->image('besar.jpg', 800, 600)->size(4096),
]);

it('AC-16 paslon ke-7 ditolak dengan pesan dari kartu', function () {
    foreach (range(1, 6) as $n) {
        Candidate::factory()->for($this->election)->number($n)->create();
    }

    Livewire::test(CreateCandidate::class)
        ->fillForm(candidateData(['number' => 6, 'accent' => 'paslon-6']))
        ->call('create')
        ->assertHasFormErrors(['number' => 'Maksimal 6 paslon.']);

    expect(Candidate::count())->toBe(6);
});

it('AC-16 batas 6 paslon juga dijaga di level model', function () {
    foreach (range(1, 6) as $n) {
        Candidate::factory()->for($this->election)->number($n)->create();
    }

    Candidate::factory()->for($this->election)->create(['number' => 7, 'accent' => 'paslon-1']);
})->throws(RuntimeException::class, 'Maksimal 6 paslon.');

it('AC-17 misi kosong atau lebih dari 7 butir ditolak', function (array $missions, string $message) {
    Livewire::test(CreateCandidate::class)
        ->fillForm(candidateData(['missions' => $missions]))
        ->call('create')
        ->assertHasFormErrors(['missions' => $message]);

    expect(Candidate::count())->toBe(0);
})->with([
    'kosong' => [[], 'Isi minimal 1 butir misi.'],
    '8 butir' => [array_fill(0, 8, 'Misi'), 'Maksimal 7 butir misi.'],
]);

it('AC-18 paslon terkunci saat pemungutan Dibuka atau Ditutup', function (string $status, string $banner) {
    $candidate = Candidate::factory()->for($this->election)->number(1)->create();
    $this->election->update(['status' => $status]);

    // Lewat URL langsung.
    $this->get(CandidateResource::getUrl('edit', ['record' => $candidate]))->assertForbidden();
    $this->get(CandidateResource::getUrl('create'))->assertForbidden();

    // Lewat tabel: tidak ada tombol ubah/hapus, ada banner kunci.
    Livewire::test(ListCandidates::class)
        ->assertSee($banner)
        ->assertActionHidden(TestAction::make('edit')->table($candidate))
        ->assertActionHidden(TestAction::make('delete')->table($candidate))
        ->assertActionVisible(TestAction::make('view')->table($candidate));

    // Halaman lihat tetap bisa dibuka (hanya-baca).
    $this->get(CandidateResource::getUrl('view', ['record' => $candidate]))->assertOk()->assertSee($banner);

    // Lewat model langsung.
    expect(fn () => $candidate->update(['chair_name' => 'Ganti']))->toThrow(CandidatesLockedException::class, $banner)
        ->and(fn () => $candidate->delete())->toThrow(CandidatesLockedException::class);

    expect($candidate->fresh()->chair_name)->not->toBe('Ganti');
})->with([
    'Dibuka' => ['open', 'Data paslon terkunci karena pemungutan sedang berlangsung.'],
    'Ditutup' => ['closed', 'Data paslon terkunci karena pemungutan sudah ditutup.'],
]);

it('AC-18 paslon masih bisa diubah saat Persiapan dan Uji coba', function (string $status) {
    $candidate = Candidate::factory()->for($this->election)->number(1)->create();
    $this->election->update(['status' => $status]);

    Livewire::test(EditCandidate::class, ['record' => $candidate->getRouteKey()])
        ->fillForm(['chair_name' => 'Nama Baru'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Paslon 01 tersimpan.');

    expect($candidate->fresh()->chair_name)->toBe('Nama Baru');

    $log = ActivityLog::where('action', 'candidate.updated')->sole();
    expect($log->summary())->toContain('Paslon 01')->toContain('Nama Baru');
})->with(['preparation', 'trial']);

it('paslon pemilihan lain tidak bisa dibuka lewat URL', function () {
    $other = Candidate::factory()->for(Election::factory())->number(1)->create();

    $this->get(CandidateResource::getUrl('edit', ['record' => $other]))->assertNotFound();
});

it('UX-07 daftar paslon kosong menampilkan teks state kosong dari kartu', function () {
    Livewire::test(ListCandidates::class)
        ->assertSee('Belum ada paslon. Tambahkan minimal 2 paslon.')
        ->assertSee('Tambah paslon');
});

it('UX-10 hapus paslon memakai dialog yang menyebut nomor dan nama', function () {
    $candidate = Candidate::factory()->for($this->election)->number(1)
        ->create(['chair_name' => 'Arka Ramadhan', 'vice_name' => 'Naila Putri']);

    Livewire::test(ListCandidates::class)
        ->mountAction(TestAction::make('delete')->table($candidate))
        ->assertMountedActionModalSee('Hapus Paslon 01 (Arka Ramadhan & Naila Putri)?')
        ->callMountedAction()
        ->assertNotified('Paslon 01 dihapus.');

    expect(Candidate::count())->toBe(0)
        ->and(ActivityLog::where('action', 'candidate.deleted')->exists())->toBeTrue();
});

afterEach(function () {
    ($this->undoRepeaterFake)();
});
