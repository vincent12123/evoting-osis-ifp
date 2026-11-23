# UX Flow — E-Voting OSIS Layar Sentuh

> **Layer 1: Spec (pengalaman pengguna).** Dokumen ini menjelaskan *bagaimana pengguna
> menyelesaikan tugasnya*: layar apa, urutan apa, dan apa yang tampil di setiap keadaan.
> Layar yang belum berstatus **Disetujui** tidak boleh dikoding.

- **Versi:** 0.3
- **Status:** Draft
- **Mengacu SPEC versi:** 1.0

---

## 0. Fakta Fisik Layar (dasar semua keputusan kiosk)

| Hal | Nilai | Akibat untuk desain |
|---|---|---|
| Panel | Hisense 75WM61FE, 75", 16:9, 4K (3840×2160), layar sentuh multi-titik | Viewport CSS bisa 1920×1080 (skala 2×) atau 3840×2160, jadi semua ukuran kiosk memakai `vh`/`clamp`, bukan px tetap. Dicek di IFP asli (SPEC §10) |
| Area aktif | ±165 cm × 93 cm | 1vh ≈ 9,3 mm. Tombol 9vh ≈ 8 cm |
| Pemasangan umum | Tepi bawah layar ±80–90 cm dari lantai (troli/dinding), tepi atas ±175 cm | Siswa SMP (tinggi ±135–165 cm) nyaman menjangkau **±90–150 cm dari lantai**, yaitu **pita 35%–95% tinggi layar**. Sepertiga atas layar hanya untuk informasi, **tidak ada target sentuh siswa** di sana (termasuk tombol tutup ✕ di pojok kanan atas seperti di prototipe) |
| Jarak pandang | Siswa berdiri ±40–60 cm dari layar, antrean ±3 m | Siswa melihat layar dari dekat sehingga isi penting harus di tengah, bukan di tepi kiri/kanan yang jauh. Karena layar sangat besar, **antrean bisa melihat pilihan**. Atur posisi IFP menghadap dinding/sekat dan jangan tampilkan pilihan di layar terima kasih (REQ-18) |
| Prosesor | SoC Android, bukan PC | Tanpa blur latar, animasi ringan (transform/opacity saja), foto WebP (NFR-25) |
| Sentuhan tak sengaja | Telapak tangan, lengan baju, dua jari | Abaikan multi-sentuh; jeda 400 ms; tombol "Ya" aktif setelah 600 ms (SPEC §5.5) |

---

## 1. Pengguna & Konteks Pemakaian

| Peran | Perangkat utama | Lokasi/kondisi pakai | Kemampuan teknologi | Catatan |
|---|---|---|---|---|
| Siswa pemilih (kelas 7–9) | IFP 75", berdiri | Ruang kelas/aula/lab, antre, diawasi guru, ±15 detik per orang | Terbiasa HP layar sentuh; banyak yang baru pertama kali memakai IFP; sebagian gugup | Tidak membaca teks panjang; mengenali paslon dari **foto & nomor urut** |
| Guru pengawas | — (tidak memakai aplikasi) | Di samping IFP | Beragam, sebagian senior | Hanya perlu tahu arti layar status ("Belum dibuka", "Koneksi terputus — panggil panitia") |
| Admin / panitia | Laptop (utama), HP (memantau) | Ruang panitia; pada hari-H berkeliling | Terbiasa komputer & Excel | Sibuk pada hari-H; yang penting bisa memantau IFP daring/luring dan buka/tutup dengan cepat |

---

## 2. Tugas Utama (urut dari yang paling sering)

| ID | Peran | Tugas | Frekuensi | Target (langkah / waktu) | REQ terkait |
|---|---|---|---|---|---|
| TSK-01 | Siswa | Memberi suara untuk paslon pilihan | 500–800× pada hari-H | **2 sentuhan, ≤ 15 detik** dari berdiri di depan layar sampai layar terima kasih | REQ-06, 08, 09, 11, 12 |
| TSK-02 | Siswa | Membaca visi-misi satu paslon lalu memilihnya | Sebagian siswa | ≤ 3 sentuhan, ≤ 60 detik | REQ-07 |
| TSK-03 | Admin | Menyiapkan satu IFP sebagai TPS | 2–4× sebelum hari-H (dan bila IFP diganti) | ≤ 2 menit per IFP | REQ-04, 05 |
| TSK-04 | Admin | Membuka / menutup pemungutan | 2× pada hari-H | ≤ 3 klik, efek di IFP ≤ 10 detik | REQ-02, 13 |
| TSK-05 | Admin | Mengisi data satu paslon | 2–6× sebelum hari-H | ≤ 5 menit per paslon | REQ-03 |
| TSK-06 | Admin | Melihat rekap & mencetak berita acara | Setelah ditutup | ≤ 2 menit | REQ-15, 16 |
| TSK-07 | Admin | Memantau IFP (daring/luring, suara masuk) | Terus-menerus pada hari-H | 1 layar, tanpa klik | REQ-22 |

---

## 3. Peta Layar (Sitemap)

```
KIOSK (IFP, tanpa login)                     ADMIN (/admin, login)
/kiosk                                        SCR-A1 Login
 ├─ SCR-K1 Aktivasi IFP (belum ada token)      └─ SCR-A2 Dasbor Pemilihan  ← halaman utama hari-H
 ├─ SCR-K2 Siap — "Sentuh untuk mulai"             ├─ SCR-A3 Paslon (daftar + form)
 └─ SCR-K3 Pilih Paslon                            ├─ SCR-A4 TPS & IFP
     ├─ SCR-K4 Visi & Misi (lapisan)               ├─ SCR-A5 Pengaturan Pemilihan
     ├─ SCR-K5 Konfirmasi (lapisan)                ├─ SCR-A6 Rekap Hasil
     ├─ SCR-K6 Terima Kasih (lapisan)              │    └─ SCR-A7 Berita Acara (cetak)
     └─ SCR-K7 Layar Status (belum dibuka /        └─ SCR-A8 Log Aktivitas
               ditutup / TPS nonaktif / putus)
```

| ID Layar | Nama | Peran yang boleh membuka | Mode desain |
|---|---|---|---|
| SCR-K1 | Aktivasi IFP | Siapa pun di IFP tanpa token | Kiosk |
| SCR-K2 | Siap | IFP teraktivasi | Kiosk |
| SCR-K3 | Pilih Paslon | IFP teraktivasi, status Uji coba/Dibuka | Kiosk |
| SCR-K4 | Visi & Misi | idem | Kiosk |
| SCR-K5 | Konfirmasi | idem (mode 2x sentuh) | Kiosk |
| SCR-K6 | Terima Kasih | idem | Kiosk |
| SCR-K7 | Layar Status | IFP teraktivasi | Kiosk |
| SCR-A1 | Login | Semua | Fungsional |
| SCR-A2 | Dasbor Pemilihan | Admin | Fungsional |
| SCR-A3 | Paslon | Admin | Fungsional |
| SCR-A4 | TPS & IFP | Admin | Fungsional |
| SCR-A5 | Pengaturan Pemilihan | Admin | Fungsional |
| SCR-A6 | Rekap Hasil | Admin | Fungsional |
| SCR-A7 | Berita Acara | Admin | Fungsional (cetak A4) |
| SCR-A8 | Log Aktivitas | Admin | Fungsional |

> **Mode "Kiosk"** = turunan Ekspresif dengan aturan ketat untuk layar sentuh besar (lihat `design-system.md §0`).

---

## 4. Alur per Tugas

### TSK-01 — Siswa memberi suara (mode 2x sentuh, bawaan)
```
SCR-K3 ── sentuh foto ATAU tombol "PILIH 02" ──► SCR-K5 Konfirmasi
SCR-K5 ── "YA, PILIH 02" ──► (Menyimpan suara…) ──► SCR-K6 Terima kasih (3…2…1) ──► SCR-K3
```
- **Mode 1x sentuh:** SCR-K3 ── sentuh ──► (Menyimpan suara…) ──► SCR-K6.
- **Jalan keluar:** "BATAL, GANTI PILIHAN" di SCR-K5 kembali ke SCR-K3 tanpa suara. Konfirmasi yang dibiarkan 30 detik tertutup sendiri.
- **Pencegahan kesalahan:** foto + nomor + nama paslon diulang besar di SCR-K5; tombol "Ya" aktif setelah 600 ms; satu sentuhan "Ya" = satu `client_uuid`; tombol nonaktif selama menyimpan; tombol Batal dan Ya berjauhan (kiri/kanan, jarak ≥ 10vh), dan Ya berwarna paslon.

### TSK-02 — Siswa membaca visi-misi lalu memilih
```
SCR-K3 ── "Visi & Misi" di kartu 02 ──► SCR-K4 ── "PILIH 02" ──► SCR-K5 (atau langsung simpan di mode 1x) ──► SCR-K6
```
- **Jalan keluar:** tombol "KEMBALI" **di bagian bawah** SCR-K4 (bukan ✕ di pojok atas). Sentuh area gelap di luar panel juga menutup. Otomatis tertutup setelah 30 detik tanpa sentuhan.

### TSK-03 — Admin menyiapkan IFP
```
Laptop: SCR-A4 → [Tambah TPS] "TPS Aula" → [Buat kode aktivasi] → kode "482 913" tampil besar + hitung mundur 30:00
IFP:    buka https://<subdomain>/kiosk → SCR-K1 → ketik 482913 di keypad layar → [Hubungkan]
        → SCR-K2 "TPS Aula siap" → guru/panitia sentuh "Mulai" (layar penuh + suara aktif) → SCR-K3 / SCR-K7
Laptop: baris TPS Aula berubah jadi "Daring" ≤ 10 detik
```
- **Jalan keluar:** kode bisa dibatalkan/dibuat ulang; IFP salah TPS → [Lepas IFP] lalu aktivasi ulang.

### TSK-04 — Admin membuka/menutup pemungutan
```
SCR-A2 → [Buka pemungutan] → dialog "Buka pemungutan suara 'Pemilihan Ketua & Wakil OSIS 2026/2027'? Suara uji coba (12) akan dikosongkan." → [Ya, buka]
       → status "Dibuka" + semua IFP pindah dari SCR-K7 ke SCR-K3 ≤ 10 detik
SCR-A2 → [Tutup pemungutan] → dialog "Tutup pemungutan? Setelah ditutup, IFP tidak menerima suara lagi." → [Ya, tutup] → SCR-A6
```

### TSK-05 — Admin mengisi paslon
```
SCR-A3 → [Tambah paslon] → isi nomor, nama ketua/wakil, slogan, visi, misi (repeater), foto, warna → [Simpan paslon] → daftar + "Paslon 02 tersimpan."
```
- Pratinjau kartu kiosk tampil di form, agar admin melihat apakah nama/slogan terlalu panjang.

### TSK-06 — Rekap & berita acara
```
SCR-A2/SCR-A6 → lihat rekap → [Cetak berita acara] → SCR-A7 (tab baru) → dialog cetak browser (A4 / Simpan PDF)
```

---

## 5. Kartu Layar

> Teks di kolom state ditulis **persis** seperti yang akan tampil. `{…}` = isi dinamis.

### SCR-K1 — Aktivasi IFP

- **Status:** Draft
- **Tujuan:** mengaitkan IFP ini ke satu TPS dengan kode dari panitia.
- **Aksi utama:** "Hubungkan"
- **Aksi lain:** keypad angka 0–9, "Hapus"
- **Data:** judul aplikasi, logo sekolah, 6 kotak kode.
- **Hak akses:** siapa pun di IFP yang belum punya token.

```
┌──────────────────────────────────────────────────────────────────────┐
│ [logo]  E-Voting OSIS SMPN 2 Sintang                                  │  ← info (atas)
│                                                                      │
│           Hubungkan IFP ini ke TPS                                    │
│           Masukkan kode 6 angka dari panitia                          │
│              [4][8][2][9][1][_]                                       │
│        ┌───┬───┬───┐                                                  │
│        │ 1 │ 2 │ 3 │                                                  │  ← keypad di pita 40–90%
│        │ 4 │ 5 │ 6 │                                                  │    tombol ≥ 9vh
│        │ 7 │ 8 │ 9 │                                                  │
│        │Hps│ 0 │ ✓ Hubungkan │                                        │
└──────────────────────────────────────────────────────────────────────┘
```

| State | Yang tampil (teks persis) |
|---|---|
| Memuat | Tombol "Hubungkan" berubah jadi "Menghubungkan…" dan nonaktif |
| Kosong | Kotak kode kosong; tombol "Hubungkan" nonaktif sampai 6 angka terisi |
| Gagal | "Kode tidak dikenal atau sudah kedaluwarsa. Minta kode baru ke panitia." · (koneksi) "Tidak bisa terhubung ke server. Periksa WiFi IFP lalu coba lagi." · (terlalu sering) "Terlalu banyak percobaan. Coba lagi dalam 10 menit." · (dilepas) "IFP ini sudah dilepas dari TPS. Masukkan kode aktivasi baru." |
| Berhasil | Pindah ke SCR-K2 |
| Validasi | Hanya angka; maksimal 6 digit |

### SCR-K2 — Siap

- **Status:** Draft
- **Tujuan:** memulai layar penuh dan mengaktifkan suara (browser mewajibkan satu sentuhan) sebelum siswa memakai.
- **Aksi utama:** "Mulai"
- **Data:** nama TPS, nama pemilihan.
- **Hak akses:** IFP teraktivasi. Tampil setelah aktivasi dan setiap kali halaman dimuat ulang/IFP dinyalakan.

```
┌──────────────────────────────────────────────────────────────────────┐
│ [logo]  PEMILIHAN KETUA & WAKIL OSIS 2026/2027                        │
│                                                                      │
│                    ● TPS AULA                                        │
│              IFP sudah terhubung dan siap dipakai                    │
│                                                                      │
│                  ┌──────────────────────────┐                        │
│                  │   ▶  MULAI               │   ← pita 55–75%         │
│                  └──────────────────────────┘                        │
│        Disentuh oleh guru/panitia sebelum siswa mulai memilih         │
└──────────────────────────────────────────────────────────────────────┘
```

| State | Yang tampil (teks persis) |
|---|---|
| Memuat | "Memeriksa koneksi ke server…" |
| Kosong | — (tidak berlaku) |
| Gagal | "Tidak bisa terhubung ke server. Periksa WiFi IFP. Layar akan mencoba lagi otomatis." |
| Berhasil | Setelah "Mulai": layar penuh → SCR-K3 bila status Uji coba/Dibuka, selain itu SCR-K7 |

### SCR-K3 — Pilih Paslon (layar utama siswa)

- **Status:** Draft
- **Tujuan:** siswa mengenali dan menyentuh paslon pilihannya.
- **Aksi utama:** sentuh foto atau tombol "PILIH {nomor}" (satu per kartu)
- **Aksi lain:** "Visi & Misi" per kartu
- **Data:** nama pemilihan, nama TPS, jumlah suara masuk TPS ini, untuk tiap paslon: foto, nomor urut, nama ketua & wakil, slogan, ringkasan visi (maks 2 baris). Pita "UJI COBA" bila status Uji coba.
- **Hak akses:** IFP teraktivasi, status Uji coba/Dibuka.
- **Tata letak:** 2–4 paslon dalam satu baris; 5–6 paslon dalam 3×2. Tanpa scroll. Kepala layar ≤ 12vh. Tombol "PILIH" berada di pita 70–92% tinggi layar.

```
┌──────────────────────────────────────────────────────────────────────┐
│[logo] PEMILIHAN KETUA & WAKIL OSIS 2026/2027   ● TPS AULA   127 suara masuk│ ≤12vh, info saja
│  Sentuh foto paslon pilihanmu. Satu siswa, satu suara.                │
├──────────────────────┬──────────────────────┬────────────────────────┤
│ ┌──┐                 │ ┌──┐                 │ ┌──┐                   │
│ │01│   [ FOTO ]      │ │02│   [ FOTO ]      │ │03│   [ FOTO ]        │ foto: area sentuh
│ └──┘                 │ └──┘                 │ └──┘                   │
│ Arka R. & Naila P.   │ Dimas A. & Citra L.  │ Rian P. & Alisa Z.     │
│ "Inovatif, …"        │ "Harmoni …"          │ "Aksi Nyata …"         │
│ Visi: …(2 baris)     │ Visi: …              │ Visi: …                │
│ [Visi & Misi][PILIH 01]│[Visi & Misi][PILIH 02]│[Visi & Misi][PILIH 03]│ pita 70–92%
└──────────────────────┴──────────────────────┴────────────────────────┘
```

| State | Yang tampil (teks persis) |
|---|---|
| Memuat | Kerangka kartu abu-abu sejumlah paslon + "Memuat data paslon…" |
| Kosong | (seharusnya tidak terjadi karena Buka butuh ≥ 2 paslon) "Data paslon belum tersedia. Panggil panitia." |
| Gagal | Koneksi terputus saat tidak ada siswa memilih → pindah ke SCR-K7 varian "Koneksi terputus" |
| Berhasil | Kartu tampil; foto yang gagal dimuat diganti kotak warna paslon bertuliskan "PASLON {nomor}" |
| Sentuhan | Kartu yang disentuh tampil tertekan (skala 0,97 + garis tebal warna paslon) ≤ 150 ms |

### SCR-K4 — Visi & Misi

- **Status:** Draft
- **Tujuan:** siswa membaca visi & misi satu paslon.
- **Aksi utama:** "PILIH {nomor}"
- **Aksi lain:** "KEMBALI"
- **Data:** foto, nomor, nama, kelas (bila diisi), slogan, visi, misi bernomor.
- **Tata letak:** panel tengah lebar ±60% layar; tombol di bagian bawah panel (pita 80–92%); isi panjang bisa digulir **di dalam panel**.

```
┌──────────────────────────────────────────────────────────────────────┐
│░░░░░░┌────────────────────────────────────────────────────┐░░░░░░░░░│
│░░░░░░│ [foto] 02  Dimas Anggara & Citra Lestari            │░░░░░░░░░│
│░░░░░░│        "Harmoni Sekolah: Kreasi Seni & Prestasi"    │░░░░░░░░░│
│░░░░░░│ VISI   Menjadikan OSIS sebagai pusat …              │░░░░░░░░░│
│░░░░░░│ MISI   1 …   2 …   3 …                              │░░░░░░░░░│
│░░░░░░│ [   KEMBALI   ]                [   PILIH 02   ]      │░░░░░░░░░│
│░░░░░░└────────────────────────────────────────────────────┘░░░░░░░░░│
└──────────────────────────────────────────────────────────────────────┘
```

| State | Yang tampil (teks persis) |
|---|---|
| Memuat | — (data sudah ada di IFP) |
| Kosong | Misi kosong tidak mungkin (wajib 1–7); visi selalu ada |
| Gagal | — |
| Berhasil | Panel tampil; tertutup sendiri setelah 30 detik tanpa sentuhan |

### SCR-K5 — Konfirmasi (mode 2x sentuh)

- **Status:** Draft
- **Tujuan:** memastikan pilihan benar sebelum suara dikirim.
- **Aksi utama:** "YA, PILIH {nomor}"
- **Aksi lain:** "BATAL, GANTI PILIHAN"
- **Data:** foto besar, nomor besar, nama ketua & wakil.
- **Tata letak:** panel tengah; kedua tombol di pita 75–92%, kiri = Batal (netral), kanan = Ya (warna paslon), jarak antar-tombol ≥ 10vh.

```
┌──────────────────────────────────────────────────────────────────────┐
│░░░░░░░░░░┌──────────────────────────────────────────┐░░░░░░░░░░░░░░░│
│░░░░░░░░░░│          Yakin memilih paslon ini?        │░░░░░░░░░░░░░░░│
│░░░░░░░░░░│   [ FOTO BESAR ]   02                     │░░░░░░░░░░░░░░░│
│░░░░░░░░░░│                    Dimas Anggara          │░░░░░░░░░░░░░░░│
│░░░░░░░░░░│                    & Citra Lestari        │░░░░░░░░░░░░░░░│
│░░░░░░░░░░│ [ BATAL, GANTI PILIHAN ]   [ YA, PILIH 02 ]│░░░░░░░░░░░░░░░│
│░░░░░░░░░░└──────────────────────────────────────────┘░░░░░░░░░░░░░░░│
└──────────────────────────────────────────────────────────────────────┘
```

| State | Yang tampil (teks persis) |
|---|---|
| Memuat | Tombol Ya: "Menyimpan suara…" + putaran; kedua tombol nonaktif |
| Kosong | — |
| Gagal | Judul berganti: **"Suara belum tercatat."** + "Koneksi ke server terputus. Jangan pergi dulu — panggil guru pengawas." + tombol "COBA LAGI" (kirim ulang `client_uuid` yang sama) dan "BATAL". · (ditutup) "Pemungutan suara sudah ditutup. Suara ini tidak tercatat." + "KEMBALI" · (rem 2 detik) "Tunggu sebentar, lalu pilih lagi." |
| Berhasil | Pindah ke SCR-K6 |
| Waktu habis | 30 detik tanpa sentuhan → kembali ke SCR-K3 tanpa suara |

### SCR-K6 — Terima Kasih

- **Status:** Draft
- **Tujuan:** memberi tahu suara tercatat dan menyiapkan layar untuk siswa berikutnya.
- **Aksi utama:** tidak ada (otomatis). Tidak ada tombol "lanjut sekarang", supaya siswa tidak menyentuh berulang-ulang.
- **Data:** tanda centang besar, teks terima kasih, hitung mundur. Tidak menyebut paslon (REQ-18).

```
┌──────────────────────────────────────────────────────────────────────┐
│                           ✔ (besar)                                  │
│                    SUARAMU SUDAH TERCATAT                            │
│                       Terima kasih!                                  │
│            Silakan kembali ke tempat dudukmu.                        │
│        Layar siap untuk siswa berikutnya dalam  3  detik             │
└──────────────────────────────────────────────────────────────────────┘
```

| State | Yang tampil (teks persis) |
|---|---|
| Berhasil | Seperti wireframe; bel + confetti bila diaktifkan; pita "UJI COBA — suara ini tidak dihitung" saat status Uji coba |
| Lainnya | Sentuhan selama layar ini diabaikan |

### SCR-K7 — Layar Status

- **Status:** Draft
- **Tujuan:** menjelaskan kenapa siswa belum/tidak bisa memilih, dan apa yang harus dilakukan guru.
- **Aksi utama:** tidak ada (berubah otomatis saat status berubah).
- **Data:** nama TPS, satu ikon + satu judul + satu kalimat.

| Varian | Judul | Kalimat |
|---|---|---|
| Persiapan | "Pemungutan suara belum dibuka" | "Mohon tunggu. Layar akan terbuka otomatis saat panitia membuka pemungutan." |
| Ditutup | "Pemungutan suara sudah ditutup" | "Terima kasih atas partisipasinya." |
| TPS nonaktif | "TPS ini sedang tidak aktif" | "Hubungi panitia." |
| Koneksi terputus | "Koneksi ke server terputus" | "Guru pengawas: periksa WiFi IFP atau hubungi panitia. Layar akan tersambung lagi otomatis." |

### SCR-A1 — Login

- **Status:** Disetujui (2026-11-23) · **Aksi utama:** "Masuk" · Halaman login bawaan Filament, diterjemahkan.

| State | Yang tampil (teks persis) |
|---|---|
| Gagal | "Email atau kata sandi salah." · "Terlalu banyak percobaan masuk. Coba lagi dalam {n} detik." |

### SCR-A2 — Dasbor Pemilihan

- **Status:** Disetujui (2026-11-23)
- **Tujuan:** satu layar untuk hari-H: status, tombol buka/tutup, pantauan IFP & jumlah suara.
- **Aksi utama:** satu tombol yang berubah sesuai status: "Mulai uji coba" / "Buka pemungutan" / "Tutup pemungutan" / "Lihat rekap"
- **Aksi lain:** "Kosongkan suara uji coba", "Tambah 10 suara simulasi" (hanya status Uji coba)
- **Data:** nama pemilihan + lencana status; kartu: total suara, suara terakhir; tabel TPS: nama, IFP Daring/Luring (+ terakhir terlihat), suara masuk, suara terakhir. Diperbarui tiap 10 detik. Perolehan per paslon **tidak** di dasbor (ada di SCR-A6) supaya layar laptop panitia tidak membocorkan hasil sementara di ruangan.

```
┌─────────────────────────────────────────────────────────────┐
│ Pemilihan Ketua & Wakil OSIS 2026/2027   [● DIBUKA]          │
│                                   [ Tutup pemungutan ]       │
├───────────────┬───────────────┬─────────────────────────────┤
│ Total suara   │ Suara terakhir│ IFP daring                  │
│ 412           │ 10.42 WIB     │ 3 dari 4                    │
├───────────────┴───────────────┴─────────────────────────────┤
│ TPS        │ IFP              │ Suara │ Terakhir            │
│ TPS Aula   │ ● Daring         │ 151   │ 10.42               │
│ TPS Lab    │ ○ Luring (3 mnt) │  88   │ 10.37               │
└─────────────────────────────────────────────────────────────┘
```

| State | Yang tampil (teks persis) |
|---|---|
| Memuat | Skeleton kartu & tabel |
| Kosong | (belum ada pemilihan) "Belum ada pemilihan. Buat pemilihan untuk mulai." + "Buat pemilihan" · (belum ada TPS) "Belum ada TPS. Tambahkan TPS dan hubungkan IFP." + "Kelola TPS" |
| Gagal | "Data dasbor gagal diperbarui. Mencoba lagi dalam 10 detik." |
| Berhasil | Toast: "Pemungutan suara dibuka." / "Pemungutan suara ditutup." / "Suara uji coba dikosongkan ({n} suara)." |
| Validasi | Tombol Buka nonaktif + alasan: "Tambahkan minimal 2 paslon lengkap sebelum membuka pemungutan." / "Belum ada IFP yang terhubung ke TPS mana pun." |

### SCR-A3 — Paslon

- **Status:** Disetujui (2026-11-23) · **Aksi utama:** "Tambah paslon" (daftar) / "Simpan paslon" (form)
- **Data:** tabel: nomor, foto kecil, ketua & wakil, slogan. Form: field §4.3 + pratinjau kartu kiosk.

| State | Yang tampil (teks persis) |
|---|---|
| Kosong | "Belum ada paslon. Tambahkan minimal 2 paslon." + "Tambah paslon" |
| Gagal | "Paslon gagal disimpan. Periksa isian yang ditandai merah." |
| Berhasil | "Paslon {nomor} tersimpan." / "Paslon {nomor} dihapus." |
| Validasi | "Nomor urut {nn} sudah dipakai." · "Foto harus JPG, PNG, atau WebP, maksimal 3 MB." · "Maksimal 6 paslon." · "Isi minimal 1 butir misi." |
| Terkunci | Banner: "Data paslon terkunci karena pemungutan sedang berlangsung." / "…karena pemungutan sudah ditutup." Form hanya-baca |
| Hapus | Dialog: "Hapus Paslon {nomor} ({ketua} & {wakil})?" + "Hapus" (danger) / "Batal" |

### SCR-A4 — TPS & IFP

- **Status:** Disetujui (2026-11-23) · **Aksi utama:** "Tambah TPS"; per baris: "Buat kode aktivasi"
- **Aksi lain:** "Lepas IFP", "Nonaktifkan TPS", "Ubah"
- **Data:** nama, lokasi, status TPS, IFP (Belum terhubung / Daring / Luring + terakhir terlihat), suara masuk.
- **Kode aktivasi:** dialog berisi kode besar (format `482 913`), "Berlaku sampai {jam} WIB (30 menit)", alamat kiosk yang harus dibuka di IFP, tombol "Buat kode baru" dan "Tutup". Dialog berubah jadi "IFP terhubung ke {TPS}." begitu kode dipakai.

| State | Yang tampil (teks persis) |
|---|---|
| Kosong | "Belum ada TPS. Tambahkan TPS untuk setiap IFP yang dipakai." + "Tambah TPS" |
| Gagal | "TPS gagal disimpan." · "TPS yang sudah punya suara tidak bisa dihapus. Nonaktifkan saja." |
| Berhasil | "TPS {nama} tersimpan." · "IFP dilepas dari {nama}." |
| Validasi | "Nama TPS sudah dipakai." |
| Lepas IFP | Dialog: "Lepas IFP dari {nama}? IFP itu akan kembali ke layar aktivasi." |

### SCR-A5 — Pengaturan Pemilihan

- **Status:** Disetujui (2026-11-23) · **Aksi utama:** "Simpan pengaturan"
- **Field:** nama pemilihan, tahun ajaran, mode sentuh (radio: "2x sentuh — pilih lalu konfirmasi (disarankan)" / "1x sentuh — langsung tercatat"), jeda layar terima kasih (2/3/5 detik), bel (ya/tidak), confetti (ya/tidak).
- **Bagian "Berita acara"** (v0.3): nama sekolah, alamat sekolah, tempat penandatanganan, daftar penandatangan (jabatan + nama, bisa ditambah/dihapus/diurutkan; bawaan Ketua Panitia, Pembina OSIS, Kepala Sekolah), sakelar "Kolom tanda tangan saksi untuk setiap paslon".
- Memilih 1x sentuh menampilkan peringatan: "Pada mode 1x sentuh, sentuhan tidak sengaja langsung menjadi suara dan tidak bisa dibatalkan."

| State | Yang tampil (teks persis) |
|---|---|
| Berhasil | "Pengaturan tersimpan. IFP akan memakai pengaturan baru dalam 10 detik." |
| Validasi | "Tahun ajaran harus berformat 2026/2027." |

### SCR-A6 — Rekap Hasil

- **Status:** Disetujui (2026-11-23) · **Aksi utama:** "Cetak berita acara"
- **Data:** total suara sah, perolehan & persentase per paslon (batang horizontal + angka, terbanyak diberi tanda "Terbanyak" atau "Suara sama"), tabel silang TPS × paslon, waktu buka/tutup. Saat Uji coba: tab terpisah "Uji coba".

| State | Yang tampil (teks persis) |
|---|---|
| Kosong | "Belum ada suara masuk." |
| Gagal | "Rekap gagal dimuat. Muat ulang halaman." |
| Sementara | Banner: "Hasil sementara — pemungutan belum ditutup." |

### SCR-A7 — Berita Acara (cetak)

- **Status:** Disetujui (2026-11-23) · **Aksi utama:** "Cetak / Simpan PDF"
- **Isi (A4 tegak):** kop (nama & alamat sekolah dari Pengaturan), judul "BERITA ACARA PENGHITUNGAN SUARA PEMILIHAN KETUA DAN WAKIL KETUA OSIS", nama pemilihan & tahun ajaran, waktu buka/tutup (WIB), tabel perolehan per paslon, tabel TPS × paslon + kolom isian tangan "Jumlah pemilih menurut catatan pengawas", total suara, "{tempat}, {tanggal}", kolom tanda tangan penandatangan dari Pengaturan (nama kosong = garis) + kolom "Saksi Paslon {nn}" bila diaktifkan, tanggal cetak. Sebelum Ditutup: watermark "SEMENTARA – PEMUNGUTAN BELUM DITUTUP". Hanya suara resmi (bukan uji coba).

### SCR-A8 — Log Aktivitas

- **Status:** Disetujui (2026-11-23) · Tabel hanya-baca: waktu (WIB), admin, aksi, keterangan. Filter per aksi.

| State | Yang tampil (teks persis) |
|---|---|
| Kosong | "Belum ada aktivitas tercatat." |

---

## 6. Komponen & Pola Berulang

| Pola | Aturan |
|---|---|
| Lapisan (overlay) kiosk | Latar gelap 80% tanpa blur; panel tengah; tombol di bagian bawah panel; tidak ada ✕ di pojok atas |
| Sentuhan kiosk | Respons visual ≤ 150 ms; hanya sentuhan pertama diproses; jeda 400 ms; tanpa efek hover |
| Waktu habis kiosk | Lapisan apa pun ditutup otomatis setelah 30 detik tanpa sentuhan |
| Pembaruan kiosk | Tanya status tiap 5 detik; data paslon dimuat ulang hanya bila `settings_version` berubah **dan** tidak ada lapisan yang terbuka |
| Konfirmasi admin | Dialog menyebut nama data/aksi + tombol danger untuk aksi berisiko + "Batal" |
| Notifikasi admin | Toast Filament 4 detik, kata kerja sama dengan tombol |
| Angka & waktu | Format Indonesia: `1.234`, `10.42 WIB`, persentase `45,3%` |

---

## 7. Daftar Istilah (Bahasa Antarmuka)

| Istilah di UI | Bukan | Catatan |
|---|---|---|
| Paslon | Kandidat, calon (sendirian) | "Pasangan calon" pada judul resmi berita acara |
| Pilih / Memilih | Coblos, vote, submit | Prototipe memakai "Coblos"; diganti agar konsisten (bisa dikembalikan bila panitia suka) |
| Suara | Vote | "Suaramu sudah tercatat" |
| TPS | Bilik, kelas | Satu TPS = satu IFP |
| IFP | TV, layar, perangkat | Di admin: "IFP"; di layar siswa tidak perlu disebut |
| Pemungutan suara | Voting, sesi | "Buka/Tutup pemungutan" |
| Uji coba | Simulasi, tes | |
| Kode aktivasi | PIN, token | Token perangkat tidak pernah tampil ke pengguna |
| Berita acara | Laporan, report | |

---

## 8. Riwayat Persetujuan

| Versi | Tanggal | Layar | Status | Oleh |
|---|---|---|---|---|
| 0.1 | 2026-11-23 | Semua | Draft | AI |
| 0.2 | 2026-11-23 | SCR-A1 – SCR-A8 | Disetujui | Pemilik |
| 0.3 | 2026-11-23 | SCR-A5 (bagian Berita acara), SCR-A7 (isi) | Disetujui (keputusan pemilik CP5) | Pemilik |
