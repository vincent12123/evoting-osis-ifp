<?php

use App\Console\KioskFixture;
use Illuminate\Support\Facades\Artisan;

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
