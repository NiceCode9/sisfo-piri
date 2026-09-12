# Roadmap Modul Absensi

> Sumber kebutuhan: `docs/PRD.md` §6.1 (ABS-01–ABS-13).
> Aturan kerja: **satu fase satu eksekusi — jangan lanjut fase berikutnya sebelum konfirmasi user.**

## Keputusan yang sudah disetujui

| # | Keputusan |
|---|---|
| 1 | Absensi **harian per rombel**, bukan per mapel/jam |
| 2 | Status: `hadir, sakit, izin, alpa, terlambat` (+ `jam_datang`) |
| 3 | Scan QR via **device sekolah** (petugas memindai); input manual tanpa scan adalah jalur utama guru piket |
| 4 | Akun ortu: role `orang-tua` (sudah ada), username `ortu-{NISN}`, password awal = NISN anak, auto-generate via observer (CRUD, import, seeder) |
| 5 | WA: bangun queue + log di Laravel sekarang, konektor gateway `whatsapp-web.js` menyusul |
| 6 | Batas terlambat default **07:00** via tabel `pengaturans` (bisa diubah admin) |
| 7 | Eksekusi per fase, clustering: Absensi → E-Learning → CBT |

## Fase

### Fase A1 — Fondasi data + akun ortu [SELESAI]
- [x] Migrasi: `absensis` (unique `siswa+tanggal`), `siswas.qr_token`, `wali_murids`, `pengaturans`, `notifikasi_logs`
- [x] Model + relasi (`Absensi`, `WaliMurid`, `Pengaturan`, `NotifikasiLog`)
- [x] `SiswaObserver` auto akun ortu + command backfill `siswa:generate-orangtua`
- [x] Permission `absensis.*` + role `guru-piket` + hak `orang-tua` (menu menyusul Fase A2 bersama route)
- [x] Package `simplesoftwareio/simple-qrcode`

### Fase A2 — Input [SELESAI]
- [x] Route + controller + menu Absensi (`absensis`, `absensis/batch`, `absensis/scan`, `siswas/{siswa}/qr`)
- [x] Grid manual per rombel (upsert sekaligus, filter rombel tahun aktif + tanggal default hari ini)
- [x] Scan QR kamera (AJAX kontinu, CDN `html5-qrcode`, fallback token manual)
- [x] Aturan `terlambat` dari `pengaturans.batas_terlambat`
- [x] QR pakai `qr_token` acak (kartu + detail siswa + regenerate); NISN tidak lagi dipakai sebagai isi QR

### Fase A3 — Rekap + pantauan [SELESAI]
- [x] Helper `rentangPeriode()` (minggu/bulan/ganjil/genap/tahun; konvensi Jul–Des/Jan–Jun, siap migrasi ke entitas)
- [x] Rekap admin (`absensis/rekap`): matriks harian ≤45 hari, ringkas di atasnya, total + % hadir
- [x] Ekspor Excel + PDF (library existing, tanpa dependensi baru)
- [x] Scope wali (terkunci ampuan) + permission `absensis.view` untuk role `guru`
- [x] Dashboard ortu (`ortu.dashboard` + `ortu.anak`) + redirect admin root

### Fase A4 — Notifikasi WA parsial (belum dikerjakan)
- [ ] Queue job + `notifikasi_logs` + event (alpa, belum hadir lewat jam X via scheduler command)
- [ ] Konektor gateway menyusul (config webhook URL, mode log-only sementara)

## Catatan teknis

- Keanggotaan rombel diturunkan dari `RiwayatKelas` — absensi menempel `rombel_id` langsung agar histori utuh.
- `orang-tua`: satu akun boleh menaungi kakak-beradik (deduplikasi via no WA).
- Keuangan (SPP) tetap out-of-scope; pembayaran PPDB existing tidak diperluas.
