<?php

namespace App\Models;

use App\Enums\ElectionStatus;
use App\Observers\PollingStationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/** TPS — SPEC §4.4. Satu TPS = satu IFP. */
#[ObservedBy(PollingStationObserver::class)]
class PollingStation extends Model
{
    use HasFactory;

    /** IFP dianggap Daring bila terakhir menanyakan status ≤ 30 detik lalu (REQ-22). */
    public const ONLINE_SECONDS = 30;

    public const DELETE_BLOCKED_MESSAGE = 'TPS yang sudah punya suara tidak bisa dihapus. Nonaktifkan saja.';

    protected $fillable = [
        'name',
        'location',
        'is_active',
    ];

    /** Kode & hash token tidak pernah ikut terserialisasi. */
    protected $hidden = [
        'activation_code',
        'device_token_hash',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activation_code_expires_at' => 'datetime',
            'device_activated_at' => 'datetime',
            'device_last_seen_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (PollingStation $station) {
            if ($station->votes()->where('is_trial', false)->exists()) {
                throw new LogicException(self::DELETE_BLOCKED_MESSAGE);
            }
        });
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function hasDevice(): bool
    {
        return $this->device_token_hash !== null;
    }

    /** 'none' | 'online' | 'offline' */
    public function deviceStatus(): string
    {
        if (! $this->hasDevice()) {
            return 'none';
        }

        return $this->device_last_seen_at?->gt(now()->subSeconds(self::ONLINE_SECONDS)) ? 'online' : 'offline';
    }

    /** Label untuk tabel admin, mis. "Daring", "Luring (3 menit lalu)". */
    public function deviceStatusLabel(): string
    {
        return match ($this->deviceStatus()) {
            'none' => 'Belum terhubung',
            'online' => 'Daring',
            'offline' => 'Luring'.($this->device_last_seen_at ? ' ('.$this->device_last_seen_at->locale('id')->diffForHumans().')' : ''),
        };
    }

    /** Jumlah suara TPS ini pada pemilihan aktif, sesuai mode (uji coba / resmi). */
    public function voteCountFor(?Election $election): int
    {
        if ($election === null) {
            return 0;
        }

        return $this->votes()
            ->where('election_id', $election->id)
            ->where('is_trial', $election->status === ElectionStatus::Trial)
            ->count();
    }
}
