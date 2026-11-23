<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Resources\Candidates\CandidateResource;
use App\Models\Election;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCandidate extends CreateRecord
{
    protected static string $resource = CandidateResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Tambah paslon';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['election_id'] = Election::active()?->id;

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Simpan paslon');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "Paslon {$this->record->number_label} tersimpan.";
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()->danger()->title('Paslon gagal disimpan. Periksa isian yang ditandai merah.')->send();
    }
}
