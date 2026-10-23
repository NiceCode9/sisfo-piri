# SDD ledger — plan: docs/superpowers/plans/2026-10-23-absensi-izin-sakit-jam-pertama.md

Pre-flight scan:
- Task 2 ← Task 1 (routes `guru.absensi-qr.izin`, `guru.absensi-qr.izin.bukti`): nama Produce/Consume konsisten. — bersih
- Ruling: Task 1/2 test memanggil `rombelQrMandiri()`/`siswaQrMandiri()` milik file lain — test tunggal `vendor/bin/pest <file>` akan gagal karena helper tak terdefinisi. Solusi: file test baru mendefinisikan helper sendiri yang diguard (`rombelIzin`, `siswaIzin`, `guruKartuIzin`) — biaya salah: fixture melenceng, kecil.
- Ruling: implementasi langsung di `develop` (tanpa worktree) — preseden sesi ini (Fase A5 dieksekusi di develop, user bekerja di direktori ini) — biaya salah: cabang tercampur, ditinjau reviewer akhir.
- Task 3 memakai route/filter dari Task 1–2; tanpa konflik nama lain.

Task 1: complete (tests: vendor/bin/pest tests/Feature/AbsensiIzinGuruTest.php → 9/9 PASS 40 asersi; RED terverifikasi: 9 error "Route not defined").
