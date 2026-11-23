<?php

namespace App\Exceptions;

use RuntimeException;

/** Perpindahan status pemilihan ditolak; pesan ditampilkan apa adanya ke admin (kartu SCR-A2). */
class ElectionTransitionException extends RuntimeException
{
    public const NOT_ENOUGH_CANDIDATES = 'Tambahkan minimal 2 paslon lengkap sebelum membuka pemungutan.';

    public const NO_DEVICE = 'Belum ada IFP yang terhubung ke TPS mana pun.';
}
