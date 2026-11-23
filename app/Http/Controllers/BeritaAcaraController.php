<?php

namespace App\Http\Controllers;

use App\Enums\ElectionStatus;
use App\Models\Election;
use App\Support\ElectionRecap;
use Illuminate\Contracts\View\View;

/** Kartu SCR-A7 — Berita Acara (cetak A4). Hanya suara resmi. */
class BeritaAcaraController extends Controller
{
    public function __invoke(): View
    {
        $election = Election::active();
        abort_if($election === null, 404, 'Belum ada pemilihan aktif.');

        $signatories = collect($election->signatories ?? Election::DEFAULT_SIGNATORIES)
            ->filter(fn ($s) => filled($s['role'] ?? null))
            ->values();

        return view('print.berita-acara', [
            'election' => $election,
            'recap' => new ElectionRecap($election, trial: false),
            'provisional' => $election->status !== ElectionStatus::Closed,
            'signatories' => $signatories,
            'printedAt' => now()->timezone(config('app.display_timezone')),
        ]);
    }
}
