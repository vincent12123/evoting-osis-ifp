<?php

namespace App\Models;

use App\Enums\ElectionStatus;
use App\Enums\TouchMode;
use App\Observers\ElectionObserver;
use App\Support\LogoImage;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/** Pemilihan — SPEC §4.2. */
#[ObservedBy(ElectionObserver::class)]
class Election extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'school_year',
        'status',
        'is_active',
        'touch_mode',
        'reset_delay_seconds',
        'sound_enabled',
        'confetti_enabled',
        'opened_at',
        'closed_at',
        'settings_version',
        'school_name',
        'school_address',
        'signing_place',
        'signatories',
        'witness_signatures',
        'logo_path',
    ];

    /** URL publik logo (SPEC v1.3), atau null bila belum diunggah. */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk(LogoImage::DISK)->url($this->logo_path) : null;
    }

    /** Penandatangan bawaan berita acara (keputusan pemilik CP5); nama diisi admin di Pengaturan. */
    public const DEFAULT_SIGNATORIES = [
        ['role' => 'Ketua Panitia', 'name' => null],
        ['role' => 'Pembina OSIS', 'name' => null],
        ['role' => 'Kepala Sekolah', 'name' => null],
    ];

    protected $attributes = [
        'witness_signatures' => true,
        'signatories' => '[{"role":"Ketua Panitia","name":null},{"role":"Pembina OSIS","name":null},{"role":"Kepala Sekolah","name":null}]',
        'status' => 'preparation',
        'is_active' => false,
        'touch_mode' => 'confirm',
        'reset_delay_seconds' => 3,
        'sound_enabled' => true,
        'confetti_enabled' => true,
        'settings_version' => 1,
    ];

    protected function casts(): array
    {
        return [
            'status' => ElectionStatus::class,
            'touch_mode' => TouchMode::class,
            'is_active' => 'boolean',
            'sound_enabled' => 'boolean',
            'confetti_enabled' => 'boolean',
            'reset_delay_seconds' => 'integer',
            'settings_version' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'signatories' => 'array',
            'witness_signatures' => 'boolean',
        ];
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class)->orderBy('number');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }
}
