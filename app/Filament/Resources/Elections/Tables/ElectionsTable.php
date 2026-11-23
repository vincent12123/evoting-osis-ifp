<?php

namespace App\Filament\Resources\Elections\Tables;

use App\Actions\ActivateElection;
use App\Models\Election;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ElectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nama pemilihan')->searchable(),
                TextColumn::make('school_year')->label('Tahun ajaran'),
                TextColumn::make('status')->label('Status')->badge(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make()->label('Pengaturan'),
                Action::make('activate')
                    ->label('Jadikan aktif')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Election $record): bool => ! $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Election $record): string => "Jadikan \"{$record->name}\" pemilihan aktif?")
                    ->modalDescription('Pemilihan aktif lainnya akan dinonaktifkan. IFP hanya menampilkan pemilihan aktif.')
                    ->modalSubmitActionLabel('Ya, jadikan aktif')
                    ->action(function (Election $record): void {
                        app(ActivateElection::class)->handle($record, auth()->user());
                        Notification::make()->success()->title("{$record->name} sekarang pemilihan aktif.")->send();
                    }),
            ])
            ->emptyStateHeading('Belum ada pemilihan. Buat pemilihan untuk mulai.')
            ->emptyStateDescription(null)
            ->emptyStateActions([
                CreateAction::make()->label('Buat pemilihan'),
            ]);
    }
}
