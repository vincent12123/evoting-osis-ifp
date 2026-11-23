<?php

namespace App\Actions;

use App\Exceptions\KioskException;
use App\Models\ActivityLog;
use App\Models\PollingStation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * REQ-05: tukar kode aktivasi dengan token perangkat.
 * Server hanya menyimpan hash SHA-256; token lama TPS itu otomatis tidak berlaku.
 */
class ActivateDevice
{
    public const TOKEN_LENGTH = 48;

    /** @return array{0: PollingStation, 1: string} [TPS, token mentah untuk IFP] */
    public function handle(string $code): array
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            throw KioskException::invalidCode();
        }

        return DB::transaction(function () use ($code) {
            $station = PollingStation::query()
                ->where('activation_code', $code)
                ->where('activation_code_expires_at', '>', now())
                ->first();

            if ($station === null) {
                throw KioskException::invalidCode();
            }

            $token = Str::random(self::TOKEN_LENGTH);

            $station->forceFill([
                'activation_code' => null,
                'activation_code_expires_at' => null,
                'device_token_hash' => self::hash($token),
                'device_activated_at' => now(),
                'device_last_seen_at' => now(),
            ])->save();

            ActivityLog::record('device.activated', null, $station, ['new' => ['tps' => $station->name]]);

            return [$station, $token];
        });
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
