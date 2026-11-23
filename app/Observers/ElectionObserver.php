<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Election;
use App\Models\User;

class ElectionObserver
{
    /** Kolom yang memengaruhi tampilan IFP → settings_version naik (SPEC §4.2). */
    public const KIOSK_FIELDS = ['name', 'touch_mode', 'reset_delay_seconds', 'sound_enabled', 'confetti_enabled', 'logo_path'];

    /** Kolom yang tidak perlu dicatat sebagai "ubah pengaturan". */
    private const IGNORED = ['updated_at', 'settings_version', 'is_active'];

    public function creating(Election $election): void
    {
        // Pemilihan pertama langsung aktif agar admin tidak perlu langkah tambahan.
        if (! Election::query()->where('is_active', true)->exists()) {
            $election->is_active = true;
        }
    }

    public function updating(Election $election): void
    {
        if ($election->isDirty(self::KIOSK_FIELDS)) {
            $election->settings_version = (int) $election->getOriginal('settings_version') + 1;
        }
    }

    public function created(Election $election): void
    {
        ActivityLog::record('election.created', $this->user(), $election, [
            'new' => $election->only(['name', 'school_year']),
        ]);
    }

    public function updated(Election $election): void
    {
        $changes = array_diff_key($election->getChanges(), array_flip(self::IGNORED));

        if ($changes === []) {
            return;
        }

        ActivityLog::record('election.updated', $this->user(), $election, [
            'old' => array_intersect_key($election->getRawOriginal(), $changes),
            'new' => $changes,
        ]);
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
