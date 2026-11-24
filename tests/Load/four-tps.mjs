/**
 * Uji 4 TPS sampai hasil (NF-22) — acceptance-criteria.md NF-22.
 *
 *   npm run test:4tps                    # 10 menit (bawaan)
 *   DURATION_SEC=30 npm run test:4tps    # uji singkat
 *
 * Alur:
 * 1. Database uji terpisah (APP_ENV=browser → database/browser.sqlite): 4 paslon, 4 TPS, tiap TPS satu IFP
 *    terhubung lewat kode aktivasi, pemungutan dibuka lewat alur sebenarnya.
 * 2. Empat proses PHP terpisah (port 8131–8134), satu per TPS, menulis ke SQLite yang sama — konkurensi nyata.
 * 3. Tiap "IFP" memakai API kiosk seperti IFP sungguhan: tanya status tiap 5 detik, kirim 1 suara per 2 detik
 *    (jeda dihitung dari jawaban sebelumnya, sesuai rem 2 detik per TPS), dan tiap suara ke-25 dikirim ulang
 *    dengan client_uuid yang sama (harus 200, bukan suara baru).
 * 4. Pemungutan ditutup, rekap server dicocokkan dengan hitungan di sisi IFP, berita acara dicetak ke PDF (Edge).
 * 5. Laporan: verification/hasil-uji-4tps.md (+ JSON & PDF di storage/app/uji-4tps/).
 */
import { spawn, execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync, existsSync, closeSync, openSync } from 'node:fs';
import { randomUUID } from 'node:crypto';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const DURATION_SEC = Number(process.env.DURATION_SEC ?? 600);
const INTERVAL_MS = 2000;
const STATUS_MS = 5000;
const RESEND_EVERY = 25;
const PORT_BASE = 8131;
const OUT = path.join(ROOT, 'storage/app/uji-4tps');
const ENV = { ...process.env, APP_ENV: 'browser' };
const EDGE = 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
// Bobot pilihan acak agar ada pemenang yang jelas: 01 35%, 02 30%, 03 20%, 04 15%.
const WEIGHTS = [35, 30, 20, 15];

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const log = (...a) => console.log(new Date().toISOString().slice(11, 19), ...a);

function writeBrowserEnv() {
    const db = path.join(ROOT, 'database', 'browser.sqlite').replaceAll('\\', '/');
    if (!existsSync(db)) closeSync(openSync(db, 'w'));
    let env = readFileSync(path.join(ROOT, '.env'), 'utf8');
    const set = (k, v) => {
        const re = new RegExp(`^${k}=.*$`, 'm');
        env = re.test(env) ? env.replace(re, `${k}=${v}`) : `${env}\n${k}=${v}`;
    };
    set('APP_ENV', 'browser');
    set('APP_DEBUG', 'false');
    set('DB_CONNECTION', 'sqlite');
    set('DB_DATABASE', db);
    set('CACHE_STORE', 'array');
    set('SESSION_DRIVER', 'array');
    set('LOG_LEVEL', 'error');
    writeFileSync(path.join(ROOT, '.env.browser'), env);
}

function fixture(...args) {
    const out = execFileSync('php', ['artisan', 'kiosk:fixture', ...args], { cwd: ROOT, env: ENV, encoding: 'utf8' });
    return JSON.parse(out.trim().split('\n').pop());
}

async function waitFor(url) {
    for (let i = 0; i < 100; i++) {
        try { if ((await fetch(url)).ok) return; } catch { /* belum siap */ }
        await sleep(100);
    }
    throw new Error(`Server tidak menyala: ${url}`);
}

function pickCandidate(candidates) {
    let r = Math.random() * WEIGHTS.reduce((a, b) => a + b, 0);
    for (let i = 0; i < candidates.length; i++) {
        r -= WEIGHTS[i];
        if (r <= 0) return candidates[i];
    }
    return candidates.at(-1);
}

async function call(base, token, method, url, body) {
    const t0 = performance.now();
    try {
        const res = await fetch(base + url, {
            method,
            headers: { Accept: 'application/json', Authorization: `Bearer ${token}`, ...(body ? { 'Content-Type': 'application/json' } : {}) },
            body: body ? JSON.stringify(body) : undefined,
            signal: AbortSignal.timeout(8000), // sama dengan batas waktu kiosk
        });
        const data = await res.json().catch(() => null);
        return { status: res.status, data, ms: performance.now() - t0 };
    } catch (e) {
        return { status: 0, data: { error: String(e) }, ms: performance.now() - t0 };
    }
}

/** Satu IFP virtual: status tiap 5 detik + suara tiap 2 detik sampai waktu habis. */
async function runIfp(name, base, token, candidates, deadline) {
    const s = { name, sent: 0, created: 0, resent: 0, resendOk: 0, statusCalls: 0, statusOk: 0, errors: {}, latency: [], expected: {} };
    const bump = (code) => { s.errors[code] = (s.errors[code] ?? 0) + 1; };

    const statusLoop = (async () => {
        while (Date.now() < deadline) {
            const r = await call(base, token, 'GET', '/api/kiosk/status');
            s.statusCalls++;
            if (r.status === 200) s.statusOk++; else bump(`status:${r.status}`);
            await sleep(STATUS_MS);
        }
    })();

    while (Date.now() < deadline) {
        const c = pickCandidate(candidates);
        const uuid = randomUUID();
        const r = await call(base, token, 'POST', '/api/kiosk/votes', { candidate_id: c.id, client_uuid: uuid });
        s.sent++;
        s.latency.push(r.ms);
        if (r.status === 201) {
            s.created++;
            s.expected[c.number] = (s.expected[c.number] ?? 0) + 1;
        } else {
            bump(`vote:${r.status}${r.data?.code ? ':' + r.data.code : ''}`);
        }

        if (r.status === 201 && s.sent % RESEND_EVERY === 0) {
            const again = await call(base, token, 'POST', '/api/kiosk/votes', { candidate_id: c.id, client_uuid: uuid });
            s.resent++;
            if (again.status === 200) s.resendOk++; else bump(`resend:${again.status}`);
        }
        await sleep(INTERVAL_MS); // jeda dari jawaban sebelumnya → selalu ≥ 2 detik di server (REQ-21)
    }
    await statusLoop;
    return s;
}

const pct = (arr, p) => {
    if (!arr.length) return 0;
    const sorted = [...arr].sort((a, b) => a - b);
    return sorted[Math.min(sorted.length - 1, Math.floor((p / 100) * sorted.length))];
};
const fmt = (n) => new Intl.NumberFormat('id-ID').format(n);
const ms = (n) => `${Math.round(n)} ms`;

async function main() {
    mkdirSync(OUT, { recursive: true });
    writeBrowserEnv();

    log('Menyiapkan database uji: 4 paslon, 4 TPS, IFP terhubung, pemungutan dibuka…');
    const setup = fixture('four-tps', '--candidates=4');
    const names = Object.keys(setup.tokens);

    const servers = names.map((_, i) => spawn('php', ['-S', `127.0.0.1:${PORT_BASE + i}`,
        path.join(ROOT, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
        { cwd: path.join(ROOT, 'public'), env: ENV, stdio: 'ignore' }));
    const stop = () => servers.forEach((s) => s.kill());
    process.on('exit', stop);

    try {
        await Promise.all(names.map((_, i) => waitFor(`http://127.0.0.1:${PORT_BASE + i}/up`)));
        log(`4 server PHP siap (port ${PORT_BASE}–${PORT_BASE + 3}). Pemungutan berjalan ${DURATION_SEC} detik…`);

        const startedAt = new Date();
        const deadline = Date.now() + DURATION_SEC * 1000;
        const ticker = setInterval(() => log(`… sisa ${Math.max(0, Math.round((deadline - Date.now()) / 1000))} detik`), 60000);
        const results = await Promise.all(names.map((name, i) =>
            runIfp(name, `http://127.0.0.1:${PORT_BASE + i}`, setup.tokens[name], setup.candidates, deadline)));
        clearInterval(ticker);
        const finishedAt = new Date();

        log('Menutup pemungutan dan mengambil rekap dari server…');
        fixture('close');
        const recap = fixture('recap');

        // ---------- pencocokan ----------
        const created = results.reduce((a, r) => a + r.created, 0);
        const expectedByCand = {};
        for (const r of results) for (const [n, v] of Object.entries(r.expected)) expectedByCand[n] = (expectedByCand[n] ?? 0) + v;

        const checks = [];
        const check = (label, ok, detail) => checks.push({ label, ok, detail });
        check('Total suara di server = jumlah jawaban 201 di sisi IFP', recap.total === created, `${fmt(recap.total)} vs ${fmt(created)}`);
        for (const c of recap.candidates) {
            check(`Paslon 0${c.number}: server = hitungan IFP`, c.votes === (expectedByCand[c.number] ?? 0), `${fmt(c.votes)} vs ${fmt(expectedByCand[c.number] ?? 0)}`);
        }
        for (const st of recap.stations) {
            const r = results.find((x) => x.name === st.name);
            check(`${st.name}: server = hitungan IFP`, st.total === (r?.created ?? 0), `${fmt(st.total)} vs ${fmt(r?.created ?? 0)}`);
        }
        const errorsByType = {};
        for (const r of results) for (const [k, v] of Object.entries(r.errors)) errorsByType[k] = (errorsByType[k] ?? 0) + v;
        const totalErrors = Object.values(errorsByType).reduce((a, v) => a + v, 0);
        check('Tidak ada galat (5xx, timeout, 429, dll.)', totalErrors === 0, totalErrors ? JSON.stringify(errorsByType) : '0');
        const resent = results.reduce((a, r) => a + r.resent, 0);
        const resendOk = results.reduce((a, r) => a + r.resendOk, 0);
        check('Kiriman ulang UUID sama → 200, tidak menambah suara', resent === resendOk, `${resendOk}/${resent}`);
        check('Tidak ada client_uuid ganda di database', recap.integrity.duplicate_uuids === 0, String(recap.integrity.duplicate_uuids));
        check('Baris votes = total rekap (tanpa suara uji coba)', recap.integrity.rows === recap.total && recap.integrity.trial_rows === 0, `${recap.integrity.rows} baris, ${recap.integrity.trial_rows} uji coba`);
        check('Tabel votes tanpa kolom identitas', JSON.stringify([...recap.integrity.votes_columns].sort()) === JSON.stringify(['candidate_id', 'client_uuid', 'created_at', 'election_id', 'id', 'is_trial', 'polling_station_id']), recap.integrity.votes_columns.join(', '));
        check('Pemungutan berstatus Ditutup', recap.status === 'closed', recap.status);

        // ---------- berita acara ----------
        const html = path.join(OUT, 'berita-acara.html');
        const pdf = path.join(OUT, 'berita-acara.pdf');
        fixture('berita-acara', html);
        let pdfOk = false;
        if (existsSync(EDGE)) {
            try {
                execFileSync(EDGE, ['--headless=new', '--disable-gpu', '--no-pdf-header-footer',
                    `--user-data-dir=${path.join(OUT, 'edge-profile')}`, `--print-to-pdf=${pdf}`, `file:///${html.replaceAll('\\', '/')}`],
                    { stdio: 'ignore', timeout: 60000 });
                pdfOk = existsSync(pdf);
            } catch { pdfOk = false; }
        }

        // ---------- laporan ----------
        const allLatency = results.flatMap((r) => r.latency);
        const passed = checks.every((c) => c.ok);
        const lines = [
            '# Hasil Uji 4 TPS (NF-22)',
            '',
            `- **Waktu:** ${startedAt.toISOString()} → ${finishedAt.toISOString()} (${DURATION_SEC} detik)`,
            '- **Lingkungan:** komputer pengembang (Windows), 4 proses `php -S` terpisah (satu per TPS), satu database SQLite (WAL, busy_timeout 5 detik, transaksi suara diawali perintah tulis), database uji `browser.sqlite` (bukan data lokal/produksi)',
            `- **Skenario:** 4 IFP virtual memakai API kiosk; status tiap ${STATUS_MS / 1000} detik; 1 suara per TPS setiap ${INTERVAL_MS / 1000} detik setelah jawaban sebelumnya (secepat yang diizinkan rem 2 detik, ±1 suara per 2,3 detik); kiriman ulang UUID sama tiap suara ke-${RESEND_EVERY}; pilihan acak berbobot ${WEIGHTS.join('/')}%`,
            `- **Kesimpulan:** ${passed ? '✅ **LOLOS** — semua suara tercatat tepat sekali, rekap server cocok dengan hitungan di IFP, tanpa galat' : '❌ **GAGAL** — lihat tabel pemeriksaan'}`,
            '',
            '## Hasil pemungutan (rekap resmi dari server)',
            '',
            `Total suara sah: **${fmt(recap.total)}** · Paslon terbanyak: **${recap.top === 'Suara sama' ? 'suara sama' : '0' + [...recap.candidates].sort((a, b) => b.votes - a.votes)[0].number}**`,
            '',
            '| Paslon | Suara | Persentase |',
            '|---|---:|---:|',
            ...recap.candidates.map((c) => `| 0${c.number} | ${fmt(c.votes)} | ${c.percent.toFixed(1).replace('.', ',')}% |`),
            '',
            '| TPS | ' + recap.candidates.map((c) => `0${c.number}`).join(' | ') + ' | Jumlah |',
            '|---|' + recap.candidates.map(() => '---:').join('|') + '|---:|',
            ...recap.stations.map((s) => `| ${s.name} | ` + setup.candidates.map((c) => fmt(s.votes[c.id] ?? 0)).join(' | ') + ` | **${fmt(s.total)}** |`),
            '',
            '## Pemeriksaan',
            '',
            '| Pemeriksaan | Hasil | Detail |',
            '|---|---|---|',
            ...checks.map((c) => `| ${c.label} | ${c.ok ? '✅' : '❌'} | ${c.detail} |`),
            '',
            '## Kinerja per TPS',
            '',
            '| TPS | Suara dikirim | Tercatat (201) | Kiriman ulang → 200 | Panggilan status OK | p50 | p95 | maks |',
            '|---|---:|---:|---:|---:|---:|---:|---:|',
            ...results.map((r) => `| ${r.name} | ${fmt(r.sent)} | ${fmt(r.created)} | ${r.resendOk}/${r.resent} | ${r.statusOk}/${r.statusCalls} | ${ms(pct(r.latency, 50))} | ${ms(pct(r.latency, 95))} | ${ms(Math.max(...r.latency))} |`),
            `| **Semua** | ${fmt(results.reduce((a, r) => a + r.sent, 0))} | ${fmt(created)} | ${resendOk}/${resent} | ${results.reduce((a, r) => a + r.statusOk, 0)}/${results.reduce((a, r) => a + r.statusCalls, 0)} | ${ms(pct(allLatency, 50))} | ${ms(pct(allLatency, 95))} | ${ms(Math.max(...allLatency))} |`,
            '',
            '## Berita acara',
            '',
            pdfOk ? '- Dicetak ke PDF: `storage/app/uji-4tps/berita-acara.pdf` (tidak di-commit).' : '- PDF tidak dibuat (Edge tidak ditemukan); HTML: `storage/app/uji-4tps/berita-acara.html`.',
            '',
            '## Catatan',
            '',
            '- Server uji adalah `php -S` di Windows, bukan nginx + php-fpm di VPS. Uji ini membuktikan logika & integritas data di bawah tulis bersamaan; kinerja di VPS diukur ulang saat deploy (CP7).',
            '- Uji tidak memakai layar IFP fisik; perilaku layar diuji terpisah di `tests/Browser/kiosk.test.mjs` dan nanti di IFP Hisense asli (CP6).',
            '',
        ];
        writeFileSync(path.join(ROOT, 'verification/hasil-uji-4tps.md'), lines.join('\n'));
        writeFileSync(path.join(OUT, 'hasil.json'), JSON.stringify({ setup: { candidates: setup.candidates, stations: names }, results, recap, checks }, null, 2));

        log(passed ? 'LOLOS' : 'GAGAL', `— total ${fmt(recap.total)} suara, galat ${totalErrors}. Laporan: verification/hasil-uji-4tps.md`);
        for (const c of checks) log(c.ok ? '✔' : '✖', c.label, '—', c.detail);
        process.exitCode = passed ? 0 : 1;
    } finally {
        stop();
    }
}

main().catch((e) => { console.error(e); process.exitCode = 1; });
