# Acceptance Criteria — E-Voting OSIS Layar Sentuh

> **Layer 2: Verifier.** Dokumen ini mendefinisikan kapan sebuah pekerjaan dianggap **selesai**.
> Setiap kriteria harus bisa dijawab **ya/tidak**. Diturunkan dari SPEC.md v1.0 dan docs/ux-flow.md v0.1.

---

## Cara Memakai

1. Sebelum mulai coding satu fitur, baca kriteria fitur tersebut.
2. Setelah selesai, periksa setiap kriteria dan tandai hasilnya.
3. Pekerjaan **belum selesai** selama masih ada kriteria yang gagal.
4. Tuliskan bukti verifikasi (perintah test, screenshot, URL, output).
5. Kolom **Req** wajib diisi.

**Metode:** `AUTO` (Pest feature/unit test, atau Playwright/Dusk untuk kiosk) · `MANUAL` (dicek manusia, termasuk di IFP asli) · `REVIEW` (ditinjau terhadap SPEC/ux-flow/design system)

---

## A. Setup & Login Admin (Checkpoint 1)

| ID | Req | Kriteria (Given – When – Then) | Jenis | Metode | Status | Bukti |
|---|---|---|---|---|---|---|
| AC-01 | REQ-01 | **Diberikan** `ADMIN_EMAIL`/`ADMIN_PASSWORD` di `.env`, **ketika** `php artisan migrate:fresh --seed`, **maka** akun admin ada dan bisa login ke `/admin` | Positif | AUTO | ✅ | `AdminAuthTest` (4 test AC-01) hijau; login manual di browser lokal → Dasbor |
| AC-02 | REQ-01 | **Diberikan** pengunjung belum login, **ketika** membuka `/admin`, `/admin/candidates`, atau `/admin/recap`, **maka** dialihkan ke halaman login | Negatif | AUTO | ✅ | `AdminAuthTest` AC-02 (/admin, /admin/candidates, /admin/elections, /admin/activity-logs) + `RecapTest` (/admin/recap, /berita-acara) |
| AC-03 | REQ-01 | **Diberikan** password salah 5 kali dalam 1 menit, **ketika** mencoba ke-6, **maka** ditolak dengan pesan "Terlalu banyak percobaan masuk…" | Negatif | AUTO | ✅ | `AdminAuthTest` — percobaan ke-6 (sandi benar) ditolak, notifikasi "Terlalu banyak percobaan masuk." |
| AC-04 | REQ-23 | **Diberikan** admin berhasil login, **ketika** login selesai, **maka** ada baris log `auth.login` dengan user & waktu | Positif | AUTO | ✅ | `AdminAuthTest` + baris `auth.login user=1` di DB lokal setelah login manual |

## B. Pemilihan & Paslon (Checkpoint 2)

| ID | Req | Kriteria (Given – When – Then) | Jenis | Metode | Status | Bukti |
|---|---|---|---|---|---|---|
| AC-10 | REQ-02 | **Diberikan** pemilihan baru dibuat, **maka** status = Persiapan, mode sentuh = 2x sentuh, jeda = 3 detik | Positif | AUTO | ✅ | `ElectionSettingsTest` AC-10 |
| AC-11 | REQ-02 | **Diberikan** pemilihan A aktif, **ketika** admin mengaktifkan pemilihan B, **maka** hanya B yang `is_active` | Positif | AUTO | ✅ | `ElectionSettingsTest` AC-11 (aksi "Jadikan aktif" + log `election.activated`) |
| AC-12 | REQ-02 | **Diberikan** tahun ajaran diisi `2026-2027`, **ketika** disimpan, **maka** ditolak dengan "Tahun ajaran harus berformat 2026/2027." | Negatif | AUTO | ✅ | `ElectionSettingsTest` AC-12 — 4 format salah + nama kosong |
| AC-13 | REQ-03 | **Diberikan** data paslon lengkap + foto JPG 2 MB, **ketika** disimpan, **maka** paslon tersimpan, foto disimpan sebagai WebP dengan sisi terpanjang ≤ 1600px | Positif | AUTO | ✅ | `CandidateTest` AC-13 — hasil 1600×1200 WebP ≤ 300 KB; dicek juga di browser (foto tampil di daftar) |
| AC-14 | REQ-03 | **Diberikan** paslon nomor 02 sudah ada, **ketika** admin menyimpan paslon lain bernomor 02, **maka** ditolak "Nomor urut 02 sudah dipakai." | Negatif | AUTO | ✅ | `CandidateTest` AC-14 (+ boleh sama di pemilihan lain, warna aksen ganda ditolak) |
| AC-15 | REQ-03 | **Diberikan** berkas foto PDF atau gambar 4 MB, **ketika** diunggah, **maka** ditolak "Foto harus JPG, PNG, atau WebP, maksimal 3 MB." | Negatif | AUTO | ✅ | `CandidateTest` AC-15 — PDF dan JPG 4 MB |
| AC-16 | REQ-03 | **Diberikan** sudah ada 6 paslon, **ketika** menambah paslon ke-7, **maka** ditolak "Maksimal 6 paslon." | Negatif | AUTO | ✅ | `CandidateTest` AC-16 — form + penjaga di level model |
| AC-17 | REQ-03 | **Diberikan** misi kosong atau > 7 butir, **ketika** disimpan, **maka** ditolak | Negatif | AUTO | ✅ | `CandidateTest` AC-17 — kosong dan 8 butir |
| AC-18 | REQ-19 | **Diberikan** status Dibuka atau Ditutup, **ketika** admin mengubah/menghapus paslon (lewat UI maupun request langsung), **maka** ditolak dan data tidak berubah | Negatif | AUTO | ✅ | `CandidateTest` AC-18 — status Dibuka dan Ditutup: URL edit/create 403, tombol ubah/hapus tersembunyi, banner kunci tampil, model melempar `CandidatesLockedException`; Persiapan/Uji coba tetap bisa diubah |
| AC-19 | REQ-08, REQ-17, REQ-23 | **Diberikan** admin mengubah mode sentuh/jeda/bel/confetti, **ketika** disimpan, **maka** nilai tersimpan, `settings_version` naik 1, dan tercatat di log (lama → baru) | Positif | AUTO | ✅ | `ElectionSettingsTest` AC-19 — version 1→2, log lama→baru; ubah tahun ajaran saja tidak menaikkan version; peringatan 1x sentuh dicek juga di browser |

## C. TPS, Aktivasi IFP & API Kiosk (Checkpoint 3)

| ID | Req | Kriteria (Given – When – Then) | Jenis | Metode | Status | Bukti |
|---|---|---|---|---|---|---|
| AC-20 | REQ-04 | **Diberikan** TPS dibuat, **ketika** admin menekan "Buat kode aktivasi", **maka** muncul kode 6 angka dengan kedaluwarsa = sekarang + 30 menit | Positif | AUTO | ✅ | `PollingStationAdminTest` AC-20 (+ kode baru mengganti kode lama); dicek di browser |
| AC-21 | REQ-05 | **Diberikan** kode sah, **ketika** IFP mengirim kode ke API aktivasi, **maka** IFP menerima token ≥ 40 karakter, DB hanya menyimpan hash SHA-256, dan kode terhapus (sekali pakai) | Positif | AUTO | ✅ | `KioskActivationApiTest` AC-21; uji curl lokal: token 48 karakter |
| AC-22 | REQ-05 | **Diberikan** kode yang sama dipakai kedua kali, atau kode berumur > 30 menit, **ketika** dikirim, **maka** ditolak dengan pesan "Kode tidak dikenal atau sudah kedaluwarsa…" | Negatif | AUTO | ✅ | `KioskActivationApiTest` AC-22 — kode terpakai, > 30 menit, format salah; uji curl lokal |
| AC-23 | REQ-05 | **Diberikan** 10 kode salah dari IP yang sama dalam 10 menit, **ketika** percobaan ke-11, **maka** ditolak 429 | Negatif | AUTO | ✅ | `KioskActivationApiTest` AC-23 — hanya percobaan gagal yang dihitung |
| AC-24 | REQ-05 | **Diberikan** TPS X sudah punya IFP-1, **ketika** IFP-2 diaktivasi ke TPS X, **maka** token IFP-1 tidak berlaku lagi (401) | Negatif | AUTO | ✅ | `KioskActivationApiTest` AC-24 |
| AC-25 | REQ-22 | **Diberikan** admin melepas IFP dari TPS, **maka** token terhapus, request IFP berikutnya → 401, dan tercatat di log | Positif | AUTO | ✅ | `PollingStationAdminTest` + `KioskActivationApiTest` AC-25 (dialog bernama, log `device.released`) |
| AC-26 | REQ-22 | **Diberikan** IFP terakhir menanyakan status 20 detik lalu, **maka** SCR-A4/A2 menampilkan "Daring"; bila 40 detik lalu → "Luring" | Positif | AUTO | ✅ | `PollingStationAdminTest` AC-26; Daring tampil di dasbor & SCR-A4 (browser) |
| AC-27 | REQ-04 | **Diberikan** TPS yang sudah punya suara sah, **ketika** admin menghapusnya, **maka** ditolak "TPS yang sudah punya suara tidak bisa dihapus…" | Negatif | AUTO | ✅ | `PollingStationAdminTest` AC-27 (+ penjaga model) |
| AC-28 | REQ-13 | **Diberikan** token sah, **ketika** IFP memanggil API status, **maka** respons berisi status pemilihan, nama TPS, jumlah suara TPS itu, `settings_version`, mode sentuh, jeda, bel, confetti, dan `device_last_seen_at` diperbarui | Positif | AUTO | ✅ | `KioskActivationApiTest` AC-28; uji curl lokal |
| AC-29 | REQ-14 | **Diberikan** token sah, **ketika** memanggil API status atau API paslon, **maka** respons **tidak** memuat jumlah suara per paslon | Negatif | AUTO | ✅ | `KioskActivationApiTest` AC-29; uji curl: field paslon tanpa angka suara |
| AC-30 | REQ-09 | **Diberikan** status Dibuka & token sah, **ketika** IFP mengirim suara untuk paslon pemilihan aktif, **maka** satu baris `votes` dibuat dengan `is_trial=false` dan respons 201 | Positif | AUTO | ✅ | `VoteApiTest` AC-30 |
| AC-31 | REQ-11 | **Diberikan** suara dengan `client_uuid` U sudah tersimpan, **ketika** IFP mengirim ulang U (sekali atau 5 kali bersamaan), **maka** respons sukses dan jumlah baris tetap 1 | Negatif | AUTO | ✅ | `VoteApiTest` AC-31 (5× ulang + ulang setelah ditutup); uji curl: kiriman ulang → 200, tetap 1 suara. Kiriman paralel sungguhan diuji di NF-22 (CP6) |
| AC-32 | REQ-13 | **Diberikan** status Persiapan atau Ditutup, **ketika** IFP mengirim suara, **maka** ditolak 409 dengan kode `election_not_open`, tidak ada baris baru | Negatif | AUTO | ✅ | `VoteApiTest` AC-32 — Persiapan, Ditutup, tanpa pemilihan aktif |
| AC-33 | REQ-09 | **Diberikan** token tidak ada/salah, TPS nonaktif, atau `candidate_id` milik pemilihan lain, **ketika** mengirim suara, **maka** ditolak (401/403/422) tanpa baris baru | Negatif | AUTO | ✅ | `VoteApiTest` AC-33 — TPS nonaktif, paslon pemilihan lain, payload salah |
| AC-34 | REQ-20 | **Diberikan** status Uji coba, **ketika** IFP mengirim suara, **maka** baris dibuat dengan `is_trial=true` | Positif | AUTO | ✅ | `VoteApiTest` AC-34; uji curl saat Uji coba → is_trial=1 |
| AC-35 | REQ-21 | **Diberikan** TPS X mencatat suara 1 detik lalu, **ketika** suara baru (UUID lain) dari TPS X tiba, **maka** ditolak 429 `too_fast`; suara dari TPS Y pada saat yang sama tetap diterima | Negatif | AUTO | ✅ | `VoteApiTest` AC-35 (presisi milidetik); uji curl: suara kedua < 2 detik → 429 too_fast |
| AC-36 | REQ-18, NFR-21 | Tabel `votes` tidak memiliki kolom identitas siswa, IP, user-agent, atau token; request suara tidak menulis data tersebut ke DB maupun log aplikasi | Negatif | AUTO + REVIEW | ✅ | `VoteApiTest` AC-36 — IP/user-agent/token tidak ada di votes, activity_logs, polling_stations, laravel.log |
| AC-37 | REQ-19, NFR-24 | Tidak ada rute/aksi admin untuk mengubah atau menghapus suara dengan `is_trial=false` | Negatif | AUTO | ✅ | `VoteApiTest` AC-37 — tanpa rute admin vote; model menolak ubah/hapus suara sah |
| AC-38 | REQ-13 | **Diberikan** paslon < 2 atau tidak ada IFP teraktivasi, **ketika** admin mencoba Buka pemungutan, **maka** ditolak dengan alasan sesuai kartu SCR-A2 | Negatif | AUTO | ✅ | `ElectionLifecycleTest` AC-38 — tombol Buka nonaktif + alasan tampil; dicek di browser |
| AC-39 | REQ-20, REQ-23 | **Diberikan** status Uji coba dengan 12 suara uji coba, **ketika** admin Buka pemungutan, **maka** 12 suara uji coba terhapus, status Dibuka, `opened_at` terisi, dan log mencatat keduanya | Positif | AUTO | ✅ | `ElectionLifecycleTest` AC-39 |

## D-K. Layar Kiosk (Checkpoint 4)

| ID | Req | Kriteria (Given – When – Then) | Jenis | Metode | Status | Bukti |
|---|---|---|---|---|---|---|
| AC-40 | REQ-05 | **Diberikan** IFP sudah teraktivasi, **ketika** halaman dimuat ulang atau browser ditutup lalu dibuka, **maka** tampil SCR-K2 TPS yang sama tanpa meminta kode | Positif | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` UX-01/AC-40 (aktivasi → muat ulang tetap TPS yang sama) + IFP dilepas kembali ke aktivasi |
| AC-41 | REQ-06 | **Diberikan** 2, 3, 4, dan 6 paslon, **ketika** SCR-K3 tampil di viewport 1920×1080 dan 3840×2160, **maka** semua kartu terlihat utuh tanpa scroll | Positif | AUTO (screenshot) | ✅ | `tests/Browser/kiosk.test.mjs` AC-41 — 2, 3, 4, 6 paslon × 1920×1080 & 3840×2160: tanpa scroll, kartu utuh; screenshot storage/app/browser-shots |
| AC-42 | REQ-08 | **Diberikan** mode 2x sentuh, **ketika** siswa menyentuh foto, **maka** SCR-K5 tampil dan belum ada suara terkirim; **ketika** "BATAL, GANTI PILIHAN", **maka** kembali ke SCR-K3 tanpa suara | Positif + Negatif | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` AC-42 |
| AC-43 | REQ-08 | **Diberikan** mode 1x sentuh, **ketika** siswa menyentuh foto, **maka** suara langsung terkirim tanpa SCR-K5 | Positif | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` AC-43 |
| AC-44 | REQ-11 | **Diberikan** SCR-K5 baru muncul, **ketika** "YA" disentuh dalam < 600 ms, **maka** tidak terjadi apa-apa; **ketika** disentuh dua kali cepat setelah aktif, **maka** hanya 1 request dengan 1 `client_uuid` | Negatif | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` AC-44 — YA nonaktif < 600 ms; klik ganda = 1 permintaan |
| AC-45 | REQ-10 | **Diberikan** server tidak terjangkau atau membalas 500, **ketika** siswa menyentuh "YA", **maka** tampil "Suara belum tercatat." dalam ≤ 8 detik, SCR-K6 **tidak** tampil, dan "COBA LAGI" mengirim `client_uuid` yang sama | Negatif | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` AC-45 — jaringan diputus: "Suara belum tercatat." ≤ 8 detik, tanpa K6; Coba lagi memakai UUID sama |
| AC-46 | REQ-12, REQ-18 | **Diberikan** suara tercatat dengan jeda 3 detik, **maka** SCR-K6 tampil tanpa nama/nomor paslon dan kembali ke SCR-K3 setelah 3 ± 0,5 detik; sentuhan selama SCR-K6 diabaikan | Positif | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` AC-46 — tanpa nama/nomor paslon, kembali 2,5–3,6 detik (jeda 3), sentuhan diabaikan |
| AC-47 | REQ-13 | **Diberikan** IFP di SCR-K3, **ketika** admin menutup pemungutan, **maka** IFP menampilkan SCR-K7 "Pemungutan suara sudah ditutup" dalam ≤ 10 detik; sebaliknya Persiapan → Dibuka membuka SCR-K3 ≤ 10 detik | Positif | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` AC-47 — Persiapan→Dibuka, Dibuka→Ditutup, TPS nonaktif, koneksi putus & pulih, masing-masing ≤ 10 detik |
| AC-48 | REQ-07 | **Diberikan** siswa membuka "Visi & Misi" paslon 02, **maka** SCR-K4 menampilkan visi + semua misi; "PILIH 02" membawa ke SCR-K5 paslon 02; "KEMBALI" ke SCR-K3 | Positif | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` AC-48 |
| AC-49 | REQ-14, REQ-17 | **Diberikan** suara masuk di TPS itu, **maka** angka "suara masuk" di kepala SCR-K3 naik dan tidak ada angka per paslon di layar mana pun; bila bel/confetti dimatikan admin, keduanya tidak muncul di SCR-K6 | Positif | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` AC-49 — angka suara masuk naik, kartu tanpa angka suara, confetti mati bila dimatikan |

## E-R. Rekap & Berita Acara (Checkpoint 5)

| ID | Req | Kriteria (Given – When – Then) | Jenis | Metode | Status | Bukti |
|---|---|---|---|---|---|---|
| AC-50 | REQ-15 | **Diberikan** 10 suara sah (01: 5, 02: 3, 03: 2) di 2 TPS + 4 suara uji coba lama, **ketika** rekap dibuka, **maka** total = 10, persentase 50,0% / 30,0% / 20,0%, tabel TPS × paslon cocok dengan isi DB, suara uji coba tidak ikut | Positif | AUTO | ✅ | `RecapTest` AC-50 — 10 suara sah + 4 uji coba: total 10, 50,0/30,0/20,0%, tabel TPS cocok DB |
| AC-51 | REQ-15 | **Diberikan** dua paslon teratas seri, **maka** rekap menampilkan "Suara sama", bukan "Terbanyak" untuk salah satunya | Negatif | AUTO | ✅ | `RecapTest` AC-51 |
| AC-52 | REQ-15 | **Diberikan** belum ada suara, **maka** rekap menampilkan "Belum ada suara masuk." | Negatif | AUTO | ✅ | `RecapTest` AC-52 |
| AC-53 | REQ-16 | **Diberikan** status Ditutup, **ketika** halaman berita acara dicetak ke PDF A4, **maka** memuat kop, judul, waktu buka/tutup WIB, perolehan per paslon, tabel per TPS, total, kolom tanda tangan, dan muat dalam ≤ 2 halaman untuk 6 paslon × 4 TPS | Positif | MANUAL | ✅ | `RecapTest` AC-53 (isi + pengaturan penandatangan/saksi) dan PDF A4 Edge headless untuk 6 paslon × 4 TPS × 760 suara = **2 halaman**, tempat/tanggal menyatu dengan tanda tangan. Cetak di printer sekolah tetap dicek pemilik ⏳ |
| AC-54 | REQ-16 | **Diberikan** status masih Dibuka, **ketika** berita acara dibuka, **maka** ada watermark "SEMENTARA – PEMUNGUTAN BELUM DITUTUP" | Negatif | AUTO | ✅ | `RecapTest` AC-54 |
| AC-55 | REQ-02, REQ-13 | **Diberikan** status Dibuka, **ketika** admin Tutup pemungutan, **maka** status Ditutup, `closed_at` terisi, tercatat di log; tidak ada tombol maupun rute untuk membuka kembali | Positif | AUTO | ✅ | `ElectionLifecycleTest` AC-55 (dikerjakan lebih awal di CP3) |
| AC-56 | REQ-20 | **Diberikan** status Dibuka atau Ditutup, **ketika** admin mencoba "Tambah 10 suara simulasi" atau "Kosongkan suara uji coba" (UI atau request langsung), **maka** ditolak | Negatif | AUTO | ✅ | `ElectionLifecycleTest` AC-56 (dikerjakan lebih awal di CP3) |
| AC-57 | REQ-24 | **Diberikan** admin mengunggah logo PNG 1000×1000 di Pengaturan, **maka** tersimpan WebP ≤ 512 px dengan transparansi, `settings_version` naik, tercatat di log; SVG/PDF/> 2 MB ditolak "Logo harus PNG, JPG, atau WebP, maksimal 2 MB."; logo bisa dihapus | Positif + Negatif | AUTO | ✅ | `LogoTest` AC-57 — PNG 1000×1000 → WebP 512×512, sudut tetap transparan, version +1, log "logo diubah"; SVG/PDF/PNG 3 MB ditolak; hapus logo |
| AC-58 | REQ-24 | **Diberikan** logo diunggah, **maka** API status memuat `logo_url`, kepala layar IFP menampilkan logo (bukan teks "OSIS") ≤ 10 detik, dan kop berita acara memuat logo; tanpa logo tampil kotak "OSIS" | Positif | AUTO | ✅ | `LogoTest` AC-58 (API `logo_url`, kop berita acara) + `kiosk.test.mjs` AC-58 (logo muncul di kepala layar ≤ 10 detik, sebelumnya "OSIS") |

---

## C. Kebutuhan Non-Fungsional

| ID | Req | Kriteria | Metode | Status | Bukti |
|---|---|---|---|---|---|
| NF-01 | NFR-01 | Semua rute `/admin/*` butuh login; semua rute `/api/kiosk/*` kecuali aktivasi butuh token perangkat sah; situs produksi hanya lewat HTTPS | AUTO | ⏳ | Admin & API kiosk teruji (`AdminAuthTest` AC-02, `KioskActivationApiTest` NF-01); HTTPS dicek saat deploy (CP7) |
| NF-02 | NFR-02 | Panel admin tanpa scroll horizontal di 360px; kiosk tanpa scroll di 1920×1080 & 3840×2160 | MANUAL | ☐ | |
| NF-03 | NFR-04 | Waktu di dasbor, rekap, log, berita acara tampil WIB meski DB menyimpan UTC | AUTO | ☐ | |
| NF-04 | NFR-05 | Backup otomatis per jam berjalan di VPS, dan satu backup berhasil di-restore ke salinan uji dengan jumlah suara sama | MANUAL | ☐ | |
| NF-05 | NFR-03 | Tidak ada teks UI berbahasa Inggris di kiosk maupun admin (termasuk pesan validasi Laravel/Filament) | REVIEW | ☐ | |
| NF-20 | NFR-20 | Setiap aksi REQ-23 menghasilkan satu baris log dengan user, waktu, nilai lama → baru | AUTO | ☐ | |
| NF-22 | NFR-22 | Uji beban: 4 klien × 1 suara per 2 detik × 10 menit (±1.200 suara) ke server produksi/staging → 0 respons 5xx, jumlah baris `votes` = jumlah respons 201 | AUTO | ☐ | |
| NF-23 | NFR-23 | WiFi IFP dimatikan 30 detik lalu dinyalakan → SCR-K7 "Koneksi terputus" tampil ≤ 10 detik, lalu kembali normal ≤ 10 detik setelah tersambung, tanpa disentuh | MANUAL | ⏳ | Simulasi di browser lolos (AC-47: putus → K7 ≤ 10 detik → pulih otomatis); uji WiFi IFP asli di CP6 |
| NF-25 | NFR-25 | Halaman kiosk tidak memuat skrip/CSS dari CDN eksternal; tidak ada `backdrop-filter`; foto paslon ≤ 300 KB; di IFP asli respons visual sentuhan terasa seketika (≤ 150 ms, dicek rekaman video 60 fps) | REVIEW + MANUAL | ⏳ | Tanpa CDN, tanpa backdrop-filter (CSS dibatasi `source(none)`, 16 KB), font lokal, foto WebP ≤ 300 KB ✅; respons sentuhan di IFP asli dicek CP6 |

---

## D. UI/UX

### D1. State & Alur

| ID | Layar/Tugas | Kriteria (Given – When – Then) | Metode | Status | Bukti |
|---|---|---|---|---|---|
| UX-01 | SCR-K1 | **Diberikan** kode salah, **maka** tampil teks gagal persis dari kartu; tombol "Hubungkan" nonaktif sampai 6 angka terisi | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` UX-01 |
| UX-02 | SCR-K2 | **Diberikan** server tidak terjangkau, **maka** tampil teks gagal persis dari kartu dan mencoba ulang otomatis | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` UX-02 (SCR-K2 gagal lalu pulih otomatis) + AC-45 |
| UX-03 | SCR-K3 | **Diberikan** foto paslon gagal dimuat, **maka** tampil kotak warna paslon bertuliskan "PASLON {nomor}" | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` UX-03 |
| UX-04 | SCR-K4, SCR-K5 | **Diberikan** lapisan terbuka 30 detik tanpa sentuhan, **maka** tertutup dan kembali ke SCR-K3 tanpa suara | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` UX-04 (jam dipercepat 31 detik) |
| UX-05 | SCR-K7 | Keempat varian (Persiapan, Ditutup, TPS nonaktif, Koneksi terputus) tampil dengan teks persis dari kartu | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` AC-47 — keempat varian |
| UX-06 | SCR-A2 | **Diberikan** belum ada TPS, **maka** tampil "Belum ada TPS. Tambahkan TPS dan hubungkan IFP." + tombol "Kelola TPS" | AUTO | ✅ | `ElectionLifecycleTest` UX-06; dicek di browser |
| UX-07 | SCR-A3 | **Diberikan** belum ada paslon, **maka** tampil "Belum ada paslon. Tambahkan minimal 2 paslon." | AUTO | ✅ | `CandidateTest` UX-07 |
| UX-08 | SCR-A4 | **Diberikan** kode aktivasi baru dipakai IFP, **maka** dialog kode berubah menjadi "IFP terhubung ke {TPS}." ≤ 10 detik | AUTO | ✅ | `PollingStationAdminTest` UX-08; dicek di browser: dialog berubah ≤ 6 detik setelah aktivasi curl |
| UX-09 | SCR-A6, A8 | State kosong tampil dengan teks persis dari kartu (bukan tabel/grafik kosong) | AUTO | ✅ | SCR-A8 (`ActivityLogPageTest`) + SCR-A6 (`RecapTest` AC-52) |
| UX-10 | Semua admin | Aksi hapus/lepas/buka/tutup memunculkan dialog yang menyebut nama data/aksi | AUTO | ✅ | Hapus paslon, jadikan aktif, lepas IFP, hapus TPS, buka, tutup, kosongkan uji coba — semua dialog bernama teruji |
| UX-11 | Semua | Tidak ada kode error mentah / stack trace tampil ke pengguna (`APP_DEBUG=false` di produksi; kiosk memetakan semua error ke teks kartu) | AUTO | ☐ | |
| UX-12 | Semua | Teks tombol & notifikasi memakai istilah `ux-flow.md §7` | REVIEW | ☐ | |

### D1-K. Ergonomi Kiosk 75"

| ID | Kriteria | Metode | Status | Bukti |
|---|---|---|---|---|
| UX-K1 | Semua target sentuh siswa (foto, PILIH, Visi & Misi, BATAL, YA, KEMBALI, keypad) berada di pita **35%–95% tinggi viewport** (diukur `getBoundingClientRect` di 1920×1080) | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` UX-K1 — K1, K3 (6 paslon), K4, K5 diukur di 1920×1080: semua target di 35–95% |
| UX-K2 | Semua tombol siswa tingginya ≥ 9vh dan lebarnya ≥ 12vh; jarak antara BATAL dan YA ≥ 10vh | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` UX-K2 — semua tombol ≥ 9vh × 12vh; jarak BATAL–YA ≥ 10vh |
| UX-K3 | Nama paslon ≥ 3,5vh, teks badan ≥ 2,2vh, nomor urut ≥ 6vh | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` UX-K3 — nama 3,6vh, badan 2,2vh, nomor 7vh |
| UX-K4 | Sentuhan dua jari / telapak (multi-pointer) tidak membuka konfirmasi maupun mengirim suara | AUTO | ✅ | `tests/Browser/kiosk.test.mjs` UX-K4 — dua titik sentuh diabaikan; pembanding satu jari tetap bekerja |
| UX-K5 | Di IFP asli: tidak ada menu tekan-lama, seleksi teks, zoom cubit, atau tarik-untuk-muat-ulang di halaman kiosk | MANUAL | ⏳ | Menunggu uji di IFP Hisense asli (CP6). CSS sudah: user-select none, touch-action manipulation, overscroll none, menu konteks dicegah |
| UX-K6 | Di IFP asli dari jarak antrean 3 m dengan posisi IFP sesuai SOP: pengamat tidak bisa memastikan paslon mana yang dipilih dari layar terima kasih | MANUAL | ⏳ | Menunggu uji di IFP asli sesuai SOP (CP6); layar terima kasih sudah tanpa nama paslon (AC-46) |

### D2. Aksesibilitas

| ID | Kriteria | Metode | Status | Bukti |
|---|---|---|---|---|
| A11Y-01 | Admin: semua tombol/link ≥ 24×24 px (44px tinggi di mobile) | REVIEW | ☐ | |
| A11Y-02 | Admin: TSK-04 (buka/tutup) bisa diselesaikan hanya dengan keyboard; fokus terlihat | MANUAL | ☐ | |
| A11Y-03 | Kontras teks ≥ 4.5:1 di kiosk & admin (axe tanpa error kontras), termasuk teks putih di atas warna aksen paslon dan di atas foto (dengan overlay) | AUTO | ☐ | |
| A11Y-04 | Setiap input admin punya label terlihat; foto paslon punya `alt` "Foto paslon {nomor}: {ketua} dan {wakil}" | AUTO | ☐ | |
| A11Y-05 | Status (Daring/Luring, Dibuka/Ditutup, paslon) tidak dibedakan hanya lewat warna: selalu ada teks/ikon/nomor | REVIEW | ☐ | |

### D3. Uji Tugas dengan Pengguna Nyata (di IFP asli)

| ID | Tugas | Kriteria | Metode | Status | Bukti |
|---|---|---|---|---|---|
| UX-T-01 | TSK-01 | **5 dari 5** siswa (campuran kelas 7–9, termasuk siswa bertubuh pendek) memilih tanpa bantuan dalam ≤ 15 detik, dan tidak ada yang salah pilih menurut pengakuan mereka | MANUAL | ☐ | |
| UX-T-02 | TSK-03 | 1 panitia (bukan pengembang) menghubungkan IFP ke TPS hanya dengan membaca layar, ≤ 2 menit | MANUAL | ☐ | |
| UX-T-03 | TSK-04 | 1 panitia membuka & menutup pemungutan tanpa bantuan, ≤ 3 klik masing-masing | MANUAL | ☐ | |

### D4. Kepatuhan Design System

| ID | Kriteria | Metode | Status |
|---|---|---|---|
| DS-01 | Hanya warna yang terdaftar di `docs/design-system.md` (token Tailwind/CSS variable, tanpa hex lepas di Blade/JS) | REVIEW | ✅ |
| DS-02 | Kiosk & admin memakai font yang ditetapkan, disajikan dari server sendiri | REVIEW | ✅ |
| DS-03 | Satu aksi utama per layar; tombol mengikuti varian design system | REVIEW | ✅ |
| DS-04 | Mode desain tiap area sesuai `design-system.md §0` (admin = tema Filament, kiosk = aturan Kiosk) | REVIEW | ✅ |
| DS-05 | Warna design system disetujui pemilik sebagai warna final (keputusan 2026-11-23, SPEC v1.3) | REVIEW | ✅ |

---

## D5. Matriks Keterlacakan

| Req | Layar terkait | Kriteria terkait | Semua lolos? |
|---|---|---|---|
| REQ-01 | SCR-A1 | AC-01, AC-02, AC-03, NF-01 | ⏳ |
| REQ-02 | SCR-A2, SCR-A5 | AC-10, AC-11, AC-12, AC-55 | ✅ |
| REQ-03 | SCR-A3 | AC-13 – AC-17, UX-07 | ✅ |
| REQ-04 | SCR-A4 | AC-20, AC-27, UX-08 | ✅ |
| REQ-05 | SCR-K1, SCR-K2 | AC-21 – AC-24, AC-40, UX-01 | ✅ |
| REQ-06 | SCR-K3 | AC-41, UX-03, UX-K1 – UX-K3 | ✅ |
| REQ-07 | SCR-K4 | AC-48, UX-04 | ✅ |
| REQ-08 | SCR-K3, SCR-K5, SCR-A5 | AC-19, AC-42, AC-43 | ✅ |
| REQ-09 | SCR-K5, SCR-K6 | AC-30, AC-33 | ✅ |
| REQ-10 | SCR-K5 | AC-45, NF-23 | ⏳ |
| REQ-11 | SCR-K5 | AC-31, AC-44, UX-K4 | ✅ |
| REQ-12 | SCR-K6 | AC-46 | ✅ |
| REQ-13 | SCR-A2, SCR-K7 | AC-28, AC-32, AC-38, AC-47, AC-55, UX-05 | ✅ |
| REQ-14 | SCR-K3 | AC-29, AC-49 | ✅ |
| REQ-15 | SCR-A6 | AC-50, AC-51, AC-52, UX-09 | ✅ |
| REQ-16 | SCR-A7 | AC-53, AC-54 | ✅ |
| REQ-17 | SCR-K6, SCR-A5 | AC-19, AC-49 | ✅ |
| REQ-18 | SCR-K6 | AC-36, AC-46, UX-K6 | ⏳ |
| REQ-19 | SCR-A3 | AC-18, AC-37 | ✅ |
| REQ-20 | SCR-A2, SCR-K3, SCR-K6 | AC-34, AC-39, AC-56 | ✅ |
| REQ-21 | SCR-K5 | AC-35 | ✅ |
| REQ-22 | SCR-A2, SCR-A4 | AC-25, AC-26 | ✅ |
| REQ-23 | SCR-A8 | AC-04, AC-19, AC-25, AC-39, NF-20 | ⏳ |
| REQ-24 | SCR-A5, SCR-K3, SCR-A7 | AC-57, AC-58 | ✅ |

---

## E. Definition of Done

- [ ] Semua kriteria fitur terkait berstatus lolos
- [ ] Semua test otomatis hijau (`php artisan test`)
- [ ] Tidak ada error / warning baru di `storage/logs`
- [ ] SPEC.md diperbarui bila ada perubahan perilaku
- [ ] Tidak ada data dummy / kredensial tertinggal di kode
- [ ] Setiap fitur yang dikerjakan punya minimal satu kriteria negatif yang lolos
- [ ] Layar admin yang diubah: 4 state bisa dimunculkan, screenshot 360px & 1280px
- [ ] Layar kiosk yang diubah: semua state bisa dimunculkan, screenshot 1920×1080 & 3840×2160
- [ ] `PROGRESS.md` diperbarui

---

## F. Riwayat Verifikasi

| Tanggal | Checkpoint | Hasil | Catatan |
|---|---|---|---|
| 2026-11-23 | 1 | Lolos | 14 test, 58 assertion hijau; AC-01, AC-03, AC-04 ✅, AC-02 ✅ sebagian (rute CP2/CP5 belum ada); `migrate:fresh --seed` sukses, journal_mode=wal |
| 2026-11-23 | 2 | Lolos | 49 test, 273 assertion hijau; AC-10 s.d. AC-19 ✅, UX-07 ✅, UX-09 dan UX-10 sebagian; cek browser: daftar paslon + foto, peringatan 1x sentuh, log dalam WIB |
| 2026-11-23 | 3 | Lolos | 105 test, 548 assertion hijau; AC-20 s.d. AC-39 ✅ (+ AC-55, AC-56 lebih awal), UX-06/08/10 ✅; uji curl lokal (aktivasi, status, suara, kiriman ulang, rem 2 detik) + browser (TPS, dialog kode, dasbor) |
| 2026-11-23 | 5 | Lolos | 116 test, 615 assertion hijau; AC-50 s.d. AC-54 ✅, UX-09 ✅, AC-02 lengkap; PDF berita acara 6×4 = 2 halaman A4; cek browser halaman Rekap (tab Resmi/Uji coba) |
| 2026-11-23 | 4 | Lolos (⏳ uji IFP asli) | 17 test browser (Playwright + Edge) + 116 Pest hijau; AC-40 s.d. AC-49, UX-01–05, UX-K1–K4 ✅; screenshot 1920×1080 & 3840×2160; UX-K5/K6, NF-23, NF-25 menunggu IFP asli (CP6) |
| 2026-11-23 | 4+ | Lolos | Warna final (DS-05 ✅) + unggah logo (REQ-24): 123 Pest + 18 browser hijau; AC-57, AC-58 ✅ |
