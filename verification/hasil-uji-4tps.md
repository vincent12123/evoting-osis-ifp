# Hasil Uji 4 TPS (NF-22)

- **Waktu:** 2026-11-24T03:47:04.205Z → 2026-11-24T03:57:09.412Z (600 detik)
- **Lingkungan:** komputer pengembang (Windows), 4 proses `php -S` terpisah (satu per TPS), satu database SQLite (WAL, busy_timeout 5 detik, transaksi suara diawali perintah tulis), database uji `browser.sqlite` (bukan data lokal/produksi)
- **Skenario:** 4 IFP virtual memakai API kiosk; status tiap 5 detik; 1 suara per TPS setiap 2 detik setelah jawaban sebelumnya (secepat yang diizinkan rem 2 detik, ±1 suara per 2,3 detik); kiriman ulang UUID sama tiap suara ke-25; pilihan acak berbobot 35/30/20/15%
- **Kesimpulan:** ✅ **LOLOS** — semua suara tercatat tepat sekali, rekap server cocok dengan hitungan di IFP, tanpa galat

## Hasil pemungutan (rekap resmi dari server)

Total suara sah: **1.033** · Paslon terbanyak: **01**

| Paslon | Suara | Persentase |
|---|---:|---:|
| 01 | 375 | 36,3% |
| 02 | 317 | 30,7% |
| 03 | 184 | 17,8% |
| 04 | 157 | 15,2% |

| TPS | 01 | 02 | 03 | 04 | Jumlah |
|---|---:|---:|---:|---:|---:|
| TPS Aula | 89 | 78 | 52 | 40 | **259** |
| TPS Kelas 9A | 83 | 86 | 44 | 45 | **258** |
| TPS Lab Komputer | 99 | 80 | 43 | 36 | **258** |
| TPS Perpustakaan | 104 | 73 | 45 | 36 | **258** |

## Pemeriksaan

| Pemeriksaan | Hasil | Detail |
|---|---|---|
| Total suara di server = jumlah jawaban 201 di sisi IFP | ✅ | 1.033 vs 1.033 |
| Paslon 01: server = hitungan IFP | ✅ | 375 vs 375 |
| Paslon 02: server = hitungan IFP | ✅ | 317 vs 317 |
| Paslon 03: server = hitungan IFP | ✅ | 184 vs 184 |
| Paslon 04: server = hitungan IFP | ✅ | 157 vs 157 |
| TPS Aula: server = hitungan IFP | ✅ | 259 vs 259 |
| TPS Kelas 9A: server = hitungan IFP | ✅ | 258 vs 258 |
| TPS Lab Komputer: server = hitungan IFP | ✅ | 258 vs 258 |
| TPS Perpustakaan: server = hitungan IFP | ✅ | 258 vs 258 |
| Tidak ada galat (5xx, timeout, 429, dll.) | ✅ | 0 |
| Kiriman ulang UUID sama → 200, tidak menambah suara | ✅ | 40/40 |
| Tidak ada client_uuid ganda di database | ✅ | 0 |
| Baris votes = total rekap (tanpa suara uji coba) | ✅ | 1033 baris, 0 uji coba |
| Tabel votes tanpa kolom identitas | ✅ | id, election_id, candidate_id, polling_station_id, is_trial, client_uuid, created_at |
| Pemungutan berstatus Ditutup | ✅ | closed |

## Kinerja per TPS

| TPS | Suara dikirim | Tercatat (201) | Kiriman ulang → 200 | Panggilan status OK | p50 | p95 | maks |
|---|---:|---:|---:|---:|---:|---:|---:|
| TPS Aula | 259 | 259 | 10/10 | 114/114 | 277 ms | 579 ms | 823 ms |
| TPS Lab Komputer | 258 | 258 | 10/10 | 114/114 | 275 ms | 581 ms | 865 ms |
| TPS Perpustakaan | 258 | 258 | 10/10 | 113/113 | 278 ms | 588 ms | 877 ms |
| TPS Kelas 9A | 258 | 258 | 10/10 | 114/114 | 284 ms | 584 ms | 911 ms |
| **Semua** | 1.033 | 1.033 | 40/40 | 455/455 | 277 ms | 581 ms | 911 ms |

## Berita acara

- Dicetak ke PDF: `storage/app/uji-4tps/berita-acara.pdf` (tidak di-commit).

## Catatan

- Uji singkat pertama (sebelum perbaikan) menemukan **29 galat 500 "database is locked" dalam 30 detik**: di PHP 8.3 Laravel mengabaikan `transaction_mode=IMMEDIATE`. Diperbaiki di `CastVote` (perintah tulis di awal transaksi + 3 percobaan) — uji ini dijalankan setelah perbaikan.
- Target NF-22 "±1.200 suara" mengasumsikan tepat 2 detik per suara; dengan jeda dihitung dari jawaban server (agar tidak terkena rem 2 detik) tercapai 1.033 suara ≈ 258 per TPS — tetap jauh di atas kebutuhan hari-H (±200 siswa per IFP dengan ±15 detik per siswa).

- Server uji adalah `php -S` di Windows, bukan nginx + php-fpm di VPS. Uji ini membuktikan logika & integritas data di bawah tulis bersamaan; kinerja di VPS diukur ulang saat deploy (CP7).
- Uji tidak memakai layar IFP fisik; perilaku layar diuji terpisah di `tests/Browser/kiosk.test.mjs` dan nanti di IFP Hisense asli (CP6).
