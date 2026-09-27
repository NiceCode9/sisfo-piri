---
paths:
  - app/Observers/SiswaObserver.php
---

# Observers

## Username akun orang-tua = nomor HP, password = NISN
Username akun wali dibuat dari nomor HP wali (hanya digit, hasil normalisasi), password tetap NISN anak. Bila nomor belum terisi, jatuh ke username cadangan `ortu-{nisn}` dan otomatis di-rename ke nomor begitu `siswas.no_hp_orang_tua` diisi (event `updated` preench observer). Kalau username nomor bentrok dengan akun non-wali, pakai username cadangan agar tidak mengambil alih akun. Kakak-beradik dengan no WA sama berbagi satu akun. Jangan hardcode `ortu-` di tempat lain — panggil `SiswaObserver::usernameUntukWali()`.
