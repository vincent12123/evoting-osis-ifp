/**
 * Layar IFP (kiosk) — SPEC §5.4–§5.5, kartu SCR-K1–K7 (docs/ux-flow.md).
 *
 * Aturan penting:
 * - Suara sah hanya setelah server membalas sukses (REQ-09); tidak ada antrean offline (REQ-10).
 * - Satu sentuhan "YA" = satu client_uuid; "Coba lagi" mengirim UUID yang sama (REQ-11).
 * - Hanya sentuhan pertama diproses, jeda 400 ms, multi-sentuh diabaikan, tombol YA aktif setelah 600 ms.
 * - Lapisan tertutup sendiri setelah 30 detik tanpa sentuhan.
 */
import Alpine from 'alpinejs';
import confetti from 'canvas-confetti';

const TOKEN_KEY = 'evoting.kiosk.token';
const POLL_MS = 5000;
const STATUS_TIMEOUT_MS = 4000;
const VOTE_TIMEOUT_MS = 8000;
const TAP_DEBOUNCE_MS = 400;
const ARM_DELAY_MS = 600;
const IDLE_MS = 30000;

/*
 * --kvh = 1% dari tinggi area yang BENAR-BENAR terlihat. Di browser Android (IFP), 1vh dihitung dari tinggi
 * layar tanpa bilah alamat, padahal layar kode (SCR-K1) tampil sebelum layar penuh → bawahnya tertutup bilah.
 * Layar SCR-K1 memakai var(--kvh) agar seluruh susunannya mengecil mengikuti area yang terlihat.
 */
function syncVisibleHeight() {
    document.documentElement.style.setProperty('--kvh', `${window.innerHeight / 100}px`);
}
syncVisibleHeight();
window.addEventListener('resize', syncVisibleHeight);
window.visualViewport?.addEventListener('resize', syncVisibleHeight);

const STATUS_TEXT = {
    preparation: {
        title: 'Pemungutan suara belum dibuka',
        body: 'Mohon tunggu. Layar akan terbuka otomatis saat panitia membuka pemungutan.',
    },
    closed: { title: 'Pemungutan suara sudah ditutup', body: 'Terima kasih atas partisipasinya.' },
    inactive: { title: 'TPS ini sedang tidak aktif', body: 'Hubungi panitia.' },
    offline: {
        title: 'Koneksi ke server terputus',
        body: 'Guru pengawas: periksa WiFi IFP atau hubungi panitia. Layar akan tersambung lagi otomatis.',
    },
};

const MESSAGES = {
    invalidCode: 'Kode tidak dikenal atau sudah kedaluwarsa. Minta kode baru ke panitia.',
    activationNetwork: 'Tidak bisa terhubung ke server. Periksa WiFi IFP lalu coba lagi.',
    tooManyAttempts: 'Terlalu banyak percobaan. Coba lagi dalam 10 menit.',
    released: 'IFP ini sudah dilepas dari TPS. Masukkan kode aktivasi baru.',
};

// ---- penyimpanan lokal aman (bisa diblokir browser) ----
const storage = {
    get(key) { try { return window.localStorage.getItem(key); } catch { return null; } },
    set(key, value) { try { window.localStorage.setItem(key, value); } catch { /* abaikan */ } },
    remove(key) { try { window.localStorage.removeItem(key); } catch { /* abaikan */ } },
};

function uuid() {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }
    const b = window.crypto.getRandomValues(new Uint8Array(16));
    b[6] = (b[6] & 0x0f) | 0x40;
    b[8] = (b[8] & 0x3f) | 0x80;
    const h = [...b].map((x) => x.toString(16).padStart(2, '0')).join('');
    return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20)}`;
}

async function fetchJson(url, { method = 'GET', token, body, timeout = STATUS_TIMEOUT_MS } = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeout);
    try {
        const res = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                ...(body ? { 'Content-Type': 'application/json' } : {}),
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
            signal: controller.signal,
            cache: 'no-store',
        });
        let data = null;
        try { data = await res.json(); } catch { data = null; }
        return { ok: res.ok, status: res.status, data };
    } catch {
        return { ok: false, status: 0, data: null }; // jaringan putus / timeout
    } finally {
        clearTimeout(timer);
    }
}

// ---- bunyi (Web Audio, tanpa berkas MP3) ----
let audioCtx = null;
function unlockAudio() {
    try {
        audioCtx ??= new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();
    } catch { audioCtx = null; }
}
function chime() {
    if (!audioCtx) return;
    const now = audioCtx.currentTime;
    [523.25, 659.25, 783.99, 1046.5].forEach((freq, i) => {
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(freq, now + i * 0.09);
        gain.gain.setValueAtTime(0.22, now + i * 0.09);
        gain.gain.exponentialRampToValueAtTime(0.008, now + i * 0.09 + 0.35);
        osc.connect(gain).connect(audioCtx.destination);
        osc.start(now + i * 0.09);
        osc.stop(now + i * 0.09 + 0.36);
    });
}

window.kiosk = (config) => ({
    preview: Boolean(config.preview),
    api: config.api,

    screen: 'boot', // activation | ready | vote | status
    overlay: null, // vision | confirm | thanks
    statusVariant: 'preparation',

    token: null,
    online: null,
    station: null,
    election: null,
    settingsVersion: null,
    candidates: [],
    candidatesLoading: false,
    brokenPhotos: {},

    code: '',
    activation: { busy: false, error: null },
    ready: { state: 'checking' },
    started: false,

    selected: null,
    pressedId: null,
    confirm: { state: 'ask', armed: false, error: null, message: '', uuid: null },
    thanks: { seconds: 3, trial: false },

    // sentuhan
    lastTapAt: 0,
    activePointers: new Set(),
    multiTouch: false,
    idleTimer: null,
    armTimer: null,
    thanksTimer: null,
    pollTimer: null,

    get statusText() {
        return STATUS_TEXT[this.statusVariant] ?? STATUS_TEXT.preparation;
    },

    init() {
        // Lacak semua jari/telapak: sentuhan saat > 1 titik aktif diabaikan (UX-K4).
        document.addEventListener('pointerdown', (e) => {
            this.activePointers.add(e.pointerId);
            if (this.activePointers.size > 1) this.multiTouch = true;
            this.resetIdle();
        }, { capture: true });
        const release = (e) => {
            this.activePointers.delete(e.pointerId);
            if (this.activePointers.size === 0) setTimeout(() => { this.multiTouch = false; }, 50);
        };
        document.addEventListener('pointerup', release, { capture: true });
        document.addEventListener('pointercancel', release, { capture: true });
        document.addEventListener('gesturestart', (e) => e.preventDefault()); // cubit-zoom Safari/Android lama

        if (this.preview) {
            this.station = config.station;
            this.election = config.election;
            this.candidates = config.candidates;
            this.screen = 'vote';
            return;
        }

        this.token = storage.get(TOKEN_KEY);
        if (!this.token) {
            this.screen = 'activation';
            return;
        }
        this.screen = 'ready';
        this.poll();
    },

    // ---------- sentuhan ----------
    /** Penjaga semua sentuhan siswa: jeda 400 ms, abaikan multi-sentuh, abaikan saat layar terima kasih. */
    tap(action, event) {
        const now = Date.now();
        if (this.multiTouch || this.activePointers.size > 1) return;
        if (this.overlay === 'thanks') return;
        if (now - this.lastTapAt < TAP_DEBOUNCE_MS) return;
        this.lastTapAt = now;
        event?.stopPropagation?.();
        action();
    },

    resetIdle() {
        clearTimeout(this.idleTimer);
        if (this.overlay === 'vision' || (this.overlay === 'confirm' && this.confirm.state !== 'saving')) {
            this.idleTimer = setTimeout(() => this.closeOverlay(), IDLE_MS);
        }
    },

    // ---------- SCR-K1 ----------
    typeDigit(d) {
        if (this.code.length < 6) this.code += d;
        this.activation.error = null;
    },

    async activate() {
        if (this.code.length !== 6 || this.activation.busy) return;
        this.activation.busy = true;
        this.activation.error = null;
        const res = await fetchJson(this.api.activate, { method: 'POST', body: { code: this.code } });
        this.activation.busy = false;

        if (res.ok && res.data?.token) {
            this.token = res.data.token;
            storage.set(TOKEN_KEY, this.token);
            this.station = res.data.station;
            this.code = '';
            this.screen = 'ready';
            this.ready.state = 'checking';
            this.poll();
            return;
        }
        this.code = '';
        this.activation.error = res.status === 0
            ? MESSAGES.activationNetwork
            : res.status === 429 ? MESSAGES.tooManyAttempts : MESSAGES.invalidCode;
    },

    deviceReleased() {
        storage.remove(TOKEN_KEY);
        this.token = null;
        this.started = false;
        this.overlay = null;
        clearTimeout(this.pollTimer);
        this.screen = 'activation';
        this.activation.error = MESSAGES.released;
    },

    // ---------- SCR-K2 ----------
    start() {
        unlockAudio();
        try { document.documentElement.requestFullscreen?.().catch(() => {}); } catch { /* abaikan */ }
        this.started = true;
        this.applyStatus();
    },

    // ---------- status & polling ----------
    async poll() {
        clearTimeout(this.pollTimer);
        if (!this.token) return;

        const res = await fetchJson(this.api.status, { token: this.token });

        if (res.status === 401) {
            this.deviceReleased();
            return;
        }

        if (res.ok && res.data) {
            this.station = res.data.station;
            this.election = res.data.election;
            this.online = true;
            if (this.screen === 'ready') this.ready.state = 'ok';

            const version = this.election?.settings_version ?? null;
            if (version !== null && version !== this.settingsVersion && !this.overlay) {
                await this.loadCandidates(version);
            }
        } else {
            this.online = false;
            if (this.screen === 'ready') this.ready.state = 'failed';
        }

        if (this.started) this.applyStatus();
        this.pollTimer = setTimeout(() => this.poll(), POLL_MS);
    },

    async loadCandidates(version) {
        this.candidatesLoading = true;
        const res = await fetchJson(this.api.candidates, { token: this.token });
        this.candidatesLoading = false;
        if (res.ok && res.data) {
            this.candidates = res.data.candidates ?? [];
            this.settingsVersion = version;
            this.brokenPhotos = {};
        }
    },

    /** Pilih layar dasar dari status server. Lapisan yang sedang terbuka tidak diputus. */
    applyStatus() {
        let variant = null;
        if (this.online === false) variant = 'offline';
        else if (!this.station?.is_active) variant = 'inactive';
        else if (!this.election || this.election.status === 'preparation') variant = 'preparation';
        else if (this.election.status === 'closed') variant = 'closed';

        if (variant) {
            this.statusVariant = variant;
            // Siswa yang sedang di konfirmasi tetap melihat hasil kirimnya; lapisan lain ditutup.
            if (this.overlay === 'vision' || (this.overlay === 'confirm' && this.confirm.state === 'ask')) {
                this.closeOverlay();
            }
            if (!this.overlay) this.screen = 'status';
            return;
        }
        if (this.screen !== 'vote') this.screen = 'vote';
    },

    // ---------- SCR-K3 / K4 ----------
    flashPressed(c) {
        this.pressedId = c.id;
        setTimeout(() => { if (this.pressedId === c.id) this.pressedId = null; }, 160);
    },

    openVision(c) {
        this.selected = c;
        this.overlay = 'vision';
        this.resetIdle();
    },

    choose(c) {
        if (!c) return;
        this.flashPressed(c);
        this.selected = c;
        this.confirm = { state: 'ask', armed: false, error: null, message: '', uuid: null };

        if (this.election?.touch_mode === 'instant') {
            this.overlay = 'confirm';
            this.submitVote();
            return;
        }

        this.overlay = 'confirm';
        clearTimeout(this.armTimer);
        // Tombol YA baru aktif 600 ms kemudian: sentuhan ganda tidak tembus jadi suara (AC-44).
        this.armTimer = setTimeout(() => { this.confirm.armed = true; }, ARM_DELAY_MS);
        this.resetIdle();
    },

    closeOverlay() {
        clearTimeout(this.idleTimer);
        clearTimeout(this.armTimer);
        if (this.overlay === 'confirm' && this.confirm.state === 'saving') return;
        this.overlay = null;
        this.selected = null;
        if (this.started) this.applyStatus();
    },

    // ---------- SCR-K5 kirim suara ----------
    async submitVote(retry = false) {
        if (!this.selected || this.confirm.state === 'saving') return;
        if (!retry || !this.confirm.uuid) this.confirm.uuid = uuid();

        this.confirm.state = 'saving';
        clearTimeout(this.idleTimer);

        if (this.preview) {
            setTimeout(() => this.showThanks(false), 300);
            return;
        }

        const res = await fetchJson(this.api.votes, {
            method: 'POST',
            token: this.token,
            body: { candidate_id: this.selected.id, client_uuid: this.confirm.uuid },
            timeout: VOTE_TIMEOUT_MS,
        });

        if (res.ok) {
            if (this.station && typeof res.data?.vote_count === 'number') this.station.vote_count = res.data.vote_count;
            this.showThanks(this.election?.status === 'trial');
            return;
        }

        if (res.status === 401) {
            this.deviceReleased();
            return;
        }

        const code = res.data?.code;
        this.confirm.state = 'error';
        if (res.status === 0 || res.status >= 500) {
            this.confirm.error = 'network'; // UUID dipertahankan untuk "Coba lagi"
        } else {
            this.confirm.error = code ?? 'unknown';
            this.confirm.message = res.data?.message ?? 'Suara belum tercatat. Panggil guru pengawas.';
            if (code === 'invalid_candidate') this.settingsVersion = null; // muat ulang paslon pada polling berikutnya
            if (code === 'station_inactive' || code === 'election_not_open') this.poll();
        }
        this.resetIdle();
    },

    // ---------- SCR-K6 ----------
    showThanks(trial) {
        this.overlay = 'thanks';
        this.selected = null;
        this.thanks = { seconds: this.election?.reset_delay_seconds ?? 3, trial };
        this.confirm.state = 'ask';

        if (this.election?.sound_enabled !== false) chime();
        if (this.election?.confetti_enabled !== false) {
            try { confetti({ particleCount: 90, spread: 80, origin: { y: 0.6 }, disableForReducedMotion: true }); } catch { /* abaikan */ }
        }

        clearInterval(this.thanksTimer);
        this.thanksTimer = setInterval(() => {
            this.thanks.seconds -= 1;
            if (this.thanks.seconds <= 0) {
                clearInterval(this.thanksTimer);
                this.overlay = null;
                if (this.started) this.applyStatus();
            }
        }, 1000);
    },

    formatNumber(n) {
        return new Intl.NumberFormat('id-ID').format(n);
    },
});

window.Alpine = Alpine;
Alpine.start();
