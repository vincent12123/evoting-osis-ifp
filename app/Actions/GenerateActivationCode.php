<?php

namespace App\Actions;

use App\Models\PollingStation;

/** REQ-04: kode 6 angka acak, sekali pakai, berlaku 30 menit. */
class GenerateActivationCode
{
    public const VALID_MINUTES = 30;

    public function handle(PollingStation $station): string
    {
        do {
            $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        } while (PollingStation::query()->where('activation_code', $code)->exists());

        // Tulis langsung ke DB: model di memori bisa basi (mis. kode sempat dipakai IFP),
        // sehingga Eloquent menganggap kolom tidak berubah dan tidak menyimpannya.
        PollingStation::query()->whereKey($station->getKey())->update([
            'activation_code' => $code,
            'activation_code_expires_at' => now()->addMinutes(self::VALID_MINUTES),
        ]);
        $station->refresh();

        return $code;
    }
}
