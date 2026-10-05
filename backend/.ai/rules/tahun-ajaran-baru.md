---
paths:
  - 'app/Actions/**'
  - 'app/Http/Controllers/Admin/TahunAjaranBaruController.php'
  - 'app/Http/Controllers/Admin/KenaikanKelasController.php'
  - 'app/Http/Controllers/Admin/RombelController.php'
---

# Tahun Ajaran Baru

## Logika ada di Action, bukan controller

`SalinRombelAction` (salin rombel + wali + penugasan) dan
`ProsesKenaikanAction` (naik / tinggal / lulus) adalah satu-satunya
implementasi. `RombelController::prosesSalin()`,
`KenaikanKelasController::proses()`, dan wizard `Tahun AjaranBaruController`
semuanya memanggilnya.

Kalau butuh mengubah cara rombel disalin atau cara kenaikan memindahkan siswa,
ubah di Action — jangan di salah satu controller. Kalau aturan yang sama ditulis
dua kali, keduanya pasti akan berbeda setelah salah satu diubah.

## Urutan wajib: salin dulu, baru kenaikan

Wizard menjalankan tiga langkah berurutan:

1. `SalinRombelAction` — bentuk rombel tahun tujuan beserta wali + penugasan.
2. Pastikan setiap **kelas tujuan punya rombel**. Kelas yang baru dibuat (mis.
   8A saat sekolah naik dari kelas 7) tidak punya sumber untuk disalin, jadi
   rombelnya dibuat otomatis — kosong, tanpa wali dan penugasan. Kelas yang
   id-nya tidak dikenal sama sekali akan menolak seluruh wizard, jangan dipaksa.
3. `ProsesKenaikanAction` — pindahkan siswa.

Kenapa urutan ini tidak boleh dibalik: keanggotaan rombel diturunkan dari
`RiwayatKelas`, dan rekap absensi/nilai tugas/nilai ujian butuh rombel yang
ada. Siswa yang dipindahkan ke `kelas_id` tanpa rombel **lenyap** dari semua
rekap tanpa ada yang memberitahu — bukan error, hanya data yang tidak muncul.

## Pemetaan kelas dikunci kelas asal

Payload `pemetaan` berformat `kelas_asal_id => kelas_tujuan_id`. Nilai tujuan
boleh `"LULUS"` atau `""` (lewati, tidak dipindahkan). Salah membalik kunci
akan membuat semua siswa dilewati karena tidak ada sumber yang cocok — gejalanya
"0 naik" tanpa error.

## Idempoten dan boleh dijalankan berulang

Kedua Action aman dijalankan berkali-kali: yang sudah ada dilewati, bukan ditulis
ulang. Wizard boleh dipakai ulang selama tahun ajaran sedang disiapkan tanpa
merusak data.

Sisa "dilewati" pada kenaikan berlaku untuk siswa yang **masih tercatat di tahun
asal** tapi sudah punya baris riwayat di tahun tujuan — bukan untuk siswa yang
sudah pindah. Setelah pindah, siswa tidak lagi masuk query dan dihitung lagi.