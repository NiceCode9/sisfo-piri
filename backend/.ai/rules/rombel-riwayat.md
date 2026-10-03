---
paths:
  - 'app/Models/Rombel.php'
  - 'database/migrations/*riwayat_kelas*'
  - 'database/migrations/*absensis*'
---

# Rombel & Riwayat Kelas

## Keanggotaan rombel: pilih anggotaIds() atau anggotaIdsAktif()

`RiwayatKelas` adalah sumber keanggotaan rombel **dan** sumber rekonstruksi
histori. Karena barisnya sengaja tidak dihapus saat siswa pindah, satu siswa
bisa punya beberapa baris untuk satu tahun ajaran.

| Kebutuhan | Pakai |
|---|---|
| Menyusun **rekap historis** (kehadiran, nilai tugas, nilai ujian) — siswa yang sudah pindah harus tetap terlihat | `anggotaIds()` (semua baris, tanpa filter status) |
| **Pencatatan operasional** — grid absensi, validasi batch, gate scan QR, otorisasi unduh berkas, notifikasi belum hadir | `anggotaIdsAktif()` (hanya baris `aktif`) |

Salah pilih berakibat salah di kedua arah: memakai `anggotaIds()` untuk
pencatatan membuat siswa yang sudah pindah masih bisa dicatat hadir di kelas
lama; memakai `anggotaIdsAktif()` untuk rekap membuat catatan yang sudah
tercatat hilang dari laporan.

## Memindahkan siswa menutup baris riwayat lama

Saat kelas atau tahun ajaran berubah, baris riwayat lama di-set `pindah` dan
baris baru dibuat `aktif` (`SiswaController::update()`). Melewatkan
penutupan membuat siswa terhitung anggota dua rombel sekaligus pada
pemanggilan operasional. Satu siswa hanya boleh punya satu baris `aktif` per
tahun ajaran.

## Hapus rombel: cek seluruh histori terlampir

`RombelController::destroy()` memakai `Rombel::historiAttached()`. Semua
relasi itu (`pengampus`, `absensis`, `materis`, `tugas`, `exams`) memakai
`cascadeOnDelete`, jadi "hapus rombel" berarti menghapus kehadiran, materi,
tugas, dan nilai ujian kelas itu secara permanen. Menambah tabel history
baru berarti menambah relasi ke `historiAttached()` — kalau tidak, data itu
bisa terhapus tanpa dicek.

## Keunikan absensi sudah dilonggarkan ke (siswa, rombel, tanggal)

Dulu `unique(siswa_id, tanggal)`, yang secara struktural menolak satu siswa
punya dua konteks rombel pada tanggal sama — bertentangan dengan penyimpanan
histori saat pindah kelas di tengah tahun.

Konsekuensi yang harus dijaga setiap kali menulis query absensi:
- `firstOrNew`/`updateOrCreate` **harus** menyertakan `rombel_id` pada kunci.
  Tanpa itu, pencatatan untuk kelas kedua menimpa baris kelas pertama.
- Query yang tidak memfilter `rombel_id` (rekap bulanan orang tua/siswa)
  harus memakai `COUNT(DISTINCT tanggal)`, bukan `COUNT(*)`, supaya satu
  tanggal tidak terhitung dua kali.

## Memperbaiki baris riwayat lewat UI

`RiwayatKelasController` (permission `riwayat-kelas.manage`, admin saja) adalah
satu-satunya tempat yang boleh menghapus data histori. `destroy()` memeriksa
konfirmasi eksplisit dan menyebutkan rekap apa yang akan hilang dari tampilan.