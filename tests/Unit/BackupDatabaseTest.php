<?php

// NFR-05 · NF-04 (sebagian): backup otomatis lewat PHP (VACUUM INTO), tanpa alat `sqlite3`.
// Memakai database berkas sementara (bukan :memory: + transaksi RefreshDatabase, karena VACUUM tidak boleh di dalam transaksi).

use App\Actions\BackupDatabase;
use App\Actions\ElectionLifecycle;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\Vote;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->db = tempnam(sys_get_temp_dir(), 'evtdb').'.sqlite';
    touch($this->db);
    config(['database.connections.sqlite.database' => $this->db]);
    DB::purge('sqlite');
    Artisan::call('migrate', ['--force' => true]);

    $this->backupDir = sys_get_temp_dir().'/evt-backup-'.Str::random(8);
    $this->app->instance(BackupDatabase::class, new class($this->backupDir) extends BackupDatabase
    {
        public function __construct(private string $dir) {}

        public function directory(): string
        {
            return $this->dir;
        }
    });
});

afterEach(function () {
    DB::purge('sqlite');
    File::deleteDirectory($this->backupDir);
    foreach ([$this->db, $this->db.'-wal', $this->db.'-shm'] as $f) {
        @unlink($f);
    }
});

function seedVotes(int $n): Election
{
    $election = Election::factory()->active()->create();
    $candidate = Candidate::factory()->for($election)->number(1)->create();
    $station = PollingStation::factory()->create();
    foreach (range(1, $n) as $_) {
        Vote::create(['election_id' => $election->id, 'candidate_id' => $candidate->id, 'polling_station_id' => $station->id, 'client_uuid' => (string) Str::uuid()]);
    }

    return $election;
}

it('perintah evoting:backup membuat salinan SQLite yang utuh dan bisa dibuka', function () {
    seedVotes(7);

    Artisan::call('evoting:backup', ['--reason' => 'uji']);

    $files = File::glob($this->backupDir.'/evoting-*-uji.sqlite');
    expect($files)->toHaveCount(1);

    $copy = new PDO('sqlite:'.$files[0]);
    expect((int) $copy->query('select count(*) from votes')->fetchColumn())->toBe(7)
        ->and($copy->query('PRAGMA integrity_check')->fetchColumn())->toBe('ok');
});

it('backup menyalin folder foto paslon & logo', function () {
    $media = storage_path('app/public/logos');
    File::ensureDirectoryExists($media);
    $name = 'uji-'.Str::random(6).'.webp';
    file_put_contents("{$media}/{$name}", 'x');

    app(BackupDatabase::class)->handle('uji');

    expect(file_exists($this->backupDir."/media/logos/{$name}"))->toBeTrue();
    @unlink("{$media}/{$name}");
});

it('salinan lama dibersihkan, hanya '.BackupDatabase::KEEP.' terbaru disimpan', function () {
    File::ensureDirectoryExists($this->backupDir);
    foreach (range(1, BackupDatabase::KEEP + 5) as $i) {
        touch(sprintf('%s/evoting-20260101-%06d-lama.sqlite', $this->backupDir, $i));
    }

    app(BackupDatabase::class)->handle('baru');

    $files = File::glob($this->backupDir.'/evoting-*.sqlite');
    expect($files)->toHaveCount(BackupDatabase::KEEP)
        ->and(collect($files)->contains(fn ($f) => str_contains($f, '-baru.sqlite')))->toBeTrue()
        ->and(file_exists($this->backupDir.'/evoting-20260101-000001-lama.sqlite'))->toBeFalse();
});

it('menutup pemungutan otomatis membuat backup dan mencatatnya di log', function () {
    $election = seedVotes(3);
    $election->forceFill(['status' => 'open'])->saveQuietly();

    app(ElectionLifecycle::class)->close($election->fresh());

    expect(File::glob($this->backupDir.'/evoting-*-setelah-ditutup.sqlite'))->toHaveCount(1)
        ->and(ActivityLog::where('action', 'election.backup')->exists())->toBeTrue();
});

it('backup terjadwal tiap jam terdaftar di scheduler', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toContain('evoting:backup')->toContain('0 * * * *');
});
