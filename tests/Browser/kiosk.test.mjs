/**
 * Test browser layar IFP — acceptance-criteria.md AC-40–AC-49, UX-01–UX-05, UX-K1–UX-K4.
 *
 * Jalankan:  npm run test:browser
 * - Memakai Microsoft Edge yang terpasang (playwright-core, channel "msedge"), tanpa unduh browser.
 * - Server uji terpisah di 127.0.0.1:8123 dengan APP_ENV=browser + database/browser.sqlite
 *   (berkas .env.browser dibuat otomatis dari .env) — DB lokal tidak disentuh.
 * - Skenario diatur lewat `php artisan kiosk:fixture` (routes/console.php, hanya di APP_ENV=browser).
 * - Screenshot bukti disimpan ke folder SHOTS (default: storage/app/browser-shots).
 */
import { test, before, after, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { spawn, execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync, existsSync, closeSync, openSync } from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright-core';

const ROOT = path.resolve(import.meta.dirname, '../..');
const PORT = 8123;
const BASE = `http://127.0.0.1:${PORT}`;
const SHOTS = process.env.SHOTS ?? path.join(ROOT, 'storage/app/browser-shots');
const ENV = { ...process.env, APP_ENV: 'browser' };

let server;
let browser;
let page;

// ---------- persiapan ----------
function writeBrowserEnv() {
    const db = path.join(ROOT, 'database', 'browser.sqlite').replaceAll('\\', '/');
    if (!existsSync(db)) closeSync(openSync(db, 'w'));
    let env = readFileSync(path.join(ROOT, '.env'), 'utf8');
    const set = (key, value) => {
        const line = `${key}=${value}`;
        env = new RegExp(`^${key}=.*$`, 'm').test(env) ? env.replace(new RegExp(`^${key}=.*$`, 'm'), line) : `${env}\n${line}`;
    };
    set('APP_ENV', 'browser');
    set('APP_URL', BASE);
    set('DB_CONNECTION', 'sqlite');
    set('DB_DATABASE', db);
    set('CACHE_STORE', 'array');
    set('SESSION_DRIVER', 'array');
    writeFileSync(path.join(ROOT, '.env.browser'), env);
}

function fixture(...args) {
    const out = execFileSync('php', ['artisan', 'kiosk:fixture', ...args], { cwd: ROOT, env: ENV, encoding: 'utf8' });
    const last = out.trim().split('\n').pop();
    return JSON.parse(last);
}

async function waitForServer() {
    for (let i = 0; i < 50; i++) {
        try {
            const res = await fetch(`${BASE}/up`);
            if (res.ok) return;
        } catch { /* belum siap */ }
        await new Promise((r) => setTimeout(r, 200));
    }
    throw new Error('Server uji tidak menyala');
}

before(async () => {
    writeBrowserEnv();
    mkdirSync(SHOTS, { recursive: true });
    // server.php Laravel membaca index.php dari direktori kerja → jalankan dari folder public (seperti `artisan serve`).
    server = spawn('php', ['-S', `127.0.0.1:${PORT}`, path.join(ROOT, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
        cwd: path.join(ROOT, 'public'), env: ENV, stdio: 'ignore',
    });
    await waitForServer();
    browser = await chromium.launch({ channel: 'msedge', headless: true });
});

after(async () => {
    await browser?.close();
    server?.kill();
});

beforeEach(async () => {
    await page?.context().close();
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 1 });
    page = await context.newPage();
});

// ---------- bantuan ----------
const screenOf = (id) => page.locator(`[data-screen="${id}"]`);
const visible = (id, timeout = 3000) => screenOf(id).waitFor({ state: 'visible', timeout });
const hidden = (id, timeout = 3000) => screenOf(id).waitFor({ state: 'hidden', timeout });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** Buka kiosk dengan token tersimpan, lalu tekan Mulai. */
async function openKiosk(token, { start = true } = {}) {
    await page.addInitScript((t) => window.localStorage.setItem('evoting.kiosk.token', t), token);
    await page.goto(`${BASE}/kiosk`);
    await visible('K2');
    if (start) {
        await page.locator('[data-action="start"]').waitFor({ state: 'visible', timeout: 6000 });
        await page.click('[data-action="start"]');
        await sleep(450); // sentuhan siswa < 400 ms setelah sentuhan sebelumnya memang diabaikan (SPEC §5.5)
    }
}

const card = (label) => page.locator(`[data-candidate="${label}"]`);

async function shot(name) {
    await page.screenshot({ path: path.join(SHOTS, `${name}.png`) });
}

// ===================================================================
test('UX-01 / AC-40 aktivasi: kode salah ditolak, kode benar menyimpan TPS walau dimuat ulang', async () => {
    const { code } = fixture('reset');
    await page.goto(`${BASE}/kiosk`);
    await visible('K1');
    await shot('K1-aktivasi-1920');

    const go = page.locator('[data-action="activate"]');
    assert.equal(await go.isDisabled(), true, 'Hubungkan nonaktif sebelum 6 angka');

    // Keypad dipakai guru: tidak terkena jeda 400 ms, jadi diketik cepat.
    for (const d of '000000') await page.click(`button[aria-label="Angka ${d}"]`);
    await go.click();
    await page.getByText('Kode tidak dikenal atau sudah kedaluwarsa. Minta kode baru ke panitia.').waitFor();

    for (const d of code) await page.click(`button[aria-label="Angka ${d}"]`);
    await go.click();
    await visible('K2', 5000);
    await page.getByText('TPS Lab').first().waitFor();

    await page.reload();
    await visible('K2');
    await page.getByText('TPS Lab').first().waitFor();
    assert.equal(await screenOf('K1').isVisible(), false, 'tidak meminta kode lagi');
});

test('UX-02 SCR-K2 server tak terjangkau → teks gagal, lalu pulih otomatis', async () => {
    const { token } = fixture('reset');
    await page.route('**/api/kiosk/status', (r) => r.abort());
    await openKiosk(token, { start: false });
    await page.getByText('Tidak bisa terhubung ke server. Periksa WiFi IFP. Layar akan mencoba lagi otomatis.').waitFor({ timeout: 8000 });
    await page.unroute('**/api/kiosk/status');
    await page.locator('[data-action="start"]').waitFor({ state: 'visible', timeout: 8000 });
});

test('AC-41 semua paslon terlihat utuh tanpa scroll (2, 3, 4, 6 paslon; 1920×1080 dan 3840×2160)', async () => {
    for (const n of [2, 3, 4, 6]) {
        const { token } = fixture('reset', `--candidates=${n}`);
        for (const [w, h] of [[1920, 1080], [3840, 2160]]) {
            await page.setViewportSize({ width: w, height: h });
            await openKiosk(token);
            await visible('K3', 6000);
            await page.locator('.k-card').nth(n - 1).waitFor();
            const res = await page.evaluate(() => ({
                scroll: document.scrollingElement.scrollHeight - window.innerHeight,
                scrollX: document.scrollingElement.scrollWidth - window.innerWidth,
                cards: [...document.querySelectorAll('.k-card')].map((c) => {
                    const r = c.getBoundingClientRect();
                    return { top: r.top, bottom: r.bottom, left: r.left, right: r.right };
                }),
                vw: window.innerWidth, vh: window.innerHeight,
            }));
            assert.ok(res.scroll <= 0 && res.scrollX <= 0, `tanpa scroll (${n} paslon, ${w}×${h})`);
            assert.equal(res.cards.length, n);
            for (const c of res.cards) {
                assert.ok(c.top >= 0 && c.bottom <= res.vh && c.left >= 0 && c.right <= res.vw, `kartu utuh (${n} paslon, ${w}×${h})`);
            }
            await shot(`K3-${n}paslon-${w}`);
        }
        await page.setViewportSize({ width: 1920, height: 1080 });
    }
});

test('AC-42 mode 2x sentuh: sentuh foto → konfirmasi tanpa suara; Batal kembali tanpa suara', async () => {
    const { token } = fixture('reset');
    await openKiosk(token);
    await visible('K3', 6000);
    await card('02').locator('[data-touch="photo"]').click();
    await visible('K5');
    await page.getByText('Yakin memilih paslon ini?').waitFor();
    await shot('K5-konfirmasi-1920');
    assert.deepEqual(fixture('votes'), { total: 0, trial: 0 });

    await sleep(450);
    await page.click('[data-screen="K5"] [data-touch="cancel"]');
    await hidden('K5');
    await visible('K3');
    assert.deepEqual(fixture('votes'), { total: 0, trial: 0 });
});

test('AC-43 mode 1x sentuh: sentuhan langsung mencatat suara tanpa konfirmasi', async () => {
    const { token } = fixture('reset', '--mode=instant');
    await openKiosk(token);
    await visible('K3', 6000);
    await card('01').locator('[data-touch="choose"]').click();
    await visible('K6', 5000);
    assert.equal(await page.getByText('Yakin memilih paslon ini?').isVisible(), false);
    assert.equal(fixture('votes').total, 1);
});

test('AC-44 tombol YA belum aktif < 600 ms; sentuhan ganda cepat = 1 permintaan, 1 UUID', async () => {
    const { token } = fixture('reset');
    const posts = [];
    page.on('request', (r) => { if (r.url().endsWith('/api/kiosk/votes') && r.method() === 'POST') posts.push(r.postDataJSON()); });
    await openKiosk(token);
    await visible('K3', 6000);

    await card('03').locator('[data-touch="choose"]').click();
    await visible('K5');
    const yes = page.locator('[data-screen="K5"] [data-touch="yes"]');
    assert.equal(await yes.isDisabled(), true, 'YA nonaktif tepat setelah konfirmasi muncul');
    await yes.click({ force: true, timeout: 500 }).catch(() => {});
    assert.equal(posts.length, 0);

    await sleep(700);
    await yes.dblclick();
    await visible('K6', 5000);
    await sleep(300);
    assert.equal(posts.length, 1, 'hanya satu permintaan');
    assert.equal(fixture('votes').total, 1);
});

test('AC-45 / UX-02 server gagal → "Suara belum tercatat.", tanpa layar terima kasih; Coba lagi memakai UUID sama', async () => {
    const { token } = fixture('reset');
    const bodies = [];
    await openKiosk(token);
    await visible('K3', 6000);
    await page.route('**/api/kiosk/votes', (r) => { bodies.push(r.request().postDataJSON()); return r.abort(); });

    await card('01').locator('[data-touch="choose"]').click();
    await sleep(700);
    await page.click('[data-screen="K5"] [data-touch="yes"]');
    const started = Date.now();
    await page.getByText('Suara belum tercatat.').waitFor({ timeout: 8500 });
    assert.ok(Date.now() - started <= 8500);
    await page.getByText('Koneksi ke server terputus. Jangan pergi dulu — panggil guru pengawas.').waitFor();
    await shot('K5-gagal-1920');
    assert.equal(await screenOf('K6').isVisible(), false);
    assert.equal(fixture('votes').total, 0);

    await page.unroute('**/api/kiosk/votes');
    const retried = [];
    page.on('request', (r) => { if (r.url().endsWith('/api/kiosk/votes')) retried.push(r.postDataJSON()); });
    await sleep(450);
    await page.click('[data-screen="K5"] [data-touch="retry"]');
    await visible('K6', 5000);
    assert.equal(retried[0].client_uuid, bodies[0].client_uuid, 'UUID sama saat Coba lagi');
    assert.equal(fixture('votes').total, 1);
});

test('AC-46 / REQ-18 layar terima kasih tanpa nama paslon, kembali setelah 3 ± 0,5 detik, sentuhan diabaikan', async () => {
    const { token } = fixture('reset', '--delay=3');
    await openKiosk(token);
    await visible('K3', 6000);
    await card('02').locator('[data-touch="choose"]').click();
    await sleep(700);
    await page.click('[data-screen="K5"] [data-touch="yes"]');
    await visible('K6', 5000);
    const shownAt = Date.now();
    await shot('K6-terima-kasih-1920');

    const text = await screenOf('K6').innerText();
    assert.ok(!/Dimas|Citra|02/.test(text), 'tidak menyebut paslon');
    await page.mouse.click(960, 900); // sentuhan saat layar terima kasih
    await hidden('K6', 5000);
    const elapsed = (Date.now() - shownAt) / 1000;
    assert.ok(elapsed >= 2.5 && elapsed <= 3.6, `durasi ${elapsed}s`);
    await visible('K3');
    assert.equal(fixture('votes').total, 1);
});

test('AC-47 / UX-05 perubahan status sampai ke IFP ≤ 10 detik; semua varian layar status', async () => {
    const { token } = fixture('reset', '--status=preparation');
    await openKiosk(token);
    await visible('K7', 6000);
    await page.getByText('Pemungutan suara belum dibuka').waitFor();
    await shot('K7-belum-dibuka-1920');

    fixture('status', 'open');
    await visible('K3', 10500);

    fixture('status', 'closed');
    await page.getByText('Pemungutan suara sudah ditutup').waitFor({ timeout: 10500 });
    await page.getByText('Terima kasih atas partisipasinya.').waitFor();

    fixture('status', 'open');
    fixture('station-active', '0');
    await page.getByText('TPS ini sedang tidak aktif').waitFor({ timeout: 10500 });
    fixture('station-active', '1');
    await visible('K3', 10500);

    await page.route('**/api/kiosk/status', (r) => r.abort());
    await screenOf('K7').getByText('Koneksi ke server terputus', { exact: true }).waitFor({ timeout: 10500 });
    await shot('K7-koneksi-putus-1920');
    await page.unroute('**/api/kiosk/status');
    await visible('K3', 10500);
});

test('AC-40 IFP yang dilepas kembali ke layar aktivasi dengan pesan', async () => {
    const { token } = fixture('reset');
    await openKiosk(token);
    await visible('K3', 6000);
    fixture('release');
    await visible('K1', 10500);
    await page.getByText('IFP ini sudah dilepas dari TPS. Masukkan kode aktivasi baru.').waitFor();
});

test('AC-48 Visi & Misi menampilkan visi + semua misi; PILIH ke konfirmasi paslon itu; KEMBALI ke daftar', async () => {
    const { token } = fixture('reset');
    await openKiosk(token);
    await visible('K3', 6000);
    await card('02').locator('[data-touch="vision"]').click();
    await visible('K4');
    await page.getByText('Mewujudkan OSIS sebagai wadah aspirasi').first().waitFor();
    assert.equal(await screenOf('K4').locator('li').count(), 3);
    await shot('K4-visi-misi-1920');

    await sleep(450);
    await page.click('[data-screen="K4"] [data-touch="back"]');
    await visible('K3');

    await sleep(450);
    await card('02').locator('[data-touch="vision"]').click();
    await sleep(450);
    await page.click('[data-screen="K4"] [data-touch="choose-from-vision"]');
    await visible('K5');
    assert.match(await screenOf('K5').innerText(), /Dimas Anggara/);
});

test('AC-49 jumlah suara masuk naik, tidak ada angka per paslon; bel & confetti bisa dimatikan', async () => {
    const { token } = fixture('reset', '--sound=0', '--confetti=0');
    await openKiosk(token);
    await visible('K3', 6000);
    await page.locator('[data-testid="vote-count"]').getByText('0').waitFor();

    await card('01').locator('[data-touch="choose"]').click();
    await sleep(700);
    await page.click('[data-screen="K5"] [data-touch="yes"]');
    await visible('K6', 5000);
    assert.equal(await page.locator('canvas').count(), 0, 'confetti tidak muncul');
    await visible('K3', 5000);
    await page.locator('[data-testid="vote-count"]').getByText('1').waitFor();

    const cardsText = await page.locator('.k-grid').innerText();
    assert.ok(!/\bsuara\b/i.test(cardsText), 'kartu paslon tidak memuat angka suara');
});

test('UX-03 foto gagal dimuat → kotak warna "PASLON 01"', async () => {
    const { token } = fixture('reset');
    fixture('break-photo');
    await openKiosk(token);
    await visible('K3', 6000);
    await card('01').getByText('PASLON 01').waitFor();
});

test('AC-58 logo dari Pengaturan tampil di kepala layar IFP ≤ 10 detik; tanpa logo tampil "OSIS"', async () => {
    const { token } = fixture('reset');
    await openKiosk(token);
    await visible('K3', 6000);
    const header = screenOf('K3').locator('[data-testid="kiosk-logo"]');
    await header.getByText('OSIS').waitFor();

    fixture('logo');
    await header.locator('img[alt="Logo sekolah"]').waitFor({ state: 'visible', timeout: 10500 });
    const loaded = await header.locator('img').evaluate((img) => img.complete && img.naturalWidth > 0);
    assert.equal(loaded, true, 'gambar logo termuat');
    assert.equal(await header.getByText('OSIS').isVisible(), false);
    await shot('K3-dengan-logo-1920');
});

test('UX-04 lapisan tertutup sendiri setelah 30 detik tanpa sentuhan', async () => {
    const { token } = fixture('reset');
    await page.clock.install();
    await openKiosk(token);
    await visible('K3', 6000);
    await card('01').locator('[data-touch="vision"]').click();
    await visible('K4');
    await page.clock.fastForward(31000);
    await hidden('K4');

    await sleep(450);
    await card('02').locator('[data-touch="choose"]').click();
    await visible('K5');
    await page.clock.fastForward(31000);
    await hidden('K5');
    assert.equal(fixture('votes').total, 0);
});

test('UX-K1 / UX-K2 / UX-K3 ergonomi 75": pita sentuh 35–95%, ukuran tombol & teks', async () => {
    const { token, code } = fixture('reset', '--candidates=6');

    const measure = () => page.evaluate(() => {
        const vh = window.innerHeight / 100;
        const targets = [...document.querySelectorAll('[data-touch], .k-keypad .k-btn')]
            .filter((el) => el.offsetParent !== null)
            .map((el) => {
                const r = el.getBoundingClientRect();
                return { name: el.dataset.touch ?? el.textContent.trim(), top: r.top / vh, bottom: r.bottom / vh, h: r.height / vh, w: r.width / vh, isButton: el.tagName === 'BUTTON' };
            });
        return { targets };
    });
    const checkBand = (targets, where) => {
        assert.ok(targets.length > 0, `ada target di ${where}`);
        for (const t of targets) {
            assert.ok(t.top >= 35 - 0.01 && t.bottom <= 95 + 0.01, `${where}: "${t.name}" di ${t.top.toFixed(1)}–${t.bottom.toFixed(1)}%`);
            if (t.isButton) assert.ok(t.h >= 9 - 0.01 && t.w >= 12 - 0.01, `${where}: "${t.name}" ${t.h.toFixed(1)}×${t.w.toFixed(1)}vh`);
        }
    };

    // K1 keypad
    await page.goto(`${BASE}/kiosk`);
    await visible('K1');
    checkBand((await measure()).targets, 'K1');

    // K3, K4, K5
    await page.evaluate(() => localStorage.clear());
    await openKiosk(token);
    await visible('K3', 6000);
    checkBand((await measure()).targets, 'K3');

    const fonts = await page.evaluate(() => {
        const vh = window.innerHeight / 100;
        const px = (sel) => parseFloat(getComputedStyle(document.querySelector(sel)).fontSize) / vh;
        return { names: px('.k-card .names'), body: px('.k-card .vision'), number: px('.k-number') };
    });
    assert.ok(fonts.names >= 3.5 - 0.01, `nama ${fonts.names}vh`);
    assert.ok(fonts.body >= 2.2 - 0.01, `badan ${fonts.body}vh`);
    assert.ok(fonts.number >= 6 - 0.01, `nomor ${fonts.number}vh`);

    await card('01').locator('[data-touch="vision"]').click();
    await visible('K4');
    checkBand((await measure()).targets, 'K4');
    await sleep(450);
    await page.click('[data-screen="K4"] [data-touch="choose-from-vision"]');
    await visible('K5');
    await sleep(700);
    const k5 = (await measure()).targets.filter((t) => ['cancel', 'yes'].includes(t.name));
    checkBand(k5, 'K5');
    const gap = await page.evaluate(() => {
        const vh = window.innerHeight / 100;
        const [a, b] = [...document.querySelectorAll('[data-screen="K5"] .buttons')][0].querySelectorAll('button');
        return (b.getBoundingClientRect().left - a.getBoundingClientRect().right) / vh;
    });
    assert.ok(gap >= 10 - 0.01, `jarak BATAL–YA ${gap.toFixed(1)}vh`);
});

test('UX-K4 sentuhan dua jari / telapak tidak membuka konfirmasi maupun mengirim suara', async () => {
    const { token } = fixture('reset', '--mode=instant');
    await openKiosk(token);
    await visible('K3', 6000);
    await sleep(450);
    const opened = await page.evaluate(() => {
        const target = document.querySelector('[data-candidate="01"] [data-touch="photo"]');
        const ev = (type, id) => new PointerEvent(type, { pointerId: id, bubbles: true, isPrimary: id === 1, pointerType: 'touch' });
        target.dispatchEvent(ev('pointerdown', 1));
        target.dispatchEvent(ev('pointerdown', 2));
        target.click();
        target.dispatchEvent(ev('pointerup', 2));
        target.dispatchEvent(ev('pointerup', 1));
        return !!document.querySelector('[data-screen="K5"]').offsetParent;
    });
    await sleep(600);
    assert.equal(opened, false);
    assert.equal(await screenOf('K6').isVisible(), false);
    assert.equal(fixture('votes').total, 0);

    // Pembanding: sentuhan satu jari di tempat yang sama tetap bekerja.
    await card('01').locator('[data-touch="photo"]').click();
    await visible('K6', 5000);
    assert.equal(fixture('votes').total, 1);
});

test('Bukti: screenshot SCR-K1, K2, K6, K7 di 3840×2160', async () => {
    const { token } = fixture('reset', '--status=preparation');
    await page.setViewportSize({ width: 3840, height: 2160 });
    await page.goto(`${BASE}/kiosk`);
    await visible('K1');
    await shot('K1-aktivasi-3840');
    await openKiosk(token, { start: false });
    await page.locator('[data-action="start"]').waitFor({ state: 'visible', timeout: 6000 });
    await shot('K2-siap-3840');
    await page.click('[data-action="start"]');
    await visible('K7', 6000);
    await shot('K7-belum-dibuka-3840');
});
