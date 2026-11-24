<?php

use App\Actions\BackupDatabase;
use App\Console\KioskFixture;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| NFR-05: backup database + foto. Dijadwalkan tiap jam (cron server: `* * * * * php artisan schedule:run`),
| dan dijalankan otomatis sesaat setelah pemungutan ditutup (ElectionLifecycle::close).
*/
Artisan::command('evoting:backup {--reason=manual}', function (BackupDatabase $backup) {
    $path = $backup->handle((string) $this->option('reason'));
    $this->info("Backup tersimpan: {$path}");
})->purpose('Salin database e-voting (VACUUM INTO) + foto ke storage/app/backups');

Schedule::command('evoting:backup --reason=terjadwal')->hourly()->withoutOverlapping();

/*
| Pengatur skenario untuk test browser kiosk (tests/Browser). HANYA aktif saat APP_ENV=browser
| (berkas .env.browser dibuat oleh test runner, memakai database/browser.sqlite) — tidak pernah di produksi.
*/
if (app()->environment('browser')) {
    Artisan::command('kiosk:fixture {action} {value?} {--candidates=3} {--status=open} {--mode=confirm} {--delay=3} {--sound=1} {--confetti=1}',
        function (string $action, ?string $value = null) {
            $this->line(json_encode(app(KioskFixture::class)->run($action, $value, $this->options())));
        })->purpose('Atur skenario test browser kiosk');
}
