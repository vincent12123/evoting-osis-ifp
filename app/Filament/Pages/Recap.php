<?php

namespace App\Filament\Pages;

use App\Enums\ElectionStatus;
use App\Models\Election;
use App\Support\ElectionRecap;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

/** Kartu SCR-A6 — Rekap Hasil. */
class Recap extends Page
{
    protected string $view = 'filament.pages.recap';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Rekap Hasil';

    protected static ?string $title = 'Rekap Hasil';

    protected static ?string $slug = 'recap';

    protected static ?int $navigationSort = 4;

    /** 'official' | 'trial' — tab "Uji coba" hanya ada saat status Uji coba. */
    #[Url]
    public string $mode = 'official';

    public function getSubheading(): ?string
    {
        return Election::active()?->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Cetak berita acara')
                ->icon('heroicon-o-printer')
                ->url(route('berita-acara'))
                ->openUrlInNewTab()
                ->visible(fn (): bool => Election::active() !== null),
        ];
    }

    protected function getViewData(): array
    {
        $election = Election::active();
        $hasTrialTab = $election?->status === ElectionStatus::Trial;
        $trial = $hasTrialTab && $this->mode === 'trial';

        return [
            'election' => $election,
            'recap' => $election ? new ElectionRecap($election, $trial) : null,
            'hasTrialTab' => $hasTrialTab,
            'trial' => $trial,
            'provisional' => $election !== null && $election->status !== ElectionStatus::Closed,
        ];
    }
}
