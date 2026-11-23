# AGENTS.md — Instruksi untuk AI

> **Layer 3: Environment.** File ini dibaca AI di awal setiap sesi.
> Tujuannya: AI tidak perlu dijelaskan ulang tentang project, aturan, dan cara kerja.

---

## 1. Konteks Project

- **Nama:** E-Voting OSIS Layar Sentuh
- **Deskripsi singkat:** E-voting Ketua & Wakil OSIS SMPN 2 Sintang. Siswa memilih langsung dengan menyentuh layar IFP Hisense 75" (2–4 titik TPS, tanpa HP, diawasi guru); suara terkumpul ke satu server (Laravel + SQLite); panitia mengatur & merekap lewat panel admin.
- **Pemilik / pengambil keputusan:** Sukardi (pengembang) bersama Panitia Pemilihan OSIS SMPN 2 Sintang
- **Bahasa komunikasi:** Bahasa Indonesia
- **Bahasa kode (nama variabel, komentar):** English untuk nama tabel/kelas/variabel (konvensi Laravel); komentar & teks UI Bahasa Indonesia

## 2. Dokumen Acuan (urutan prioritas)

1. `SPEC.md` — apa yang dibangun (sumber kebenaran)
2. `verification/acceptance-criteria.md` — kapan dianggap selesai
3. `docs/ux-flow.md` — alur pengguna, layar, dan teks setiap state
4. `docs/design-system.md` — bagaimana tampilannya
5. `README.md` — cara menjalankan project
6. `PROGRESS.md` — posisi terakhir pengerjaan (bukan sumber requirement)

Jika ada konflik, ikuti dokumen dengan prioritas lebih tinggi, lalu **laporkan konfliknya**.

---

## 3. Cara Kerja

### Sebelum mulai
- Baca `PROGRESS.md` untuk mengetahui posisi terakhir dan langkah berikutnya.
- Baca SPEC.md bagian yang relevan dengan tugas.
- Teks `[kurung siku]` dan contoh berawalan `mis.` **bukan keputusan**. Jika bagian yang dibutuhkan masih berisi placeholder → **berhenti dan tanyakan**, jangan pakai contohnya.
- Identifikasi checkpoint mana yang sedang dikerjakan (SPEC.md §9).
- Jika requirement ambigu atau tidak ada di SPEC → **tanya dulu, jangan berasumsi.**

### Saat bekerja
- Kerjakan **satu checkpoint** pada satu waktu.
- Perubahan kecil dan bertahap; jelaskan apa yang diubah dan kenapa.
- Jangan menambah fitur di luar SPEC tanpa persetujuan.
- Tulis test untuk kasus negatif (input salah, tanpa akses, data kosong), bukan hanya happy path.

### Mengubah SPEC
- AI **tidak boleh** mengubah isi SPEC.md secara langsung.
- Jika menemukan requirement yang kurang, salah, atau bertentangan: tulis usulan di SPEC.md §12 dengan status **Diusulkan**, lalu laporkan.
- Setelah pemilik menyetujui: terapkan perubahan, naikkan versi SPEC, catat di §11 Log Keputusan, dan perbarui acceptance criteria terkait.

### Sebelum menyatakan "selesai"
- Jalankan verifikasi sesuai `acceptance-criteria.md`.
- Laporkan hasil tiap kriteria (lolos / gagal) beserta buktinya.
- **Jangan pernah bilang selesai jika belum diverifikasi.**

### Sebelum sesi berakhir
- Perbarui `PROGRESS.md`: checkpoint aktif, yang sudah selesai, yang tertunda, blocker, dan langkah berikutnya.
- Perbarui matriks keterlacakan di acceptance-criteria.md §D5.

---

## 4. Tech Stack & Konvensi

> Diisi dari SPEC.md §8. Berlaku untuk stack apa pun (PHP, Node.js, Python, Apps Script, Go, Flutter, dll).

| Hal | Aturan |
|---|---|
| Jenis aplikasi | Web: panel admin Filament (`/admin`) + halaman kiosk (`/kiosk`) + API JSON kiosk (`/api/kiosk/*`) |
| Bahasa & versi runtime | PHP 8.3+, Node 22 (hanya untuk build aset) |
| Framework | Laravel 13 + Filament 5 (pakai skill `filament5-dev` saat mengerjakan admin) |
| Frontend / UI | Kiosk: Blade + Alpine.js + Tailwind (build Vite, tanpa CDN). Admin: tema Filament |
| Database & akses data | SQLite (`database/database.sqlite`), mode WAL + `busy_timeout=5000`, Eloquent |
| Migrasi skema | `php artisan migrate` |
| Manajer paket | Composer, npm |
| Test | Pest (`php artisan test`); test layar kiosk dengan Pest Browser/Playwright |
| Format & lint | Laravel Pint (`./vendor/bin/pint`) |
| Penamaan | Tabel jamak bahasa Inggris (`elections`, `candidates`, `polling_stations`, `votes`, `activity_logs`), model tunggal; enum status disimpan dalam bahasa Inggris, label Indonesia lewat enum `getLabel()` |
| Konfigurasi rahasia | `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`, `APP_KEY`); tidak pernah di-commit |

### Perintah penting
| Tujuan | Perintah |
|---|---|
| Instal dependency | `composer install && npm install` |
| Jalankan lokal | `composer run dev` (server + Vite) atau `php artisan serve` + `npm run dev` |
| Jalankan test | `php artisan test` (Pest) dan `npm run test:browser` (layar kiosk, Playwright + Edge, ±2,5 menit; butuh `npm run build` dulu) |
| Format / lint | `./vendor/bin/pint` |
| Migrasi database | `php artisan migrate` (lokal: `migrate:fresh --seed`) |
| Build produksi | `npm run build && php artisan optimize` |

### Struktur folder penting
```
app/Enums/                  ElectionStatus, TouchMode, CandidateAccent
app/Models/                 Election, Candidate, PollingStation, Vote, ActivityLog
app/Filament/               Resources (Candidate, PollingStation), Pages (Dashboard, Settings, Recap)
app/Http/Controllers/Kiosk/ ActivationController, StatusController, VoteController (API JSON)
app/Http/Middleware/        AuthenticateKioskDevice (token perangkat Bearer)
app/Actions/                CastVote, OpenElection, CloseElection, ActivateDevice (logika inti, mudah dites)
resources/views/kiosk/      halaman kiosk (satu halaman, state Alpine)
resources/views/print/      berita acara
resources/js/kiosk/         logika kiosk (state, polling, sentuhan, suara)
tests/Feature/              satu file per kelompok AC (mis. VoteApiTest untuk AC-30–AC-37)
tests/Browser/              test layar kiosk (AC-40–AC-49, UX-K1–UX-K4)
```

---

## 4A. Aturan UI/UX

> Hapus bagian ini bila aplikasi tanpa antarmuka.

- **Mode desain** per area mengikuti `docs/design-system.md §0`:
  - **Fungsional** (aplikasi internal/admin): ikuti tema bawaan framework, ubah sesedikit mungkin, utamakan konsistensi dan kecepatan tugas. Jangan menambah dekorasi, animasi, atau gaya yang tidak diminta.
  - **Ekspresif** (halaman publik): boleh memakai skill `frontend-design` penuh (rencana token → review terhadap brief → kode → kritik screenshot), tetap dalam token design system.
- Layar hanya dibuat bila kartunya di `docs/ux-flow.md` berstatus **Disetujui**.
- Setiap layar wajib mengimplementasikan **4 state** (memuat, kosong, gagal, berhasil) dengan teks **persis** seperti di kartu layar.
- Teks tombol, notifikasi, dan istilah mengikuti `docs/ux-flow.md §7 Daftar Istilah`.
- Batas mutu minimum: responsif 360px, fokus keyboard terlihat, target klik ≥ 24×24 px, kontras ≥ 4.5:1, label di setiap input.
- Sebelum melaporkan checkpoint UI: screenshot 360px & 1280px, cek 4 state, cocokkan dengan design system, lampirkan sebagai bukti.

---

## 5. Larangan

- ❌ Mengubah struktur database tanpa memperbarui SPEC.md §4
- ❌ Mengubah SPEC.md tanpa persetujuan pemilik (usulkan di §12)
- ❌ Memakai nilai contoh (`mis. ...`) dari template sebagai keputusan
- ❌ Menampilkan / mengubah data di luar lingkup unit pengguna (SPEC §6.1)
- ❌ Menjalankan migrasi destruktif (drop kolom/tabel) tanpa persetujuan
- ❌ Menghapus atau melemahkan test agar lolos
- ❌ Menaruh kredensial / API key di dalam kode (pakai `.env`)
- ❌ Menambah dependency baru tanpa menyebut alasannya
- ❌ Memakai warna / font di luar design system
- ❌ Membuat layar, menu, atau alur yang tidak ada di `docs/ux-flow.md`
- ❌ Mengarang teks pesan error / state kosong sendiri bila sudah ditulis di kartu layar
- ❌ Menampilkan kode error mentah atau stack trace ke pengguna
- ❌ Menyimpan identitas siswa, IP, user-agent, atau token pada tabel `votes` maupun log (REQ-18)
- ❌ Menambah rute/aksi untuk mengubah/menghapus suara sah (REQ-19)
- ❌ Menghitung suara di IFP (`localStorage`) — sumber kebenaran hanya database server
- ❌ Menampilkan layar "Terima kasih" sebelum server membalas sukses (REQ-09)
- ❌ Menampilkan perolehan per paslon di kiosk atau di API kiosk (REQ-14)
- ❌ Memuat aset kiosk dari CDN (Tailwind CDN, unpkg, Google Fonts)
- ❌ Deploy ke produksi saat status pemilihan **Dibuka**

## 6. Wajib

- ✅ Setiap fitur baru disertai test untuk acceptance criteria-nya
- ✅ Validasi semua input dari pengguna
- ✅ Pesan error dan label UI dalam Bahasa Indonesia
- ✅ Pesan error menyebut apa yang salah dan cara memperbaikinya; isian pengguna tidak hilang saat gagal
- ✅ Aksi berisiko (hapus, batalkan, terbitkan) meminta konfirmasi yang menyebut nama datanya
- ✅ Simpan suara di dalam DB transaction dengan `client_uuid` unik (idempoten) dan periksa status pemilihan di dalam transaksi yang sama
- ✅ Logika inti (catat suara, buka/tutup, aktivasi) di kelas Action yang dites langsung, bukan di Filament page
- ✅ Teks kiosk persis dari kartu layar `docs/ux-flow.md`; ukuran kiosk memakai `vh`/`clamp`
- ✅ Uji layar kiosk di 1920×1080 **dan** 3840×2160, dan di IFP asli sebelum checkpoint 6 dinyatakan lolos

---

## 7. Format Laporan Setelah Tugas

```
## Ringkasan
[Apa yang dikerjakan]

## File yang diubah
- path/file — [alasan]

## Verifikasi
- AC-01 (REQ-01): ✅ lolos — [bukti]
- AC-02 (REQ-02): ❌ gagal — [penyebab & rencana]

## Usulan perubahan SPEC
- [jika ada — juga dicatat di SPEC.md §12]

## Pertanyaan / Keputusan yang dibutuhkan
- [jika ada]

## PROGRESS.md
- [sudah diperbarui ✅]
```

---

## 8. Pelajaran (diisi seiring waktu)

> Setiap kali AI melakukan kesalahan berulang, tambahkan aturannya di sini
> supaya tidak terulang di sesi berikutnya.

- 2026-11-23 Composer tidak ada di PATH Bash: pakai `php /c/php83/composer.phar …` (atau `composer` di PowerShell).
- 2026-11-23 Jangan mengedit file PHP ber-namespace lewat skrip Python di heredoc Bash — backslash bisa berubah; pakai alat Edit/Write.
- 2026-11-23 `vendor/bin/pest --init` bersifat interaktif dan menggantung — jangan dijalankan; `tests/Pest.php` sudah ada.
- 2026-11-23 npm global (Herd, Node 24.11.1) sempat rusak ("Class extends value undefined" di `minipass-flush`): sisa upgrade npm meninggalkan `minipass` 3.3.6 di `npm/node_modules/minipass-flush/node_modules/` dan `minipass-sized/node_modules/` (keduanya butuh ^7). **Diperbaiki** dengan mengganti nama folder itu menjadi `minipass.v3-basi-2026-11-23`. Kalau npm di-upgrade lagi dan error sama muncul, periksa pola ini.
- 2026-11-23 `npm install` di mesin ini lambat (±6 menit). Plugin `laravel:fonts` bawaan skeleton mengunduh font dari fonts.bunny.net saat build dan bisa timeout — CP4 menggantinya dengan font lokal (`@fontsource`).
- 2026-11-23 Rule closure kustom di form Filament harus dibungkus: `->rules([fn (): Closure => function ($attribute, $value, $fail) {…}])`. Closure telanjang dievaluasi Filament dan gagal ("[$attribute] was unresolvable").
- 2026-11-23 Repeater `->simple()`: di test, isi state sebagai `[['text' => '…'], …]` dan pakai `Repeater::fake()`. Data yang tersimpan tetap daftar teks biasa.
- 2026-11-23 FileUpload di form edit membuang path yang berkasnya tidak ada di disk. Di test, buat berkasnya dulu di `Storage::fake('public')`.
- 2026-11-23 `->value('kolom')` pada query Eloquent mengembalikan nilai ber-cast (mis. enum), bukan string mentah.
- 2026-11-23 Di `lang/id/validation.php` pakai `:Attribute` di awal kalimat. Filament mengirim label dalam huruf kecil.
- 2026-11-23 Jangan menandai dokumen dengan `sed` bila teks penggantinya memuat `/` atau `&`; pakai alat Edit.
- 2026-11-23 **Model basi tidak tersimpan:** `forceFill([...])->save()` pada instance lama melewatkan kolom yang nilainya sama dengan nilai lama di memori, walau DB sudah berubah (bug nyata di `GenerateActivationCode` & `ReleaseDevice`). Untuk perubahan status penting, tulis lewat `Model::query()->whereKey()->update([...])` lalu `refresh()`.
- 2026-11-23 Penjaga model yang membaca status (mis. boleh hapus?) pakai `getOriginal()`, bukan atribut di memori.
- 2026-11-23 Di test, buat paslon **sebelum** status pemilihan Dibuka/Ditutup — paslon terkunci setelahnya (REQ-19).
- 2026-11-23 Kelas Tailwind kustom di view Blade panel admin tidak ter-compile tanpa tema Filament (butuh npm). Sementara pakai style inline + variabel warna Filament (`var(--success-600)`, `var(--gray-500)`).
- 2026-11-23 `php artisan tinker <berkas>` masuk mode interaktif dan menggantung. Jalankan skrip dengan `php artisan tinker --execute="require '<berkas.php>';" </dev/null`; berkas wajib diawali `<?php`. Skrip uji yang butuh data palsu: alihkan ke `:memory:` dulu (`config([...database => ':memory:']); DB::purge('sqlite');`) agar DB lokal tidak tersentuh.
- 2026-11-23 Cek cetak A4 tanpa printer: `msedge.exe --headless=new --no-pdf-header-footer --user-data-dir=<scratch> --print-to-pdf=<out.pdf> file:///<html>`, lalu baca PDF-nya dengan alat Read.
- 2026-11-23 Hasil `php artisan test` berupa JSON ringkas. Untuk membaca kegagalan, simpan ke berkas di scratchpad lalu baca `failures` dan `error_details` (daftar `failures` saja tidak memuat error).
- 2026-11-23 Di Blade, atribut Alpine `@error` dibaca sebagai direktif Blade `@error` → error "unexpected end of file". Pakai `x-on:error` (berlaku juga untuk nama direktif lain seperti `@json`, `@class`).
- 2026-11-23 Jeda 400 ms antar sentuhan hanya untuk sentuhan siswa (`tap()`); keypad aktivasi dipakai guru dan tidak boleh kena jeda (angka bisa hilang saat mengetik cepat). Di test browser, tunggu ≥ 450 ms setelah "Mulai" sebelum menyentuh kartu.
- 2026-11-23 Test browser: `server.php` Laravel membaca `index.php` dari direktori kerja — jalankan `php -S` dengan cwd = `public/`. Server uji memakai `.env.browser` (dibuat runner, DB `database/browser.sqlite`); skenario lewat `php artisan kiosk:fixture` yang hanya terdaftar saat APP_ENV=browser.
- 2026-11-23 Tailwind v4 memindai seluruh project secara otomatis (termasuk `evoting.txt`) → kelas tak terpakai seperti `backdrop-blur` ikut masuk. `kiosk.css` memakai `@import 'tailwindcss' source(none);` + `@source` eksplisit.
- 2026-11-23 Plus Jakarta Sans hanya sampai ketebalan 800 (`@fontsource/plus-jakarta-sans/900.css` tidak ada).
