<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Resources\Candidates\CandidateResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCandidates extends ListRecords
{
    protected static string $resource = CandidateResource::class;

    public function getSubheading(): ?string
    {
        return CandidateResource::statusNotice();
    }

    protected function getHeaderActions(): array
    {
        return [
            // TSK-05: lihat paslon persis seperti di layar IFP (tanpa mengirim suara).
            Action::make('previewKiosk')
                ->label('Pratinjau layar IFP')
                ->icon('heroicon-o-tv')
                ->color('gray')
                ->url(route('kiosk.preview'))
                ->openUrlInNewTab(),
            CreateAction::make()->label('Tambah paslon'),
        ];
    }
}
