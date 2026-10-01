# Analisa & Temuan Bug — Modul PPDB/SPMB

**Tanggal audit:** 2026-09-29
**Ruang lingkup:** alur pendaftaran publik (SPMB), alur admin PPDB, berkas & sertifikat, pembayaran & angsuran, master-kuota & jadwal.
**Metode:** pembacaan kode menyeluruh (controller, form request, model, view, migrasi, seeder, test) + verifikasi manual pada poin berisiko tinggi. Tidak ada perubahan kode saat audit.
**Referensi kode:** `backend/app/**`, `backend/resources/views/**`, `backend/database/**`, `backend/routes/web.php`, `backend/tests/**`.

## Ringkasan

| Severity | Jumlah | Karakteristik |
|---|---|---|
| **Critical** | 5 | Kerusakan data, kebocoran data pribadi, atau bypass logika inti |
| **High** | 8 | Fungsional inti tidak bekerja / tidak konsisten antar-subsistem |
| **Medium** | 12 | Salah tampilan, validasi tak simetris, deadlock UX, data slander |
| **Low** | 6 | Teks/angka hardcoded, N+1, placeholder bocor, konsistensi-copy |
| **TOTAL** | **31** | |

Temuan paling Struktural: **dokumen identitas anak terekspos lewat URL publik tanpa autentikasi** (C1), dan **tiga subsistem — kuota, log audit, tagihan — dapat dilewati hanya dengan satu field** (C2).

Legend verifikasi:
- ✅ **Diverifikasi manual** — dibaca ulang langsung oleh penulis audit.
- 🔍 **Temuan audit** — hasil pembacaan kode menyeluruh, belum diuji ulang manual.
- 🛠️ **Sudah diperbaiki** — sudah ada di `main`, lihat bagian "Status Perbaikan".

---

# Status Perbaikan

Kelima temuan Critical dan seluruh 8 temuan High sudah diperbaiki pada `main`:

| ID | Commit | Perbaikan inti |
|---|---|---|
| C2 | `c038f27` | `status_pendaftaran` dikeluarkan dari `update()` + dihapus dari `$fillable`/FormRequest; calon baru selalu `menunggu` |
| C5 | `0fd831c` | `App\Support\Penomor` — placeholder unik saat insert lalu nomor final berbasis ID (PPDB/PAY/ANG/LNN) |
| C3 | `1903ce7` | `destroy()` menolak calon yang sudah punya record `Siswa` |
| C1 | `bae09a0` | Dokumen pindah ke disk privat `berkas`; seluruh akses lewat `DokumenController` terotorisasi |
| C4 | `692ac7d` | Penolakan membatalkan invoice `menunggu`, rencana `aktif`, menonaktifkan `Siswa`, melepas tautan `WaliMurid` |

Catatan penting C1: file lama tidak lagi terekspos lewat symlink `public/storage`, dan `storage/app/public/berkas` + `bukti` sudah dipindahkan ke `storage/app/private/berkas`. Folder-folder tersebut **tidak** ikut ter-*commit* (di-*ignore* di dalam `storage/app/private/`), jadi saat deploy ulang di server, dokumen yang sudah ada perlu dimuat ulang.

Catatan penting C4: enum `status` pada `pembayarans` **dan** `pembayaran_lainnyas` diberi nilai `batal` lewat migrasi `2026_10_01_090000`. Invoice yang sudah `berhasil` sengaja dibiarkan utuh.

### Tahap 2 — 8 temuan High

Seluruh temuan **High** juga sudah diperbaiki (suites penuh hijau, 453 test / 1402 assertions):

| ID | Commit | Perbaikan inti |
|---|---|---|
| H6 | `5c391da` | ID jalur/tahun dinormalisasi ke `int` sebelum dibandingkan; edit calon `diterima` tanpa ganti jalur tidak lagi terblokir |
| H2 | `9fea23f` | `unique:users,email` + `unique:users,username`; `catch (UniqueConstraintViolationException)` & `QueryException` agar SQL mentah tidak bocor ke pengunjung |
| H3 | `6264bb9` | View membaca `$jadwalPpdbs` (cocok dengan controller); fallback tanggal hardcoded dihapus, diganti pesan "jadwal belum dipublikasikan" |
| H4 | `52c491c` | 2 tombol CTA ke `route('login')` / `route('spmb.pendaftaran')`; handler smooth-scroll mengabaikan tautan tanpa target; kartu & ikon media sosial disembunyikan sampai ada URL resmi |
| H5 | `54c330d` | Calon `ditolak` tidak bisa ditagih (4 jalur: manual, angsuran, rencana, pembayaran lainnya) + tombol penagihan disembunyikan |
| H8 | `7258e81` | `App\Support\Berkas` menunda penghapusan file sampai setelah commit (dengan pembuangan file baru saat rollback); `destroy()` kini ikut menghapus bukti `pembayarans` yang tertinggal |
| H1 | `a749301` | Kuota dipecah: `kuota_pendaftaran`/`terisi_pendaftaran` (batas pendaftar) vs `kuota`/`terisi` (batas penerimaan). Migrasi `2026_10_01_100000` |
| H7 | `3ec2670` | Kolom `tipe` pada `jadwal_ppdbs` + gate server di `create()`/`store()`. Migrasi `2026_10_01_110000` |

**Koreksi terhadap audit awal — H2 bukan HTTP 500.** `QueryException extends PDOException extends RuntimeException`, sehingga tetap tertangkap `catch (\RuntimeException)`. Bug aslinya adalah **SQL mentah bocor ke pengunjung publik** (karena pesan error memakai `$e->getMessage()`), bukan error 500. Perbaikannya tetap sama: rule unique lintas tabel + pesan ramah.

**Catatan H1:** `kuota_pendaftaran` di-*backfill* `NULL` (= tidak dibatasi), jadi tidak ada perubahan perilaku sampai admin mengisinya lewat Master Kuota. `lockForUpdate()` kini benar-benar berguna karena baris kuota benar-benar ditulis.

**Catatan H7:** bila admin belum menandai satu pun baris bertipe `pendaftaran`, pendaftaran **tetap dibuka** (sengaja permissive agar selisih tanggal tidak mengunci sekolah tanpa sengaja). Gate hanya aktif setelah ada baris `tipe = 'pendaftaran'`.

Sisa temuan **Medium / Low** belum dikerjakan.

---

# CRITICAL

## C1. Dokumen identitas anak terekspos lewat URL publik tanpa autentikasi ✅

**Lokasi**
- `app/Http/Controllers/SpmbController.php:142,151` — `->store('berkas', 'public')`, `->store('berkas/sertifikat', 'public')`
- `app/Http/Controllers/Admin/CalonSiswaController.php` — berkas & sertifikat juga memakai disk `public`
- `public/storage` → **junction** ke `storage/app/public` (diverifikasi: `LinkType = Junction`)
- `public/.htaccess` — **tidak ada** aturan `deny` untuk `/storage` (diverifikasi)
- URL bocor di: `resources/views/admin/calon-siswas/show.blade.php:1330,1345,1453,1730`, `resources/views/siswa/dashboard.blade.php:88,124`, `resources/views/admin/calon-siswas/_form.blade.php:231,267`, `resources/views/admin/pembayarans/show.blade.php:74`

**Masalah**
Berkas ijazah, akta kelahiran, KK, SKL, pas foto, KRM, KIP, dan sertifikat prestasi — semuanya dokumen identitas/data pribadi anak — ditulis ke disk `public` yang diserve web server secara langsung. View memuat `Storage::disk('public')->url(...)` tanpa Signed URL dan tanpa pengecekan autentikasi.

**Dampak**
- URL `https://host/storage/berkas/<hash40>.pdf` dapat diakses siapa pun, tanpa login.
- Hash 40-karakter pada nama file hanya *obscurity*, bukan kontrol akses. Setiap kali URL bocor (referensi WhatsApp, screenshot, cache browser, log server, `Referer` header), akses bersifat permanen dan tidak bisa dicabut.
- Dokumen KIP adalah identitas beasiswa nasional; KK memuat alamat & komposisi keluarga; akta/ijazah memuat NIK dan data orang tua.
- Tidak ada mekanisme pencabutan: mengganti berkas tidak meng-invalidasi URL lama.

**Perbaikan yang diperlukan** (detail di plan perbaikan)
Pindahkan ke disk non-publik (`local`/dedicated), tambahkan controller download ber-auth + Signed URL, dan jangan pernah mencetak `Storage::disk('public')->url()` untuk dokumen ini.

---

## C2. `status_pendaftaran` dapat di-bypass lewat endpoint edit biasa ✅

**Lokasi**
- `app/Http/Controllers/Admin/CalonSiswaController.php:174` — `collect($validated)->except([...$berkasFields, 'sertifikat'])->toArray()`
- `app/Http/Controllers/Admin/CalonSiswaController.php:179` — `$calonSiswa->update($calonData)`
- `app/Http/Requests/Admin/UpdateCalonSiswaRequest.php:49` — `'status_pendaftaran' => ['nullable', 'in:menunggu,diterima,ditolak,daftar_ulang']`
- `app/Models/CalonSiswa.php` — `status_pendaftaran` ada di `$fillable`

**Masalah**
`update()` mengecualikan field berkas & sertifikat, tetapi **tidak** mengecualikan `status_pendaftaran`. Field ini lolos validasi FormRequest dan langsung masuk ke `update()`. Akibatnya `updateStatus()` — satu-satunya tempat logika penerimaan dijalankan — dilewati sepenuhnya.

**Dampak**
`PUT /admin/calon-siswas/{id}` dengan `status_pendaftaran=diterima` menyebabkan:
- Kuota `terisi` **tidak** naik (tidak ada `lockForUpdate`/increment seperti `:225-238`)
- `LogStatusPendaftaran` **tidak** ditulis (`:243-249`) → panel "Riwayat Status" menampilkan data yang salah
- `Siswa` **tidak** dibuat (`:251-268`) → siswa tidak pernah masuk Master Siswa
- `Pembayaran` **tidak** terbit (`:271-289`) → tidak ada invoice sama sekali

Kwotanya bisa "dibayar penuh" tanpa satu pun tagihan, dan `destroy()` (`:357-365`) tetap membaca status lalu decrement kuota → kuota berakhir negatif/bersalah secara permanen.

Form UI memang tidak menyediakan field status, tetapi tidak ada apa pun yang mencegah POST tersebut.

**Perbaikan**
Tambahkan `'status_pendaftaran'` ke daftar `except()` di `CalonSiswaController:174`, dan/atau hapus dari `$fillable` + FormRequest karena transisi status harus satu pintu (`updateStatus()`).

---

## C3. Menghapus calon siswa menghapus seluruh riwayat akademik 🔍

**Lokasi**
- `app/Http/Controllers/Admin/CalonSiswaController.php:381` — `$calonSiswa->delete()`
- `database/migrations/2026_09_07_144116_create_siswas_table.php` — `calon_siswa_id` cascadeOnDelete
- Rantai cascade: `riwayat_kelas`, `absensis`, `wali_murids`, `pengumpulan_tugas`

**Masalah**
`calon_siswas` → `siswas` (cascade) → seluruh tabel riwayat (cascade). Menghapus baris di halaman PPDB berarti menghapus data siswa yang sudah berjalan.

**Dampak**
Calon yang sudah `diterima` dan sudah ditempatkan ke kelas (via import penempatan) kehilangan: data Master Siswa, seluruh riwayat kehadiran, riwayat kelas, dan tautan wali murid — hanya dengan satu klik "Hapus" di halaman PPDB. Tidak ada guard `is_aktif` maupun konfirmasi, berbeda dengan `SiswaController` yang memblokir hapus bila sudah ada riwayat.

**Perbaikan**
Tolak hapus bila sudah ada `Siswa`/riwayat (misalnya seperti `SiswaController::destroy()`), atau ubah jadi soft-delete / nonaktifkan.

---

## C4. Transisi `diterima → ditolak` tidak membersihkan hasil penerimaan 🔍

**Lokasi**
- `app/Http/Controllers/Admin/CalonSiswaController.php:236-238` — hanya `$kuota->decrement('terisi')`

**Masalah**
Saat `diterima`, sistem membuat: `Siswa` (`:251-268`), `WaliMurid` + akun `User` role `orang-tua` (dari `SiswaObserver::created()`, password = NISN anak), seluruh `Pembayaran` invoice wajib (`:271-289`), dan `RencanaAngsuran` bila ada. Saat dibalik ke `ditolak`, **tidak ada satu pun** artefak itu dibersihkan.

**Dampak**
Calon yang ditolak tetap:
- Muncul sebagai siswa aktif di Master Siswa (`is_aktif = true`)
- Punya tagihan aktif yang bisa ditagih
- Walsi/ortunya masih bisa login ke `/ortu/dashboard` (akun `User` tidak dihapus/disable)

Tidak ada kode di `app/` yang bereaksi terhadap status `ditolak` (verified: `status_pendaftaran` hanya ditulis di `CalonSiswaController.php:89,241` dan `SpmbController.php:136`).

**Perbaikan**
Saat transisi keluar dari `diterima`, nonaktifkan `Siswa` (`is_aktif = false`), hapus/batalkan invoice, dan set defaultkan/nonaktifkan akun wali.

---

## C5. Nomor pendaftaran & kode pembayaran bentrok → HTTP 500 (deterministik) 🔍

**Lokasi**
- `app/Http/Controllers/SpmbController.php:103` — `sprintf('PPDB-%s-%04d', date('Y'), CalonSiswa::whereYear('created_at', date('Y'))->count() + 1)`
- `app/Http/Controllers/Admin/CalonSiswaController.php:387-393` — `generateNoPendaftaran()`, pola identik
- `app/Http/Controllers/Admin/CalonSiswaController.php:398` — `generateKodePembayaran()`, pola identik
- `app/Http/Controllers/Admin/PembayaranController.php:484` — pola identik
- `app/Http/Controllers/Admin/RencanaAngsuranController.php:173,181` — pola identik
- `no_pendaftaran` UNIQUE: `database/migrations/2026_09_07_143004_create_calon_siswas_table.php:18`
- `kode_pembayaran` UNIQUE: `database/migrations/2026_09_07_143744_create_pembayarans_table.php:18`

**Masalah**
Nomor dibuat dari `count() + 1` tanpa `lockForUpdate`, tanpa sequence, tanpa retry. Angka `count()` bisa turun karena penghapusan, sehingga nomor yang sudah terpakai bisa dibuat ulang.

**Dampak (deterministik, bukan hanya race)**
Ada `PPDB-2026-0001..0005`. Admin menghapus `0002` → `count()` = 4 → pendaftar berikutnya mendapat `PPDB-2026-0005` (sudah ada) → **unique violation → `QueryException` → HTTP 500** (tidak tertangkap; `SpmbController` hanya catch `RuntimeException`).
Berlaku sama untuk `kode_pembayaran` (PAY-xxx) dan nomor angsuran. Risiko yang sama berlaku bila `QueryException` tidak tertangkap.

**Perbaikan**
Ganti dengan nomor berbasis kolom auto-increment/sequence atau `max()+1` dengan `lockForUpdate` + retry pada unique violation.

---

# HIGH

## H1. Kuota tidak pernah naik dari pendaftaran publik — check jadi dead code ✅

**Lokasi**
- `app/Http/Controllers/SpmbController.php:94-101` — membaca `$kuota->terisi >= $kuota->kuota` (VERIFIED: tidak ada `increment` di seluruh `SpmbController`)
- `app/Http/Controllers/Admin/CalonSiswaController.php:225-238,358-365` — satu-satunya tempat `terisi` di-increment/decrement

**Masalah**
Pendaftaran publik **membaca** kuota tapi tidak pernah menaikkan `terisi`. `terisi` hanya bergerak saat admin mengubah status lewat `updateStatus`.

**Dampak**
Gate kuota di `:99` tidak pernah bisa terpicu dari trafik publik — nilainya tetap di nilai seed. Administrator melihat kuota "0 terisi" padahal sudah ratusan mendaftar. Kuota efektif hanya berlaku di tahap admin.

**Perbaikan**
Tentukan semantik yang benar: bila kuota berarti *penerimaan*, pindahkan gate ke `updateStatus` (sudah ada) dan hapus check di publik. Bila kuota berarti *jumlah pendaftar*, tambahkan increment saat pendaftaran.

---

## H2. Email bentrok di tabel `users` → HTTP 500 (bukan pesan validasi) 🔍

**Lokasi**
- `app/Http/Requests/Spmb/StorePendaftaranRequest.php:41` — `'email' => [..., 'unique:calon_siswas,email']`
- `database/migrations/0001_01_01_000000_create_users_table.php:18` — `email` UNIQUE
- `app/Http/Controllers/SpmbController.php:165-167` — hanya catch `\RuntimeException`

**Masalah**
Validasi hanya mengecek unikness di `calon_siswas`, padahal email juga UNIQUE di `users`. Pendaftar yang memakai email yang sudah terdaftar (admin/guru/ortu) lolos validasi, lalu `User::create()` memicu `QueryException` — bukan `RuntimeException` — sehingga **tidak tertangkap** dan menjadi HTTP 500. Dengan `APP_DEBUG=true`, detail SQL ikut tampil.

**Perbaikan**
Tambahkan `unique:users,email` (dan `username`/NISN uniqueness) di FormRequest, dan/atau tangkap `QueryException` untuk mengubahnya jadi pesan validasi ramah.

---

## H3. Timeline Jadwal PPDB di halaman publik selalu menampilkan data dummy ✅

**Lokasi**
- `app/Http/Controllers/SpmbController.php:28-29,44,58-59,70` — mengirim key **`$jadwalPpdbs`** (jamak)
- `resources/views/spmb/pendaftaran.blade.php:10,18,272` — membaca **`$jadwalPpdb`** (tunggal)

**Masalah (VERIFIED)**
Selisih nama variabel_plural vs _singgal_ → variabel selalu `null` di view → fallback `?? collect([...])` aktif → timeline menampilkan lima baris hardcoded: `1 Nov 2026`, `15 Nov 2026`, `1 Des 2026`, `15 Des 2026`, `31 Des 2026` dengan `keterangan => null`. Data `JadwalPpdb` asli (seeded di `PpdbSeeder.php:183-223`) tidak pernah tampil.

**Dampak**
Bagian "Timeline Pendaftaran" di halaman pendaftaran publik Entirely fictional. Pendaftar melihat tanggal yang salah.

**Perbaikan**
Samakan nama variabel di controller/view.

---

## H4. Dua tombol CTA utama di landing page tidak berfungsi ✅

**Lokasi**
- `resources/views/spmb/partials/hero.blade.php:41` — tombol "Login Calon Murid" `<a href="#">`
- `resources/views/spmb/partials/alur-pendaftaran.blade.php:166` — tombol "Mulai Daftar Sekarang" `<a href="#">`
- `resources/views/layouts/spmb.blade.php:28-31` — handler `a[href^="#"]` → `preventDefault()` lalu `document.querySelector(this.getAttribute('href'))`

**Masalah (VERIFIED)**
`href="#"` membuat handler memanggil `document.querySelector('#')` → melempar `SyntaxError` (selector tidak valid). Kedua tombol tidak melakukan navigasi apa pun dan menghasilkan error console.

**Dampak**
Dua tombol konversi utama di landing page (login & daftar) tidak menuju `/login` maupun `/pendaftaran`. Kedua tautan tersebut sama sekali tidak berfungsi.

**Perbaikan**
Ganti `href="#"` dengan `route('login')` / `route('spmb.pendaftaran')`, atau perbaiki handler agar mengabaikan `href="#"` yang invalid.

---

## H5. Calon yang DITOLAK masih dapat ditagih pembayaran 🔍

**Lokasi**
- `app/Http/Controllers/Admin/PembayaranController.php:157-206` (`store`), `:211-324` (`storeDenganAngsuran`), `:463-479` (`calonBelumLunas`)
- `app/Models/CalonSiswa.php:89-97` — `scopeBelumLunas` tanpa filter status

**Masalah**
Tidak ada satu pun dari alur pembayaran (buat, buat bers angsuran, update, update status) yang memeriksa `$calon->status_pendaftaran === 'diterima'`. `scopeBelumLunas` juga tidak memfilter status.

**Dampak**
Calon berstatus `ditolak` muncul di pilihan "Tambah Pembayaran" dan di modal pembayaran pada halaman detailnya. Uang bisa ditagih kepada pendaftar yang sudah ditolak. Invoice-nya lalu ikut terhapus diam-diam bila kandidat dihapus (bersama C3).

**Perbaikan**
Filter `status_pendaftaran = 'diterima'` pada semua query & guard pembayaran.

---

## H6. Calon `diterima` tidak dapat diedit sama sekali — data tidak bisa dikoreksi ✅

**Lokasi**
- `app/Http/Controllers/Admin/CalonSiswaController.php:163-171`
  ```php
  $oldJalur = $calonSiswa->jalur_pendaftaran_id;   // int dari DB
  $newJalur = $validated['jalur_pendaftaran_id'];  // string dari POST
  if (($oldJalur !== $newJalur || $oldTahun != $newTahun) && $oldStatus === 'diterima') {
      return back()->with('error', 'Tidak dapat mengganti jalur/tahun...');
  }
  ```
- Kolom `jalur_pendaftaran_id` adalah `foreignId` (INTEGER), tanpa `$casts` di `CalonSiswa`
- **Tidak ada test yang menutup `update()`** sama sekali (VERIFIED via grep)

**Masalah (VERIFIED)**
Operator `!==` membandingkan tipe **dan** nilai. `1 !== "1"` bernilai **true**. Karena `jalur_pendaftaran_id` integer di DB dan string dari form, guard ini selalu terbakar — bahkan ketika admin tidak mengubah jalur sama sekali. Perhatikan juga inkonsistensi dalam satu baris: `!==` untuk jalur, tapi `!=` untuk tahun.

**Dampak**
Setiap upaya memperbaiki NIK/nama/alamat/phone calon yang sudah `diterima` ditolak dengan pesan "Tidak dapat mengganti jalur/tahun". Data yang salah pada siswa diterima tidak dapat dikoreksi.

**Perbaikan**
Normalisasi tipe sebelum perbandingan (`(int)` keduanya), atau gunakan `where` scoped update.

---

## H7. Batas waktu pendaftaran (`JadwalPpdb`) tidak pernah ditegakkan 🔍

**Lokasi**
- `app/Http/Controllers/SpmbController.php:79-83` — satu-satunya gate: `TahunAjaran::aktif()`
- `app/Models/JadwalPpdb.php` — tidak ada `scope`/`is_aktif`/flag tipe
- `routes/console.php` / `Pengaturan` — tidak ada kunci "pendaftaran dibuka"

**Masalah**
`JadwalPpdb` hanya dipakai untuk tampilan. Tidak ada satupun logika gating berdasarkan `tanggal_mulai`/`tanggal_selesai` di seluruh `app/`.

**Dampak**
Pendaftaran publik terbuka 24/7, bahkan sudah lewat periode "Pendaftaran Online" yang ditampilkan di halaman publik. Jadwal PPDB tidak pernah ditegakkan di sisi server.

**Perbaikan**
Tambahkan gate server-side di `create()` dan `store()` berdasarkan `JadwalPpdb` aktif + tanggal hari ini.

---

## H8. Penghapusan berkas & calon: file hilang sebelum commit DB; bukti pembayaran tidak pernah dibersihkan 🔍

**Lokasi**
- `app/Http/Controllers/Admin/CalonSiswaController.php:331-340` (`verifyBerkas`) — `Storage::delete()` + `->store()` keduanya jalan **sebelum** `$berkas->update()`
- `app/Http/Controllers/Admin/CalonSiswaController.php:366-381` (`destroy`) — file dihapus di dalam `DB::transaction()` tetapi **sebelum** `$calonSiswa->delete()`
- `app/Http/Controllers/Admin/CalonSiswaController.php:376-380` — hanya menyapu `pembayaranLainnya`, **tidak** iterasi `$calonSiswa->pembayaran`

**Masalah**
1. `verifyBerkas`: jika update DB gagal/terputus, baris DB tetap menunjuk file lama yang sudah dihapus → link 404 di panel verifikasi; file baru yatim di disk.
2. `destroy`: bila rollback terjadi (FK restrict, error DB), baris bertahan dengan path menggantung.
3. Bukti pembayaran (`pembayarans.bukti_pembayaran_path`) **tidak pernah dihapus** — file tetap di disk publik selamanya.

**Perbaikan**
Pindahkan operasi file setelah commit transaksi (atau compensating), dan s comprehensivelyiterate `pembayaran` saat destroy.

---

# MEDIUM

| ID | Temuan | Lokasi | Dampak |
|---|---|---|---|
| **M1** | Teks "max 2MB" di halaman publik, tapi validasi `max:5120` (5MB) untuk ijazah/KK/akta/SKL; hanya foto 2MB | `alur-pendaftaran.blade.php:137` vs `StorePendaftaranRequest.php:47-49,51` | Pendaftar mengikuti instruksi publik → file ditolak |
| **M2** | Step 1 "Buat Akun … dengan email dan password" — tidak ada form registrasi terpisah; password dibuat server (`Str::random(8)`) | `alur-pendaftaran.blade.php:34,165`; `SpmbController.php:105` | Proses yang dipublikasikan salah; tidak ada cara pilih password |
| **M3** | `agama` publik free-text (`max:50`), tapi admin pakai `<select>` dengan 7 opsi tetap | `StorePendaftaranRequest.php:37` vs `StoreCalonSiswaRequest.php:29` | Nilai seperti "islam" tidak cocok select → saat admin edit & simpan, **data agama tertimpa/terhapus diam-diam** |
| **M4** | `wajib_sertifikat` **tidak ditegakkan di form admin** — hanya JS client-side, tanpa `withValidator`; helper `jalurWajibSertifikat()` dead code | `StoreCalonSiswaRequest`, `UpdateCalonSiswaRequest`, `BersihkanSertifikatKosong.php:68-71`, `_form.blade.php:254,321-329` | Admin bisa membuat/edit jalur prestasi tanpa sertifikat |
| **M5** | `throttle:5,1` per IP di endpoint pendaftaran | `routes/web.php:55` | Sekolah di balik 1 IP/NAT → 5 submit/menit **seluruh sekolah**; gagal validasi pun membakar kuota |
| **M6** | Kuota dipakai sebagai gate **intake**, bukan gate **acceptance** | `CalonSiswaController.php:104-106`, `SpmbController.php:99-101` | Saat slot penuh, pendaftaran ditutup (termasuk walk-in/telat) |
| **M7** | Definisi "lunas" berbeda: halaman detail menjumlahkan **semua** biaya; `scopeBelumLunas` hanya `wajib_bayar` | `show.blade.php:1494,1550` vs `CalonSiswa.php:93-96` | Pendaftar bisa "Lunas" di halaman tapi "belum lunas" di picker pembayaran |
| **M8** | Label "Berkas perlu perbaikan" menampilkan nama kolom mentah (`ijazah_path`, `kk_path`, dst) | `show.blade.php:1395-1398` | UI tidak profesional; tidak dipahami pengguna |
| **M9** | Tidak ada route upload ulang untuk siswa saat berkas `perlu perbaikan` | `routes/web.php` (tidak ada endpoint) | Dead end — siswa diminta perbaiki berkas tanpa mekanisme |
| **M10** | `status_verifikasi` tidak pernah dicek saat menerima | `CalonSiswaController.php:208-297` | Berkas yang ditolak tetap bisa di-set `diterima` tanpa peringatan |
| **M11** | Jalur non-aktif tetap menerima pendaftaran publik (validasi hanya `exists`, bukan `aktif:true`) | `StorePendaftaranRequest.php:30` vs `SpmbController.php:57` (dropdown filter `aktif`) | Jalur yang dinonaktifkan masih bisa dipilih via POST |
| **M12** | `$kuotaMap` dikirim ke view tapi tidak pernah dipakai; tidak ada info kuota di form publik | `SpmbController.php:62-71`; `pendaftaran.blade.php` | Pendaftar tidak melihat sisa kuota/status penuh |

---

# LOW

| ID | Temuan | Lokasi |
|---|---|---|
| **L1** | Angka hardcoded kontradiktif: "Kuota Terbatas — 180 siswa" & "6 Kelas @30" vs data asli kuota 320 | `keunggulan.blade.php:98-116` vs `PpdbSeeder.php:91-127` |
| **L2** | "500+ Siswa Aktif" di hero, tapi halaman about memakai data asli — dua angka berbeda di satu halaman | `hero.blade.php:52-62` vs `about.blade.php:23` |
| **L3** | Placeholder `GANTI: (0274) ...` tercetak di halaman publik + `href="tel:GANTI: ..."` rusak | `ProfilSekolahSeeder.php` → `pendaftaran.blade.php:79,723,730` |
| **L4** | `Pendaftaran Dibuka!` hardcoded, tidak pernah bisa dimatikan admin | `hero.blade.php:20` |
| **L5** | Identitas sekolah tidak konsisten: `SMKN Ngaglik` (seeder) vs fallback `"SMP Harapan Bangsa"`, deskripsi "kelas 6 SD" & "usia 15" | `ProfilSekolahSeeder`, `navbar.blade.php:3`, `footer.blade.php:3`, `jalur-seleksi.blade.php:54,60` |
| **L6** | Gelombang: `terisi`/`kuota` statis (tidak ada `gelombang_id` di `calon_siswas`), `tanggal_buka`/`tutup` tidak ditegakkan | `gelombang.blade.php:27,53-54,99-104`; migrasi `calon_siswas` |

---

# Kesimpulan Alur Kerja (alur yang berjalan saat ini)

```
Publik:  Landing → [CTA MATI] → /pendaftaran (form 4 langkah)
                → validasi → cek kuota (dead code) → buat User(siswa) + CalonSiswa(menunggu)
                → simpan berkas ke DISK PUBLIK → flash password sekali → LANGSUNG masuk /pendaftaran (sukses)
Admin:   /admin/calon-siswas → ubah status → diterima (kuota++, buat Siswa + Wali + invoice)
                → (opsional) Export Diterima → isi kelas → Import penempatan (snapshot)
                → hapus calon = HAPUS SELURUH DATA SISWA
```

Yang **tidak** berjalan sama sekali: gate waktu (H7), gate kuota publik (H1), gate jalur aktif (M11), gate `wajib_sertifikat` di admin (M4), upload ulang berkas oleh siswa (M9), pembersihan saat ditolak (C4).

---

# Cakupan Test yang Tidak Tercakup (kesenjangan)

`tests/Feature/SpmbPendaftaranTest.php` (6 test) & `CalonSiswaTest.php` menutup jalur *sukses* dan beberapa validasi dasar, **tidak** menutup:
1. `kuota->terisi` naik setelah pendaftaran publik (H1) — test bahkan **manual** set `terisi=1` sehingga bug tersembunyi
2. `update()` `CalonSiswa` sama sekali (H6) — nol test
3. Email bentrok di `users` (H2)
4. Nomor pendaftaran bentrok / integritas sequence (C5)
5. Pendaftaran ke jalur non-aktif (M11)
6. Gate `JadwalPpdb` (H7)
7. `wajib_sertifikat` melalui endpoint publik
8. Role `siswa` & username = NISN & kredensial flash bisa login (round-trip)
9. `LogStatusPendaftaran` dibuat
10. Batas per-file (mime/ukuran) melalui endpoint publik
11. Assertion `User`/`Siswa`/`BerkasCalonSiswa` pada admin create

---

# Rekomendasi Prioritas

| Tahap | Cakupan | Alasan |
|---|---|---|
| **Tahap 1** | C1, C2, C3, C4, C5 | Kerusakan data & kebocoran data pribadi — harus prior |
| **Tahap 2** | H1–H8 | Fungsional inti tidak bekerja / tidak konsisten |
| **Tahap 3** | M1–M12 | Konsistensi data & UX |
| **Tahap 4** | L1–L6 | Polish teks publik & konsistensi |

*Catatan: beberapa temuan (terutama C1 dan C3) bersifat struktural dan menyentuh storage/security — perlu hatian khusus saat memperbaiki agar tidak merusak alur yang sudah berjalan.*
