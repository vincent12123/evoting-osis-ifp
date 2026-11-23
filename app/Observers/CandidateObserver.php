<?php

namespace App\Observers;

use App\Enums\ElectionStatus;
use App\Exceptions\CandidatesLockedException;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use RuntimeException;

/**
 * Pertahanan terakhir di level model: kunci REQ-19 dan batas 6 paslon (REQ-03)
 * tetap berlaku walau ada jalur yang melewati UI. Juga mencatat log & menaikkan settings_version.
 */
class CandidateObserver
{
    public const MAX_CANDIDATES = 6;

    public function creating(Candidate $candidate): void
    {
        $this->guardLock($candidate);

        if (Candidate::where('election_id', $candidate->election_id)->count() >= self::MAX_CANDIDATES) {
            throw new RuntimeException('Maksimal 6 paslon.');
        }
    }

    public function updating(Candidate $candidate): void
    {
        $this->guardLock($candidate);
    }

    public function deleting(Candidate $candidate): void
    {
        $this->guardLock($candidate);
    }

    public function created(Candidate $candidate): void
    {
        $this->bumpVersion($candidate);
        ActivityLog::record('candidate.created', $this->user(), $candidate, [
            'new' => $candidate->only(['number', 'chair_name', 'vice_name']),
        ]);
    }

    public function updated(Candidate $candidate): void
    {
        $changes = array_diff_key($candidate->getChanges(), ['updated_at' => true]);

        if ($changes === []) {
            return;
        }

        $this->bumpVersion($candidate);
        ActivityLog::record('candidate.updated', $this->user(), $candidate, [
            'number' => $candidate->number,
            'old' => array_intersect_key($candidate->getRawOriginal(), $changes),
            'new' => $changes,
        ]);
    }

    public function deleted(Candidate $candidate): void
    {
        $this->bumpVersion($candidate);
        ActivityLog::record('candidate.deleted', $this->user(), $candidate, [
            'old' => $candidate->only(['number', 'chair_name', 'vice_name']),
        ]);
    }

    private function guardLock(Candidate $candidate): void
    {
        $raw = Election::query()->whereKey($candidate->election_id)->value('status');
        $status = $raw instanceof ElectionStatus ? $raw : ($raw === null ? null : ElectionStatus::from($raw));

        if ($status?->locksCandidates()) {
            throw CandidatesLockedException::for($status);
        }
    }

    private function bumpVersion(Candidate $candidate): void
    {
        Election::query()->whereKey($candidate->election_id)->increment('settings_version');
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
