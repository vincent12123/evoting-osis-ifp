# PROGRESS.md — Posisi Pengerjaan

> Diperbarui AI **di akhir setiap sesi**. Dibaca AI **di awal setiap sesi**.
> File ini bukan sumber requirement — requirement tetap di `SPEC.md`.

---

## Status Saat Ini

- **Checkpoint aktif:** 4 — Layar kiosk di IFP: SCR-K1 s/d SCR-K7 (belum dimulai, **terblokir**: lihat di bawah)
- **Checkpoint selesai:** 1 ✅, 2 ✅, 3 ✅, 5 ✅ (2026-11-23)
- **Versi SPEC yang dipakai:** 1.1 · **ux-flow:** 0.3 (SCR-A1–A8 Disetujui, SCR-K1–K7 masih Draft)
- **Terakhir diperbarui:** 2026-11-23 oleh AI

## ✅ Sudah Selesai — Checkpoint 5
- **Keputusan pemilik (SPEC v1.1):** penandatangan berita acara diatur di Pengaturan (bawaan Ketua Panitia, Pembina OSIS, Kepala Sekolah + saksi tiap paslon), nama/alamat sekolah & tempat tanda tangan diisi admin, kolom isian tangan "jumlah pemilih menurut catatan pengawas" per TPS
- Migrasi `add_report_settings_to_elections_table` (school_name, school_address, signing_place, signatories, witness_signatures) + bagian "Berita acara" di Pengaturan Pemilihan (tidak menaikkan `settings_version`)
- `App\Support\ElectionRecap`: satu sumber angka (resmi vs uji coba, persentase 1 desimal, "Terbanyak"/"Suara sama", TPS × paslon)
- **Rekap Hasil** (`App\Filament\Pages\Recap`, `/admin/recap`, kartu SCR-A6): tab Resmi/Uji coba (saat Uji coba), banner sementara, batang perolehan, rincian per TPS, poll 10 detik
- **Berita Acara** (`/berita-acara`, `BeritaAcaraController`, `resources/views/print/berita-acara.blade.php`, kartu SCR-A7): A4, tanpa aset eksternal, watermark SEMENTARA sebelum Ditutup, hanya suara resmi
- Dasbor: "Lihat rekap" saat Ditutup; "Tutup pemungutan" langsung mengarah ke Rekap
- `/` dialihkan ke `/admin`; tamu di rute non-panel diarahkan ke login panel
- Test `RecapTest` → total **116 test / 615 assertion hijau**
- PDF A4 skenario terberat (6 paslon × 4 TPS × 760 suara) = 2 halaman — contoh di scratchpad sesi

## ✅ Sudah Selesai — Checkpoint 3
- **TPS & IFP** (`PollingStationResource`, kartu SCR-A4, dialog tambah/ubah): status TPS, status IFP Daring/Luring/Belum terhubung (≤ 30 detik = Daring), suara masuk, tabel diperbarui tiap 10 detik
  - "Buat kode aktivasi": 6 angka, berlaku 30 menit, sekali pakai; dialog (`App\Livewire\ActivationCodeStatus`, poll 5 detik) berubah menjadi "IFP terhubung ke {TPS}."
  - "Lepas IFP", "Nonaktifkan/Aktifkan TPS", hapus TPS (ditolak bila sudah ada suara sah)
- **API kiosk** (`routes/api.php`, `KioskApiController`, middleware `AuthenticateKioskDevice`):
  - `POST /api/kiosk/activate` — kode → token 48 karakter (DB menyimpan SHA-256), maks 10 gagal / 10 menit / IP
  - `GET /api/kiosk/status` — TPS, jumlah suara TPS, status & pengaturan pemilihan (memperbarui "terakhir terlihat")
  - `GET /api/kiosk/candidates` — paslon tanpa angka suara
  - `POST /api/kiosk/votes` — `App\Actions\CastVote`: transaksi IMMEDIATE, idempoten `client_uuid`, rem 2 detik per TPS (milidetik), `is_trial` saat Uji coba
  - Error JSON `{code, message}` dengan teks dari kartu (`App\Exceptions\KioskException`), tidak dicatat sebagai error di log
- **Dasbor** (`App\Filament\Pages\Dashboard`, kartu SCR-A2): status + alasan belum bisa dibuka, tombol Mulai uji coba / Buka / Tutup / Kosongkan uji coba / Tambah 10 suara simulasi (`App\Actions\ElectionLifecycle`), kartu statistik & tabel TPS (poll 10 detik), tanpa perolehan per paslon
- Status Ditutup final (AC-55) dan pembatasan simulasi/kosongkan ke status Uji coba (AC-56) dikerjakan lebih awal
- Penjaga model: `Vote` tidak bisa diubah, suara sah tidak bisa dihapus; TPS dengan suara sah tidak bisa dihapus
- SQLite `transaction_mode` = IMMEDIATE
- Test baru: `KioskActivationApiTest`, `VoteApiTest`, `PollingStationAdminTest`, `ElectionLifecycleTest` → **105 test / 548 assertion hijau**
- Uji manual lokal: curl (aktivasi, status, daftar paslon, suara, kiriman ulang → 200, rem → 429) + browser (tambah TPS, dialog kode berubah otomatis, dasbor Uji coba dengan IFP Daring)
- Data lokal: pemilihan demo berstatus **Uji coba**, TPS Aula terhubung ke "IFP palsu" (token di scratchpad sesi), 1 suara uji coba

## 🔄 Sedang Dikerjakan / Tertunda
- AC-53: cetak di printer sekolah sungguhan dicek pemilik ⏳
- Pratinjau kartu kiosk di form paslon → CP4
- NF-01 HTTPS → dicek saat deploy (CP7)
- Kiriman paralel sungguhan (NF-22) → uji beban CP6
- **Belum ada commit**

## ⛔ Blocker / Menunggu Keputusan
- **CP4 terblokir oleh npm rusak** (`npm install` gagal di npm milik Herd Node 24.11.1, AGENTS §8). Layar kiosk butuh build Tailwind + Alpine lewat Vite. Perlu diperbaiki di mesin pengguna (mis. pasang ulang Node/npm lewat Herd atau nvm)
- **Kartu layar kiosk SCR-K1–K7 belum disetujui** — wajib sebelum CP4 dikoding
- SPEC §10 tersisa: warna & logo resmi (dibutuhkan untuk tampilan kiosk), subdomain + VPS smpn2, data IFP asli, kelas paslon tampil atau tidak

## ➡️ Langkah Berikutnya
1. Pengguna memperbaiki npm, dan menyetujui kartu SCR-K1–K7
2. Checkpoint 4: halaman `/kiosk` (Blade + Alpine + Tailwind, ukuran `vh`), state mesin layar, polling status 5 detik, alur 1x/2x sentuh, timeout 30 detik, jeda 600 ms tombol YA, abaikan multi-sentuh, layar status, bel & confetti lokal; test browser AC-40–AC-49, UX-K1–UX-K4; screenshot 1920×1080 & 3840×2160

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
