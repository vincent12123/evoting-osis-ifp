<?php

namespace App\Actions;

use App\Enums\ElectionStatus;
use App\Exceptions\KioskException;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\Vote;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Inti pemungutan suara (REQ-09, REQ-11, REQ-13, REQ-20, REQ-21).
 * Semua pemeriksaan dan penulisan terjadi dalam satu transaksi IMMEDIATE.
 * Tidak menyimpan identitas pemilih (REQ-18).
 */
class CastVote
{
    public const MIN_INTERVAL_MS = 2000;

    /** @return array{vote: Vote, created: bool} */
    public function handle(PollingStation $station, int $candidateId, string $clientUuid): array
    {
        try {
            return DB::transaction(fn () => $this->record($station, $candidateId, $clientUuid));
        } catch (UniqueConstraintViolationException) {
            // Kiriman ganda bersamaan dengan client_uuid yang sama: kembalikan suara yang sudah ada.
            return ['vote' => Vote::query()->where('client_uuid', $clientUuid)->firstOrFail(), 'created' => false];
        }
    }

    private function record(PollingStation $station, int $candidateId, string $clientUuid): array
    {
        // REQ-11: kiriman ulang dengan UUID yang sama = sukses tanpa suara baru.
        $existing = Vote::query()->where('client_uuid', $clientUuid)->first();
        if ($existing !== null) {
            return ['vote' => $existing, 'created' => false];
        }

        $election = Election::active();
        if ($election === null || ! $election->status->acceptsVotes()) {
            throw KioskException::electionNotOpen();
        }

        $station->refresh();
        if (! $station->is_active) {
            throw KioskException::stationInactive();
        }

        $belongs = Candidate::query()->whereKey($candidateId)->where('election_id', $election->id)->exists();
        if (! $belongs) {
            throw KioskException::invalidCandidate();
        }

        // REQ-21: rem 2 detik per TPS.
        $tooFast = Vote::query()
            ->where('polling_station_id', $station->id)
            ->where('created_at', '>', now()->subMilliseconds(self::MIN_INTERVAL_MS)->format('Y-m-d H:i:s.v'))
            ->exists();
        if ($tooFast) {
            throw KioskException::tooFast();
        }

        $vote = Vote::create([
            'election_id' => $election->id,
            'candidate_id' => $candidateId,
            'polling_station_id' => $station->id,
            'is_trial' => $election->status === ElectionStatus::Trial,
            'client_uuid' => $clientUuid,
        ]);

        return ['vote' => $vote, 'created' => true];
    }
}
