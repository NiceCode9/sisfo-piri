# Panduan Import Soal CBT (Excel + Gambar)

**Untuk:** guru yang ingin mengunggah banyak soal sekaligus
**Dari:** halaman **Detail Ujian** → blok **"Import soal massal"**

Import ini memuat banyak soal sekaligus. **Tidak ada yang langsung tersimpan** —
setelah paket diunggah, soalnya tampil di layar review dulu supaya kamu masih
bisa memperbaikinya.

---

## 1. Unduh template

Klik **Unduh template Excel**. Template-nya sudah berisi 4 contoh soal:

| Contoh | Untuk contoh apa |
|---|---|
| Baris 2 | Pilihan tunggal tanpa gambar |
| Baris 3 | Pilihan jamak (kunci lebih dari satu) |
| Baris 4 | Uraian |
| Baris 5 | **Soal bergambar** |

Jangan hapus baris headernya. Nama kolom harus persis seperti di template.

---

## 2. Isi spreadsheet

| Kolom | Isi | Aturan |
|---|---|---|
| `teks` | Isi soal | Wajib |
| `tipe` | `single`, `multiple`, atau `essay` | Wajib |
| `opsi_a` – `opsi_d` | Teks tiap opsi | Wajib untuk soal pilihan |
| `kunci` | `b` atau `a;c` | Wajib untuk soal pilihan |
| `skor` | Bobot soal, 1–100 | Wajib |
| `gambar` | Alamat gambar di dalam paket | Opsional |
| `urutan` | Urutan tampil | Opsional |

### Tips menulis

**`tipe`** — tulis persis `single`, `multiple`, atau `essay`. Huruf besar
dianggap sama dengan huruf kecil (`Single` tetap jalan), tapi jangan pakai
`pilihan tunggal`.

**`kunci`** — huruf opsinya, dipisah titik koma kalau lebih dari satu:

| Tipe | Contoh kunci | Keterangan |
|---|---|---|
| Pilihan tunggal | `b` | Tepat satu |
| Pilihan jamak | `a;c` | Minimal dua |

**`gambar`** — ini yang membedakannya dari import biasa. Isi dengan **path di
dalam paket ZIP**, bukan nama berkas bebas. Contohnya `gambar/contoh-01.png`.

Folder di dalam paket tidak wajib bernama `gambar` — yang dicari adalah **nama
berkas**. Jadi `soal/soal-01.png` di Excel tetap ketemu kalau berkasnya ada
di paket dengan nama `soal-01.png`.

---

## 3. Susun paket ZIP

```
paket-soal.zip
├── soal.xlsx
└── gambar/
    ├── mtk-01.png
    └── mtk-02.jpg
```

Aturan:

- Ada **tepat satu** berkas `.xlsx`
- Setiap gambar referenced di kolom `gambar` harus ada di dalam ZIP
- Zip maksimal **50 MB**
- Gambar maksimal 10 MB masing-masing dan 3000 × 3000 piksel

### Kalau gambar tidak ditemukan

Baris soalnya **tetap tersimpan** (teksnya berguna), tapi kolom gambar di layar
review akan bertanda merah **"Tidak ditemukan"**. Buang baris itu kalau memang
gambarnya salah, atau perbaiki di Excel lalu unggah ulang.

---

## 4. Review sebelum menyimpan

Setelah paket diunggah, layar review menampilkan satu baris per soal:

| Yang bisa diubah | Yang tidak |
|---|---|
| Teks soal | **Gambar** |
| Poin | Baris Excel yang gagal dibaca |
| Kunci jawaban (klik chip A/B/C/D) | |

### Mengganti kunci

Klik chip huruf di baris itu. Kelilip outline ungu = jadi kunci. Klik lagi
untuk melepaskannya.

### Membuang baris

Klik tombol **×** di kanan baris. Baris itu tidak akan ikut tersimpan.

### Baris yang gagal

Baris yang gagal dibaca Excel tidak muncul sebagai baris yang bisa disunting,
tapi alasannya ditulis di kotak merah paling atas — lengkap dengan nomor
barisnya di Excel. Perbaiki di Excel lalu unggah ulang.

### Memilih bank soal

Di bagian bawah ada dropdown **"Salin juga ke bank soal"**. Soal hasil import
akan disimpan dua kali: satu di ujian ini, satu di bank yang dipilih. Ini
berguna supaya ujian berikutnya tidak perlu mengimpor ulang.

Bank harus milikmu dan mata pelajarannya sama dengan ujian. Kalau belum punya,
buat dulu lewat menu **Bank Soal**.

---

## 5. Batasan yang perlu diketahui

**Gambar yang salah tidak bisa diganti di layar review.** Kamu bisa mengoreksi
teks, poin, dan kunci — tapi tidak bisa menggantinya dengan gambar lain. Kalau
gambarnya salah, buang barisnya lalu tambahkan ulang. Ini akan berubah kalau
fitur ubah soal nanti ditambahkan.

**Paket disimpan 24 jam.** Kalau kamu menutup browser sebelum menekan "Simpan
soal", paketnya dibuang setelah 24 jam dan kamu harus mengunggah ulang.

**Batas 200 soal per import.**

---

## Kalau gagal

| Pesan | Arti | Yang harus dilakukan |
|---|---|---|
| Paket terlalu besar | Total isi ZIP melebihi 200 MB | Kompres gambar dulu |
| Mencoba keluar dari foldernya | Ada entri bermuatan `..` di ZIP | Buat ZIP ulang dari isi folder, bukan dari foldernya |
| `nama.png` bukan gambar yang valid | Berkas gambar rusak atau bukan gambar | Ganti berkasnya |
| Ukuran 3100x200 px, batasnya 3000x3000 | Gambar terlalu besar | Perkecil gambar |
| Tidak ada baris yang bisa diproses | Semua baris ditolak | Baca daftar alasannya di layar sebelumnya |
| Sesi import sudah berakhir | Paket sudah dibuang (>24 jam) | Unggah ulang |

Kalau masih gagal, buat ZIP baru dari **isi** folder (bukan dengan klik kanan
folder lalu "Compress to ZIP" di beberapa sistem operasi, yang kadang
membuat folder utuh ikut terbungkus).