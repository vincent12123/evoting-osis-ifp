<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // NFR-04: database menyimpan UTC, panel admin menampilkan WIB.
        FilamentTimezone::set(config('app.display_timezone'));

        // REQ-23: catat setiap login admin.
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                ActivityLog::record('auth.login', $event->user);
            }
        });
    }
}
