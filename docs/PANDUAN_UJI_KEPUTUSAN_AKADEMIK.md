# Panduan Uji — Keputusan Data Akademik

Panduan ini untuk menguji **tiga perbaikan** yang sudah merged ke `develop` dengan cara manual lewat browser. Untuk otomatis, lihat bagian akhir.

**Cara menyiapkan data:**

```bash
php artisan migrate:fresh --seed
```

Satu perintah itu sudah cukup. Seeder yang relevan:

| Seeder | Isi |
|---|---|
`RiwayatMultiTahunSeeder` | Data riwayat dua tahun ajaran (7A→8A, 7B→8B), dibuat dengan memanggil Action wizard |
`DemoUjiKeputusanSeeder` | Skenario uji untuk ketiga perbaikan di bawah |

> **Peringatan.** `DemoUjiKeputusanSeeder` sengaja membuat data yang terlihat
> tidak wajar — satu siswa tercatat di dua rombel pada tanggal yang sama, dan
> beberapa siswa punya dua baris riwayat dalam satu tahun ajaran. Keduanya
> **diberi label** di kolom `keterangan` dengan awalan `SKENARIO UJI`. Kalau
> menemukan baris bertanda itu, itu disengaja untuk menguji guard, bukan
> kerusakan data.
>
> Seeder ini **dilewati otomatis** bila environment `production`, karena
> akun-akunnya memakai password lemah.

---

## Akun uji

Semua password: `password` (login memakai **username**, bukan email).

| Username | Peran | Dipakai untuk |
|---|---|---|
`admin-uji` | admin | menguji kunci hapus rombel dan koreksi riwayat |
`guru-wali-uji` | guru | wali + pengampu (Guru A) |
`guru-pengampu-uji` | guru | **hanya** pengampu mapel, bukan wali |
`guru-tanpa-kelas-uji` | guru | tanpa kelas wali dan tanpa penugasan |
`00808…` | siswa | siswa skenario (NISN = username) |

---

## 1. Fail-open scoping rekap absensi

**Yang diuji:** guru tanpa penugasan harus melihat **nol** rombel, bukan seluruh rombel.

### 1a. Guru tanpa penugasan melihat nol rombel

1. Login `guru-tanpa-kelas-uji`
2. Buka **Absensi → Rekap Absensi**

**Harapan:**
- Dropdown "Rombel" **kosong** — tidak ada satu pun pilihan
- Halaman tetap terbuka dengan tampilan kosong, **bukan** halaman error 403
- Tidak ada satu pun nama kelas yang terlihat

> Kalau rombel lain ikut muncul, bug fail-open belum tertutup.

### 1b. Guru pengampu mapel boleh melihat kelas yang bukan dia wali

1. Login `guru-pengampu-uji`
2. Buka **Absensi → Rekap Absensi**

**Harapan:** yang muncul hanya **7D** — kelas yang diampu mapelnya.
Kelas ini **tidak punya wali** (kolom wali kosong), jadi satu-satunya alasan
guru ini boleh melihatnya adalah penugasan mapel.

### 1c. Guru tidak bisa membuka rekap kelas orang lain

1. Login `guru-wali-uji`
2. Buka **Absensi → Rekap**
3. Ubah URL secara manual, ganti `rombel_id` dengan id rombel milik guru lain

**Harapan:** **403 Forbidden** dengan pesan "Rombel ini bukan ampuan Anda."

---

## 2. Kunci hapus rombel

**Yang diuji:** rombel yang punya histori harus menolak dihapus dan menyebutkan jenis datanya; yang kosong harus bisa dihapus.

Setiap relasi historis (`pengampus`, `absensis`, `materis`, `tugas`, `exams`) memakai `cascadeOnDelete`, jadi "hapus rombel" berarti menghapus data itu secara permanen.

### Siapkan tabel

| Kelas (tahun aktif) | Isi | Diharapkan saat hapus |
|---|---|---|
**7U** | benar-benar kosong | **berhasil dihapus** |
**7V** | absensi saja | ditolak: "data kehadiran" |
**7W** | tugas + pengumpulan saja | ditolak: "tugas" |
**7X** | ujian saja | ditolak: "ujian CBT" |
**7Y** | ketiganya | ditolak: menyebut **semua** |

### Langkah

1. Login `admin-uji`
2. Buka **Rombel**, filter tahun = Tahun Aktif
3. Cari baris **7U**, klik ikon hapus

**Harapan 7U:** rombel hilang, flash "Rombel dihapus."

4. Ulangi untuk **7V**, **7W**, **7X**, **7Y**

**Harapan:** rombel **tidak** hilang, flash merah menyebut data yang memblokir:

- 7V → `Rombel masih memiliki data kehadiran dan tidak dapat dihapus.`
- 7W → `… memiliki tugas …`
- 7X → `… memiliki ujian CBT …`
- 7Y → `… memiliki data kehadiran, tugas, dan ujian CBT …` (ketiganya)

> Kalau 7U ikut terhapus saat salah satu di atas diuji, cek apakah rombelnya
> memang kosong — 8A dan 9A juga kosong dan boleh dihapus, itu memang
> perilaku yang benar.

---

## 3. Constraint unik + koreksi riwayat

**Yang diuji:** mengedit baris riwayat supaya bentrok harus ditolak dengan pesan terbaca — bukan 500 dari database.

Constraint-nya `unique(siswa_id, kelas_id, tahun_ajaran_id)`. Perhatikan: yang dikunci **tiga kolom**. Kelas yang sama dalam tahun yang sama **tidak boleh** dua baris, tapi kelas berbeda dalam satu tahun **boleh**.

### 3a. Menolak bentrok yang terbaca

1. Login `admin-uji`
2. Buka **Riwayat Kelas**, cari siswa ber-NISN `0080800106` (kelas 7C)
3. Klik **Koreksi**
4. Pada baris berstatus `mengulang` (kelasnya 7D), ubah **Kelas → 7C** lalu **Simpan**

**Harapan:**
- Validasi gagal, pesan merah: **"Siswa ini sudah punya baris riwayat untuk kelas dan tahun ajaran tersebut."**
- **Bukan** halaman 500 / `QueryException`

### 3b. Memperbaiki tahun yang salah

1. Di halaman koreksi siswa ber-NISN `0080011001`
2. Cari baris dengan `keterangan` berawalan `SKENARIO UJI: tahun ajaran tidak sesuai`
3. Ubah **Tahun Ajaran** ke tahun yang benar, **Simpan**

**Harapan:** flash "Riwayat kelas diperbarui." dan `keterangan`-nya masih ada.

### 3c. Menghapus baris butuh konfirmasi

1. Di halaman koreksi mana pun, klik **Hapus Baris** **tanpa** mencentang kotak konfirmasi

**Harapan:** ditolak, flash merah:
"Baris riwayat tidak dihapus. Tandai kotak konfirmasi terlebih dahulu."
(termasuk keterangan rekap apa yang akan hilang dari tampilan)

2. Centang kotaknya, lalu **Hapus Baris** lagi

**Harapan:** baris hilang.

### 3d. Hanya admin yang boleh

1. Login `guru-wali-uji`
2. Buka **Riwayat Kelas** secara langsung lewat URL

**Harapan:** **403 Forbidden** — permission `riwayat-kelas.manage` hanya diberikan ke role `admin`.

---

## 4. Skenario pendukung — absensi tanggal sama di dua rombel

Ini skenario pindah kelas di tengah tahun, dan sekaligus alasan constraint
`(siswa, rombel, tanggal)` dilonggarkan dari `(siswa, tanggal)`.

1. Login sebagai wali dari siswa ber-NISN `0080800105` (akunnya ada di
   `wali_murids`, password `password`)
2. Buka **Halaman Anak → Riwayat Lengkap**

**Harapan:**
- Rekap **tidak** menghitung hari yang sama dua kali
- Pada baris absensi bertanda `SKENARIO UJI`, hari tersebut dihitung satu kali
- Di **Rekap Kehadiran**, jumlah hari agree dengan jumlah baris yang tampil

---

## Verifikasi otomatis

Pengujian otomatis adalah pemeriksaan yang sebenarnya —UI hanya memastikan
pesan dan tombolnya benar. Test ada di
`tests/Feature/KeputusanAkademikTest.php` (18 test) dan
`tests/Feature/TahunAjaranBaruTest.php` (14 test).

```bash
# test yang menguji ketiga perbaikan ini
php artisan test --compact tests/Feature/KeputusanAkademikTest.php

# seluruh suite
php artisan test --compact
```

Beberapa test sudah diverifikasi dengan **sabotase**: perbaikannya sengaja
dikembalikan ke kondisi rusak lalu test dijalankan, untuk memastikan test benar-benar menangkap bug dan bukan hanya hijau.

| Yang disabotase | Hasil saat sabotase |
|---|---|
Hapus `Rombel::terjangkauUser()` di rekap absensi | 2 test gagal |
Hapus `absensis` dari daftar histori rombel | "Expecting null not to be null" |
Jangan tutup riwayat lama saat pindah kelas | 2 test gagal |
Hapus guard `is_aktif` + matikan throttle | 2 test gagal |
Jangan jalankan kenaikan di wizard | 4 test gagal |

---

## Catatan penting soal data

**Tabel `wali_kelas` sengaja dibiarkan kosong.** Aritanya ada tapi tidak
dibaca sebagai sumber resmi:

- Sumber wali yang dipakai sistem adalah kolom `rombels.wali_guru_id`.
- `RombelSeeder` membaca `wali_kelas` sebagai salah satu sumber pembentukan
  rombel, tapi kalau kosong, pasangan dari `riwayat_kelas` sudah cukup.

Kalau nanti `wali_kelas` diisi, ada **dua sumber kebenaran** untuk hal yang
sama dan keduanya bisa menyimpang. Putuskan satu sumber sebelum mengisinya.

**Riwayat kelas = keanggotaan rombel.** `Rombel::anggotaIdsAktif()` membaca
`riwayat_kelas`, jadi baris riwayat yang salah langsung memindahkan siswa
di grid absensi, scan QR, dan rekap nilai. Inilah alasan menu Riwayat Kelas
perlu ada.