<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;

/** REQ-19: paslon terkunci saat Dibuka/Ditutup. Paslon selalu milik pemilihan aktif. */
class CandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Candidate $candidate): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        $election = Election::active();

        return $election !== null && ! $election->status->locksCandidates();
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return ! $candidate->election->status->locksCandidates();
    }

    public function delete(User $user, Candidate $candidate): bool
    {
        return ! $candidate->election->status->locksCandidates();
    }

    /** Tidak ada hapus massal — hapus satu per satu dengan konfirmasi bernama. */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
