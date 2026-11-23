# PROGRESS.md — Posisi Pengerjaan

> Diperbarui AI **di akhir setiap sesi**. Dibaca AI **di awal setiap sesi**.
> File ini bukan sumber requirement — requirement tetap di `SPEC.md`.

---

## Status Saat Ini

- **Checkpoint aktif:** 6 — Uji beban + uji di IFP Hisense asli + uji pengguna 5 siswa + poles UX (belum dimulai)
- **Checkpoint selesai:** 1 ✅, 2 ✅, 3 ✅, 4 ✅ (⏳ uji di IFP asli), 5 ✅ (2026-11-23)
- **Versi SPEC yang dipakai:** 1.3 · **ux-flow:** 0.6 (semua layar Disetujui)
- **Terakhir diperbarui:** 2026-11-23 oleh AI

## ✅ Sudah Selesai — Warna final & logo (SPEC v1.3)
- **Warna:** token design system yang sekarang disetujui pemilik sebagai warna final → DS-05 ✅ (tidak perlu dikerjakan lagi di CP6)
- **Logo** (REQ-24): bagian "Logo" di Pengaturan Pemilihan (`elections.logo_path`, migrasi `add_logo_path_to_elections_table`), diolah `App\Support\LogoImage` (WebP ≤ 512 px, transparansi tetap, SVG ditolak); tampil di kepala layar IFP SCR-K2/K3/K7 (`resources/views/kiosk/_logo.blade.php`) dan kop berita acara; API status mengirim `logo_url`; ganti logo menaikkan `settings_version` sehingga IFP ikut berubah ≤ 10 detik
- Test: `LogoTest` (5 test) + browser AC-58 → **123 Pest + 18 browser hijau**

## ✅ Sudah Selesai — Checkpoint 4 (layar IFP)
- **npm diperbaiki** (AGENTS §8): dua salinan `minipass` 3.3.6 basi di npm Herd diganti nama
- Aset kiosk lewat Vite: `resources/css/kiosk.css` (token design-system sebagai `@theme`, ukuran `vh`, `source(none)`, 16 KB), `resources/js/kiosk.js` (Alpine + canvas-confetti), font Plus Jakarta Sans lokal (`@fontsource`). Font Bunny CDN bawaan skeleton dihapus
- **`/kiosk`** (`KioskPageController`, `resources/views/kiosk/index.blade.php`): SCR-K1 aktivasi (keypad layar), K2 siap (layar penuh + buka suara), K3 pilih paslon (satu baris, nama → foto → tombol), K4 visi & misi, K5 konfirmasi/simpan/gagal, K6 terima kasih, K7 status (4 varian), teks persis dari kartu
- Logika: token di localStorage, polling status 5 detik, muat ulang paslon saat `settings_version` berubah, deteksi putus & pulih otomatis, jeda 400 ms (sentuhan siswa saja), abaikan multi-sentuh, YA aktif setelah 600 ms, timeout lapisan 30 detik, kirim suara 8 detik dengan UUID tetap untuk "Coba lagi", pemetaan galat API ke teks kartu, bel Web Audio & confetti bisa dimatikan
- **Pratinjau layar IFP** untuk admin (`/kiosk/pratinjau`, tombol "Pratinjau layar IFP" di halaman Paslon) — memenuhi TSK-05 yang ditunda dari CP2
- **Test browser** `tests/Browser/kiosk.test.mjs` (`npm run test:browser`): Playwright-core + Edge terpasang, server uji 8123 + `database/browser.sqlite`, skenario `php artisan kiosk:fixture` (`App\Console\KioskFixture`, hanya APP_ENV=browser) → **17 test hijau** (AC-40–AC-49, UX-01–05, UX-K1–K4) + **116 Pest hijau**
- Screenshot 1920×1080 & 3840×2160 di `storage/app/browser-shots/` (tidak di-commit); kritik diri → diperbaiki: label peran terpotong, slogan menutupi foto, keypad menimpa kotak kode, teks logo meluber
- Uji manual di browser lokal: Mulai → pilih → konfirmasi → terima kasih (uji coba) → kembali, jumlah suara naik

## ✅ Sudah Selesai — Checkpoint 5
- Rekap Hasil `/admin/recap`, Berita Acara `/berita-acara` (A4, 6×4 = 2 halaman), pengaturan berita acara (SPEC v1.1)

## ✅ Sudah Selesai — Checkpoint 1–3
- Lihat riwayat sesi; ringkasnya: setup + skema, pemilihan/paslon/log, TPS + aktivasi IFP + API kiosk + dasbor

## 🔄 Sedang Dikerjakan / Tertunda
- Kriteria yang butuh **IFP Hisense asli** (CP6): UX-K5 (tanpa menu tekan-lama/zoom/tarik-muat-ulang), UX-K6 (pilihan tak terbaca dari antrean), NF-23 (WiFi IFP putus-sambung), NF-25 (respons sentuhan), viewport CSS sebenarnya, cara mengunci aplikasi browser IFP
- AC-53: cetak berita acara di printer sekolah ⏳
- A11Y-03 (kontras, axe) belum diukur → CP6
- Perubahan sejak commit `1e29b32` **belum di-commit**

## ⛔ Blocker / Menunggu Keputusan
- Logo resmi SMPN 2 Sintang tinggal **diunggah admin** di Pengaturan Pemilihan → Logo
- SPEC §10 lain: subdomain + VPS smpn2, data IFP asli, kelas paslon ditampilkan atau tidak (saat ini: kelas tampil di Visi & Misi bila diisi)

## ➡️ Langkah Berikutnya
1. Admin mengunggah logo resmi di Pengaturan Pemilihan
2. Checkpoint 6: uji beban NF-22 (4 klien × 1 suara/2 detik × 10 menit), uji di IFP asli (UX-K5, UX-K6, NF-23, NF-25), uji pengguna 5 siswa (UX-T-01) & panitia (UX-T-02/03), kontras A11Y-03
3. Checkpoint 7: deploy ke VPS smpn2 + backup otomatis + SOP hari-H

---

## Riwayat Sesi

| Tanggal | Checkpoint | Yang dikerjakan | Hasil verifikasi |
|---|---|---|---|
| 2026-11-23 | 0 | Wawancara + penyusunan seluruh dokumen SDD dari prototipe `evoting.txt` | — (belum ada kode) |
| 2026-11-23 | 0 | 3 usulan disetujui → SPEC v1.0 Disepakati | — |
| 2026-11-23 | 1 | Setup Laravel 13 + Filament 5 + SQLite WAL + Pest, migrasi §4, seeder admin, log login | AC-01–AC-04 ✅ — 14 test hijau |
| 2026-11-23 | 2 | Pemilihan, paslon (foto WebP, validasi, kunci), log aktivitas, validasi Indonesia | AC-10–AC-19 ✅, UX-07 ✅ — 49 test hijau |
| 2026-11-23 | 3 | TPS & aktivasi IFP, API kiosk, catat suara, dasbor & status pemilihan | AC-20–AC-39, AC-55, AC-56 ✅ — 105 test hijau |
| 2026-11-23 | 5 | Rekap hasil, berita acara A4, pengaturan berita acara (SPEC v1.1) | AC-50–AC-54 ✅ — 116 test hijau, PDF 2 halaman |
| 2026-11-23 | — | Commit pertama `1e29b32` | — |
| 2026-11-23 | 4 | Perbaikan npm, layar IFP SCR-K1–K7, pratinjau admin, test browser Playwright + Edge | AC-40–AC-49, UX-K1–K4 ✅ — 17 test browser + 116 Pest hijau |
| 2026-11-23 | 4 | Tata letak satu baris disetujui → SPEC v1.2, ux-flow v0.5 | — |
| 2026-11-23 | 4+ | Warna final disetujui, unggah logo di Pengaturan (SPEC v1.3) | AC-57, AC-58, DS-05 ✅ — 123 Pest + 18 browser hijau |
