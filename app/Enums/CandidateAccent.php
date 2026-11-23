<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Warna aksen paslon — token docs/design-system.md §2.4. */
enum CandidateAccent: string implements HasLabel
{
    case Paslon1 = 'paslon-1';
    case Paslon2 = 'paslon-2';
    case Paslon3 = 'paslon-3';
    case Paslon4 = 'paslon-4';
    case Paslon5 = 'paslon-5';
    case Paslon6 = 'paslon-6';

    public function getLabel(): string
    {
        return match ($this) {
            self::Paslon1 => 'Biru',
            self::Paslon2 => 'Hijau',
            self::Paslon3 => 'Ungu',
            self::Paslon4 => 'Merah',
            self::Paslon5 => 'Oranye tua',
            self::Paslon6 => 'Biru laut',
        };
    }
}
