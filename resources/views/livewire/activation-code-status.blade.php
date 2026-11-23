{{--
    Gaya inline + variabel warna Filament: kelas Tailwind kustom belum ter-compile
    sebelum tema Filament bisa di-build (npm rusak, lihat AGENTS §8). Ganti ke kelas saat tema tersedia.
--}}
<div wire:poll.5s style="display:flex;flex-direction:column;gap:1rem;text-align:center">
    @if ($connected)
        <div style="padding:1.5rem;border-radius:.75rem;background:var(--success-50);color:var(--success-700)">
            <x-filament::icon icon="heroicon-o-check-circle" style="width:3rem;height:3rem;margin:0 auto" />
            <p style="margin-top:.5rem;font-size:1.125rem;font-weight:700">IFP terhubung ke {{ $station->name }}.</p>
        </div>
    @elseif ($code)
        <p style="font-size:.875rem;color:var(--gray-500)">Kode aktivasi untuk <strong>{{ $station->name }}</strong></p>
        <p style="font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:3rem;line-height:1;font-weight:900;letter-spacing:.15em"
           aria-label="Kode aktivasi {{ implode(' ', str_split($code)) }}">
            {{ substr($code, 0, 3) }} {{ substr($code, 3) }}
        </p>
        <p style="font-size:.875rem;color:var(--gray-500)">Berlaku sampai {{ $expiresAt?->format('H.i') }} WIB (30 menit)</p>
        <div style="padding:.75rem;border-radius:.5rem;border:1px solid var(--gray-200);text-align:left;font-size:.875rem">
            <p style="font-weight:600">Di IFP, buka alamat:</p>
            <p style="margin-top:.25rem;word-break:break-all;font-family:ui-monospace,Consolas,monospace">{{ $kioskUrl }}</p>
            <p style="margin-top:.25rem">lalu masukkan kode di atas.</p>
        </div>
    @else
        <p style="font-size:.875rem;color:var(--gray-500)">Kode sudah tidak berlaku. Tekan "Buat kode baru".</p>
    @endif
</div>
