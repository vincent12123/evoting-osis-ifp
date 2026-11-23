<?php

namespace App\Filament\Resources\PollingStations\Pages;

use App\Filament\Resources\PollingStations\PollingStationResource;
use App\Filament\Resources\PollingStations\Tables\PollingStationsTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPollingStations extends ListRecords
{
    protected static string $resource = PollingStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PollingStationsTable::createAction(CreateAction::make()),
        ];
    }
}
