# Ringkasan Perubahan CBT (Update Keamanan)

Tanggal: 2026-10-24

## Perubahan sebelumnya (sudah diterapkan)
- Resume aman saat browser ditutup: `/exam/active` mengembalikan sesi selesai/expired/disqualified, frontend mengarah ke `/finished`.
- Sinkronisasi waktu server-client untuk timer (offset dari `server_time`).
- Pengacakan soal/opsi stabil per sesi (seed `exam_session_id`).
- Cache invalidation pada hapus ujian + toleransi waktu 5 detik untuk gambar soal.
- Cooldown 10 detik untuk deteksi devtools.

## Perubahan baru (keamanan)
### Auth token via HttpOnly cookie
- Login men-set cookie `cbt_auth_token` (HttpOnly, Secure di production, SameSite=Lax).
- Middleware `cbt.cookie` mengubah cookie menjadi Authorization Bearer sebelum `auth:sanctum`.
- Frontend tidak lagi menyimpan token di `localStorage`; request memakai `withCredentials`.
- Logout menghapus cookie.

### CSP dan hardening frontend
- Meta CSP ditambahkan di `cbt/index.html`.
- Header keamanan backend: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`.

### Monitoring heartbeat stale
- `ExamMonitoringController::data` menyertakan `is_stale` per sesi dan jumlah stale pada summary.

### Idempotency key untuk pelanggaran
- Frontend mengirim `client_request_id` pada `/exam/violation`.
- Backend menolak duplikat berdasarkan cache key per sesi + request id, sehingga retry tidak menambah `violation_count` dua kali.

### Retry autosave
- `useAutosaveAnswer` mencoba ulang hingga 2 kali sebelum menandai status `error`.

## Catatan
- Perubahan ini menjaga kompatibilitas dengan test backend yang memakai Bearer token.
- Beberapa item (SEB penuh, HSTS, rate limit lanjutan) tetap bergantung pada deployment/infra.

## Usulan Lanjutan (Belum Diterapkan)
- Perpendek interval heartbeat dari 20 detik menjadi 10 detik, dengan menaikkan rate limit dari 6/menit menjadi minimal 12/menit. Dampak: timer lebih akurat dan deteksi stale lebih cepat, tetapi beban server dan queue meningkat.
- Tampilkan peringatan jika selisih jam lokal vs server terlalu besar (> 30 detik). Peringatan ditampilkan setelah login, sebelum/awal submit token (halaman TokenEntry atau awal ruang ujian), agar siswa bisa memperbaiki jam perangkat sebelum ujian dimulai.
