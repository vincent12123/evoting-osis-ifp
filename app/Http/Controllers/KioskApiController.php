<?php

namespace App\Http\Controllers;

use App\Actions\ActivateDevice;
use App\Actions\CastVote;
use App\Exceptions\KioskException;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

/**
 * API JSON untuk layar IFP (SPEC §5.4, §5.5).
 * Tidak pernah mengirim perolehan per paslon (REQ-14).
 */
class KioskApiController extends Controller
{
    public const MAX_FAILED_ACTIVATIONS = 10;

    public const ACTIVATION_DECAY_SECONDS = 600;

    public function activate(Request $request, ActivateDevice $activate): JsonResponse
    {
        $key = 'kiosk-activation:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_ACTIVATIONS)) {
            throw KioskException::tooManyAttempts();
        }

        try {
            [$station, $token] = $activate->handle((string) $request->input('code', ''));
        } catch (KioskException $e) {
            RateLimiter::hit($key, self::ACTIVATION_DECAY_SECONDS);
            throw $e;
        }

        RateLimiter::clear($key);

        return response()->json([
            'token' => $token,
            'station' => ['name' => $station->name],
        ], 201);
    }

    public function status(Request $request): JsonResponse
    {
        /** @var PollingStation $station */
        $station = $request->attributes->get('station');
        $election = Election::active();

        return response()->json([
            'station' => [
                'name' => $station->name,
                'is_active' => $station->is_active,
                'vote_count' => $station->voteCountFor($election),
            ],
            'election' => $election ? [
                'name' => $election->name,
                'school_year' => $election->school_year,
                'status' => $election->status->value,
                'touch_mode' => $election->touch_mode->value,
                'reset_delay_seconds' => $election->reset_delay_seconds,
                'sound_enabled' => $election->sound_enabled,
                'confetti_enabled' => $election->confetti_enabled,
                'settings_version' => $election->settings_version,
            ] : null,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function candidates(): JsonResponse
    {
        $election = Election::active();

        return response()->json([
            'settings_version' => $election?->settings_version,
            'candidates' => $election
                ? $election->candidates->map(fn (Candidate $c) => [
                    'id' => $c->id,
                    'number' => $c->number,
                    'number_label' => $c->number_label,
                    'chair_name' => $c->chair_name,
                    'chair_class' => $c->chair_class,
                    'vice_name' => $c->vice_name,
                    'vice_class' => $c->vice_class,
                    'tagline' => $c->tagline,
                    'vision' => $c->vision,
                    'missions' => $c->missions,
                    'photo_url' => Storage::disk('public')->url($c->photo_path),
                    'accent' => $c->accent->value,
                ])->values()
                : [],
        ]);
    }

    public function vote(Request $request, CastVote $castVote): JsonResponse
    {
        $data = $request->validate([
            'candidate_id' => ['required', 'integer'],
            'client_uuid' => ['required', 'uuid'],
        ]);

        /** @var PollingStation $station */
        $station = $request->attributes->get('station');

        ['created' => $created] = $castVote->handle($station, (int) $data['candidate_id'], $data['client_uuid']);

        return response()->json([
            'ok' => true,
            'vote_count' => $station->voteCountFor(Election::active()),
        ], $created ? 201 : 200);
    }
}
