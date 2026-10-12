# Rencana Implementasi — Import Soal CBT (Excel + Gambar)

**Status:** selesai diimplementasikan
**Tanggal:** 2026-10-10
**Status saat dokumen ini ditulis:** rencana disetujui, implementasi berjalan

## Masalah yang diselesaikan

Guru ingin mengunggah soal + jawaban sekaligus banyak. Excel sudah cukup untuk
teksnya. Yang tidak bisa ditangani Excel adalah **gambar**:

1. Gambar tertanam di dalam sel tidak lewat jalur import. `Maatwebsite\Excel`
   punya interface `WithDrawings`, tapi satu-satunya pemanggilnya adalah
   `Sheet.php:415` — sisi **export**. Reader Xlsx tidak pernah menyerahkan
   drawing ke `ToCollection`.
2. Anchoring gambar ke sel rapuh. Sisip satu baris di tengah, semua bergeser.
3. Base64 di dalam sel mustahil — Excel membatasi sel di 32.767 karakter, dan
   base64 menambah ukuran sekitar 33%.

Jadi gambarnya **dibawa di samping spreadsheet**, di dalam satu paket ZIP.

## Keputusan produk

| Aspek | Keputusan |
|---|---|
| Alur | Unggah → review → submit → tersimpan ke ujian **dan** bank soal |
| Baris invalid | Valid disimpan, invalid dilaporkan sebagai daftar kegagalan |
| Template | Ada, lengkap dengan contoh soal bergambar |
| Bank tujuan | Guru memilih di layar review, default bank baru |
| Gambar | Disimpan apa adanya, tanpa resize |
| Batas unggah | 50 MB |
| Sisa import | Stateless (token di URL), disapu setelah 24 jam |

### Alur yang dipilih dan alasannya

Import langsung ke ujian dengan review di tengahnya, lalu otomatis disalin ke
bank. Alternatifnya import ke bank dulu baru disalin manual, atau langsung ke
ujian tanpa review.

Yang dipilih menutup masalah utama: **belum ada endpoint edit soal**, sehingga
guru hanya bisa tambah dan hapus. Tanpa review, satu baris yang salah harus
dihapus — dan menghapus soal ujian ikut menghapus jawaban siswa. Layar review
menjadi satu-satunya kesempatan memperbaiki sebelum data masuk.

Dual-write ke bank memastikan bank soal tidak tetap kosong, sehingga import tidak
harus diulang untuk ujian berikutnya.

## Bentuk paket

```
paket-soal.zip
├── soal.xlsx
└── gambar/
    ├── mtk-01.png
    └── mtk-02.jpg
```

| Kolom | Isi | Wajib |
|---|---|---|
| `teks` | Isi soal | ya |
| `tipe` | `single` \| `multiple` \| `essay` | ya |
| `opsi_a` … `opsi_d` | Teks tiap opsi | untuk soal pilihan |
| `kunci` | `a` atau `a;c` | untuk soal pilihan |
| `skor` | Bobot soal | ya |
| `gambar` | Path relatif di dalam zip, boleh kosong | tidak |
| `urutan` | Urutan tampil | tidak |

Pencocokan nama gambar memakai **basename**, jadi `gambar/mtk-01.png` tetap
ketemu walau guru menaruhnya di folder lain.

## Arsitektur

```
Unggah ZIP
   │  validasi: mimes:zip, max 50 MB
   ▼
PaketSoal::buka()                    ← tidak memakai extractTo()
   │  jumlahkan ukuran SEBELUM ekstraksi
   │  tolak entri: .., path absolut, backslash
   │  validasi gambar: getimagesize() + MIME
   ▼
cbt/import/{token}/                   ← disk privat 'berkas'
   ├── manifest.json                 { "mtk-01.png": "a1b2….jpg" }
   ├── a1b2….jpg
   └── soal.xlsx
   │
   ▼
Excel::import(SoalCbtImport)          ← read_only sudah true di config/excel.php
   │  baris valid → payload review
   │  baris invalid → customFailures (baris + alasan)
   ▼
Layar review                          ← teks/opsi/kunci/skor bisa diedit
   │  gambar lewat signed URL (30 menit)
   │  dropdown bank tujuan
   ▼
POST submit
   │  validasi ulang payload
   │  App\Support\Berkas → siklus hidup file transaksional
   ▼
ExamQuestion di ujian  +  salinan di bank  (berbagi satu path gambar)
```

### Keputusan teknis

**Tidak memakai `ZipArchive::extractTo()`.** Setiap entri ditulis manual lewat
`getStream()`. `extractTo()` adalah tempat Zip Slip terjadi — dengan menulis
manual, setiap tujuan sepenuhnya terkendali.

**Gambar disimpan dengan nama acak**, bukan nama asli dari zip. Manifest
dipakai sebagai satu-satunya pemetaan. Ini membuat nama berkas pada disk tidak
pernah berasal dari input pengguna.

**Stateless.** Tidak ada tabel import. Token acak ada di URL, folder di disk
dibersihkan sweeper. Tidak ada state yang bisa menggantung.

**`config('excel.imports.read_only')` sudah `true`** di repo ini
(`config/excel.php`), jadi PhpSpreadsheet tidak mengurai formatasi. Nilai ini
diverifikasi, bukan digandakan lewat kode — kalau nilai bakunya berubah, kode review
tidak akan menangkapnya.

**`App\Support\Berkas` dipakai ulang** untuk siklus hidup file: file baru yang
gagal commit dibuang, file yang tak dirujuk dihapus setelah commit.

## Keamanan

| Ancaman | Penangkal | Letak |
|---|---|---|
| **Zip Slip** — entri `../../config/app.php` | tulis manual via `getStream()`; entri berisi `..`, path absolut, atau backslash ditolak | `PaketSoal::namaAman()` |
| **Zip bomb** — 1 MB melebar jadi GB | jumlahkan `getSize()` seluruh entri **sebelum** ekstraksi; batas 200 MB | `PaketSoal::buka()` |
| **Berkas palsu** — `.php` ganti nama `.png` | `getimagesize()` + MIME nyata; bukan ekstensi | `PaketSoal::tipeGambar()` |
| **Beban** | batas entri (500), soal (200), ukuran gambar (10 MB), dimensi (3000×3000) | `PaketSoal` |
| **Akses** | signed URL 30 menit + `auth` + `cbt.manage` | `SoalImportPreviewController` |

**Catatan bom dekompresi:** PNG berukuran 10 KB bisa melebar jadi 4 GB saat
didekompresi. Karena gambar disimpan apa adanya tanpa diproses GD, risiko itu
tidak muncul di server — tapi dimensi tetap diperiksa sebelum dipakai, supaya
siswa tidak memuat gambar yang membuat browser mereka hang.

## Penanganan berkas besar

| Tahap | Yang dilakukan | Kenapa |
|---|---|---|
| Unggah | Laravel stream ke tmp; aturan `max:` cek ukuran tanpa memuat isi | `file_get_contents()` pada zip akan menghabiskan memori |
| Ekstraksi | Total ukuran dijumlahkan sebelum ekstraksi | Kalau menunggu setelah ekstraksi, kerusakannya terlanjur terjadi |
| Excel | `read_only = true` + `WithChunkReading` | Tanpa ini, xlsx besar menguras memori |
| Gambar | `getimagesize()` hanya baca header | Biaya memori tetap kecil |

## Konfigurasi server

`docs/server-config/php-fpm-pool.conf.example` dan `docs/PANDUAN_DEPLOY_VPS.md`:

```ini
upload_max_filesize = 50M
post_max_size = 52M
upload_tmp_size = 52M
```

## Batasan yang diketahui

**Gambar salah tidak bisa diganti di layar review.** Guru dapat mengoreksi teks,
opsi, kunci, skor, dan urutan — semuanya field biasa. Tapi tabel review tidak
punya cara mengunggah gambar pengganti. Baris itu harus dibuang lalu ditambah
ulang. Ini konsekuensi belum adanya endpoint edit soal, bukan sesuatu yang bisa
diperbaiki di dalam fitur import.

**Berapa lama paket tersimpan.** Folder `cbt/import/` disapu setelah 24 jam.
Kalau guru butuh melanjutkan besok, mereka harus mengunggah ulang — konsekuensi
memilih desain stateless.

## Referensi

Panduan untuk guru: `docs/PANDUAN_IMPORT_SOAL_CBT.md`
Temuan audit: `docs/BUG_CBT.md` (bagian import)