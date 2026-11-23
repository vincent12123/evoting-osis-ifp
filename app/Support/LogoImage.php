<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Mengolah logo sekolah/OSIS (SPEC v1.3 REQ-24): sisi terpanjang ≤ 512 px,
 * transparansi dipertahankan, disimpan WebP di disk public. SVG tidak diterima (bisa memuat skrip).
 */
class LogoImage
{
    public const DISK = 'public';

    public const DIRECTORY = 'logos';

    public const MAX_SIDE = 512;

    public function store(UploadedFile $file): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new RuntimeException('Logo tidak bisa dibaca.');
        }

        $image = $this->resize($source);
        imagedestroy($source);

        ob_start();
        imagewebp($image, null, 90);
        $webp = (string) ob_get_clean();
        imagedestroy($image);

        $path = self::DIRECTORY.'/'.Str::uuid().'.webp';
        Storage::disk(self::DISK)->put($path, $webp);

        return $path;
    }

    private function resize(GdImage $source): GdImage
    {
        $w = imagesx($source);
        $h = imagesy($source);
        $scale = min(1, self::MAX_SIDE / max($w, $h));
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        $target = imagecreatetruecolor($tw, $th);
        // Latar transparan agar logo PNG tanpa latar tetap tanpa latar.
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $tw, $th, $w, $h);

        return $target;
    }
}
