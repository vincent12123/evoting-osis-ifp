<?php

namespace App\Exceptions;

use App\Enums\ElectionStatus;
use RuntimeException;

/** REQ-19: data paslon tidak boleh berubah saat pemungutan Dibuka/Ditutup. */
class CandidatesLockedException extends RuntimeException
{
    public static function for(ElectionStatus $status): self
    {
        return new self(self::messageFor($status));
    }

    /** Teks persis dari kartu SCR-A3. */
    public static function messageFor(ElectionStatus $status): string
    {
        return $status === ElectionStatus::Closed
            ? 'Data paslon terkunci karena pemungutan sudah ditutup.'
            : 'Data paslon terkunci karena pemungutan sedang berlangsung.';
    }
}
