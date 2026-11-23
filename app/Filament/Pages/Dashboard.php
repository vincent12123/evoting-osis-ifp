<?php

namespace App\Filament\Pages;

use App\Actions\ElectionLifecycle;
use App\Enums\ElectionStatus;
use App\Exceptions\ElectionTransitionException;
use App\Filament\Resources\Elections\ElectionResource;
use App\Filament\Widgets\ElectionStatsWidget;
use App\Filament\Widgets\PollingStationsWidget;
use App\Models\Election;
use App\Models\Vote;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;

/** Kartu SCR-A2 — Dasbor Pemilihan, halaman utama panitia pada hari-H. */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dasbor';

    public function getHeading(): string
    {
        return $this->election()?->name ?? 'Dasbor';
    }

    public function getSubheading(): ?string
    {
        $election = $this->election();

        if ($election === null) {
            return 'Belum ada pemilihan. Buat pemilihan untuk mulai.';
        }

        $text = 'Status: '.$election->status->getLabel();

        if (in_array($election->status, [ElectionStatus::Preparation, ElectionStatus::Trial], true)) {
            $blockers = app(ElectionLifecycle::class)->openBlockers($election);
            if ($blockers !== []) {
                $text .= ' · '.implode(' ', $blockers);
            }
        }

        return $text;
    }

    public function getWidgets(): array
    {
        return $this->election() ? [ElectionStatsWidget::class, PollingStationsWidget::class] : [];
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createElection')
                ->label('Buat pemilihan')
                ->url(ElectionResource::getUrl('create'))
                ->visible(fn (): bool => $this->election() === null),

            Action::make('startTrial')
                ->label('Mulai uji coba')
                ->color('warning')
                ->outlined()
                ->visible(fn (): bool => $this->status() === ElectionStatus::Preparation)
                ->requiresConfirmation()
                ->modalHeading('Mulai uji coba?')
                ->modalDescription('IFP akan menerima suara uji coba yang tidak masuk rekap resmi.')
                ->modalSubmitActionLabel('Ya, mulai uji coba')
                ->action(fn () => $this->run(fn (Election $e) => app(ElectionLifecycle::class)->startTrial($e, auth()->user()), 'Uji coba dimulai.')),

            Action::make('clearTrial')
                ->label('Kosongkan suara uji coba')
                ->color('gray')
                ->outlined()
                ->visible(fn (): bool => $this->status() === ElectionStatus::Trial)
                ->requiresConfirmation()
                ->modalHeading(fn (): string => 'Kosongkan suara uji coba ('.$this->trialCount().' suara)?')
                ->modalSubmitActionLabel('Kosongkan')
                ->action(function (): void {
                    $this->run(function (Election $e) {
                        $n = app(ElectionLifecycle::class)->clearTrialVotes($e, auth()->user());

                        return "Suara uji coba dikosongkan ({$n} suara).";
                    });
                }),

            Action::make('simulate')
                ->label('Tambah 10 suara simulasi')
                ->color('gray')
                ->outlined()
                ->visible(fn (): bool => $this->status() === ElectionStatus::Trial)
                ->action(fn () => $this->run(fn (Election $e) => app(ElectionLifecycle::class)->addSimulatedVotes($e, 10, auth()->user()), '10 suara simulasi ditambahkan.')),

            Action::make('open')
                ->label('Buka pemungutan')
                ->color('success')
                ->visible(fn (): bool => in_array($this->status(), [ElectionStatus::Preparation, ElectionStatus::Trial], true))
                ->disabled(fn (): bool => $this->openBlockers() !== [])
                ->tooltip(fn (): ?string => $this->openBlockers()[0] ?? null)
                ->requiresConfirmation()
                ->modalHeading(fn (): string => 'Buka pemungutan suara "'.$this->election()?->name.'"?')
                ->modalDescription(fn (): ?string => ($n = $this->trialCount()) > 0 ? "Suara uji coba ({$n}) akan dikosongkan." : null)
                ->modalSubmitActionLabel('Ya, buka')
                ->action(fn () => $this->run(fn (Election $e) => app(ElectionLifecycle::class)->open($e, auth()->user()), 'Pemungutan suara dibuka.')),

            Action::make('close')
                ->label('Tutup pemungutan')
                ->color('danger')
                ->visible(fn (): bool => $this->status() === ElectionStatus::Open)
                ->requiresConfirmation()
                ->modalHeading('Tutup pemungutan?')
                ->modalDescription('Setelah ditutup, IFP tidak menerima suara lagi.')
                ->modalSubmitActionLabel('Ya, tutup')
                ->action(function (): void {
                    // TSK-04: setelah ditutup, langsung ke Rekap Hasil (SCR-A6).
                    if ($this->run(fn (Election $e) => app(ElectionLifecycle::class)->close($e, auth()->user()), 'Pemungutan suara ditutup.')) {
                        $this->redirect(Recap::getUrl());
                    }
                }),

            Action::make('recap')
                ->label('Lihat rekap')
                ->url(fn (): string => Recap::getUrl())
                ->visible(fn (): bool => $this->status() === ElectionStatus::Closed),
        ];
    }

    /**
     * Jalankan aksi status dan tampilkan hasil/penolakannya sebagai notifikasi.
     *
     * @param  callable(Election): (string|mixed)  $callback  boleh mengembalikan teks notifikasi
     */
    private function run(callable $callback, ?string $successTitle = null): bool
    {
        $election = $this->election();

        if ($election === null) {
            return false;
        }

        try {
            $result = $callback($election);
        } catch (ElectionTransitionException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return false;
        }

        Notification::make()->success()->title(is_string($result) ? $result : $successTitle)->send();

        return true;
    }

    private function election(): ?Election
    {
        return Election::active();
    }

    private function status(): ?ElectionStatus
    {
        return $this->election()?->status;
    }

    /** @return list<string> */
    private function openBlockers(): array
    {
        $election = $this->election();

        return $election ? app(ElectionLifecycle::class)->openBlockers($election) : [];
    }

    private function trialCount(): int
    {
        $election = $this->election();

        return $election ? Vote::query()->where('election_id', $election->id)->where('is_trial', true)->count() : 0;
    }
}
