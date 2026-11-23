<?php

namespace App\Filament\Resources\PollingStations\Tables;

use App\Actions\GenerateActivationCode;
use App\Actions\ReleaseDevice;
use App\Models\Election;
use App\Models\PollingStation;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

/** Kartu SCR-A4. */
class PollingStationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('TPS')
                    ->description(fn (PollingStation $record): ?string => $record->location)
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('is_active')
                    ->label('Status TPS')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
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
                    ->label('Suara masuk')
                    ->state(fn (PollingStation $record): int => $record->voteCountFor(Election::active()))
                    ->numeric(locale: 'id'),
            ])
            ->recordActions([
                self::generateCodeAction(),
                ActionGroup::make([
                    EditAction::make()
                        ->label('Ubah')
                        ->successNotificationTitle(fn (PollingStation $record): string => "TPS {$record->name} tersimpan."),
                    self::releaseAction(),
                    self::toggleActiveAction(),
                    self::deleteAction(),
                ]),
            ])
            ->emptyStateHeading('Belum ada TPS. Tambahkan TPS untuk setiap IFP yang dipakai.')
            ->emptyStateDescription(null)
            ->emptyStateActions([
                self::createAction(CreateAction::make()),
            ]);
    }

    public static function createAction(CreateAction $action): CreateAction
    {
        return $action
            ->label('Tambah TPS')
            ->createAnother(false)
            ->successNotificationTitle(fn (PollingStation $record): string => "TPS {$record->name} tersimpan.");
    }

    public static function generateCodeAction(): Action
    {
        return Action::make('generateCode')
            ->label('Buat kode aktivasi')
            ->icon('heroicon-o-key')
            ->modalHeading(fn (PollingStation $record): string => "Hubungkan IFP ke {$record->name}")
            ->mountUsing(fn (PollingStation $record) => app(GenerateActivationCode::class)->handle($record))
            ->modalContent(fn (PollingStation $record): View => view('filament.activation-code-modal', [
                'station' => $record,
                'issuedAt' => now()->toDateTimeString(),
            ]))
            ->modalSubmitActionLabel('Buat kode baru')
            ->modalCancelActionLabel('Tutup')
            ->action(function (PollingStation $record, Action $action): void {
                app(GenerateActivationCode::class)->handle($record);
                $action->halt();
            });
    }

    public static function releaseAction(): Action
    {
        return Action::make('release')
            ->label('Lepas IFP')
            ->icon('heroicon-o-link-slash')
            ->color('danger')
            ->visible(fn (PollingStation $record): bool => $record->hasDevice())
            ->requiresConfirmation()
            ->modalHeading(fn (PollingStation $record): string => "Lepas IFP dari {$record->name}?")
            ->modalDescription('IFP itu akan kembali ke layar aktivasi.')
            ->modalSubmitActionLabel('Lepas IFP')
            ->action(function (PollingStation $record): void {
                app(ReleaseDevice::class)->handle($record, auth()->user());
                Notification::make()->success()->title("IFP dilepas dari {$record->name}.")->send();
            });
    }

    public static function toggleActiveAction(): Action
    {
        return Action::make('toggleActive')
            ->label(fn (PollingStation $record): string => $record->is_active ? 'Nonaktifkan TPS' : 'Aktifkan TPS')
            ->icon(fn (PollingStation $record): string => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
            ->requiresConfirmation()
            ->modalHeading(fn (PollingStation $record): string => $record->is_active
                ? "Nonaktifkan {$record->name}? IFP di TPS ini tidak akan menerima suara."
                : "Aktifkan {$record->name}?")
            ->action(function (PollingStation $record): void {
                $record->update(['is_active' => ! $record->is_active]);
                Notification::make()->success()->title("TPS {$record->name} tersimpan.")->send();
            });
    }

    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->modalHeading(fn (PollingStation $record): string => "Hapus {$record->name}?")
            ->modalSubmitActionLabel('Hapus')
            ->before(function (DeleteAction $action, PollingStation $record): void {
                if ($record->votes()->where('is_trial', false)->exists()) {
                    Notification::make()->danger()->title(PollingStation::DELETE_BLOCKED_MESSAGE)->send();
                    $action->cancel();
                }
            })
            ->successNotificationTitle(fn (PollingStation $record): string => "TPS {$record->name} dihapus.");
    }
}
