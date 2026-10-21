# Desain: QR Absen Mandiri (Fase A5)

Tanggal: 2026-10-21. Status: **menunggu persetujuan pemilik**. Belum ada kode.
Modul: `backend`, absensi harian. Rujukan alur lama: `docs/ROADMAP_ABSENSI.md`.

## Latar & niat

Alur lama ternyata lahir dari salah paham dengan client: sistem memakai
**guru piket yang memindai kartu QR tiap siswa**. Sekarang jelas cara itu
tidak efektif: siswa sekitar 600 orang, guru piket hanya sedikit, dan
sekolah ini mengizinkan siswa membawa HP.

Arah baru yang disetujui client: **siswa yang memindai, QR-nya digenerate
harian di akun guru**. Absen hanya berlaku di jam mata pelajaran pertama.
QR hari ini tidak berlaku untuk esok hari (tahan kecurangan foto QR), dan
scan di atas batas jam otomatis berstatus terlambat.

## Alur yang disetujui

1. Guru (role `guru` atau `guru-piket`) membuka menu **QR Absen** di akunnya
   (HP/laptop) saat jam pertama masuk. Halaman menampilkan QR spesifik
   **guru + tanggal**. QR-nya otomatis berganti setiap `menit_ganti_qr`
   (default 10 menit, pengaturan admin).
2. Siswa membuka panel siswa → **Absen Mandiri** → kamera memindai QR yang
   ditampilkan guru → submit.
3. Server menentukan sendiri (jam HP tidak dipercaya):
   - token valid, tanggal hari ini,
   - `now < jam_buka` → tolak ("Belum dibuka", default 07:00),
   - `jam_buka ≤ now < batas_terlambat` → status **hadir** (batas default
     pindah ke 07:15),
   - `batas_terlambat ≤ now < jam_tutup` → status **terlambat** (otomatis),
   - `now ≥ jam_tutup` → tolak ("Sudah ditutup", default 07:45; siswa yang
     hadir sesudahnya dicatat manual guru lewat grid, seperti sekarang).
4. Baris absensi: satu baris per siswa+tanggal dengan rombel aktif siswa;
   `metode` tetap `qr`; `dicatat_oleh` = user pemilik QR (keinginan client:
   guru yang menampilkan ikut tercatat).
5. Scan ulang idempoten mengikuti aturan arrival pertama yang sudah berlaku di
   `AbsensiController`: jam datang pertama menang, status hadir tidak turun
   menjadi terlambat karena scan kedua.
6. Scan tidak pernah menimpa catatan manual guru. Baris sakit/izin/alpa yang
   sudah ada di grid membuat scan ditolak dengan pesan "sudah tercatat
   (status X)", koreksi tetap harus lewat jalur guru dengan alasan. Keputusan
   saya sendiri, hasil builder check: menimpa catatan guru diam-diam
   bertentangan dengan notifikasi WA alpa yang mungkin sudah terkirim.

## Keputusan yang sudah dibuat (sumber: percakapan 2026-10-21)

| # | Keputusan |
|---|---|
| 1 | Siswa scan sendiri; QR digenerate harian di akun setiap guru (bukan hanya guru piket), token `guru+tanggal` |
| 2 | Jendela: buka 07:00, telat ≥ 07:15, tutup 07:45, dan semuanya jadi pengaturan admin |
| 3 | QR berganti tiap 10 menit, jadi QR yang difoto tidak bisa dipakai teman di rumah lama-lama; intervalnya juga pengaturan |
| 4 | Guru pemilik QR dicatat di baris absensi siswa (`dicatat_oleh`) |

## Non-goals

- Geofencing / deteksi lokasi. Bukti kehadiran tetap berbasis scan saat guru
  menampilkan QR di kelas.
- Notifikasi WA baru untuk status `terlambat`. Notifikasi alpa yang ada
  (`absensi:cek-belum-hadir`, hook grid) tidak berubah.
- Rekap, ekspor, import, riwayat koreksi: semua jalur baca dan grid manual
  tidak disentuh.
- Kolom `metode` dan status enum tidak diperluas; tidak ada dependensi baru.

## Rancangan teknis

### Token QR guru (tanpa tabel baru)

Isi QR: teks pendek `absqr|{user_id}|{tanggal}|{slot}|{hmac}`.

- `slot` = `intdiv(detik sejak tengah malam di zona waktu sekolah,
  menit_ganti_qr × 60)`, dihitung server.
- `hmac` = `hash_hmac('sha256', "absqr|{user_id}|{tanggal}|{slot}", config('app.key'))`.
- Validasi: `tanggal == hari ini`, hmac cocok, `slot` sama dengan slot saat
  ini atau satu slot di belakangnya (toleransi saat pergeseran). Token dengan
  slot lain ditolak, jadi QR kedaluwarsa atau tangkapan layar mati sendirinya.
- Identitas guru dibaca dari `user_id`, jadi tidak ada tabel token dan tidak
  ada tulis ke DB saat guru menampilkan QR.

### Endpoint

- `GET  guru/absensi-qr` (`guru.absensi-qr`), halaman tampilan QR plus info
  jam buka/tutup, role `guru|guru-piket`.
- `GET  guru/absensi-qr/refresh` (`guru.absensi-qr.refresh`), JSON:
  `{ token, interval_menit, ganti_pada }` untuk auto-refresh di JS.
- `GET  siswa/absensi/scan` (`siswa.absensi.scan`), halaman pindai (kamera).
- `POST siswa/absensi/scan` (`siswa.absensi.scan.store`), JSON `{ token }`;
  throttle pakai `RateLimiter::for('absensi-scan')` yang sudah ada.

### Aturan tulis (meneruskan perilaku yang ada)

- Scan siswa diperiksa: user role `siswa` + riwayatKelas aktif → `Rombel`
  (pola pencocokan `kelas_id` + `tahun_ajaran_id` seperti
  `Rombel::historiLengkap()`). Tanpa rombel → tolak dengan pesan jelas.
- Helper tulis (`catatKehadiran`, `isiArrivalPertama`, `simpanAbsensi`) di
  `AbsensiController` dipindahkan ke service statis baru (gaya
  `App\Services\RiwayatSiswa`) dengan parameter `dicatat_oleh` eksplisit,
  supaya grid admin, import, dan scan mandiri memakai satu otak yang sama.
  Jalur grid `storeBatch` tidak berubah perilaku (tetap koreksi eksplisit
  dengan alasan), hanya memanfaatkan helper `simpanAbsensi` yang dipindah.

### Penghapusan (menutup jalur lama)

- Route `absensis/scan` + `absensis/scan.store`, method `scan`/`storeScan`,
  view `admin/absensis/scan.blade.php`, dan tombol "Scan QR" di
  `admin/absensis/index.blade.php`: client menegaskan alur scan kamera
  device piket tidak dipakai lagi. Halaman scan tidak punya item menu DB,
  jadi tidak ada yang perlu disentuh di `MenuSeeder`.
- Jalur regenerasi kartu siswa (`siswas/{siswa}/qr`,
  `SiswaController::regenerateQr`, form di `show.blade.php`) dan blok QR di
  kartu cetak (`kartu-siswa.blade.php` + penulisan token di
  `SiswaController::kartu()`) kehilangan tujuan karena tidak ada lagi pemindai
  kartu: kartu cetak tetap ada sebagai kartu identitas, hanya blok QR-nya
  yang dihapus. Putusan saya dari builder check: QR di kartu resmi tidak boleh
  berubah diam-diam mencetak QR NISN yang tidak dipakai siapa pun. Kolom
  `siswas.qr_token` lalu di-drop (builder mencentang dulu tidak ada pemakai
  lain, `grep qr_token`).

### Pengaturan baru (tabel `pengaturans`, pola yang sama)

`jam_buka_absen` (default 07:00), `jam_tutup_absen` (default 07:45),
`menit_ganti_qr` (menit, default 10): masuk `kunciList` PengaturanController,
rules `date_format:H:i` / integer (min 5, maks 120), dan `KUNCI_CACHE`
(dibaca di setiap jalur scan); `batas_terlambat` diisi 07:15 via migrasi
(nilai baru atas permintaan client). Semantik migrasi (putusan saya, hasil
builder check): baris yang belum ada dibuat dengan 07:15; baris eksisting
yang masih bernilai bawaan lama 07:00 dinaikkan ke 07:15; nilai kustom admin
yang lain dibiarkan. Dua hal mengikuti di belakangnya:

- `PengaturanSeeder` masih menanam 07:00, dan seeder berjalan setelah migrasi
  pada `migrate --seed`; bawaan seeder ikut dinaikkan supaya instalasi baru
  tidak kembali ke 07:00.
- Validasi menyimpan pengaturan menambah cek relasi antar kunci:
  `jam_buka_absen < batas_terlambat < jam_tutup_absen`. Tanpa cek itu, satu
  input salah membunuh semua scan di esok pagi dengan gejala yang tidak jelas.

### UI

- Guru: view baru `resources/views/guru/absensi-qr.blade.php`, mengikuti gaya
  layout guru yang ada; QR dirender server via `simplesoftwareio/simple-qrcode`
  yang sudah terpasang; JS memanggil ulang endpoint refresh sesuai interval
  dan menampilkan indikator kapan QR berikutnya berganti. Kondisi: gagal
  fetch menampilkan error dan bisa dicoba lagi.
- Siswa: view baru `siswa/absensi/scan.blade.php` memakai kembali pola
  `html5-qrcode` dari scanner yang dihapus. Kondisi yang diwajibkan (tidak
  boleh ada layar mati): belum dibuka, sudah ditutup, kamera tidak
  diizinkan, token tidak valid/kedaluwarsa, gagal simpan, sukses hadir,
  sukses terlambat, sudah tercatat (scan ulang, atau barisnya sudah masuk catatan manual guru sehingga scan ditolak). Semua teks bahasa Indonesia
  yang menjelaskan apa yang terjadi dan apa yang harus dilakukan.
- Tanpa elemen dekoratif baru; mengikuti pola layout/Tailwind yang sudah ada
  di panel siswa dan admin/guru.

### Test (Pest, sesuai aturan repo)

File baru `tests/Feature/AbsensiQrMandiriTest` (RefreshDatabase diaktifkan
sesuai pola yang ada di test absensi):
- batas menit: 07:14:59 hadir, 07:15:00 terlambat,
- ≥ `jam_tutup` ditolak; sebelum `jam_buka` ditolak,
- token: slot salah, tanggal kemarin, hmac salah → ditolak,
- `dicatat_oleh` = pemilik QR; metode `qr`,
- scan ulang setelah hadir tidak menurunkan status dan tidak menimpa
  `jam_datang` (aturan arrival pertama),
- scan menabrak baris manual (sakit/izin/alpa) yang sudah ada: ditolak,
  baris tidak berubah,
- siswa tanpa rombel aktif ditolak,
- test lama yang menyentuh `admin.absensis.scan` disesuaikan atau dihapus.

### Langkah deploy

1. `php artisan migrate` (tiga kunci pengaturan baru, batas 07:15, drop
   `siswas.qr_token`).
2. Perubahan menu lewat seeder yang idempoten atau lewat admin.
3. API kamera membutuhkan secure context: **HTTPS wajib** di domain produksi
   (konfirmasi lewat PANDUAN_DEPLOY_VPS sebelum rollout).
4. Aman terhadap cache: jam jendela dibaca lewat kunci yang terdaftar di
   `KUNCI_CACHE`, dan pembersihan cache sudah jalan ketika admin menyimpan
   pengaturan.

## Yang belum ditentukan (putusan builder, tidak mengubah hasil)

- Nama class controller/service dan teks view: mengikuti konvensi repo.
- Bentuk penampilan QR (SVG inline vs data URI): yang paling pendek dan
  kompatibel dengan view yang ada.
- Bersih-bersih kecil (import tak terpakai, menu lain): tertangkap saat build
  oleh pint dan test.

## Ringkasan ukuran

Satu fase (A5), tanpa dependensi baru, satu migrasi data, dua view baru,
dua endpoint guru (halaman + refresh) dan dua endpoint siswa (halaman + post),
satu ekstraksi service kecil, dan penghapusan dua halaman lama.
