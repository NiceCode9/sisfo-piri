---
paths:
  - 'app/Http/Controllers/{Admin,Siswa,Ortu}/**'
---

# Admin Siswa Ortu

## Scope E-Learning by rombel via Rombel::terjangkauUser, never fail open
Guru boleh sentuh materi/tugas hanya di rombel yang ia wali (rombels.wali_guru_id) ATAU ia pengampu mapel (pengampus). Satu-satunya sumber kebenaran: `Rombel::terjangkauUser()` untuk query dan `Rombel::terjangkauOleh()` untuk satu baris. Ketinggalan penugasan harus menghasilkan NOL rombel, bukan semua rombel — versi lama jatuh ke "kalau kosong, semua" sehingga guru tanpa kelas bisa membaca kelas milik guru lain. Bypass hanya lewat `Rombel::ROLE_UNIVERSAL`. Jangan tulis ulang daftar rombel manual; view/ekspor pun wajib ikut memakai scope ini.
