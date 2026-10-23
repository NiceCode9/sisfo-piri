# Desain: Catatan Izin/Sakit + Bukti di Jam Pertama

Tanggal: 2026-10-23
Status: menunggu persetujuan user
Pendahulu: [Absensi QR Mandiri](2026-10-21-absensi-qr-mandiri-design.md) (Fase A5, sudah dieksekusi)

## Inti & alasan

Sejak Fase A5, siswa menandai diri hadir dengan memindai QR milik guru di jam
mata pelajaran pertama. Siswa yang izin atau sakit tidak memindai QR — justru
mereka yang membawa surat (surat izin orang tua, atau surat keterangan sakit
dari dokter). Saat ini surat itu berhenti di tata usaha guru: tidak ada tempat
di sistem untuk mencatatnya, sehingga baris absensi siswa tetap kosong (rawan
terhitung alpa di rekap) dan buktinya tidak jejak.

Desain ini menambah satu **kartu "Izin/Sakit"** di halaman yang sudah dibuka
guru setiap pagi — halaman QR Absen (`guru.absensi-qr`) — supaya guru
menyelesaikan semuanya di satu tempat saat jam pertama: pilih siswa, tandai
izin atau sakit, pasang foto surat bila surat sedang di tangan.

## Keputusan yang sudah ditegaskan user

1. **Lokasi**: kartu di halaman QR Absen guru, bukan di grid absensi. Guru tidak
   perlu pindah halaman pagi-pagi.
2. **Cakupan waktu**: pencatatan cukup per hari ini. Untuk sakit berjangka
   beberapa hari, guru menandai ulang di hari berikutnya **tanpa perlu
   mengunggah dokumen lagi** (rentang tanggal sengaja tidak dibuat).
3. **Sumber bukti**: dua jalur (surat kertas yang guru foto di tempat, atau
   foto/pesan WA orang tua yang diteruskan) — keduanya berakhir sama, guru yang
   mengunggah. Surat kertas adalah kasus paling sering, jadi bentuk form harus
   nyaman dipakai satu per satu, bukan batch.
4. **Hak menulis bagi guru biasa (opsi 1 dari tiga pilihan)**: penggunaan hari
   ini memang dilakukan guru pengajar jam pertama, sedangkan `absensis.create`
   hanya dipegang `admin`/`super-admin`/`guru-piket`. Diputuskan: **pengecualian
   sempit** — role `guru` boleh menulis lewat kartu ini dengan batas tetap:
   status hanya `izin`/`sakit`, hanya untuk rombel yang diampu/wali, hanya baris
   baru (bukan koreksi), dan unggah bukti hanya pada baris berstatus itu.
   Hak `absensis.create` milik grid manual/impor tidak diubah sama sekali.

## Tujuan

- Guru menandai siswa sebagai `izin` / `sakit` pada hari itu, lengkap dengan
  bukti foto sekaligus, tanpa meninggalkan halaman QR Absen.
- Guru bisa menandai lagi hari-hari berikutnya tanpa mengunggah bukti ulang;
  bukti periode pertama tetap tersimpan pada baris hari pertamanya.
- Baris yang terbentuk memakai jalur yang persis sama dengan grid manual
  (`metode = 'manual'`, `dicatat_oleh`, `PencatatanAbsensi::simpan`), jadi rekap,
  ekspor, dan pengecekan `dokumen.absensi` otomatis benar tanpa diubah.
- Bukti bisa diunggah menyusul bila surat baru tiba sore (saluran WA).
- **Tidak** membuka `absensis.create` untuk role `guru` — pengecualiannya
  terbatas pada titik pintu kartu izin/sakit dan unggah bukti versi guru, dan
  positif diuji test. Grid manual, impor, dan endpoint admin tetap sebagaimana
  adanya.

## Non-tujuan & anti-tujuan

- **Tidak** membuat pencatatan rentang tanggal (sudah diputuskan: cukup hari
  ini).
- **Tidak** menandai `alpa` dari kartu ini — alpa jalurnya tetap grid manual
  (kartu ini hanya untuk siswa yang punya keterangan).
- **Tidak** menimpa baris yang sudah tercatat. Baris yang sudah punya status
  (`hadir`, `izin`, dst.) hanya ditampilkan; koreksinya lewat grid dengan alasan
  (aturan `absensis.edit` yang ada). Kalau siswa ternyata keliru pindai QR lalu
  guru tandai sakit, itu koreksi — jalurnya grid, bukan kartu.
- **Tidak** menandai siswa di luar rombel yang terjangkau guru (pakai
  penjaga yurisdiksi yang ada).
- **Tidak** menyentuh notifikasi WA, rekap/ekspor, atau grid manual —
  tidak ada kode di area itu yang berubah.
- Tidak ada dependensi baru.

## Bentuk yang dibangun

### Kartu di halaman QR Absen (`guru/absensi-qr.blade.php`)

Satu `card-nexus` baru di halaman, di bawah dua kartu QR/jendela yang ada:

- **Pemilih rombel**: dropdown rombel yang terjangkau guru. Daftar siswa
  mengikutinya; berubah pilihan lewat form GET kecil (pola sama dengan filter
  grid admin yang sudah ada).
- **Tabel siswa**: nama siswa + badge status absensi hari ini.
  - Belum tercatat: form kecil per baris — pilih status (`Izin`/`Sakit`),
    input file foto bukti (opsional), tombol simpan.
  - Terlambat (`terlambat`): tampilkan saja; tidak bisa ditandai dari kartu.
  - `izin`/`sakit` tanpa bukti: badge + tombol "Unggah Bukti" kecil (endpoint
    guru yang diambil dari `storeBukti`; admin/guru-piket tetap bisa lewat
    grid).
  - `izin`/`sakit` berbukti: badge + ikon bahwa bukti ada, mengarah ke halaman
    yang sudah ada (`dokumen.absensi`).

Satu siswa, satu form kecil — bukan batch. Surat datang satu per satu di
depan guru; batch hanya berguna di grid yang memang tempatnya.

### Endpoint pencatatan

- Satu route `POST` baru di area guru (calon nama: `guru.absensi-qr.izin`;
  pilih nama yang rapi saat eksekusi) menerima: `siswa_id`, `status`
  (`izin`|`sakit`), `berkas` opsional.
- Menulis baris lewat jalur yang sama seperti `storeBatch` di
  `Admin\AbsensiController`: kunci `(siswa_id, rombel_id, tanggal hari ini)`,
  `Absensi::firstOrNew`, `metode='manual'`, `dicatat_oleh=auth()->id()`,
  lalu `PencatatanAbsensi::simpan($absensi, $kunci)`. Jangan memanggil
  `PencatatanAbsensi::catat` — yang itu jalurnya scan dan berisiko menimpa
  (sudah dipagari, tetap jangan disentuh).
- Bila baris sudah ada (memiliki status apa pun): kembali ke halaman dengan
  pesan kecil yang menyuruh pakai grid untuk koreksi; daftar siswa tetap
  tampil, jadi guru tidak kehilangan pekerjaannya. Ini menjaga aturan alasan
  koreksi tanpa halaman error di hadapan guru pagi-pagi.
- Yurisdiksi: siswa harus anggota aktif rombel yang terjangkau guru. Pakai
  penjaga yang sama dengan yang grid pakai
  (helper `rombelAbsensiTerjangkau` di
  `Admin\AbsensiController` adalah polanya; bila perlu, angkat guardnya, jangan
  tulis ulang logika cakupan).
- Bila `berkas` dipakai: validasi gambar yang sama seperti
  `StoreBuktiAbsensiRequest`, simpan `berkas_path` ke disk privat `berkas`
  (`store('absensi', 'berkas')`) langsung pada baris yang baru dibuat. Nol
  mekanisme baru.
- Bila `berkas` kosong: baris dibuat tanpa bukti — ini pintu untuk "besoknya
  tandai sakit tanpa dokumen ulang".

### Model izin (pengecualian sempit untuk role `guru`)

- Status quo: `absensis.create` membatasi penulisan absensi ke
  `admin`/`super-admin`/`guru-piket`; role `guru` hanya `absensis.view`. Aturan
  itu tetap.
- Karena pengecualian ini hanya untuk kartu izin/sakit, penjagaannya bukan
  lewat permission baru di seeder, melainkan satu fungsi kecil yang dipanggil
  di kedua titik pintu guru (pencatatan izin/sakit & unggah bukti versi guru)
  dan menolak bila: status bukan `izin`/`sakit`, rombel di luar cakupan, atau
  baris yang diseruh bukan baru. Pengembangan ke depan yang menambah pintu
  guru ikut memanggil penjaga yang sama.
- Unggah bukti menyusul untuk guru biasa memakai endpoint guru kecil yang
  polanya mengikuti `storeBukti` (validasi gambar, cek status baris,
  hapus file lama bila mengganti, disk privat) + penjaga di atas. Endpoint
  `admin.absensis.bukti` tidak diubah dan tetap dipakai admin/guru-piket.

### Setelah menyimpan

Setelah menandai, halaman kembali menampilkan daftar dengan badge terbari.
Tidak ada form multi-langkah, tidak ada validasi berkas yang menahan baris
status (guru yang memutuskan suratnya penting atau tidak).

## Yang tersisa untuk builder

- Nama route + tata letak controller (`Guru\AbsensiIzinController` baru atau
  metode di `Guru\AbsensiQrController` — pilih yang membiarkan controller QR
  tetap kecil).
- Helper cakupan rombel guru: pakai ulang yang ada; bila masih privat,
  angkat jadi satu pintu yang mau dipakai grid maupun kartu (jangan duplikat
  logika cakupan — inilah titik kebocoran data kalau salah).
- Bentuk validasi request (`StoreAbsensiIzinRequest`) mengikuti pola
  request-kelas yang sudah ada.
- Test: contoh di `AbsensiQrMandiriTest` + `AbsensiInputTest` — kasus wajib:
  (1) guru menandai izin + bukti → baris manual terbentuk, disk isi;
  (2) sakit tanpa berkas → baris tanpa bukti, tidak error;
  (3) menandai baris yang sudah tercatat → kembali ke halaman dengan pesan
      kecil (bukan halaman error);
  (4) siswa rombel di luar cakupan guru → ditolak;
  (5) bukan gambar → validasi menolak;
  (6) kartu tampil di view QR;
  (7) guru mencoba status `hadir`/`alpa` atau mengubah baris milik orang →
      ditolak penjaga sempit;
  (8) admin/guru-piket tetap bisa lewat jalur admin lama (tidak terkena
      regresi).

## Cara pengujiannya dinyatakan sukses

Di kelas nyata: guru buka halaman QR di jam pertama, siswa yang datang pindai
QR, siswa pembawa surat ditangani lewat kartu dalam < 10 detik per siswa, bukti
terlihat di halaman bukti yang sudah ada, dan besoknya guru cukup menandai ulang
tanpa berurusan dokumen.
