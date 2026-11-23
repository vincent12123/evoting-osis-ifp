<?php

namespace App\Http\Middleware;

use App\Actions\ActivateDevice;
use App\Exceptions\KioskException;
use App\Models\PollingStation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengenali IFP dari token perangkat (Bearer). Tidak memakai login admin (SPEC §6).
 * Memperbarui "terakhir terlihat" untuk status Daring/Luring (REQ-22).
 */
class AuthenticateKioskDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $station = $token
            ? PollingStation::query()->where('device_token_hash', ActivateDevice::hash($token))->first()
            : null;

        if ($station === null) {
            throw KioskException::deviceUnknown();
        }

        $station->forceFill(['device_last_seen_at' => now()])->saveQuietly();
        $request->attributes->set('station', $station);

        return $next($request);
    }
}
