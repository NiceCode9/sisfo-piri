# Roadmap Modul Absensi

> Sumber kebutuhan: `docs/PRD.md` §6.1 (ABS-01–ABS-13).
> Aturan kerja: **satu fase satu eksekusi — jangan lanjut fase berikutnya sebelum konfirmasi user.**

## Keputusan yang sudah disetujui

| # | Keputusan |
|---|---|
| 1 | Absensi **harian per rombel**, bukan per mapel/jam |
| 2 | Status: `hadir, sakit, izin, alpa, terlambat` (+ `jam_datang`) |
| 3 | Scan QR via **device sekolah** (petugas memindai); input manual tanpa scan adalah jalur utama guru piket |
| 4 | Akun ortu: role `orang-tua` (sudah ada), **username = nomor HP wali** (hanya digit; cadangan `ortu-{NISN}` bila nomor belum terisi), password awal = NISN anak, auto-generate via observer (CRUD, import, seeder) |
| 5 | WA: bangun queue + log di Laravel sekarang, konektor gateway `whatsapp-web.js` menyusul |
| 6 | Batas terlambat default **07:00** via tabel `pengaturans` (bisa diubah admin) |
| 7 | Eksekusi per fase, clustering: Absensi → E-Learning → CBT |
| 8 | **Perubahan arah (okt 2026)**: QR absen mandiri — siswa yang scan, QR digenerate harian di akun setiap guru (Fase A5) |
| 9 | Jendela absen: buka 07:00, telat 07:15, tutup 07:45; semuanya jadi pengaturan admin |
| 10 | QR guru berganti tiap 10 menit (QR yang difoto cepat mati); interval ikut menjadi pengaturan |
| 11 | Guru pemilik QR tercatat sebagai `dicatat_oleh` di baris absensi siswa |
| 12 | Jalur lama (scan kamera kartu piket + QR kartu siswa) dihapus; grid manual tetap jalur cadangan |

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

### Fase A4 — Notifikasi WA parsial [SELESAI]
- [x] Job `KirimNotifikasiWhatsapp` (retry 3×, template disetujui, mode log-only tanpa URL gateway)
- [x] Hook alpa di `storeBatch` (satu log per no WA, lewati tanpa nomor)
- [x] Command `absensi:cek-belum-hadir` self-gating (jam admin + sekali sehari + `--tanggal` manual)
- [x] Jadwal `everyFiveMinutes` + pengaturan `jam_cek_belum_hadir`, `cek_belum_hadir_terakhir`, `whatsapp_gateway_url`
- [ ] Konektor gateway `whatsapp-web.js` (service terpisah — di luar repo ini)

### Fase A5 — QR absen mandiri [RENCANA — menunggu persetujuan user]

> Desain lengkap: `docs/superpowers/specs/2026-10-21-absensi-qr-mandiri-design.md`.
> Jangan mulai kode sebelum persetujuan.

- [ ] Migrasi: kunci `jam_buka_absen`, `jam_tutup_absen`, `menit_ganti_qr`; `batas_terlambat` digeser 07:15; drop `siswas.qr_token`
- [ ] Ekstraksi service pencatatan absensi (helper di `AbsensiController`, param `dicatat_oleh` eksplisit)
- [ ] Token harian guru: `hash_hmac` per slot `menit_ganti_qr`, tanpa tabel; halaman `guru/absensi-qr` + endpoint refresh JSON
- [ ] Siswa: halaman absen mandiri (kamera `html5-qrcode`) + POST scan dengan jendela jam, status hadir/terlambat otomatis, idempoten
- [ ] Hapus jalur lama: route+view scan piket, tombol "Scan QR" di grid, `regenerateQr`, blok QR kartu cetak
- [ ] Pengaturan admin: 3 kunci baru + validasi relasi `jam_buka < batas < jam_tutup`
- [ ] Test `AbsensiQrMandiriTest` (batas menit, token kadaluarsa, tabrakan manual, arrival-first, tanpa rombel) + pembaharuan test lama

## Catatan teknis

- Keanggotaan rombel diturunkan dari `RiwayatKelas` — absensi menempel `rombel_id` langsung agar histori utuh.
- `orang-tua`: satu akun boleh menaungi kakak-beradik (deduplikasi via no WA).
- Keuangan (SPP) tetap out-of-scope; pembayaran PPDB existing tidak diperluas.
