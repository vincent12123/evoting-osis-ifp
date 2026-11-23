<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status pemilihan — SPEC §4.2 & §5.2. */
enum ElectionStatus: string implements HasColor, HasLabel
{
    case Preparation = 'preparation';
    case Trial = 'trial';
    case Open = 'open';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Preparation => 'Persiapan',
            self::Trial => 'Uji coba',
            self::Open => 'Dibuka',
            self::Closed => 'Ditutup',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Preparation => 'gray',
            self::Trial => 'warning',
            self::Open => 'success',
            self::Closed => 'danger',
        };
    }

    /** IFP menerima suara hanya pada status ini (REQ-13, REQ-20). */
    public function acceptsVotes(): bool
    {
        return $this === self::Trial || $this === self::Open;
    }

    /** Data paslon terkunci pada status ini (REQ-19). */
    public function locksCandidates(): bool
    {
        return $this === self::Open || $this === self::Closed;
    }
}
