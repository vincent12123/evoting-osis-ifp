<?php

namespace App\Filament\Widgets;

use App\Enums\ElectionStatus;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\Vote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Kartu SCR-A2: total suara, suara terakhir, IFP daring.
 * Sengaja TANPA perolehan per paslon (dasbor bisa terlihat orang lain di ruang panitia).
 */
class ElectionStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '10s';

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $election = Election::active();

        if ($election === null) {
            return [];
        }

        $trial = $election->status === ElectionStatus::Trial;
        $votes = Vote::query()->where('election_id', $election->id)->where('is_trial', $trial);
        $last = (clone $votes)->max('created_at');

        $stations = PollingStation::query()->whereNotNull('device_token_hash')->get();
        $online = $stations->filter(fn (PollingStation $s) => $s->deviceStatus() === 'online')->count();

        return [
            Stat::make($trial ? 'Total suara uji coba' : 'Total suara', number_format((clone $votes)->count(), 0, ',', '.')),
            Stat::make('Suara terakhir', $last
                ? Carbon::parse($last)->timezone(config('app.display_timezone'))->format('H.i').' WIB'
                : 'Belum ada'),
            Stat::make('IFP daring', "{$online} dari {$stations->count()}"),
        ];
    }
}
