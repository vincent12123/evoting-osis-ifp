{{--
    Kartu SCR-A6. Gaya inline + variabel warna Filament karena tema kustom belum bisa di-build (AGENTS §8).
--}}
@php
    use App\Support\ElectionRecap;
@endphp

<x-filament-panels::page>
    <div wire:poll.10s style="display:flex;flex-direction:column;gap:1.5rem">
        @if (! $election)
            <x-filament::section>
                <p>Belum ada pemilihan aktif.</p>
            </x-filament::section>
        @else
            @if ($hasTrialTab)
                <x-filament::tabs>
                    <x-filament::tabs.item :active="! $trial" wire:click="$set('mode', 'official')">Resmi</x-filament::tabs.item>
                    <x-filament::tabs.item :active="$trial" wire:click="$set('mode', 'trial')">Uji coba</x-filament::tabs.item>
                </x-filament::tabs>
            @endif

            @if ($trial)
                <div role="status" style="padding:.75rem 1rem;border-radius:.5rem;background:var(--warning-50);color:var(--warning-700);font-weight:600">
                    UJI COBA — suara ini tidak masuk rekap resmi.
                </div>
            @elseif ($provisional)
                <div role="status" style="padding:.75rem 1rem;border-radius:.5rem;background:var(--warning-50);color:var(--warning-700);font-weight:600">
                    Hasil sementara — pemungutan belum ditutup.
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(12rem,1fr));gap:1rem">
                <x-filament::section>
                    <p style="font-size:.875rem;color:var(--gray-500)">{{ $trial ? 'Total suara uji coba' : 'Total suara sah' }}</p>
                    <p style="font-size:2rem;font-weight:800">{{ ElectionRecap::number($recap->total) }}</p>
                </x-filament::section>
                <x-filament::section>
                    <p style="font-size:.875rem;color:var(--gray-500)">Dibuka</p>
                    <p style="font-size:1.125rem;font-weight:700">{{ ElectionRecap::wib($election->opened_at) }}</p>
                </x-filament::section>
                <x-filament::section>
                    <p style="font-size:.875rem;color:var(--gray-500)">Ditutup</p>
                    <p style="font-size:1.125rem;font-weight:700">{{ ElectionRecap::wib($election->closed_at) }}</p>
                </x-filament::section>
            </div>

            @if ($recap->isEmpty())
                <x-filament::section>
                    <p style="text-align:center;padding:2rem 0;font-weight:600">Belum ada suara masuk.</p>
                </x-filament::section>
            @else
                <x-filament::section heading="Perolehan suara per paslon">
                    <div style="display:flex;flex-direction:column;gap:1.25rem">
                        @foreach ($recap->candidates as $row)
                            @php($c = $row['candidate'])
                            <div>
                                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:.5rem;align-items:baseline">
                                    <p style="font-weight:700">
                                        <span style="display:inline-block;min-width:2.25rem;padding:.1rem .4rem;border-radius:.375rem;background:var(--gray-900);color:white;text-align:center;font-size:.875rem">{{ $c->number_label }}</span>
                                        {{ $c->chair_name }} &amp; {{ $c->vice_name }}
                                        @if ($row['is_top'] && $recap->topLabel())
                                            <span style="margin-left:.5rem;padding:.1rem .5rem;border-radius:999px;background:var(--warning-100);color:var(--warning-800);font-size:.75rem">{{ $recap->topLabel() }}</span>
                                        @endif
                                    </p>
                                    <p><strong style="font-size:1.25rem">{{ ElectionRecap::number($row['votes']) }}</strong> suara · <strong>{{ ElectionRecap::percent($row['percent']) }}</strong></p>
                                </div>
                                <div style="margin-top:.5rem;height:1rem;border-radius:999px;background:var(--gray-200);overflow:hidden"
                                     role="img" aria-label="Paslon {{ $c->number_label }}: {{ ElectionRecap::percent($row['percent']) }}">
                                    <div style="height:100%;width:{{ $row['percent'] }}%;background:{{ $row['is_top'] ? 'var(--primary-600)' : 'var(--gray-500)' }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            <x-filament::section heading="Rincian per TPS">
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                        <thead>
                            <tr style="border-bottom:1px solid var(--gray-200);text-align:left">
                                <th style="padding:.5rem">TPS</th>
                                @foreach ($recap->candidates as $row)
                                    <th style="padding:.5rem;text-align:right">{{ $row['candidate']->number_label }}</th>
                                @endforeach
                                <th style="padding:.5rem;text-align:right">Jumlah</th>
                                <th style="padding:.5rem;text-align:right">Suara terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recap->stations as $s)
                                <tr style="border-bottom:1px solid var(--gray-100)">
                                    <td style="padding:.5rem;font-weight:600">{{ $s['station']->name }}</td>
                                    @foreach ($recap->candidates as $row)
                                        <td style="padding:.5rem;text-align:right">{{ ElectionRecap::number($s['votes'][$row['candidate']->id] ?? 0) }}</td>
                                    @endforeach
                                    <td style="padding:.5rem;text-align:right;font-weight:700">{{ ElectionRecap::number($s['total']) }}</td>
                                    <td style="padding:.5rem;text-align:right">{{ $s['last_vote_at']?->format('H.i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="99" style="padding:1rem;text-align:center">Belum ada TPS.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
