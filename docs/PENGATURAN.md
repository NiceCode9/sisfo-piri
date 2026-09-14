# Panduan Pengaturan

> Kelola di `admin/pengaturans` (hanya super-admin). Seeder `PengaturanSeeder`.

## Daftar Kunci

| Kunci | Tipe | Default | Pengaruh ke | Cara Tentukan |
|---|---|---|---|---|
| `batas_terlambat` | `HH:MM` | `07:00` | `AbsensiController@storeBatch` & `storeScan`: lewat jam → `terlambat` | Jam masuk sekolah |
| `jam_cek_belum_hadir` | `HH:MM` | `08:00` | `CekBelumHadir` self-gating tiap 5 menit | Jam setelah absensi dianggap perlu ingatkan ortu |
| `whatsapp_gateway_url` | URL | kosong | `KirimNotifikasiWhatsapp` (kosong=log-only) | `http://127.0.0.1:3001/kirim` di VPS |
| `cek_belum_hadir_terakhir` | `Y-m-d` | null | Anti ganda harian | Read-only; **Re-trigger** = kosongkan via tombol |
| `semester_aktif` | `ganjil/genap` | `ganjil` | Filter default rekap & ortu | Kalender akademik berjalan |
| `batas_upload_mb` | int 1–50 | `2` | Validasi `foto`, materi, tugas | Kapasitas server |
| `maintenance_mode` | bool | `0` | `CheckMaintenance` blokir semua kecuali super-admin (503) | ON saat deploy/maintenance |
| `maintenance_pesan` | text | null | Tampil di `errors.maintenance` bila ON | Informasi untuk user |
| `rekap_default_periode` | enum | `bulan` | Default filter rekap | Preferensi sekolah |
| `notifikasi_ortu_aktif` | bool | `1` | Global on/off WA ortu (cek sebelum dispatch) | Kebijakan notifikasi |

## Re-trigger

Tombol **Re-trigger Cek Belum Hadir** di halaman pengaturan → `POST pengaturans/reset` → `nilai=null` → scheduler akan jalan lagi hari ini pada jam yang diatur.

## Maintenance

ON → semua request (kecuali super-admin) 503. JSON → `{"message": "..."}`, web → view `errors.maintenance`.
