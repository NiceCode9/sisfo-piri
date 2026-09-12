# Panduan Testing Modul Absensi

> Langkah uji manual per fase. Asumsi: database lokal MySQL, aplikasi jalan (`composer run dev`).
> Status: A1 ✅ teruji, A2 ✅ teruji, A3 ⏳ menunggu implementasi, A4 ⏳ menunggu implementasi.

## Persiapan umum (sekali saja)

```powershell
php artisan migrate:fresh --seed
```

## Fase A1 — Fondasi data + akun ortu

1. **Migrasi**: pastikan 5 tabel terbentuk (`absensis`, `siswas.qr_token`, `wali_murids`, `pengaturans`, `notifikasi_logs`) tanpa error.
2. **Default pengaturan**: tinker `Pengaturan::nilai('batas_terlambat')` → ekspektasi `07:00`.
3. **Role & permission**: tinker — role `guru-piket` punya `absensis.create` (tanpa `delete`); role `orang-tua` dan `siswa` nol permission.
4. **Tambah siswa via admin** (isi NISN, nama, no HP ortu, kelas + tahun aktif) → cek user `ortu-{NISN}` ada + role `orang-tua` + 1 baris `wali_murids` dengan no WA sesuai. Login sebagai ortu/password = NISN → bisa login.
5. **Kakak-beradik**: tambah siswa kedua dengan no HP ortu sama → hanya 1 user ortu untuk 2 baris `wali_murids`.
6. **Backfill**: hapus 1 user ortu + baris `wali_murids`-nya → `php artisan siswa:generate-orangtua` → "N akun dibuat"; run ulang → "0 akun" (idempoten).
7. **Import siswa** (bila dipakai): akun ortu ikut terbentuk.
8. **Suite**: `php artisan test --compact` → hijau (termasuk `AbsensiFondasiTest`).

## Fase A2 — Input

1. **Menu**: sidebar Akademik → Absensi. Grid tampil (rombel tahun aktif + tanggal hari ini + seluruh siswa, status default Hadir). Tombol Scan QR kanan atas.
2. **Grid manual**: ubah 2–3 status → Simpan → flash "N tersimpan", prefill menetap + Jam/Metode terisi. Simpan ulang tanpa ubahan → tidak duplikat. Tanggal kemarin bisa; tanggal besok ditolak.
3. **Scan kamera** (localhost + izin kamera): pilih rombel → pindai kartu → nama + status + jam instan, counter bertambah, kamera tetap hidup. Siswa sama diulang → update jam, counter tetap. QR rombel lain → error "bukan anggota". Tanpa kamera → warning + fallback token manual berfungsi.
4. **Terlambat**: sebelum 07:00 → `hadir`; sesudahnya → `terlambat`. Ubah `batas_terlambat` via tinker → perilaku mengikuti.
5. **QR siswa**: detail siswa → Buat QR → tampil; Kartu Siswa → QR di belakang (bukan NISN); Generate Ulang → token berubah, QR cetakan lama → "QR tidak dikenal".
6. **Peran**: `guru-piket` bisa input; user tanpa permission → 403.
7. **Suite**: `php artisan test --compact` → hijau (termasuk `AbsensiInputTest`).

## Fase A3 — Rekap + pantauan (menyusul)

_Belum diimplementasi — diisi setelah A3 selesai._

## Fase A4 — Notifikasi WA (menyusul)

_Belum diimplementasi — diisi setelah A4 selesai._
