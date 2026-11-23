<?php

namespace App\Filament\Resources\PollingStations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PollingStationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama TPS')
                    ->placeholder('TPS Aula')
                    ->helperText('Tampil di lencana layar IFP.')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Nama TPS sudah dipakai.']),
                TextInput::make('location')
                    ->label('Lokasi')
                    ->placeholder('Aula lantai 1')
                    ->maxLength(100),
            ])
            ->columns(1);
    }
}
