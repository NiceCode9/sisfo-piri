---
paths:
  - 'app/Actions/**'
  - 'app/Http/Controllers/Admin/TahunAjaranBaruController.php'
  - 'app/Http/Controllers/Admin/RombelController.php'
  - 'database/seeders/MenuSeeder.php'
---

# Tahun Ajaran Baru

## Satu menu, satu endpoint

Wizard "Tahun Ajaran Baru" adalah satu-satunya cara menjalankan kenaikan kelas.
Menu dan route `Kenaikan Kelas` lama sudah dilebur ke sini; `GET /kenaikan-kelas`
hanya redirect, dan `POST kenaikan-kelas/proses` **sudah dihapus** — jangan
dibuatkan ulang.

Kenapa dilebur: endpoint lama punya override per siswa tapi tidak menyalin rombel
dan tidak pernah memanggil `kelasTujuanTanpaRombel()`. Tombol "Atur Kenaikan
Detail" menuju ke sana membuka jalan memindahkan siswa ke kelas tanpa rombel —
persis kebalikan dari urutan wajib di bawah. Menggabungkan tidak cuma merapikan
menu, tapi menutup jalur itu secara struktural.

Kalau nanti butuh menjalankan kenaikan dari tempat lain, tambahkan ACTION-nya — jangan
buat endpoint yang memanggil `ProsesKenaikanAction` tanpa `SalinRombelAction`.

## Permission

Gerbang hanya `tahun-ajaran-baru.view` dan `tahun-ajaran-baru.execute`.
Permission `kenaikan-kelas.view/execute` sengaja **dibiarkan ada** di tabel dan
tetap diberikan ke admin supaya role yang sudah dikonfigurasi di produksi tidak
berubah — jangan pakai lagi sebagai gerbang, jangan hapus tanpa migration.

## Logika ada di Action, bukan controller

`SalinRombelAction` (salin rombel + wali + penugasan) dan
`ProsesKenaikanAction` (naik / tinggal / lulus) adalah satu-satunya
implementasi. `RombelController::prosesSalin()` dan
`TahunAjaranBaruController` semuanya memanggilnya.

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