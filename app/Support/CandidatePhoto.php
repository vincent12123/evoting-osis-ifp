<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Mengolah foto paslon (SPEC §4.3, design-system §6): potong tengah 4:3,
 * sisi terpanjang ≤ 1600px, simpan WebP ≤ 300 KB di disk public.
 */
class CandidatePhoto
{
    public const DISK = 'public';

    public const DIRECTORY = 'candidates';

    public const MAX_WIDTH = 1600;

    public const MAX_BYTES = 300 * 1024;

    public function store(UploadedFile $file): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new RuntimeException('Foto tidak bisa dibaca.');
        }

        $image = $this->cropAndResize($source);
        imagedestroy($source);

        $webp = $this->encode($image);
        imagedestroy($image);

        $path = self::DIRECTORY.'/'.Str::uuid().'.webp';
        Storage::disk(self::DISK)->put($path, $webp);

        return $path;
    }

    private function cropAndResize(\GdImage $source): \GdImage
    {
        $w = imagesx($source);
        $h = imagesy($source);

        // Potong tengah ke rasio 4:3.
        if ($w * 3 > $h * 4) {
            $cropH = $h;
            $cropW = intdiv($h * 4, 3);
        } else {
            $cropW = $w;
            $cropH = intdiv($w * 3, 4);
        }
        $srcX = intdiv($w - $cropW, 2);
        $srcY = intdiv($h - $cropH, 2);

        $targetW = min($cropW, self::MAX_WIDTH);
        $targetH = intdiv($targetW * 3, 4);

        $target = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($target, $source, 0, 0, $srcX, $srcY, $targetW, $targetH, $cropW, $cropH);

        return $target;
    }

    private function encode(\GdImage $image): string
    {
        foreach ([82, 72, 62, 50] as $quality) {
            ob_start();
            imagewebp($image, null, $quality);
            $data = (string) ob_get_clean();

            if (strlen($data) <= self::MAX_BYTES) {
                break;
            }
        }

        return $data;
    }
}
