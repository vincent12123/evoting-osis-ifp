<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Suara — SPEC §4.5. Tidak menyimpan identitas pemilih (REQ-18).
 * Tidak ada updated_at: suara tidak pernah diubah (REQ-19).
 */
class Vote extends Model
{
    public const UPDATED_AT = null;

    /** Milidetik dipakai untuk rem 2 detik per TPS (REQ-21). */
    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = [
        'election_id',
        'candidate_id',
        'polling_station_id',
        'is_trial',
        'client_uuid',
    ];

    protected function casts(): array
    {
        return [
            'is_trial' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // NFR-24: suara tidak bisa diubah; suara sah tidak bisa dihapus lewat model.
        static::updating(fn () => throw new LogicException('Suara tidak bisa diubah.'));
        static::deleting(function (Vote $vote) {
            // getOriginal: nilai di database, bukan nilai di memori yang mungkin sudah diubah.
            if (! $vote->getOriginal('is_trial')) {
                throw new LogicException('Suara sah tidak bisa dihapus.');
            }
        });
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function pollingStation(): BelongsTo
    {
        return $this->belongsTo(PollingStation::class);
    }
}
