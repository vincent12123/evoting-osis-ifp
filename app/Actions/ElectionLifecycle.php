<?php

namespace App\Actions;

use App\Enums\ElectionStatus;
use App\Exceptions\ElectionTransitionException;
use App\Models\ActivityLog;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Perpindahan status pemilihan (SPEC §5.2):
 * Persiapan → Uji coba → Dibuka → Ditutup (final). Persiapan → Dibuka juga boleh.
 */
class ElectionLifecycle
{
    public function startTrial(Election $election, ?User $by = null): void
    {
        $this->expect($election, [ElectionStatus::Preparation]);
        $this->setStatus($election, ElectionStatus::Trial, $by);
    }

    /** @return int jumlah suara uji coba yang dikosongkan */
    public function open(Election $election, ?User $by = null): int
    {
        $this->expect($election, [ElectionStatus::Preparation, ElectionStatus::Trial]);

        if ($blockers = $this->openBlockers($election)) {
            throw new ElectionTransitionException($blockers[0]);
        }

        return DB::transaction(function () use ($election, $by) {
            $cleared = $this->deleteTrialVotes($election, $by);
            $election->opened_at ??= now();
            $this->setStatus($election, ElectionStatus::Open, $by);

            return $cleared;
        });
    }

    public function close(Election $election, ?User $by = null): void
    {
        $this->expect($election, [ElectionStatus::Open]);
        $election->closed_at = now();
        $this->setStatus($election, ElectionStatus::Closed, $by);
    }

    /** @return int jumlah suara uji coba yang dikosongkan */
    public function clearTrialVotes(Election $election, ?User $by = null): int
    {
        $this->expect($election, [ElectionStatus::Trial]);

        return DB::transaction(fn () => $this->deleteTrialVotes($election, $by));
    }

    /** Pengganti tombol "Tambah 10 suara simulasi" di prototipe — hanya saat Uji coba (REQ-20). */
    public function addSimulatedVotes(Election $election, int $count = 10, ?User $by = null): void
    {
        $this->expect($election, [ElectionStatus::Trial]);

        $candidateIds = $election->candidates()->pluck('id');
        $stationIds = PollingStation::query()->pluck('id');

        if ($candidateIds->isEmpty() || $stationIds->isEmpty()) {
            throw new ElectionTransitionException('Suara simulasi butuh minimal 1 paslon dan 1 TPS.');
        }

        DB::transaction(function () use ($election, $count, $candidateIds, $stationIds) {
            foreach (range(1, $count) as $_) {
                Vote::create([
                    'election_id' => $election->id,
                    'candidate_id' => $candidateIds->random(),
                    'polling_station_id' => $stationIds->random(),
                    'is_trial' => true,
                    'client_uuid' => (string) Str::uuid(),
                ]);
            }
        });

        ActivityLog::record('election.simulated_votes', $by, $election, ['new' => ['jumlah' => $count]]);
    }

    /**
     * Alasan pemungutan belum bisa dibuka (kartu SCR-A2). Kosong = boleh dibuka.
     *
     * @return list<string>
     */
    public function openBlockers(Election $election): array
    {
        $blockers = [];

        if ($election->candidates()->whereNotNull('photo_path')->count() < 2) {
            $blockers[] = ElectionTransitionException::NOT_ENOUGH_CANDIDATES;
        }

        if (! PollingStation::query()->where('is_active', true)->whereNotNull('device_token_hash')->exists()) {
            $blockers[] = ElectionTransitionException::NO_DEVICE;
        }

        return $blockers;
    }

    private function deleteTrialVotes(Election $election, ?User $by): int
    {
        // Lewat query builder: penjaga model Vote hanya melarang menghapus suara sah.
        $count = Vote::query()->where('election_id', $election->id)->where('is_trial', true)->delete();

        if ($count > 0) {
            ActivityLog::record('election.trial_cleared', $by, $election, ['old' => ['suara uji coba' => $count]]);
        }

        return $count;
    }

    private function setStatus(Election $election, ElectionStatus $status, ?User $by): void
    {
        $old = $election->status;
        $election->status = $status;
        $election->saveQuietly();

        ActivityLog::record('election.status_changed', $by, $election, [
            'old' => ['status' => $old->getLabel()],
            'new' => ['status' => $status->getLabel()],
        ]);
    }

    /** @param list<ElectionStatus> $allowed */
    private function expect(Election $election, array $allowed): void
    {
        if (! in_array($election->status, $allowed, true)) {
            throw new ElectionTransitionException(
                "Aksi ini tidak bisa dilakukan saat status pemilihan {$election->status->getLabel()}."
            );
        }
    }
}
