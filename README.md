# E-Voting OSIS Layar Sentuh

E-voting Ketua & Wakil Ketua OSIS **SMPN 2 Sintang**. Siswa memilih langsung dengan menyentuh layar IFP Hisense 75" (2–4 titik TPS, tanpa HP, diawasi guru). Setiap suara dikirim ke satu server (Laravel 13 + Filament 5 + SQLite), dan panitia mengatur, memantau, serta mencetak berita acara lewat panel admin.

> Prototipe awal (kiosk HTML, data di `localStorage`): [`evoting.txt`](evoting.txt).

---

## 📁 Struktur Dokumentasi

Project ini memakai pendekatan **Spec-Driven Development** (template **app baru**) dengan 3 lapisan.
Untuk memperbaiki aplikasi yang sudah berjalan, pakai template **perbaikan-app**.

| Lapisan | File | Fungsi |
|---|---|---|
| **1. Spec** | [`SPEC.md`](SPEC.md) | Apa yang dibangun |
| **2. Verifier** | [`verification/acceptance-criteria.md`](verification/acceptance-criteria.md) | Kapan dianggap selesai |
| **3. Environment** | [`AGENTS.md`](AGENTS.md) | Aturan kerja AI |
| | [`CLAUDE.md`](CLAUDE.md) | Penghubung agar Claude Code membaca AGENTS.md |
| | [`PROGRESS.md`](PROGRESS.md) | Posisi terakhir pengerjaan antar sesi |
| | [`docs/ux-flow.md`](docs/ux-flow.md) | Alur pengguna, layar, dan state (bagian dari Spec) |
| | [`docs/design-system.md`](docs/design-system.md) | Aturan tampilan |
| | `README.md` | Orientasi & cara menjalankan |

```
evoting/
├── AGENTS.md
├── CLAUDE.md
├── PROGRESS.md
├── README.md
├── SPEC.md
├── docs/
│   ├── ux-flow.md
│   └── design-system.md
└── verification/
    └── acceptance-criteria.md
```

---

## 🔄 Alur Kerja dengan AI

1. **Interview** — AI mewawancarai pemilik project → hasilnya `SPEC.md` (requirement ber-ID `REQ-xx`)
2. **Desain UX** — tugas utama, peta layar, kartu layar dengan 4 state → `docs/ux-flow.md` (disetujui sebelum coding tampilan)
3. **Turunkan Verifier** — setiap REQ → kriteria ya/tidak (positif **dan** negatif), plus kriteria UX & aksesibilitas, di `acceptance-criteria.md`
4. **Siapkan Environment** — `AGENTS.md` + `design-system.md`
5. **Bangun per checkpoint** — AI kerjakan satu checkpoint (SPEC.md §9)
6. **Verifikasi** — cek terhadap acceptance criteria, baru lanjut
7. **Catat posisi** — AI memperbarui `PROGRESS.md` di akhir sesi
8. **Perbaiki sistem** — kesalahan berulang dicatat di `AGENTS.md §8`; perubahan requirement diusulkan di `SPEC.md §12`

### Prompt pembuka sesi baru
```
Baca AGENTS.md, PROGRESS.md, SPEC.md, docs/ux-flow.md, dan verification/acceptance-criteria.md.
Lanjutkan checkpoint sesuai PROGRESS.md. Jelaskan rencanamu dulu sebelum menulis kode.
```

### Prompt menutup sesi
```
Sebelum berhenti: jalankan verifikasi checkpoint ini, laporkan pakai format AGENTS.md §7,
lalu perbarui PROGRESS.md dan matriks keterlacakan.
```

### Prompt untuk memulai project baru (tahap interview)
```
Saya ingin membangun [deskripsi singkat].
Sebelum menulis apa pun, wawancarai saya secara bertahap (maks 5 pertanyaan per putaran)
sampai requirement jelas: tujuan, pengguna, data, alur, hak akses, non-fungsional, tech stack.
Tanyakan juga: kasus gagal tiap fitur, lingkup data per peran/unit, data awal yang
harus diimpor, dan cara deploy. Jika saya belum menentukan tech stack, bandingkan
2–3 pilihan (mis. Laravel / Node.js / Python / Apps Script) beserta kelebihan-kekurangannya
untuk kebutuhan ini, lalu minta saya memilih. Catat setiap jawaban saya sebagai keputusan.
Setelah selesai, susun SPEC.md mengikuti template yang ada (beri ID REQ-xx),
lalu susun docs/ux-flow.md (tugas utama, peta layar, kartu layar dengan
4 state) dan turunkan acceptance-criteria.md. Jangan isi bagian yang belum saya jawab —
masukkan ke §10 Pertanyaan Terbuka.
```

---

## ⚙️ Persyaratan

- PHP 8.3+ (ekstensi `pdo_sqlite`, `gd` atau `imagick` untuk konversi foto WebP)
- Composer 2, Node.js 22 + npm (hanya untuk build aset)
- SQLite 3 (berkas `database/database.sqlite`)
- IFP: browser bawaan/Chrome di IFP Hisense 75WM61FE, terhubung internet/WiFi sekolah

## 🚀 Instalasi

```bash
git clone [url-repo]
cd evoting
composer install && npm install
cp .env.example .env            # isi ADMIN_EMAIL, ADMIN_PASSWORD, APP_URL, APP_TIMEZONE=Asia/Jakarta
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm run build
composer run dev                # http://localhost:8000/admin dan /kiosk
```

## 🧪 Menjalankan Test

```bash
php artisan test          # Pest: model, API, panel admin
npm run build && npm run test:browser   # layar kiosk di Microsoft Edge (Playwright), ±2,5 menit
```

Test browser menyalakan server uji sendiri di `127.0.0.1:8123` dengan database `database/browser.sqlite`
(DB lokal tidak disentuh). Screenshot bukti tersimpan di `storage/app/browser-shots/`.

## 🌐 Deployment

Lihat `SPEC.md §8A`. Ringkas (di VPS smpn2, **jangan saat status Dibuka**):

```bash
# backup dulu: sqlite3 database/database.sqlite ".backup backup-$(date +%F-%H%M).sqlite"
git pull && composer install --no-dev && npm ci && npm run build
php artisan migrate --force && php artisan optimize
```

## 🗳️ SOP singkat hari-H (draf)

1. H-1: aktivasi tiap IFP ke TPS-nya (panel admin → TPS & IFP → Buat kode aktivasi), jalankan **Uji coba**.
2. Posisikan IFP agar layar tidak terlihat dari antrean (menghadap dinding/sekat, antrean ≥ 3 m).
3. Pagi hari-H: sentuh **Mulai** di tiap IFP, cek semua IFP "Daring" di dasbor, lalu **Buka pemungutan**.
4. Guru pengawas memastikan satu siswa satu kali sentuh "YA". Bila muncul "Suara belum tercatat", siswa menunggu lalu memilih ulang.
5. Selesai: **Tutup pemungutan** → Rekap → Cetak berita acara → backup database.

---

## 👤 Kontak

- **Pemilik:** Sukardi (pengembang) — Panitia Pemilihan OSIS SMPN 2 Sintang
- **Status project:** Perencanaan (SDD draft)
