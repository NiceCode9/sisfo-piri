# Panduan Pengaturan

> Kelola di `admin/pengaturans` (hanya super-admin). Seeder `PengaturanSeeder`.

## Daftar Kunci

| Kunci | Tipe | Default | Pengaruh ke | Cara Tentukan |
|---|---|---|---|---|
| `batas_terlambat` | `HH:MM` | `07:00` | `AbsensiController@storeScan`: lewat jam → `terlambat` | Jam masuk sekolah |
| `jam_cek_belum_hadir` | `HH:MM` | `08:00` | `CekBelumHadir` self-gating tiap 5 menit | Jam setelah absensi dianggap perlu ingatkan ortu |
| `cek_belum_hadir_terakhir` | `Y-m-d` | null | Anti ganda harian | Read-only; **Re-trigger** = kosongkan via tombol |
| `semester_ganjil_mulai` | `MM-DD` | `07-01` | `rentangPeriode('ganjil')` | Awal semester ganjil kalender sekolah |
| `semester_ganjil_selesai` | `MM-DD` | `12-31` |  | Akhir ganjil |
| `semester_genap_mulai` | `MM-DD` | `01-01` | `rentangPeriode('genap')` | Awal genap |
| `semester_genap_selesai` | `MM-DD` | `06-30` |  | Akhir genap |
| `maintenance_mode` | bool | `0` | `CheckMaintenance` blokir semua kecuali super-admin (503) | ON saat deploy/maintenance |
| `maintenance_pesan` | text | null | Tampil di `errors.maintenance` bila ON | Informasi untuk user |

## Hapus (sebelumnya ada, kini via env/hardcode)

- `whatsapp_gateway_url` → `.env:WHATSAPP_URL`
- `notifikasi_ortu_aktif`, `batas_upload_mb`, `rekap_default_periode`, `semester_aktif` → hardcode/hapus

## Re-trigger

Tombol **Re-trigger Cek Belum Hadir** di halaman pengaturan → `POST pengaturans/reset` → `nilai=null` → scheduler akan jalan lagi hari ini pada jam yang diatur.

## Maintenance

ON → semua request (kecuali super-admin) 503. JSON → `{"message": "..."}`, web → view `errors.maintenance`.
