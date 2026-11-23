<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Log aktivitas — SPEC §4.6, REQ-23. */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /** Label Bahasa Indonesia untuk setiap kode aksi (SCR-A8). Tambahkan baris baru di sini saat aksi baru dibuat. */
    public const ACTION_LABELS = [
        'auth.login' => 'Masuk',
        'election.created' => 'Buat pemilihan',
        'election.updated' => 'Ubah pengaturan pemilihan',
        'election.activated' => 'Jadikan pemilihan aktif',
        'candidate.created' => 'Tambah paslon',
        'candidate.updated' => 'Ubah paslon',
        'candidate.deleted' => 'Hapus paslon',
        'election.status_changed' => 'Ubah status pemilihan',
        'election.trial_cleared' => 'Kosongkan suara uji coba',
        'election.simulated_votes' => 'Tambah suara simulasi',
        'device.activated' => 'Hubungkan IFP',
        'device.released' => 'Lepas IFP',
        'polling_station.created' => 'Tambah TPS',
        'polling_station.updated' => 'Ubah TPS',
        'polling_station.deleted' => 'Hapus TPS',
    ];

    /** Nama kolom yang tampil di keterangan log. */
    private const FIELD_LABELS = [
        'name' => 'nama',
        'school_year' => 'tahun ajaran',
        'touch_mode' => 'mode sentuh',
        'reset_delay_seconds' => 'jeda terima kasih',
        'sound_enabled' => 'bel',
        'confetti_enabled' => 'confetti',
        'status' => 'status',
        'number' => 'nomor urut',
        'chair_name' => 'ketua',
        'chair_class' => 'kelas ketua',
        'vice_name' => 'wakil',
        'vice_class' => 'kelas wakil',
        'tagline' => 'slogan',
        'vision' => 'visi',
        'missions' => 'misi',
        'photo_path' => 'foto',
        'accent' => 'warna aksen',
        'active_election' => 'pemilihan aktif',
        'location' => 'lokasi',
        'school_name' => 'nama sekolah',
        'school_address' => 'alamat sekolah',
        'signing_place' => 'tempat penandatanganan',
        'signatories' => 'penandatangan',
        'witness_signatures' => 'kolom saksi',
        'logo_path' => 'logo',
        'is_active' => 'aktif',
    ];

    public static function actionLabel(string $action): string
    {
        return self::ACTION_LABELS[$action] ?? $action;
    }

    /** Ringkasan satu baris untuk kolom "Keterangan", mis. "mode sentuh: confirm → instant". */
    public function summary(): string
    {
        $props = $this->properties ?? [];
        $parts = [];

        if (isset($props['number'])) {
            $parts[] = 'Paslon '.str_pad((string) $props['number'], 2, '0', STR_PAD_LEFT);
        }

        $old = $props['old'] ?? [];
        $new = $props['new'] ?? [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            $label = self::FIELD_LABELS[$key] ?? $key;
            $before = array_key_exists($key, $old) ? $this->display($key, $old[$key]) : null;
            $after = array_key_exists($key, $new) ? $this->display($key, $new[$key]) : null;

            $parts[] = match (true) {
                in_array($key, ['vision', 'missions', 'photo_path', 'signatories', 'logo_path'], true) => "{$label} diubah",
                $before !== null && $after !== null => "{$label}: {$before} → {$after}",
                $after !== null => "{$label}: {$after}",
                default => "{$label}: {$before}",
            };
        }

        return $parts === [] ? '—' : implode('; ', $parts);
    }

    private function display(string $key, mixed $value): string
    {
        if (in_array($key, ['is_active', 'sound_enabled', 'confetti_enabled', 'witness_signatures'], true) && in_array($value, [0, 1, '0', '1'], true)) {
            $value = (bool) $value;
        }

        return match (true) {
            is_bool($value) => $value ? 'ya' : 'tidak',
            $value === null || $value === '' => '(kosong)',
            is_array($value) => implode(', ', $value),
            default => (string) $value,
        };
    }

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Catat satu aktivitas. $user null = aksi dari IFP/sistem.
     *
     * @param  array<string, mixed>|null  $properties  mis. ['old' => [...], 'new' => [...]]
     */
    public static function record(string $action, ?User $user = null, ?Model $subject = null, ?array $properties = null): self
    {
        return static::create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
        ]);
    }
}
