<?php

namespace App\Livewire;

use App\Models\PollingStation;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Isi dialog "Buat kode aktivasi" (kartu SCR-A4). Memeriksa ulang tiap 5 detik;
 * begitu kode dipakai IFP, berubah menjadi "IFP terhubung ke {TPS}." (UX-08).
 */
class ActivationCodeStatus extends Component
{
    public int $stationId;

    /** Waktu kode dibuat; aktivasi setelah waktu ini = kode ini sudah dipakai. */
    public string $issuedAt;

    public function render(): View
    {
        $station = PollingStation::findOrFail($this->stationId);

        $connected = $station->activation_code === null
            && $station->device_activated_at !== null
            && $station->device_activated_at->gte($this->issuedAt);

        return view('livewire.activation-code-status', [
            'station' => $station,
            'connected' => $connected,
            'code' => $station->activation_code,
            'expiresAt' => $station->activation_code_expires_at?->timezone(config('app.display_timezone')),
            'kioskUrl' => url('/kiosk'),
        ]);
    }
}
