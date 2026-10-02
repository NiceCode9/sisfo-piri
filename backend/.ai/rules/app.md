---
paths:
  - 'app/**/*.php'
---

# App

## Status pendaftaran hanya dari App\Support\StatusPendaftaran
Jangan pernah memutuskan "pendaftaran dibuka?" di luar `App\Support\StatusPendaftaran`. Badge hero, peringatan form, `App\Rules\GelombangTerbuka`, gate `store()`, badge kartu gelombang, dan timeline publik semuanya harus baca objek itu. Statusnya dihitung **hanya** dari Gelombang + tahap `pendaftaran` di tabel `gelombang_tahapan` — bukan dari JadwalPpdb, yang kini hanya kalender internal sekolah. `JadwalPpdb::jendelaPendaftaran()` sudah dihapus; kalau butuh gate, panggil `StatusPendaftaran::tentukan()` atau `::untuk($gelombang)`.
