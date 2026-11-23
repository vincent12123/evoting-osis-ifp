# SPEC.md — E-Voting OSIS Layar Sentuh

> **Layer 1: Spec.** Dokumen ini adalah sumber kebenaran tunggal tentang *apa* yang dibangun.
> Semua keputusan di sini hasil wawancara (interview) dengan pemilik project.
> Jika ada konflik antara dokumen lain dan SPEC.md, SPEC.md yang berlaku.
>
> **Konvensi penulisan:**
> - Teks dalam `[kurung siku]` = placeholder yang **wajib diganti**. Teks berawalan `mis.` hanya contoh, **bukan keputusan**.
> - Setiap requirement diberi ID `REQ-xx` agar bisa dilacak ke acceptance criteria.
> - SPEC hanya boleh diubah oleh pemilik. AI boleh **mengusulkan** perubahan (lihat §12), bukan mengubah langsung.

- **Versi:** 1.1
- **Status:** Disepakati
- **Pemilik:** Sukardi (pengembang) — untuk Panitia Pemilihan OSIS SMPN 2 Sintang
- **Terakhir diperbarui:** 2026-11-23
- **Sumber awal:** prototipe `evoting.txt` (kiosk HTML satu file, data di `localStorage`)

---

## 1. Latar Belakang & Tujuan

**Masalah yang diselesaikan:**
SMPN 2 Sintang ingin memilih Ketua & Wakil Ketua OSIS secara elektronik memakai **layar IFP Hisense 75 inci
(seri 75WM61FE, bantuan pemerintah)**. Siswa memilih **langsung dengan menyentuh layar**, tanpa HP,
dan diawasi guru. Prototipe yang sudah ada menyimpan suara di `localStorage` masing-masing layar.
Akibatnya suara tidak terkumpul ke satu tempat, bisa hilang kalau cache browser dibersihkan,
bisa di-nol-kan oleh siapa saja lewat panel di layar itu sendiri, dan rekapnya harus dijumlah manual dari tiap IFP.

**Tujuan project:**
- Beberapa IFP (2–4 titik) menjadi TPS yang mengirim setiap suara ke **satu database pusat** di server.
- Siswa memilih dalam ≤ 2 sentuhan tanpa mengetik apa pun, dengan layar yang mudah dijangkau siswa SMP yang berdiri.
- Pengaturan, kontrol buka/tutup, dan rekap hanya bisa dilakukan **admin/panitia** yang login, bukan dari layar siswa.
- Hasil akhir (per paslon, per TPS) dan berita acara bisa dicetak tanpa menghitung manual.

**Indikator keberhasilan:**
- 500–800 siswa selesai memilih dalam **satu hari sekolah** memakai 2–4 IFP.
- Rata-rata waktu per siswa (mendekati layar → layar terima kasih) **≤ 15 detik** pada mode 2x sentuh.
- **0 suara hilang**: jumlah suara di database = jumlah layar "Terima kasih" yang tampil (dicek saat uji coba).
- Admin menyiapkan satu IFP baru sebagai TPS dalam **≤ 2 menit**.

---

## 2. Pengguna & Peran

| Peran | Deskripsi | Jumlah |
|---|---|---|
| Siswa pemilih | Menyentuh foto paslon di IFP untuk memberi suara. **Tidak login, tidak diidentifikasi.** | ±500–800 |
| Admin / panitia | Login ke panel admin: atur pemilihan, paslon, TPS/IFP, buka/tutup pemungutan, lihat rekap, cetak berita acara | 1–3 akun |
| Guru pengawas | Mengawasi antrean dan layar **secara fisik** di tiap titik. **Tidak punya akun** dan tidak memakai aplikasi (keputusan wawancara). | 1 per TPS |
| Perangkat IFP (TPS) | Bukan orang. IFP yang sudah diaktivasi dengan kode dan dikenali server sebagai satu TPS | 2–4 |

---

## 3. Ruang Lingkup

### 3.1 Masuk MVP
| ID | Requirement | Prioritas |
|---|---|---|
| REQ-01 | Admin login ke panel admin; seluruh pengaturan & hasil hanya bisa diakses setelah login | Wajib |
| REQ-02 | Admin mengelola **satu pemilihan aktif** (nama, tahun ajaran) beserta statusnya: Persiapan → Uji coba → Dibuka → Ditutup | Wajib |
| REQ-03 | Admin mengelola paslon: nomor urut, nama ketua & wakil, slogan, visi, misi (daftar), foto, warna aksen. Minimal 2, maksimal 6 paslon | Wajib |
| REQ-04 | Admin mengelola TPS (nama/lokasi) dan membuat **kode aktivasi 6 digit** sekali pakai untuk mengaitkan satu IFP ke satu TPS | Wajib |
| REQ-05 | IFP diaktivasi sekali dengan kode tersebut. Setelah itu IFP diingat sebagai TPS itu (tetap tersimpan meski halaman dimuat ulang atau IFP dinyalakan ulang) tanpa login | Wajib |
| REQ-06 | Layar pilih paslon di IFP menampilkan semua paslon (foto besar, nomor urut, nama, slogan, ringkasan visi) tanpa scroll | Wajib |
| REQ-07 | Siswa dapat membuka **visi & misi** lengkap satu paslon dan memilih dari sana | Wajib |
| REQ-08 | Mode sentuh diatur admin: **2x sentuh** (pilih → konfirmasi "Ya") atau **1x sentuh** (langsung tercatat). Bawaan: 2x sentuh | Wajib |
| REQ-09 | Suara **hanya dianggap sah setelah server menyimpannya**. Layar "Terima kasih" tampil hanya setelah server mengonfirmasi | Wajib |
| REQ-10 | Bila suara gagal terkirim (koneksi putus/server error), IFP menampilkan pesan "Suara belum tercatat" dan **tidak** menghitungnya. Siswa memilih ulang setelah koneksi pulih | Wajib |
| REQ-11 | Satu sentuhan "Ya" menghasilkan **tepat satu** suara, termasuk bila tombol tertekan dua kali atau permintaan dikirim ulang (idempoten) | Wajib |
| REQ-12 | Setelah suara tercatat, layar "Terima kasih" tampil dengan hitung mundur (2/3/5 detik, diatur admin), lalu kembali otomatis ke layar pilih paslon | Wajib |
| REQ-13 | Admin membuka & menutup pemungutan dengan tombol. Perubahan status sampai ke semua IFP **≤ 10 detik**; saat tidak Dibuka/Uji coba, IFP menolak suara dan menampilkan layar status | Wajib |
| REQ-14 | Selama pemungutan, IFP hanya menampilkan **jumlah suara masuk di TPS itu**, tanpa perolehan per paslon | Wajib |
| REQ-15 | Admin melihat rekap: total suara, perolehan & persentase per paslon, rincian per TPS, waktu suara terakhir per TPS | Wajib |
| REQ-16 | Admin mencetak **berita acara** dari browser (cetak/PDF A4): kop sekolah, rekap per paslon & per TPS, waktu buka/tutup, kolom isian tangan "jumlah pemilih menurut catatan pengawas" per TPS, dan kolom tanda tangan. Penandatangan (jabatan + nama), nama/alamat sekolah, tempat penandatanganan, dan kolom saksi tiap paslon diatur admin di Pengaturan Pemilihan; nama kosong dicetak sebagai garis isian | Wajib |
| REQ-17 | Efek suara bel dan confetti saat suara tercatat, bisa dimatikan admin | Sebaiknya |
| REQ-18 | Suara bersifat **rahasia**: database tidak menyimpan identitas pemilih, IP, atau data perangkat per suara. Layar terima kasih **tidak** menyebut paslon yang dipilih, karena layar 75" terlihat oleh antrean (beda dari prototipe) | Wajib |
| REQ-19 | Data paslon **terkunci** (tidak bisa diubah/dihapus) saat status Dibuka atau Ditutup. Suara sah tidak bisa diubah atau dihapus lewat aplikasi | Wajib |
| REQ-20 | Status **Uji coba**: IFP menerima suara bertanda *uji coba* yang tidak masuk rekap resmi dan bisa dikosongkan admin. Fungsi "Tambah 10 suara simulasi" hanya tersedia di status ini (menggantikan tombol di prototipe) | Wajib |
| REQ-21 | Server menolak suara dari TPS yang sama bila jaraknya < 2 detik dari suara sebelumnya di TPS itu, sebagai rem terhadap sentuhan beruntun atau alat klik otomatis | Sebaiknya |
| REQ-22 | Admin melihat status setiap IFP: **Daring** (terlihat ≤ 30 detik lalu) atau **Luring**, beserta waktu terakhir terlihat, dan bisa **melepas** IFP dari TPS | Wajib |
| REQ-23 | Aktivitas penting tercatat di log: login admin, ubah status pemilihan, ubah paslon, aktivasi/lepas IFP, kosongkan suara uji coba | Wajib |

> Aturan: setiap REQ **wajib** punya minimal satu kriteria di `verification/acceptance-criteria.md`.

### 3.2 Tidak Masuk MVP (Out of Scope)
- **Daftar pemilih (DPT) / login siswa / NIS / token** — keputusan pemilik: pencegahan memilih dua kali cukup dengan pengawasan guru. Konsekuensinya diterima: sistem **tidak bisa** membuktikan satu siswa = satu suara (lihat §10 tentang pencocokan dengan absen manual).
- **Akun guru pengawas** dan tombol "buka giliran" dari HP guru — pemilik memilih reset otomatis seperti prototipe.
- **Simpan sementara di IFP saat offline** — pemilik memilih "tolak & minta ulang" agar tidak ada risiko suara ganda.
- **Layar hasil/quick count publik** — hasil hanya dilihat admin dan dicetak di berita acara.
- **Multi-unit / multi-sekolah** — hanya SMPN 2 Sintang.
- **Beberapa pemilihan berjalan bersamaan** — hanya satu pemilihan aktif. Pemilihan tahun lalu cukup diarsipkan (status Ditutup).
- **Aplikasi Android native / APK kiosk** — cukup browser bawaan IFP (layar penuh).
- **Edit nama/foto paslon dari layar IFP** (ada di prototipe) — dipindah ke panel admin.

---

## 4. Model Data / Content Model

> Nama tabel & kolom dalam bahasa Inggris mengikuti konvensi Laravel; label UI dalam Bahasa Indonesia.

### 4.1 `users` — Admin/panitia
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| name | string(100) | Ya | |
| email | string, unik | Ya | dipakai login |
| password | string (hash) | Ya | bcrypt/argon, minimal 10 karakter |

### 4.2 `elections` — Pemilihan
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| name | string(150) | Ya | mis. judul yang tampil di kepala layar IFP |
| school_year | string(9) | Ya | format `2026/2027` |
| status | enum: `preparation`, `trial`, `open`, `closed` | Ya | bawaan `preparation`. Label: Persiapan / Uji coba / Dibuka / Ditutup |
| is_active | boolean | Ya | **hanya satu** baris boleh `true` |
| touch_mode | enum: `confirm`, `instant` | Ya | bawaan `confirm` (2x sentuh) |
| reset_delay_seconds | tinyint: 2, 3, 5 | Ya | bawaan 3 |
| sound_enabled | boolean | Ya | bawaan `true` |
| confetti_enabled | boolean | Ya | bawaan `true` |
| opened_at | datetime | Tidak | diisi saat pertama kali Dibuka |
| closed_at | datetime | Tidak | diisi saat Ditutup |
| settings_version | integer | Ya | naik setiap paslon/pengaturan berubah, dipakai IFP untuk memuat ulang data |
| school_name | string(150) | Tidak | kop berita acara, mis. nama sekolah |
| school_address | string(255) | Tidak | kop berita acara |
| signing_place | string(60) | Tidak | tempat penandatanganan berita acara; kosong = garis isian |
| signatories | json: daftar {role, name}, 0–8 butir | Ya | bawaan: Ketua Panitia, Pembina OSIS, Kepala Sekolah (nama kosong) |
| witness_signatures | boolean | Ya | bawaan `true`: satu kolom tanda tangan saksi per paslon |

### 4.3 `candidates` — Paslon
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| election_id | FK | Ya | |
| number | tinyint 1–6 | Ya | unik per pemilihan, tampil `01`, `02`, … |
| chair_name | string(60) | Ya | calon ketua |
| chair_class | string(20) | Tidak | mis. kelas calon ketua |
| vice_name | string(60) | Ya | calon wakil |
| vice_class | string(20) | Tidak | |
| tagline | string(100) | Ya | slogan |
| vision | text (≤ 500 karakter) | Ya | |
| missions | json: array 1–7 butir, tiap butir ≤ 200 karakter | Ya | |
| photo_path | string | Ya | JPG/PNG/WebP ≤ 3 MB saat unggah, disimpan ulang sebagai WebP ≤ 1600px sisi terpanjang |
| accent | enum token: `paslon-1` … `paslon-6` | Ya | dari `docs/design-system.md §2.4` |

### 4.4 `polling_stations` — TPS
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| name | string(50), unik | Ya | mis. tampil di lencana IFP |
| location | string(100) | Tidak | keterangan tempat |
| is_active | boolean | Ya | TPS nonaktif menolak suara |
| activation_code | char(6) | Tidak | angka acak, **berlaku 30 menit**, sekali pakai; dihapus setelah dipakai/kedaluwarsa |
| activation_code_expires_at | datetime | Tidak | |
| device_token_hash | string(64) | Tidak | SHA-256 dari token acak 40+ karakter yang disimpan di IFP. Satu TPS = satu IFP aktif |
| device_activated_at | datetime | Tidak | |
| device_last_seen_at | datetime | Tidak | diperbarui setiap IFP menanyakan status |

### 4.5 `votes` — Suara
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| election_id | FK | Ya | |
| candidate_id | FK | Ya | harus paslon dari pemilihan yang sama |
| polling_station_id | FK | Ya | |
| is_trial | boolean | Ya | `true` bila dicatat saat status Uji coba |
| client_uuid | uuid, **unik** | Ya | dibuat IFP per sentuhan "Ya"; kunci idempoten (REQ-11) |
| created_at | datetime | Ya | |

> **Tidak ada** kolom identitas siswa, IP, user-agent, atau sidik perangkat (REQ-18). Tidak ada `updated_at`; tidak ada aksi ubah/hapus untuk suara sah.

### 4.6 `activity_logs` — Log aktivitas
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| user_id | FK nullable | Tidak | null bila aksi dari IFP (aktivasi) |
| action | string(50) | Ya | mis. `election.opened` |
| subject_type / subject_id | string / int | Tidak | |
| properties | json | Tidak | nilai lama → baru |
| created_at | datetime | Ya | |

### 4.7 Relasi
- `elections` **one-to-many** `candidates` — paslon milik satu pemilihan.
- `elections` **one-to-many** `votes`; `candidates` **one-to-many** `votes`; `polling_stations` **one-to-many** `votes`.
- `polling_stations` berdiri sendiri (dipakai ulang antar-pemilihan); rekap selalu difilter `election_id`.

---

## 5. Fitur & Workflow

### 5.1 Login admin
**Alur:** buka `/admin` → isi email & password → Dasbor pemilihan.
**Requirement terkait:** REQ-01, REQ-23 · **Layar & tugas:** SCR-A1 · —
**Aturan bisnis:** semua rute `/admin/*` wajib login; halaman kiosk & API kiosk **tidak** memakai login admin.
**Kasus gagal:** password salah → "Email atau kata sandi salah." · 5 kali gagal dalam 1 menit → dikunci 1 menit.

### 5.2 Kelola pemilihan & status
**Alur:**
```
Persiapan ──(Mulai uji coba)──► Uji coba ──(Kosongkan suara uji coba + Buka pemungutan)──► Dibuka ──(Tutup pemungutan)──► Ditutup
     └──────────────────────────(Buka pemungutan, tanpa uji coba)──────────────────────────►┘
```
**Requirement terkait:** REQ-02, REQ-13, REQ-19, REQ-20 · **Layar & tugas:** SCR-A2, SCR-A5 · TSK-04
**Aturan bisnis:**
- Hanya satu pemilihan `is_active`. Mengaktifkan yang lain = menonaktifkan yang lama.
- **Buka pemungutan** butuh ≥ 2 paslon lengkap (foto ada) dan ≥ 1 TPS aktif dengan IFP teraktivasi.
- Saat beralih dari Uji coba ke Dibuka, suara uji coba **otomatis dikosongkan** (dicatat di log); rekap resmi mulai dari 0.
- Buka dan tutup wajib lewat dialog konfirmasi yang menyebut nama pemilihan.
- Status Ditutup bersifat **final**; tidak bisa dibuka lagi dari aplikasi.
- Status baru tampil di semua IFP ≤ 10 detik (IFP menanyakan status tiap 5 detik).
**Kasus gagal:** buka tanpa paslon cukup → tombol dinonaktifkan + "Tambahkan minimal 2 paslon lengkap sebelum membuka pemungutan." · tanpa IFP aktif → "Belum ada IFP yang terhubung ke TPS mana pun."

### 5.3 Kelola paslon
**Requirement terkait:** REQ-03, REQ-19 · **Layar & tugas:** SCR-A3 · TSK-05
**Aturan bisnis:** nomor urut unik 1–6; warna aksen unik per pemilihan; foto wajib; misi 1–7 butir; terkunci saat Dibuka/Ditutup (form hanya-baca + pesan).
**Kasus gagal:** nomor urut ganda → "Nomor urut 02 sudah dipakai." · foto > 3 MB / bukan gambar → "Foto harus JPG, PNG, atau WebP, maksimal 3 MB." · paslon ke-7 → "Maksimal 6 paslon." · ubah saat Dibuka → "Data paslon terkunci karena pemungutan sedang berlangsung."

### 5.4 TPS & aktivasi IFP
**Alur:**
```
Admin: buat TPS → [Buat kode aktivasi] → kode 6 digit tampil besar (berlaku 30 menit)
IFP:   buka alamat kiosk → layar Aktivasi → ketik kode di keypad layar → [Hubungkan] → layar Siap ("TPS Aula")
```
**Requirement terkait:** REQ-04, REQ-05, REQ-22 · **Layar & tugas:** SCR-A4, SCR-K1, SCR-K2 · TSK-03
**Aturan bisnis:**
- Kode: 6 angka acak, sekali pakai, kedaluwarsa 30 menit. Setelah dipakai, server memberi IFP **token perangkat** (acak, ≥ 40 karakter) yang disimpan di `localStorage` IFP; server hanya menyimpan hash-nya.
- Satu TPS = satu IFP. Aktivasi IFP baru ke TPS yang sama membuat token lama tidak berlaku (IFP lama kembali ke layar Aktivasi).
- **Lepas IFP** menghapus token; IFP itu kembali ke layar Aktivasi ≤ 10 detik.
- Batas percobaan kode: 10 kali salah per 10 menit per alamat IP → ditolak sementara.
- TPS yang sudah punya suara sah tidak bisa dihapus, hanya dinonaktifkan.
**Kasus gagal:** kode salah/kedaluwarsa → "Kode tidak dikenal atau sudah kedaluwarsa. Minta kode baru ke panitia." · token tidak berlaku → IFP kembali ke SCR-K1 dengan pesan "IFP ini sudah dilepas dari TPS. Masukkan kode aktivasi baru."

### 5.5 Memberi suara (inti)
**Alur (mode 2x sentuh):**
```
SCR-K3 sentuh foto/tombol PILIH → SCR-K5 konfirmasi → [YA, COBLOS] → IFP kirim (client_uuid) → server simpan
  → SCR-K6 Terima kasih (hitung mundur) → SCR-K3
```
Mode 1x sentuh: sentuh foto/tombol langsung mengirim suara (tanpa SCR-K5).
**Requirement terkait:** REQ-06–REQ-12, REQ-14, REQ-17, REQ-18, REQ-21 · **Layar & tugas:** SCR-K3–SCR-K7 · TSK-01, TSK-02
**Aturan bisnis (server):** suara diterima hanya bila token perangkat sah, TPS aktif, pemilihan aktif berstatus Dibuka (→ `is_trial=false`) atau Uji coba (→ `is_trial=true`), dan paslon milik pemilihan itu. `client_uuid` yang sama → server membalas sukses tanpa menambah suara.
**Aturan bisnis (IFP):**
- Hanya sentuhan pertama yang diproses; sentuhan lain selama 400 ms diabaikan. Multi-sentuh (telapak tangan/lengan) tidak memicu pilihan.
- Tombol "YA, COBLOS" baru aktif **600 ms** setelah konfirmasi muncul, agar sentuhan ganda tidak langsung tembus jadi suara.
- Selama mengirim: tombol nonaktif + "Menyimpan suara…". Batas waktu 8 detik → state gagal.
- Konfirmasi atau visi-misi yang dibiarkan **30 detik** tanpa sentuhan → tertutup otomatis, kembali ke SCR-K3, tanpa suara.
- Layar terima kasih tidak menyebut nama/nomor paslon.
**Kasus gagal:** koneksi putus/timeout/server 5xx → SCR-K5 berubah jadi pesan gagal, suara **tidak** dihitung · pemungutan ditutup saat siswa di layar konfirmasi → "Pemungutan suara sudah ditutup. Suara ini tidak tercatat." · suara < 2 detik dari suara sebelumnya di TPS (REQ-21) → ditolak, IFP menampilkan "Tunggu sebentar, lalu pilih lagi."

### 5.6 Rekap & berita acara
**Requirement terkait:** REQ-14, REQ-15, REQ-16 · **Layar & tugas:** SCR-A6, SCR-A7 · TSK-06
**Aturan bisnis:** rekap resmi hanya menghitung `is_trial=false`; saat Uji coba, rekap terpisah berlabel "UJI COBA". Persentase 1 angka desimal; paslon terbanyak diberi tanda (bila seri: "Suara sama"). Berita acara bisa dicetak kapan saja, tetapi sebelum Ditutup diberi watermark **"SEMENTARA – PEMUNGUTAN BELUM DITUTUP"**.
**Kasus gagal:** belum ada suara → "Belum ada suara masuk." (bukan grafik kosong).

---

## 6. Hak Akses

| Aksi | Admin | IFP teraktivasi | Publik (tanpa login/token) |
|---|---|---|---|
| Kelola pemilihan, paslon, TPS, status | ✅ | ❌ | ❌ |
| Lihat rekap & cetak berita acara | ✅ | ❌ | ❌ |
| Lihat daftar paslon & status pemilihan (API kiosk) | ✅ | ✅ (pemilihan aktif saja) | ❌ |
| Kirim suara | ❌ | ✅ (hanya untuk TPS-nya) | ❌ |
| Lihat jumlah suara masuk | ✅ (semua) | ✅ (TPS-nya saja, tanpa per paslon) | ❌ |
| Aktivasi IFP dengan kode | — | — | ✅ (halaman aktivasi saja) |

> §6.1 Lingkup Data dihapus — aplikasi bukan multi-unit.

---

## 7. Kebutuhan Non-Fungsional

**Jenis project:** ☐ Website publik  ☑ Aplikasi internal (+ layar kiosk)  ☐ API / layanan  ☐ Aplikasi mobile/PWA

### 7.1 Umum
| ID | Aspek | Target |
|---|---|---|
| NFR-01 | Keamanan | Login admin wajib; CSRF untuk form admin; API kiosk memakai token perangkat (Bearer); validasi input & unggahan; password di-hash; HTTPS (sertifikat valid) |
| NFR-02 | Tampilan layar | **IFP:** landscape 16:9, layar pilih paslon tanpa scroll, viewport CSS 1920×1080 sampai 3840×2160 (ukuran memakai satuan relatif `vh`/`clamp`). **Admin:** 360px sampai desktop |
| NFR-03 | Bahasa | UI & pesan error Bahasa Indonesia, sapaan "kamu" untuk siswa di IFP, netral-formal di admin |
| NFR-04 | Zona waktu | Asia/Jakarta (WIB, Sintang); simpan UTC di DB, tampilkan WIB |
| NFR-05 | Backup & restore | Salin `database.sqlite` (pakai `sqlite3 .backup`) + folder foto: otomatis tiap 1 jam selama hari pemilihan dan sesaat setelah Ditutup; uji restore sebelum hari-H |
| NFR-06 | Usabilitas & aksesibilitas kiosk | Target sentuh siswa ≥ 9vh tinggi (≈ 8 cm di layar 75"); semua target sentuh siswa berada di pita **35%–95% tinggi layar** (terjangkau siswa SMP berdiri); teks nama paslon ≥ 3,5vh, teks badan ≥ 2,2vh; kontras ≥ 4.5:1; tidak bergantung hover; 4 state per layar; TSK-01 lolos uji 5 siswa |

### 7.3 Aplikasi internal / data sensitif
| ID | Aspek | Target |
|---|---|---|
| NFR-20 | Audit log | Aksi pada REQ-23 tercatat (siapa, kapan, nilai lama → baru) |
| NFR-21 | Kerahasiaan suara | Tidak ada data yang bisa mengaitkan suara dengan siswa (REQ-18) |
| NFR-22 | Konkurensi | 4 IFP × 1 suara per 2 detik selama 10 menit (±1.200 suara) → 0 error, 0 suara hilang/ganda. SQLite mode WAL + `busy_timeout` 5 detik |
| NFR-23 | Koneksi lemah | Tidak ada antrean offline; IFP mendeteksi putus ≤ 10 detik dan menampilkan layar status; pulih otomatis tanpa memuat ulang manual |
| NFR-24 | Integritas data | Suara sah tidak bisa diubah/dihapus dari aplikasi; jumlah suara per TPS di rekap = jumlah baris `votes` |
| NFR-25 | Kinerja IFP | Prosesor IFP bukan PC: tanpa `backdrop-blur`, tanpa CDN Tailwind (aset di-build lokal), font disajikan sendiri, foto WebP; respons sentuhan pertama tampil ≤ 150 ms |

---

## 8. Tech Stack

| Lapisan | Pilihan | Alasan |
|---|---|---|
| Jenis aplikasi | Web: panel admin + halaman kiosk + API JSON kecil untuk kiosk | IFP cukup membuka browser, tanpa APK |
| Bahasa & runtime | PHP 8.3+ | Tersedia di VPS smpn2 |
| Framework backend | Laravel 13 | Pilihan pemilik; pola sama dengan app formptk (Laravel 13 + Filament 5 + SQLite) |
| Frontend kiosk | Blade + Alpine.js + Tailwind (build Vite, tanpa CDN) | Ringan untuk prosesor IFP; state layar sederhana cukup Alpine |
| Admin panel | Filament 5 | CRUD paslon/TPS, dasbor, rekap cepat dibuat |
| Database | SQLite (WAL) | Satu sekolah, beban kecil (≤ 4 penulis); cadangan cukup salin satu file |
| Autentikasi | Session Filament (admin); token perangkat Bearer (IFP) | IFP tidak boleh memakai akun admin |
| Pembaruan status ke IFP | Polling HTTP tiap 5 detik | 4 IFP → beban sangat kecil; tanpa WebSocket/Reverb |
| Integrasi eksternal | Tidak ada | |
| Hosting | VPS smpn2 (PHP & Node tersedia), nginx + php-fpm, subdomain sekolah | Keputusan pemilik |

**Pertimbangan pemilihan:** sama dengan app lain milik pengembang (mudah dirawat), beban sangat kecil, tidak butuh MySQL.

---

## 8A. Data Awal & Deployment

### 8A.1 Data awal / migrasi
| Data | Sumber | Cara masuk | Penanggung jawab |
|---|---|---|---|
| Akun admin pertama | — | Seeder, email & password dari `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`) | Pengembang |
| Data paslon (nama, slogan, visi, misi, foto) | Panitia OSIS | Diisi manual di panel admin | Panitia |
| Daftar TPS (2–4 titik) | Panitia | Diisi manual di panel admin | Panitia |
| Logo sekolah/OSIS & warna resmi | Sekolah | Berkas di `public/images/`, token di `design-system.md` | Pengembang |

### 8A.2 Deployment & rollback
- **Lingkungan:** lokal (pengembangan) → VPS smpn2 (produksi). Uji coba di produksi memakai status **Uji coba**.
- **Cara deploy:** `git pull` → `composer install --no-dev` → `npm ci && npm run build` → `php artisan migrate --force` → `php artisan optimize` → reload php-fpm.
- **Sebelum deploy produksi:** backup `database.sqlite` + `storage/app/public` wajib.
- **Larangan deploy:** tidak ada deploy saat status **Dibuka**.
- **Rencana rollback:** checkout tag rilis sebelumnya + restore backup DB.
- **Migrasi database:** hanya yang bisa di-rollback; migrasi destruktif wajib disetujui pemilik.

---

## 9. Rencana Bertahap (Checkpoints)

| # | Checkpoint | Hasil yang bisa dicek | Status |
|---|---|---|---|
| 1 | Setup Laravel 13 + Filament 5 + SQLite (WAL) + Pest, migrasi semua tabel §4, seeder admin | `php artisan migrate:fresh --seed` sukses, test pertama hijau, login admin jalan (AC-01, AC-02) | ✅ 2026-11-23 |
| 2 | Admin: pemilihan, paslon, pengaturan sentuh, log aktivitas | AC-10 s/d AC-19 lolos | ✅ 2026-11-23 |
| 3 | Admin: TPS, kode aktivasi, status IFP; API kiosk (aktivasi, status, kirim suara) | AC-20 s/d AC-39 lolos (feature test API) | ✅ 2026-11-23 |
| 4 | Layar kiosk di IFP: SCR-K1 s/d SCR-K7 | AC-40 s/d AC-49, UX-K lolos; screenshot 1920×1080 & 3840×2160 | ☐ |
| 5 | Rekap, berita acara cetak, alur status lengkap (uji coba → dibuka → ditutup) | AC-50 s/d AC-56 lolos | ✅ 2026-11-23 |
| 6 | Uji beban + uji di IFP Hisense asli + uji pengguna 5 siswa + poles UX | NF-22, UX-T-01, UX-T-02, A11Y lolos | ☐ |
| 7 | Deploy ke VPS smpn2 + backup otomatis + SOP hari-H | NF-04, NF-05 lolos; situs bisa diakses dari subdomain | ☐ |

> Posisi terakhir pengerjaan checkpoint dicatat di `PROGRESS.md`.

---

## 10. Pertanyaan Terbuka

- [x] ~~Kerahasiaan layar terima kasih (REQ-18)~~ → disetujui 2026-11-23
- [x] ~~Rem 2 detik per TPS (REQ-21)~~ → disetujui 2026-11-23
- [x] ~~Status Ditutup final~~ → disetujui 2026-11-23
- [ ] **Warna resmi & logo SMPN 2 Sintang** — belum diterima. Token warna di `design-system.md` masih **sementara** (dari prototipe).
- [ ] **Subdomain persisnya** (domain sekolah) dan apakah HTTPS lewat Cloudflare/Let's Encrypt.
- [ ] **VPS smpn2:** OS, versi PHP, akses SSH, user kerja. Perlu dicek sebelum checkpoint 7.
- [ ] **Model & OS IFP:** versi Android dan browser bawaan Hisense 75WM61FE, ukuran viewport CSS sebenarnya (1920×1080 atau 3840×2160), dan apakah ada mode kunci aplikasi/kiosk agar siswa tidak keluar dari browser. Dicek di IFP asli pada checkpoint 4/6.
- [ ] **Jaringan di tiap titik IFP:** WiFi sekolah atau LAN? Stabil?
- [x] ~~Penandatangan berita acara~~ → diatur admin di Pengaturan; bawaan Ketua Panitia, Pembina OSIS, Kepala Sekolah + saksi tiap paslon (2026-11-23)
- [x] ~~Pencocokan dengan absen manual~~ → ya, kolom isian tangan per TPS di berita acara (2026-11-23)
- [ ] **Kelas paslon** ditampilkan di kartu atau tidak?

---

## 11. Log Keputusan

| Tanggal | Keputusan | Alasan |
|---|---|---|
| 2026-11-23 | Tanpa daftar pemilih; cegah memilih ganda lewat pengawasan guru | Keputusan pemilik; siswa tidak mengetik apa pun |
| 2026-11-23 | 2–4 IFP di beberapa titik, satu database pusat | Keputusan pemilik; ±500–800 siswa dalam satu hari |
| 2026-11-23 | Server di VPS smpn2, subdomain sekolah | Keputusan pemilik |
| 2026-11-23 | Hanya SMPN 2 Sintang (bukan multi-unit) | Keputusan pemilik |
| 2026-11-23 | Reset layar otomatis setelah suara (seperti prototipe), tanpa tombol guru | Keputusan pemilik |
| 2026-11-23 | Laravel 13 + Filament 5 + SQLite | Keputusan pemilik (rekomendasi AI) |
| 2026-11-23 | Hanya peran Admin/panitia yang memakai panel; guru tanpa akun | Keputusan pemilik |
| 2026-11-23 | IFP dikaitkan ke TPS dengan kode aktivasi 6 digit sekali pakai | Keputusan pemilik |
| 2026-11-23 | IFP hanya menampilkan jumlah suara masuk TPS-nya, tanpa perolehan | Keputusan pemilik — menjaga kerahasiaan & mencegah ikut-ikutan |
| 2026-11-23 | Saat koneksi putus suara ditolak, siswa memilih ulang (tanpa antrean offline) | Keputusan pemilik — sederhana, tanpa risiko suara ganda |
| 2026-11-23 | Dipertahankan dari prototipe: mode 1x/2x sentuh, modal visi-misi, cetak berita acara, bel + confetti | Keputusan pemilik |
| 2026-11-23 | Buka/tutup pemungutan lewat tombol admin (tanpa jadwal otomatis) | Keputusan pemilik |
| 2026-11-23 | Tampilan memakai warna resmi sekolah (menyusul) | Keputusan pemilik |
| 2026-11-23 | Layar terima kasih tidak menyebut paslon yang dipilih | Layar 75" terbaca dari antrean; menjaga kerahasiaan suara |
| 2026-11-23 | Server menolak suara dari TPS yang sama bila < 2 detik dari suara sebelumnya | Rem terhadap sentuhan beruntun/alat klik; tidak mengganggu siswa normal (layar terima kasih ≥ 2 detik) |
| 2026-11-23 | Status Ditutup final, tidak bisa dibuka lagi dari aplikasi | Hasil resmi tidak boleh berubah setelah penutupan |
| 2026-11-23 | Istilah "Pilih" menggantikan "Coblos" di layar | Konsistensi istilah (bisa dikembalikan atas permintaan panitia) |
| 2026-11-23 | Penandatangan, nama/alamat sekolah, dan tempat tanda tangan berita acara diatur admin di Pengaturan; bawaan Ketua Panitia, Pembina OSIS, Kepala Sekolah + saksi tiap paslon | Keputusan pemilik saat Checkpoint 5 |
| 2026-11-23 | Berita acara memuat kolom isian tangan "jumlah pemilih menurut catatan pengawas" per TPS | Keputusan pemilik — pembanding karena tidak ada daftar pemilih |

---

## 12. Riwayat Perubahan SPEC

| Versi | Tanggal | Bagian | Perubahan | Diusulkan oleh | Status |
|---|---|---|---|---|---|
| 0.1 | 2026-11-23 | Semua | Draft awal hasil wawancara + analisis prototipe `evoting.txt` | AI | Disetujui |
| 1.0 | 2026-11-23 | REQ-18, REQ-21, §5.2, §5.5, §10, §11 | Tiga usulan AI disetujui pemilik; SPEC disepakati | AI | Disetujui |
| 1.1 | 2026-11-23 | REQ-16, §4.2, §10, §11 | Pengaturan berita acara (penandatangan, kop, tempat, saksi) + kolom absen manual | Pemilik | Disetujui |
