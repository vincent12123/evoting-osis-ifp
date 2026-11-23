# Design System — E-Voting OSIS Layar Sentuh

> **Layer 3: Environment (visual).** AI **hanya boleh** memakai token yang terdaftar di sini.
> Token diterapkan sebagai `theme.extend.colors` Tailwind (kiosk) dan warna primer Filament (admin).
>
> ⚠️ **Warna di §2 SEMENTARA** (diturunkan dari prototipe `evoting.txt`). Pemilik memilih **warna resmi
> SMPN 2 Sintang** — ganti nilai hex begitu diterima (SPEC §10, kriteria DS-05). Nama token tidak berubah.

---

## 0. Mode Desain

| Area | Mode | Alasan |
|---|---|---|
| Layar siswa di IFP (`/kiosk`) | **Kiosk** (Ekspresif terkendali) | Momen penting bagi siswa, harus jelas dari dekat di layar 75", disentuh sambil berdiri, prosesor terbatas |
| Panel admin (`/admin`) | **Fungsional** | Dipakai panitia; tema bawaan Filament, cukup ganti warna primer, logo, font |
| Berita acara (cetak) | **Dokumen** | Hitam-putih A4, formal, mudah difotokopi |

**Aturan mode Kiosk:**
- Latar gelap (`kiosk-bg`) agar foto paslon menonjol dan layar tidak menyilaukan di ruang kelas.
- Satuan ukuran `vh`/`clamp()`; **tanpa** px tetap untuk tipografi & tombol.
- Target sentuh siswa hanya di pita 35%–95% tinggi layar (ux-flow §0).
- Animasi hanya `transform`/`opacity`, ≤ 250 ms (kecuali confetti & hitung mundur). Tanpa `backdrop-filter`, tanpa efek hover.
- Identitas resmi sekolah selalu menang atas selera AI.

---

## 1. Identitas Visual

- **Sumber:** logo SMPN 2 Sintang & logo OSIS (menunggu, simpan di `public/images/logo-sekolah.png`, `public/images/logo-osis.png`)
- **Kesan yang ingin dibangun:** resmi dan tepercaya seperti pemilu, tetapi ramah untuk siswa SMP
- **Hindari:** kesan main-main/kekanak-kanakan, terlalu banyak warna, teks kecil, warna yang memihak salah satu paslon pada elemen umum

---

## 2. Warna (SEMENTARA)

### 2.1 Warna Utama
| Token | Hex | Pemakaian |
|---|---|---|
| `primary` | #2563EB | Tombol utama admin, lencana TPS, aksen umum kiosk |
| `primary-dark` | #1E40AF | Hover/tekan, teks di atas `primary-light` |
| `primary-light` | #DBEAFE | Latar badge admin |
| `accent` | #FACC15 | Sorotan kecil (ikon petunjuk), tidak untuk teks di latar putih |

### 2.2 Netral
| Token | Hex | Pemakaian |
|---|---|---|
| `kiosk-bg` | #0F172A | Latar layar kiosk |
| `kiosk-surface` | #FFFFFF | Kartu paslon, panel lapisan |
| `kiosk-overlay` | #020617 (opasitas 80%) | Latar di balik lapisan |
| `text` | #0F172A | Teks utama di permukaan putih |
| `text-muted` | #475569 | Teks sekunder (slogan, keterangan) |
| `text-inverse` | #F8FAFC | Teks di latar gelap |
| `border` | #CBD5E1 | Garis, kerangka memuat |
| `background` | #F8FAFC | Latar halaman admin & berita acara layar |

### 2.3 Status
| Token | Hex | Pemakaian |
|---|---|---|
| `success` | #059669 | Layar terima kasih, "Daring", "Dibuka" |
| `warning` | #D97706 | "Uji coba", "Luring" |
| `danger` | #DC2626 | Gagal, hapus, "Suara belum tercatat" |

### 2.4 Aksen Paslon (dipilih admin per paslon)
| Token | Hex | Teks di atasnya |
|---|---|---|
| `paslon-1` | #2563EB biru | putih |
| `paslon-2` | #059669 hijau | putih |
| `paslon-3` | #7C3AED ungu | putih |
| `paslon-4` | #DC2626 merah | putih |
| `paslon-5` | #C2410C oranye tua | putih |
| `paslon-6` | #0E7490 biru laut | putih |

- Warna aksen hanya untuk **lencana nomor, garis kartu saat disentuh, dan tombol PILIH/YA** paslon itu.
- Paslon selalu dibedakan juga oleh **nomor urut besar** dan nama, tidak hanya warna.
- Semua kombinasi teks putih di atas aksen wajib ≥ 4.5:1 (cek ulang setelah warna resmi masuk).

---

## 3. Tipografi

| Peran | Font | Fallback |
|---|---|---|
| Heading & angka | Plus Jakarta Sans (800/900), disajikan lokal (`@fontsource`) | system-ui, sans-serif |
| Body | Plus Jakarta Sans (500/600) | system-ui, sans-serif |
| Berita acara | Times New Roman / serif sistem | serif |

### Skala Kiosk (relatif tinggi viewport)
| Level | Ukuran | Berat | Contoh |
|---|---|---|---|
| Nomor urut | `clamp(48px, 7vh, 160px)` | 900 | `02` di lencana |
| Judul lapisan | `5vh` | 900 | "Yakin memilih paslon ini?" |
| Nama paslon | `3.6vh` | 800 | "Dimas Anggara & Citra Lestari" |
| Teks tombol | `3vh` | 900, huruf kapital | "PILIH 02" |
| Badan | `2.4vh` | 600 | slogan, visi |
| Kepala layar | `2.6vh` | 800 | nama pemilihan |
| Minimum | `2.2vh` | — | tidak boleh lebih kecil |

### Admin
Ikuti skala bawaan Filament.

---

## 4. Spasi & Tata Letak

- **Kiosk:** padding tepi `3vh`; jarak antarkartu `2.5vh`; kepala layar ≤ 12vh; area kartu mengisi sisa tinggi tanpa scroll. Radius kartu `2.5vh`, tombol `1.8vh`.
- **Admin:** bawaan Filament.
- **Bayangan kiosk:** satu level, `0 0.8vh 2.4vh rgba(0,0,0,.35)`.

---

## 5. Komponen

### Tombol Kiosk
| Varian | Tampilan | Kapan dipakai |
|---|---|---|
| Pilih/Ya | Latar warna `paslon-n`, teks putih kapital, tinggi ≥ 9vh | PILIH {nomor}, YA, PILIH {nomor} di Visi & Misi |
| Netral | Latar #E2E8F0, teks `text`, tinggi ≥ 9vh | Visi & Misi, BATAL, KEMBALI |
| Mulai | Latar `primary`, teks putih, ≥ 12vh | SCR-K2 |
| Keypad | Kotak putih, angka `5vh`, ≥ 11vh × 11vh | SCR-K1 |
- Status tekan: skala 0.97 + garis 0.6vh warna paslon, tampil ≤ 150 ms.
- Nonaktif: opasitas 50% + teks penjelas (mis. "Menyimpan suara…").

### Kartu Paslon (SCR-K3)
- Foto rasio 4:3 di atas, overlay gradasi gelap di bawah foto agar nama terbaca (kontras ≥ 4.5:1).
- Lencana nomor di pojok kiri atas foto (dekoratif, tidak disentuh, jadi boleh di atas pita sentuh).
- Di bawah foto: nama (2 baris maks), slogan (1 baris), visi (2 baris), lalu baris tombol [Visi & Misi][PILIH nn].

### Lapisan (SCR-K4, K5)
- Panel putih di tengah, lebar 55–65% layar, tombol di dasar panel. Tanpa ✕ pojok atas.

### Admin
Komponen Filament bawaan (tabel, form, dialog konfirmasi, toast). Dialog konfirmasi menyebut nama data; aksi berisiko memakai warna `danger`.

### State
- **Memuat:** kerangka berwarna `border`; putaran hanya di tombol.
- **Kosong:** ikon netral + 1 kalimat + 1 tombol (admin).
- **Gagal:** judul `danger` + kalimat apa yang terjadi + apa yang dilakukan.
- **Berhasil:** kiosk = layar terima kasih `success`; admin = toast 4 detik.

---

## 6. Gambar & Ikon

- **Foto paslon:** diunggah JPG/PNG/WebP ≤ 3 MB, disimpan WebP 4:3, sisi terpanjang ≤ 1600px, ≤ 300 KB.
- **Ikon:** Lucide (paket npm, bukan `unpkg @latest`), ukuran kiosk `4vh`.
- **Alt text:** "Foto paslon {nomor}: {ketua} dan {wakil}".

---

## 7. Nada Bahasa

- **Kiosk (siswa):** sapaan "kamu", kalimat pendek, perintah jelas ("Sentuh foto paslon pilihanmu."), huruf kapital hanya untuk tombol.
- **Admin:** netral-formal, tanpa sapaan, langsung ke inti ("Paslon 02 tersimpan.").
- **Guru di layar status:** sebut perannya ("Guru pengawas: periksa WiFi IFP…").

---

## 8. Yang Tidak Boleh

- ❌ Warna hex di luar daftar token
- ❌ Lebih dari 1 keluarga font di kiosk/admin
- ❌ Teks di atas foto tanpa overlay gelap
- ❌ Status/paslon yang hanya dibedakan lewat warna
- ❌ Menghapus outline fokus di admin tanpa pengganti
- ❌ `backdrop-filter`/blur, video latar, atau animasi tak berujung di kiosk (kecuali penanda "Daring" kecil)
- ❌ Target sentuh siswa di atas 35% tinggi layar
- ❌ Ukuran px tetap untuk teks/tombol kiosk
- ❌ Memuat aset dari CDN eksternal (Tailwind CDN, unpkg, Google Fonts) di kiosk
- ❌ Menampilkan perolehan per paslon di kiosk
