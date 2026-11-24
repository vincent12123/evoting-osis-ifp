<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * NFR-05: salinan database SQLite yang konsisten (VACUUM INTO — aman walau sedang dipakai, tanpa alat `sqlite3`)
 * + cermin folder foto paslon & logo. Disimpan di storage/app/backups, salinan lama dibersihkan.
 */
class BackupDatabase
{
    public const KEEP = 168; // ±7 hari bila dijalankan tiap jam

    public function directory(): string
    {
        return storage_path('app/backups');
    }

    /** @return string path berkas salinan database */
    public function handle(string $reason = 'terjadwal'): string
    {
        File::ensureDirectoryExists($this->directory());

        $label = preg_replace('/[^a-z0-9-]/', '', strtolower($reason)) ?: 'manual';
        $target = $this->directory().'/evoting-'.now()->format('Ymd-His').'-'.$label.'.sqlite';

        if (file_exists($target)) {
            throw new RuntimeException("Berkas backup sudah ada: {$target}");
        }

        DB::statement('VACUUM INTO ?', [$target]);

        // Cermin foto paslon & logo (berkasnya kecil dan jarang berubah).
        $public = storage_path('app/public');
        foreach (['candidates', 'logos'] as $dir) {
            if (is_dir("{$public}/{$dir}")) {
                File::copyDirectory("{$public}/{$dir}", $this->directory()."/media/{$dir}");
            }
        }

        $this->prune();

        return $target;
    }

    private function prune(): void
    {
        $files = collect(File::glob($this->directory().'/evoting-*.sqlite'))->sort()->values();

        $files->slice(0, max(0, $files->count() - self::KEEP))->each(fn (string $f) => File::delete($f));
    }
}
