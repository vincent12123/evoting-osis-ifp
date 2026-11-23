<?php

namespace App\Filament\Widgets;

use App\Enums\ElectionStatus;
use App\Filament\Resources\PollingStations\PollingStationResource;
use App\Models\Election;
use App\Models\PollingStation;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;

/** Tabel TPS di dasbor (kartu SCR-A2), diperbarui tiap 10 detik. */
class PollingStationsWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $election = Election::active();
        $trial = $election?->status === ElectionStatus::Trial;

        return $table
            ->heading('TPS')
            ->poll('10s')
            ->paginated(false)
            ->query(PollingStation::query()->orderBy('name'))
            ->columns([
                TextColumn::make('name')->label('TPS')->weight('bold'),
                TextColumn::make('device_status')
                    ->label('IFP')
                    ->badge()
                    ->state(fn (PollingStation $record): string => $record->deviceStatusLabel())
                    ->color(fn (PollingStation $record): string => match ($record->deviceStatus()) {
                        'online' => 'success',
                        'offline' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('vote_count')
                    ->label($trial ? 'Suara uji coba' : 'Suara')
                    ->state(fn (PollingStation $record): int => $record->voteCountFor($election))
                    ->numeric(locale: 'id'),
                TextColumn::make('last_vote')
                    ->label('Terakhir')
                    ->state(function (PollingStation $record) use ($election, $trial): string {
                        $last = $election
                            ? $record->votes()->where('election_id', $election->id)->where('is_trial', $trial)->max('created_at')
                            : null;

                        return $last ? Carbon::parse($last)->timezone(config('app.display_timezone'))->format('H.i') : '—';
                    }),
            ])
            ->emptyStateHeading('Belum ada TPS. Tambahkan TPS dan hubungkan IFP.')
            ->emptyStateDescription(null)
            ->emptyStateActions([
                Action::make('manage')->label('Kelola TPS')->url(PollingStationResource::getUrl('index')),
            ]);
    }
}
