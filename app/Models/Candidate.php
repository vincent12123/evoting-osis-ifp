<?php

namespace App\Models;

use App\Enums\CandidateAccent;
use App\Observers\CandidateObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Paslon — SPEC §4.3. */
#[ObservedBy(CandidateObserver::class)]
class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'number',
        'chair_name',
        'chair_class',
        'vice_name',
        'vice_class',
        'tagline',
        'vision',
        'missions',
        'photo_path',
        'accent',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'missions' => 'array',
            'accent' => CandidateAccent::class,
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /** Nomor urut dua digit, mis. "02". */
    public function getNumberLabelAttribute(): string
    {
        return str_pad((string) $this->number, 2, '0', STR_PAD_LEFT);
    }
}
