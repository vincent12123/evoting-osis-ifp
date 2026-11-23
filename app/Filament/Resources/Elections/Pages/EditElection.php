<?php

namespace App\Filament\Resources\Elections\Pages;

use App\Filament\Resources\Elections\ElectionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

/** Kartu SCR-A5. */
class EditElection extends EditRecord
{
    protected static string $resource = ElectionResource::class;

    protected static ?string $title = 'Pengaturan Pemilihan';

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Simpan pengaturan');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Pengaturan tersimpan. IFP akan memakai pengaturan baru dalam 10 detik.';
    }
}
