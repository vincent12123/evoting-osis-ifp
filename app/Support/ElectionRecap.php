<?php

namespace App\Support;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\PollingStation;
use App\Models\Vote;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rekap suara satu pemilihan (REQ-15, REQ-16) — satu sumber angka untuk halaman Rekap dan Berita Acara.
 * Rekap resmi hanya menghitung is_trial = false; mode uji coba dihitung terpisah.
 */
class ElectionRecap
{
    /** @var Collection<int, array{candidate: Candidate, votes: int, percent: float, is_top: bool}> */
    public readonly Collection $candidates;

    /** @var Collection<int, array{station: PollingStation, votes: array<int, int>, total: int, last_vote_at: ?CarbonInterface}> */
    public readonly Collection $stations;

    public readonly int $total;

    /** 'none' | 'leader' | 'tie' */
    public readonly string $topState;

    public function __construct(public readonly Election $election, public readonly bool $trial = false)
    {
        $counts = Vote::query()
            ->where('election_id', $election->id)
            ->where('is_trial', $trial)
            ->selectRaw('candidate_id, polling_station_id, COUNT(*) as n, MAX(created_at) as last_at')
            ->groupBy('candidate_id', 'polling_station_id')
            ->get();

        $this->total = (int) $counts->sum('n');

        $perCandidate = $counts->groupBy('candidate_id')->map(fn ($rows) => (int) $rows->sum('n'));
        $max = (int) ($perCandidate->max() ?? 0);
        $leaders = $perCandidate->filter(fn (int $n) => $n === $max && $max > 0)->count();
        $this->topState = $max === 0 ? 'none' : ($leaders > 1 ? 'tie' : 'leader');

        $this->candidates = $election->candidates()->get()->map(function (Candidate $c) use ($perCandidate, $max) {
            $votes = (int) ($perCandidate[$c->id] ?? 0);

            return [
                'candidate' => $c,
                'votes' => $votes,
                'percent' => $this->total > 0 ? round($votes / $this->total * 100, 1) : 0.0,
                'is_top' => $max > 0 && $votes === $max,
            ];
        })->values();

        $stationIds = $counts->pluck('polling_station_id')->unique();
        $this->stations = PollingStation::query()
            ->where(fn ($q) => $q->whereIn('id', $stationIds)->orWhere('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(function (PollingStation $s) use ($counts) {
                $rows = $counts->where('polling_station_id', $s->id);
                $last = $rows->max('last_at');

                return [
                    'station' => $s,
                    'votes' => $rows->mapWithKeys(fn ($r) => [(int) $r->candidate_id => (int) $r->n])->all(),
                    'total' => (int) $rows->sum('n'),
                    'last_vote_at' => $last ? Carbon::parse($last)->timezone(config('app.display_timezone')) : null,
                ];
            })
            ->values();
    }

    public function isEmpty(): bool
    {
        return $this->total === 0;
    }

    /** Label tanda paslon teratas: "Terbanyak" atau "Suara sama" (AC-51). */
    public function topLabel(): ?string
    {
        return match ($this->topState) {
            'leader' => 'Terbanyak',
            'tie' => 'Suara sama',
            default => null,
        };
    }

    /** Format persentase Indonesia, mis. 45,3%. */
    public static function percent(float $value): string
    {
        return number_format($value, 1, ',', '.').'%';
    }

    public static function number(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    public static function wib(?CarbonInterface $time, string $format = 'd/m/Y H.i'): string
    {
        return $time ? $time->copy()->timezone(config('app.display_timezone'))->locale('id')->translatedFormat($format).' WIB' : '—';
    }
}
