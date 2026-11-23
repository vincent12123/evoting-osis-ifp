<?php

namespace App\Filament\Resources\Candidates\Tables;

use App\Models\Candidate;
use App\Support\CandidatePhoto;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('number')
            ->columns([
                TextColumn::make('number')
                    ->label('No.')
                    ->formatStateUsing(fn (int $state): string => str_pad((string) $state, 2, '0', STR_PAD_LEFT))
                    ->weight('bold'),
                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->disk(CandidatePhoto::DISK)
                    ->imageHeight(48),
                TextColumn::make('chair_name')
                    ->label('Ketua & wakil')
                    ->formatStateUsing(fn (Candidate $record): string => "{$record->chair_name} & {$record->vice_name}"),
                TextColumn::make('tagline')->label('Slogan')->limit(50),
            ])
            ->recordActions([
                ViewAction::make()->hidden(fn (Candidate $record): bool => auth()->user()->can('update', $record)),
                EditAction::make(),
                self::deleteAction(DeleteAction::make()),
            ])
            ->emptyStateHeading('Belum ada paslon. Tambahkan minimal 2 paslon.')
            ->emptyStateDescription(null)
            ->emptyStateActions([
                CreateAction::make()->label('Tambah paslon'),
            ]);
    }

    /** Dialog hapus menyebut nomor & nama (pola konfirmasi ux-flow §6). */
    public static function deleteAction(DeleteAction $action): DeleteAction
    {
        return $action
            ->modalHeading(fn (Candidate $record): string => "Hapus Paslon {$record->number_label} ({$record->chair_name} & {$record->vice_name})?")
            ->modalSubmitActionLabel('Hapus')
            ->successNotificationTitle(fn (Candidate $record): string => "Paslon {$record->number_label} dihapus.");
    }
}
