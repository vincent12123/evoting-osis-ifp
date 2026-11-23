<?php

namespace App\Filament\Resources\PollingStations;

use App\Filament\Resources\PollingStations\Pages\ListPollingStations;
use App\Filament\Resources\PollingStations\Schemas\PollingStationForm;
use App\Filament\Resources\PollingStations\Tables\PollingStationsTable;
use App\Models\PollingStation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** Kartu SCR-A4 — TPS & IFP. Tambah/ubah lewat dialog. */
class PollingStationResource extends Resource
{
    protected static ?string $model = PollingStation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $modelLabel = 'TPS';

    protected static ?string $pluralModelLabel = 'TPS & IFP';

    protected static ?string $navigationLabel = 'TPS & IFP';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'polling-stations';

    public static function form(Schema $schema): Schema
    {
        return PollingStationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PollingStationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPollingStations::route('/'),
        ];
    }
}
