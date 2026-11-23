<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu (WIB)')
                    ->dateTime('d M Y, H.i.s'),
                TextColumn::make('user.name')
                    ->label('Admin')
                    ->placeholder('IFP / sistem'),
                TextColumn::make('action')
                    ->label('Aksi')
                    ->formatStateUsing(fn (string $state): string => ActivityLog::actionLabel($state)),
                TextColumn::make('properties')
                    ->label('Keterangan')
                    ->state(fn (ActivityLog $record): string => $record->summary())
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Aksi')
                    ->options(ActivityLog::ACTION_LABELS),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateHeading('Belum ada aktivitas tercatat.')
            ->emptyStateDescription(null);
    }
}
