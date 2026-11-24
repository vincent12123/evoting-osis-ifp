<?php

namespace App\Console;

use App\Actions\ActivateDevice;
use App\Actions\ElectionLifecycle;
use App\Actions\GenerateActivationCode;
use App\Actions\ReleaseDevice;
use App\Http\Controllers\BeritaAcaraController;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\Vote;
use App\Support\CandidatePhoto;
use App\Support\ElectionRecap;
use App\Support\LogoImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Skenario untuk test browser kiosk. Dipanggil lewat `php artisan kiosk:fixture` dengan APP_ENV=browser.
 * Menolak berjalan di environment lain sebagai pengaman ganda.
 */
class KioskFixture
{
    private const NAMES = [
        ['Arka Ramadhan', 'Naila Putri'], ['Dimas Anggara', 'Citra Lestari'], ['Rian Pratama', 'Alisa Zahra'],
        ['Bagas Saputra', 'Keisha Amanda'], ['Fajar Nugroho', 'Salsabila Putri'], ['Raka Wijaya', 'Nadia Kusuma'],
    ];

    private const COLORS = [[37, 99, 235], [5, 150, 105], [124, 58, 237], [220, 38, 38], [194, 65, 12], [14, 116, 144]];

    /** @return array<string, mixed> */
    public function run(string $action, ?string $value, array $options): array
    {
        if (! app()->environment('browser')) {
            throw new RuntimeException('kiosk:fixture hanya untuk APP_ENV=browser.');
        }

        return match ($action) {
            'reset' => $this->reset($options),
            'status' => $this->update(['status' => $value]),
            'mode' => $this->update(['touch_mode' => $value]),
            'station-active' => $this->stationActive((bool) (int) $value),
            'code' => ['code' => app(GenerateActivationCode::class)->handle($this->station())],
            'release' => $this->release(),
            'break-photo' => $this->breakPhoto(),
            'logo' => $this->logo(),
            // Uji 4 TPS (NF-22): siapkan, tutup, rekap, cetak berita acara.
            'four-tps' => $this->fourTps($options),
            'close' => $this->close(),
            'recap' => $this->recap(),
            'berita-acara' => $this->beritaAcara((string) $value),
            'votes' => [
                'total' => Vote::where('is_trial', false)->count(),
                'trial' => Vote::where('is_trial', true)->count(),
            ],
            default => throw new RuntimeException("Aksi tidak dikenal: {$action}"),
        };
    }

    private function reset(array $o): array
    {
        Artisan::call('migrate:fresh', ['--force' => true]);

        $election = Election::create([
            'name' => 'Pemilihan Ketua & Wakil Ketua OSIS',
            'school_year' => '2026/2027',
            'touch_mode' => $o['mode'],
            'reset_delay_seconds' => (int) $o['delay'],
            'sound_enabled' => (bool) (int) $o['sound'],
            'confetti_enabled' => (bool) (int) $o['confetti'],
        ]);

        foreach (range(1, (int) $o['candidates']) as $n) {
            [$chair, $vice] = self::NAMES[$n - 1];
            Candidate::create([
                'election_id' => $election->id,
                'number' => $n,
                'accent' => "paslon-{$n}",
                'chair_name' => $chair,
                'vice_name' => $vice,
                'chair_class' => '8'.chr(64 + $n),
                'tagline' => 'Bersama membangun OSIS yang hebat dan peduli',
                'vision' => 'Mewujudkan OSIS sebagai wadah aspirasi seluruh siswa yang aktif, kreatif, dan berakhlak.',
                'missions' => ['Kotak aspirasi tiap bulan.', 'Lomba antarkelas tiap semester.', 'Gerakan sekolah hijau.'],
                'photo_path' => $this->photo(self::COLORS[$n - 1]),
            ]);
        }

        $election->forceFill(['status' => $o['status']])->saveQuietly();

        $station = PollingStation::create(['name' => 'TPS Aula']);
        [, $token] = app(ActivateDevice::class)->handle(app(GenerateActivationCode::class)->handle($station));
        $code = app(GenerateActivationCode::class)->handle(PollingStation::create(['name' => 'TPS Lab']));

        return ['token' => $token, 'code' => $code];
    }

    /** @param array{0:int,1:int,2:int} $rgb */
    private function photo(array $rgb): string
    {
        $img = imagecreatetruecolor(1200, 900);
        imagefill($img, 0, 0, imagecolorallocate($img, ...$rgb));
        $tmp = tempnam(sys_get_temp_dir(), 'kfx').'.png';
        imagepng($img, $tmp);
        imagedestroy($img);

        return app(CandidatePhoto::class)->store(new UploadedFile($tmp, 'foto.png', 'image/png', null, true));
    }

    private function update(array $attributes): array
    {
        $election = Election::active();
        $election->forceFill($attributes)->save();

        return ['ok' => true];
    }

    private function station(): PollingStation
    {
        return PollingStation::where('name', 'TPS Aula')->firstOrFail();
    }

    private function stationActive(bool $active): array
    {
        $this->station()->update(['is_active' => $active]);

        return ['ok' => true];
    }

    private function release(): array
    {
        app(ReleaseDevice::class)->handle($this->station());

        return ['ok' => true];
    }

    /**
     * 4 TPS dengan IFP terhubung, lalu pemungutan dibuka lewat alur sebenarnya (ElectionLifecycle).
     *
     * @return array{tokens: array<string, string>, candidates: list<array{id:int, number:int}>}
     */
    private function fourTps(array $o): array
    {
        $this->reset([...$o, 'status' => 'preparation']);
        PollingStation::query()->delete(); // TPS bawaan reset diganti 4 TPS uji

        $tokens = [];
        foreach (['TPS Aula', 'TPS Lab Komputer', 'TPS Perpustakaan', 'TPS Kelas 9A'] as $name) {
            $station = PollingStation::create(['name' => $name]);
            [, $tokens[$name]] = app(ActivateDevice::class)->handle(app(GenerateActivationCode::class)->handle($station));
        }

        $election = Election::active();
        $election->update([
            'school_name' => 'SMP Negeri 2 Sintang',
            'signing_place' => 'Sintang',
        ]);
        app(ElectionLifecycle::class)->open($election->fresh());

        return [
            'tokens' => $tokens,
            'candidates' => $election->candidates()->get(['id', 'number'])->map(fn ($c) => ['id' => $c->id, 'number' => $c->number])->all(),
        ];
    }

    private function close(): array
    {
        app(ElectionLifecycle::class)->close(Election::active());

        return ['ok' => true];
    }

    /** Rekap resmi dari server + pemeriksaan integritas data. */
    private function recap(): array
    {
        $election = Election::active();
        $recap = new ElectionRecap($election);

        return [
            'status' => $election->status->value,
            'opened_at' => $election->opened_at?->toIso8601String(),
            'closed_at' => $election->closed_at?->toIso8601String(),
            'total' => $recap->total,
            'top' => $recap->topLabel(),
            'candidates' => $recap->candidates->map(fn ($r) => [
                'number' => $r['candidate']->number, 'votes' => $r['votes'], 'percent' => $r['percent'],
            ])->all(),
            'stations' => $recap->stations->map(fn ($s) => [
                'name' => $s['station']->name, 'total' => $s['total'], 'votes' => $s['votes'],
            ])->all(),
            'integrity' => [
                'rows' => Vote::count(),
                'trial_rows' => Vote::where('is_trial', true)->count(),
                'duplicate_uuids' => Vote::query()->select('client_uuid')->groupBy('client_uuid')->havingRaw('COUNT(*) > 1')->get()->count(),
                'votes_columns' => Schema::getColumnListing('votes'),
                'journal_mode' => DB::selectOne('PRAGMA journal_mode')->journal_mode,
            ],
        ];
    }

    private function beritaAcara(string $path): array
    {
        file_put_contents($path, app(BeritaAcaraController::class)()->render());

        return ['ok' => true, 'path' => $path];
    }

    /** Logo PNG transparan (lingkaran merah) seperti yang diunggah admin di Pengaturan. */
    private function logo(): array
    {
        $img = imagecreatetruecolor(600, 600);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagefilledellipse($img, 300, 300, 560, 560, imagecolorallocate($img, 200, 30, 30));
        $tmp = tempnam(sys_get_temp_dir(), 'kfl').'.png';
        imagepng($img, $tmp);
        imagedestroy($img);

        $path = app(LogoImage::class)->store(new UploadedFile($tmp, 'logo.png', 'image/png', null, true));
        Election::active()->update(['logo_path' => $path]);

        return ['ok' => true, 'path' => $path];
    }

    private function breakPhoto(): array
    {
        Candidate::where('number', 1)->update(['photo_path' => 'candidates/tidak-ada.webp']);
        Election::active()->increment('settings_version');

        return ['ok' => true];
    }
}
