<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Penolakan yang ditampilkan IFP. `code` dibaca kiosk untuk memilih state;
 * `message` adalah teks persis dari kartu layar (docs/ux-flow.md).
 */
class KioskException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message, public readonly int $status)
    {
        parent::__construct($message);
    }

    public static function invalidCode(): self
    {
        return new self('invalid_code', 'Kode tidak dikenal atau sudah kedaluwarsa. Minta kode baru ke panitia.', 422);
    }

    public static function tooManyAttempts(): self
    {
        return new self('too_many_attempts', 'Terlalu banyak percobaan. Coba lagi dalam 10 menit.', 429);
    }

    public static function deviceUnknown(): self
    {
        return new self('device_unknown', 'IFP ini sudah dilepas dari TPS. Masukkan kode aktivasi baru.', 401);
    }

    public static function electionNotOpen(): self
    {
        return new self('election_not_open', 'Pemungutan suara sudah ditutup. Suara ini tidak tercatat.', 409);
    }

    public static function stationInactive(): self
    {
        return new self('station_inactive', 'TPS ini sedang tidak aktif. Hubungi panitia.', 403);
    }

    public static function invalidCandidate(): self
    {
        return new self('invalid_candidate', 'Data paslon berubah. Layar akan dimuat ulang.', 422);
    }

    public static function tooFast(): self
    {
        return new self('too_fast', 'Tunggu sebentar, lalu pilih lagi.', 429);
    }

    public function render(): JsonResponse
    {
        return response()->json(['code' => $this->errorCode, 'message' => $this->getMessage()], $this->status);
    }
}
