<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Resources\Candidates\CandidateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Tampilan hanya-baca, dipakai saat data paslon terkunci (REQ-19). */
class ViewCandidate extends ViewRecord
{
    protected static string $resource = CandidateResource::class;

    public function getSubheading(): ?string
    {
        return CandidateResource::statusNotice();
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
