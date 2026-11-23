<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Mode sentuh di IFP — REQ-08. */
enum TouchMode: string implements HasLabel
{
    case Confirm = 'confirm';
    case Instant = 'instant';

    public function getLabel(): string
    {
        return match ($this) {
            self::Confirm => '2x sentuh — pilih lalu konfirmasi (disarankan)',
            self::Instant => '1x sentuh — langsung tercatat',
        };
    }
}
