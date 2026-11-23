<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Resources\Candidates\CandidateResource;
use App\Filament\Resources\Candidates\Tables\CandidatesTable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCandidate extends EditRecord
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CandidatesTable::deleteAction(DeleteAction::make()),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Simpan paslon');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return "Paslon {$this->record->number_label} tersimpan.";
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()->danger()->title('Paslon gagal disimpan. Periksa isian yang ditandai merah.')->send();
    }
}
