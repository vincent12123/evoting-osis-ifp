<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\PollingStation;
use App\Models\User;

/** Mencatat tambah/ubah/hapus TPS. Kolom kode & perangkat dicatat terpisah (device.*). */
class PollingStationObserver
{
    private const LOGGED = ['name', 'location', 'is_active'];

    public function created(PollingStation $station): void
    {
        ActivityLog::record('polling_station.created', $this->user(), $station, ['new' => $station->only(['name', 'location'])]);
    }

    public function updated(PollingStation $station): void
    {
        $changes = array_intersect_key($station->getChanges(), array_flip(self::LOGGED));

        if ($changes !== []) {
            ActivityLog::record('polling_station.updated', $this->user(), $station, [
                'old' => array_intersect_key($station->getRawOriginal(), $changes),
                'new' => $changes,
            ]);
        }
    }

    public function deleted(PollingStation $station): void
    {
        ActivityLog::record('polling_station.deleted', $this->user(), $station, ['old' => $station->only(['name'])]);
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
