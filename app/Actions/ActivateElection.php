<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\Election;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** REQ-02: hanya satu pemilihan aktif. Mengaktifkan satu = menonaktifkan yang lain. */
class ActivateElection
{
    public function handle(Election $election, ?User $by = null): Election
    {
        if ($election->is_active) {
            return $election;
        }

        $previous = Election::active();

        DB::transaction(function () use ($election) {
            // Nonaktifkan dulu agar indeks unik parsial tidak bentrok.
            Election::query()->where('is_active', true)->update(['is_active' => false]);
            $election->forceFill(['is_active' => true])->saveQuietly();
        });

        ActivityLog::record('election.activated', $by, $election, [
            'old' => ['active_election' => $previous?->name],
            'new' => ['active_election' => $election->name],
        ]);

        return $election->refresh();
    }
}
