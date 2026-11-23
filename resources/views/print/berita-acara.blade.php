@php
    use App\Support\ElectionRecap;
    $date = ($election->closed_at ?? $printedAt)->copy()->timezone(config('app.display_timezone'))->locale('id');
    $line = '..............................';
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Berita Acara — {{ $election->name }}</title>
    <style>
        /* Dokumen cetak A4 hitam-putih (design-system §0 mode Dokumen). Tanpa aset eksternal. */
        @page { size: A4 portrait; margin: 18mm 16mm; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 11.5pt; color: #000; background: #fff; margin: 0; line-height: 1.35; }
        .page { max-width: 178mm; margin: 0 auto; padding: 8mm 0; position: relative; }
        .kop { position: relative; text-align: center; border-bottom: 3px double #000; padding: 0 22mm 6pt; margin-bottom: 10pt; min-height: 18mm; }
        .kop .logo { position: absolute; left: 0; top: 0; width: 18mm; height: 18mm; object-fit: contain; }
        .kop .school { font-size: 15pt; font-weight: bold; text-transform: uppercase; }
        .kop .address { font-size: 10pt; }
        h1 { font-size: 12.5pt; text-align: center; text-transform: uppercase; margin: 8pt 0 2pt; }
        .subtitle { text-align: center; margin-bottom: 10pt; }
        table { width: 100%; border-collapse: collapse; margin: 6pt 0 10pt; page-break-inside: avoid; }
        th, td { border: 1px solid #000; padding: 3pt 5pt; vertical-align: top; }
        th { background: #eee; }
        td.num, th.num { text-align: right; }
        td.manual { min-width: 22mm; }
        .meta td { border: none; padding: 1pt 4pt 1pt 0; }
        .signs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14pt 10pt; margin-top: 12pt; page-break-inside: avoid; }
        .sign { text-align: center; }
        .sign .space { height: 48pt; }
        .sign .name { font-weight: bold; text-decoration: underline; }
        .place { text-align: right; margin-top: 10pt; }
        .signing { page-break-inside: avoid; break-inside: avoid; }
        .footer { margin-top: 14pt; font-size: 9pt; color: #333; }
        .watermark { position: fixed; top: 40%; left: 0; right: 0; text-align: center; transform: rotate(-30deg);
                     font-size: 34pt; font-weight: bold; color: rgba(0,0,0,.13); pointer-events: none; z-index: 0; }
        .toolbar { position: sticky; top: 0; background: #f1f5f9; padding: 8px 16px; text-align: center; border-bottom: 1px solid #cbd5e1; font-family: system-ui, sans-serif; }
        .toolbar button { font-size: 16px; padding: 8px 18px; cursor: pointer; }
        @media print { .toolbar { display: none; } .page { padding: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    @if ($provisional)
        <div class="watermark" aria-hidden="true">SEMENTARA – PEMUNGUTAN BELUM DITUTUP</div>
    @endif

    <main class="page">
        <header class="kop">
            @if ($election->logoUrl())
                <img class="logo" src="{{ $election->logoUrl() }}" alt="Logo sekolah">
            @endif
            <div class="school">{{ $election->school_name ?: '................................................' }}</div>
            @if ($election->school_address)
                <div class="address">{{ $election->school_address }}</div>
            @endif
        </header>

        @if ($provisional)
            <p style="text-align:center;font-weight:bold">SEMENTARA – PEMUNGUTAN BELUM DITUTUP</p>
        @endif

        <h1>Berita Acara Penghitungan Suara<br>Pemilihan Ketua dan Wakil Ketua OSIS</h1>
        <p class="subtitle">{{ $election->name }} · Tahun Ajaran {{ $election->school_year }}</p>

        <table class="meta">
            <tr><td>Pemungutan suara dibuka</td><td>: {{ ElectionRecap::wib($election->opened_at, 'l, d F Y \p\u\k\u\l H.i') }}</td></tr>
            <tr><td>Pemungutan suara ditutup</td><td>: {{ ElectionRecap::wib($election->closed_at, 'l, d F Y \p\u\k\u\l H.i') }}</td></tr>
            <tr><td>Jumlah TPS</td><td>: {{ $recap->stations->count() }}</td></tr>
            <tr><td>Jumlah suara sah</td><td>: <strong>{{ ElectionRecap::number($recap->total) }}</strong></td></tr>
        </table>

        <p><strong>A. Perolehan suara pasangan calon</strong></p>
        <table>
            <thead>
                <tr><th style="width:12mm">No.</th><th>Pasangan calon</th><th class="num" style="width:24mm">Suara</th><th class="num" style="width:22mm">Persentase</th></tr>
            </thead>
            <tbody>
                @foreach ($recap->candidates as $row)
                    <tr>
                        <td style="text-align:center">{{ $row['candidate']->number_label }}</td>
                        <td>{{ $row['candidate']->chair_name }} &amp; {{ $row['candidate']->vice_name }}</td>
                        <td class="num">{{ ElectionRecap::number($row['votes']) }}</td>
                        <td class="num">{{ ElectionRecap::percent($row['percent']) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <th colspan="2" style="text-align:right">Jumlah</th>
                    <th class="num">{{ ElectionRecap::number($recap->total) }}</th>
                    <th class="num">{{ $recap->total > 0 ? '100,0%' : '0,0%' }}</th>
                </tr>
            </tbody>
        </table>

        <p><strong>B. Rincian per TPS</strong></p>
        <table>
            <thead>
                <tr>
                    <th>TPS</th>
                    @foreach ($recap->candidates as $row)
                        <th class="num">{{ $row['candidate']->number_label }}</th>
                    @endforeach
                    <th class="num">Jumlah suara (sistem)</th>
                    <th>Jumlah pemilih menurut catatan pengawas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recap->stations as $s)
                    <tr>
                        <td>{{ $s['station']->name }}</td>
                        @foreach ($recap->candidates as $row)
                            <td class="num">{{ ElectionRecap::number($s['votes'][$row['candidate']->id] ?? 0) }}</td>
                        @endforeach
                        <td class="num"><strong>{{ ElectionRecap::number($s['total']) }}</strong></td>
                        <td class="manual"></td>
                    </tr>
                @endforeach
                <tr>
                    <th style="text-align:right">Jumlah</th>
                    @foreach ($recap->candidates as $row)
                        <th class="num">{{ ElectionRecap::number($row['votes']) }}</th>
                    @endforeach
                    <th class="num">{{ ElectionRecap::number($recap->total) }}</th>
                    <th></th>
                </tr>
            </tbody>
        </table>

        <p>Demikian berita acara ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>

        {{-- Tempat/tanggal dan tanda tangan tidak boleh terpisah halaman. --}}
        <div class="signing">
            <p class="place">{{ $election->signing_place ?: $line }}, {{ $date->translatedFormat('d F Y') }}</p>

            <section class="signs" aria-label="Tanda tangan">
                @foreach ($signatories as $s)
                    <div class="sign">
                        <div>{{ $s['role'] }}</div>
                        <div class="space"></div>
                        @if (filled($s['name'] ?? null))
                            <div class="name">{{ $s['name'] }}</div>
                        @else
                            <div>{{ $line }}</div>
                        @endif
                    </div>
                @endforeach
                @if ($election->witness_signatures)
                    @foreach ($recap->candidates as $row)
                        <div class="sign">
                            <div>Saksi Paslon {{ $row['candidate']->number_label }}</div>
                            <div class="space"></div>
                            <div>{{ $line }}</div>
                        </div>
                    @endforeach
                @endif
            </section>
        </div>

        <p class="footer">Dicetak dari E-Voting OSIS pada {{ $printedAt->locale('id')->translatedFormat('d F Y H.i') }} WIB. Hanya memuat suara resmi (tanpa suara uji coba).</p>
    </main>
</body>
</html>
