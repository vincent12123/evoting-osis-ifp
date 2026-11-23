<?php

// SPEC v1.3 REQ-24 · acceptance-criteria.md AC-57, AC-58.

use App\Filament\Resources\Elections\Pages\EditElection;
use App\Models\ActivityLog;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
    $this->election = Election::factory()->active()->create();
});

/** PNG 1000×1000 dengan latar transparan dan lingkaran merah di tengah. */
function transparentPng(): UploadedFile
{
    $img = imagecreatetruecolor(1000, 1000);
    imagesavealpha($img, true);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
    imagefilledellipse($img, 500, 500, 900, 900, imagecolorallocate($img, 200, 30, 30));
    ob_start();
    imagepng($img);
    $png = (string) ob_get_clean();
    imagedestroy($img);

    // Livewire butuh berkas uji buatan UploadedFile::fake().
    return UploadedFile::fake()->createWithContent('logo.png', $png);
}

it('AC-57 logo diunggah di Pengaturan: WebP ≤ 512 px, transparansi tetap, settings_version naik, tercatat di log', function () {
    $versionBefore = $this->election->settings_version;

    Livewire::test(EditElection::class, ['record' => $this->election->getRouteKey()])
        ->fillForm(['logo_path' => transparentPng()])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->election->refresh();
    expect($this->election->logo_path)->toStartWith('logos/')->toEndWith('.webp')
        ->and($this->election->settings_version)->toBe($versionBefore + 1);

    $bytes = Storage::disk('public')->get($this->election->logo_path);
    [$w, $h, $type] = getimagesizefromstring($bytes);
    expect($type)->toBe(IMAGETYPE_WEBP)->and($w)->toBe(512)->and($h)->toBe(512);

    $img = imagecreatefromstring($bytes);
    $corner = imagecolorsforindex($img, imagecolorat($img, 2, 2));
    $center = imagecolorsforindex($img, imagecolorat($img, 256, 256));
    expect($corner['alpha'])->toBe(127)   // sudut tetap transparan
        ->and($center['alpha'])->toBe(0)  // isi logo tidak transparan
        ->and($center['red'])->toBeGreaterThan(150);

    expect(ActivityLog::where('action', 'election.updated')->latest('id')->first()->summary())->toContain('logo diubah');
});

it('AC-57 SVG, PDF, dan gambar > 2 MB ditolak dengan pesan dari kartu', function (UploadedFile $file) {
    Livewire::test(EditElection::class, ['record' => $this->election->getRouteKey()])
        ->fillForm(['logo_path' => $file])
        ->call('save')
        ->assertHasFormErrors(['logo_path' => 'Logo harus PNG, JPG, atau WebP, maksimal 2 MB.']);

    expect($this->election->fresh()->logo_path)->toBeNull();
})->with([
    'SVG' => fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'PDF' => fn () => UploadedFile::fake()->create('logo.pdf', 50, 'application/pdf'),
    'PNG 3 MB' => fn () => UploadedFile::fake()->image('besar.png', 400, 400)->size(3072),
]);

it('AC-57 logo bisa dihapus dari Pengaturan', function () {
    Storage::disk('public')->put('logos/lama.webp', 'x');
    $this->election->update(['logo_path' => 'logos/lama.webp']);

    Livewire::test(EditElection::class, ['record' => $this->election->getRouteKey()])
        ->fillForm(['logo_path' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->election->fresh()->logo_path)->toBeNull();
});

it('AC-58 API status memuat logo_url; tanpa logo bernilai null', function () {
    $token = connectDevice(PollingStation::factory()->create());

    $this->getJson('/api/kiosk/status', kioskHeaders($token))->assertJsonPath('election.logo_url', null);

    $this->election->update(['logo_path' => 'logos/sekolah.webp']);

    $this->getJson('/api/kiosk/status', kioskHeaders($token))
        ->assertJsonPath('election.logo_url', Storage::disk('public')->url('logos/sekolah.webp'));
});

it('AC-58 kop berita acara memuat logo bila ada', function () {
    $this->get(route('berita-acara'))->assertOk()->assertDontSee('alt="Logo sekolah"', false);

    $this->election->update(['logo_path' => 'logos/sekolah.webp']);

    $this->get(route('berita-acara'))
        ->assertSee('alt="Logo sekolah"', false)
        ->assertSee(Storage::disk('public')->url('logos/sekolah.webp'), false);
});
