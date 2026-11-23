<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

/** Halaman layar IFP (SCR-K1–K7) dan pratinjaunya untuk admin. */
class KioskPageController extends Controller
{
    /** Layar sungguhan: data diambil lewat API kiosk memakai token perangkat. */
    public function show(): View
    {
        return view('kiosk.index', ['config' => [
            'preview' => false,
            'api' => $this->apiUrls(),
        ]]);
    }

    /** Pratinjau untuk admin (TSK-05): data pemilihan aktif disisipkan, suara tidak dikirim. */
    public function preview(): View
    {
        $election = Election::active();

        return view('kiosk.index', ['config' => [
            'preview' => true,
            'api' => $this->apiUrls(),
            'station' => ['name' => 'PRATINJAU', 'is_active' => true, 'vote_count' => 0],
            'election' => $election ? [
                'name' => $election->name,
                'status' => 'open',
                'touch_mode' => $election->touch_mode->value,
                'reset_delay_seconds' => $election->reset_delay_seconds,
                'sound_enabled' => false,
                'confetti_enabled' => $election->confetti_enabled,
                'settings_version' => $election->settings_version,
                'logo_url' => $election->logoUrl(),
            ] : null,
            'candidates' => $election ? $election->candidates->map(fn (Candidate $c) => [
                'id' => $c->id,
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
            ])->values()->all() : [],
        ]]);
    }

    /** @return array<string, string> */
    private function apiUrls(): array
    {
        return [
            'activate' => route('kiosk.api.activate', absolute: false),
            'status' => route('kiosk.api.status', absolute: false),
            'candidates' => route('kiosk.api.candidates', absolute: false),
            'votes' => route('kiosk.api.votes', absolute: false),
        ];
    }
}
