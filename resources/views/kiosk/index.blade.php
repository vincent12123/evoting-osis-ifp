<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="robots" content="noindex">
    <title>E-Voting OSIS — Layar IFP</title>
    @vite(['resources/css/kiosk.css', 'resources/js/kiosk.js'])
</head>
{{--
    Layar siswa di IFP — kartu SCR-K1 s/d SCR-K7 (docs/ux-flow.md). Teks persis dari kartu.
    Logika: resources/js/kiosk.js (komponen Alpine "kiosk").
--}}
<body x-data="kiosk(@js($config))" x-init="init()" @contextmenu.prevent>

{{-- ===================== SCR-K1 Aktivasi IFP ===================== --}}
<section class="k-full" x-show="screen === 'activation'" x-cloak data-screen="K1">
    <div class="k1-brand">
        <div class="k-header"><div class="logo">OSIS</div></div>
        <div class="k1-brand-name">E-Voting OSIS</div>
    </div>
    <h1>Hubungkan IFP ini ke TPS</h1>
    <p>Masukkan kode 6 angka dari panitia</p>
    <div class="k-code" aria-live="polite" aria-label="Kode aktivasi">
        <template x-for="i in 6" :key="i"><span x-text="code[i - 1] ?? ''"></span></template>
    </div>
    <p class="k-message" :class="activation.error && 'error'" x-text="activation.error ?? ''" role="alert"></p>
    <div class="k-keypad">
        <template x-for="d in ['1','2','3','4','5','6','7','8','9']" :key="d">
            <button type="button" class="k-btn" x-text="d" @click="typeDigit(d)" :aria-label="'Angka ' + d"></button>
        </template>
        <button type="button" class="k-btn wide" @click="code = code.slice(0, -1)">Hapus</button>
        <button type="button" class="k-btn" @click="typeDigit('0')" aria-label="Angka 0">0</button>
        <button type="button" class="k-btn go wide" data-action="activate"
                :disabled="code.length < 6 || activation.busy"
                @click="activate()"
                x-text="activation.busy ? 'Menghubungkan…' : 'Hubungkan'"></button>
    </div>
</section>

{{-- ===================== SCR-K2 Siap ===================== --}}
<section class="k-full" x-show="screen === 'ready'" x-cloak data-screen="K2" style="justify-content:flex-start;padding-top:10vh">
    <div class="k-header" style="height:auto">@include('kiosk._logo', ['size' => 12])</div>
    <div style="margin-top:2vh;font-size:3vh;font-weight:900" x-text="election?.name ?? 'E-Voting OSIS'"></div>
    <h1 style="margin-top:4vh"><span class="k-pill" style="font-size:4vh"><span class="dot"></span><span x-text="station?.name"></span></span></h1>
    <p x-show="ready.state === 'checking'">Memeriksa koneksi ke server…</p>
    <p x-show="ready.state === 'ok'">IFP sudah terhubung dan siap dipakai</p>
    <p x-show="ready.state === 'failed'" class="k-message error" role="alert">Tidak bisa terhubung ke server. Periksa WiFi IFP. Layar akan mencoba lagi otomatis.</p>
    <div class="k-start" x-show="ready.state === 'ok'">
        <button type="button" class="k-btn start" data-action="start" @click="tap(() => start())">▶&nbsp; Mulai</button>
        <p style="margin-top:2.4vh;font-size:2.4vh">Disentuh oleh guru/panitia sebelum siswa mulai memilih</p>
    </div>
</section>

{{-- ===================== SCR-K3 Pilih Paslon ===================== --}}
<main class="k-screen" x-show="screen === 'vote'" x-cloak data-screen="K3">
    <header class="k-header">
        @include('kiosk._logo')
        <div style="min-width:0">
            <div class="title" x-text="election?.name"></div>
            <div class="hint">Sentuh foto paslon pilihanmu. Satu siswa, satu suara.</div>
        </div>
        <div class="meta">
            <span class="k-pill trial" x-show="election?.status === 'trial'">UJI COBA</span>
            <span class="k-pill"><span class="dot"></span><span x-text="station?.name"></span></span>
            <span class="k-pill" data-testid="vote-count"><span x-text="formatNumber(station?.vote_count ?? 0)"></span>&nbsp;suara masuk</span>
        </div>
    </header>

    <p x-show="candidates.length === 0 && candidatesLoading" style="font-size:3vh;text-align:center;margin-top:20vh">Memuat data paslon…</p>
    <p x-show="candidates.length === 0 && !candidatesLoading" style="font-size:3vh;text-align:center;margin-top:20vh">Data paslon belum tersedia. Panggil panitia.</p>

    <div class="k-grid" :class="candidates.length <= 2 && 'few'" x-show="candidates.length > 0">
        <template x-for="c in candidates" :key="c.id">
            <article class="k-card" :class="['accent-' + c.accent, pressedId === c.id && 'pressed']" :data-candidate="c.number_label">
                <div class="info">
                    <div class="names"><span x-text="c.chair_name"></span><br>&amp; <span x-text="c.vice_name"></span></div>
                </div>
                <div class="photo" data-touch="photo" @click="tap(() => choose(c), $event)">
                    <img :src="c.photo_url" :alt="'Foto paslon ' + c.number_label + ': ' + c.chair_name + ' dan ' + c.vice_name"
                         x-show="!brokenPhotos[c.id]" x-on:error="brokenPhotos[c.id] = true" draggable="false">
                    <div class="fallback" x-show="brokenPhotos[c.id]" x-text="'PASLON ' + c.number_label"></div>
                    <div class="k-number" aria-hidden="true" x-text="c.number_label"></div>
                    <div class="shade">
                        <div class="tagline" x-text="'“' + c.tagline + '”'"></div>
                        <div class="vision"><strong>Visi:</strong> <span x-text="c.vision"></span></div>
                    </div>
                </div>
                <div class="actions">
                    <button type="button" class="k-btn neutral" data-touch="vision" @click="tap(() => openVision(c), $event)">Visi &amp; Misi</button>
                    <button type="button" class="k-btn choose" data-touch="choose" @click="tap(() => choose(c), $event)" x-text="'Pilih ' + c.number_label"></button>
                </div>
            </article>
        </template>
    </div>
</main>

{{-- ===================== SCR-K4 Visi & Misi ===================== --}}
<div class="k-overlay" x-show="overlay === 'vision'" x-cloak data-screen="K4" @click.self="tap(() => closeOverlay())">
    <div class="k-panel" :class="selected && 'accent-' + selected.accent" role="dialog" aria-modal="true" aria-labelledby="vision-title">
        <template x-if="selected">
            <div class="candidate">
                <div class="pic"><img :src="selected.photo_url" alt="" x-show="!brokenPhotos[selected.id]"></div>
                <div>
                    <div class="num" x-text="selected.number_label"></div>
                    <div class="names" id="vision-title"><span x-text="selected.chair_name"></span> &amp; <span x-text="selected.vice_name"></span></div>
                    <div style="font-size:2.4vh;color:var(--color-text-muted)" x-show="selected.chair_class || selected.vice_class"
                         x-text="[selected.chair_class, selected.vice_class].filter(Boolean).join(' · ')"></div>
                    <div style="font-size:2.6vh;font-style:italic;margin-top:1vh" x-text="'“' + selected.tagline + '”'"></div>
                </div>
            </div>
        </template>
        <div class="scroll" x-show="selected">
            <h3>VISI</h3>
            <p style="margin:0" x-text="selected?.vision"></p>
            <h3>MISI</h3>
            <ol><template x-for="(m, i) in (selected?.missions ?? [])" :key="i"><li x-text="m"></li></template></ol>
        </div>
        <div class="buttons">
            <button type="button" class="k-btn neutral" data-touch="back" @click="tap(() => closeOverlay())">Kembali</button>
            <button type="button" class="k-btn choose" data-touch="choose-from-vision" @click="tap(() => choose(selected))" x-text="'Pilih ' + (selected?.number_label ?? '')"></button>
        </div>
    </div>
</div>

{{-- ===================== SCR-K5 Konfirmasi (dan simpan pada mode 1x sentuh) ===================== --}}
<div class="k-overlay" x-show="overlay === 'confirm'" x-cloak data-screen="K5">
    <div class="k-panel" :class="[selected && 'accent-' + selected.accent, confirm.state === 'error' && 'error']" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <h2 id="confirm-title" x-show="confirm.state !== 'error' && election?.touch_mode !== 'instant'">Yakin memilih paslon ini?</h2>
        <h2 x-show="confirm.state === 'saving' && election?.touch_mode === 'instant'">Menyimpan suara…</h2>
        <h2 x-show="confirm.state === 'error' && confirm.error === 'network'" role="alert">Suara belum tercatat.</h2>
        <h2 x-show="confirm.state === 'error' && confirm.error !== 'network'" role="alert" x-text="confirm.message"></h2>
        <p class="lead" x-show="confirm.state === 'error' && confirm.error === 'network'">Koneksi ke server terputus. Jangan pergi dulu — panggil guru pengawas.</p>

        <template x-if="selected">
            <div class="candidate" style="justify-content:center">
                <div class="pic"><img :src="selected.photo_url" alt="" x-show="!brokenPhotos[selected.id]"></div>
                <div>
                    <div class="num" x-text="selected.number_label"></div>
                    <div class="names"><span x-text="selected.chair_name"></span><br>&amp; <span x-text="selected.vice_name"></span></div>
                </div>
            </div>
        </template>

        <div class="buttons" x-show="election?.touch_mode !== 'instant' && (confirm.state === 'ask' || confirm.state === 'saving')">
            <button type="button" class="k-btn neutral" data-touch="cancel" :disabled="confirm.state === 'saving'"
                    @click="tap(() => closeOverlay())">Batal, ganti pilihan</button>
            <button type="button" class="k-btn choose" data-touch="yes"
                    :disabled="!confirm.armed || confirm.state === 'saving'"
                    @click="tap(() => submitVote())"
                    x-text="confirm.state === 'saving' ? 'Menyimpan suara…' : 'Ya, pilih ' + (selected?.number_label ?? '')"></button>
        </div>
        <div class="buttons" x-show="confirm.state === 'error' && confirm.error === 'network'">
            <button type="button" class="k-btn neutral" data-touch="cancel" @click="tap(() => closeOverlay())">Batal</button>
            <button type="button" class="k-btn choose" data-touch="retry" @click="tap(() => submitVote(true))">Coba lagi</button>
        </div>
        <div class="buttons" x-show="confirm.state === 'error' && confirm.error !== 'network'">
            <button type="button" class="k-btn neutral" data-touch="back" @click="tap(() => closeOverlay())">Kembali</button>
        </div>
    </div>
</div>

{{-- ===================== SCR-K6 Terima Kasih ===================== --}}
<section class="k-full k-thanks" x-show="overlay === 'thanks'" x-cloak data-screen="K6" aria-live="assertive">
    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m7.5 12.5 3 3 6-7"/></svg>
    <h1>SUARAMU SUDAH TERCATAT</h1>
    <p style="font-size:4vh;font-weight:900;color:#fff">Terima kasih!</p>
    <p style="margin-top:2vh">Silakan kembali ke tempat dudukmu.</p>
    <p style="margin-top:4vh">Layar siap untuk siswa berikutnya dalam <span class="count" x-text="thanks.seconds"></span> detik</p>
    <div class="k-trial-band" x-show="thanks.trial">UJI COBA — suara ini tidak dihitung</div>
</section>

{{-- ===================== SCR-K7 Layar Status ===================== --}}
<section class="k-full" x-show="screen === 'status'" x-cloak data-screen="K7" style="justify-content:center">
    <span class="k-pill" style="position:absolute;top:4vh;left:4vh" x-show="station"><span class="dot" :style="statusVariant === 'offline' && 'background:var(--color-danger)'"></span><span x-text="station?.name"></span></span>
    <div class="k-header" style="position:absolute;top:3vh;right:4vh;height:auto">@include('kiosk._logo', ['size' => 9])</div>
    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 7v6m0 3.5v.5"/></svg>
    <h1 x-text="statusText.title" role="status"></h1>
    <p x-text="statusText.body"></p>
</section>

{{-- Pratinjau admin: penanda agar tidak dikira layar sungguhan. --}}
<div x-show="preview" x-cloak style="position:fixed;bottom:0;left:0;right:0;z-index:50;background:var(--color-warning);color:#fff;text-align:center;font-size:2vh;font-weight:800;padding:.6vh">
    PRATINJAU — suara tidak dikirim
</div>
</body>
</html>
