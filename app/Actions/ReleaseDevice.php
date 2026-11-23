<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\PollingStation;
use App\Models\User;

/** REQ-22: lepas IFP dari TPS; IFP itu kembali ke layar aktivasi. */
class ReleaseDevice
{
    public function handle(PollingStation $station, ?User $by = null): void
    {
        // Tulis langsung ke DB agar tetap terhapus walau model di memori basi.
        PollingStation::query()->whereKey($station->getKey())->update([
            'device_token_hash' => null,
            'device_activated_at' => null,
            'device_last_seen_at' => null,
        ]);
        $station->refresh();

        ActivityLog::record('device.released', $by, $station, ['old' => ['tps' => $station->name]]);
    }
}
