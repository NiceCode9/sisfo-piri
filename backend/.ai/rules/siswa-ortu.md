---
paths:
  - 'app/Http/Controllers/{Siswa,Ortu}/**'
---

# Siswa Ortu

## Centralize student rombel resolution and re-check is_aktif on detail routes
Resolusi rombel siswa harus lewat `Rombel::untukSiswa($siswa)` (mengembalikan array id). Jangan campur dengan `first()?->id` — kedua pola pernah hidup berdampingan untuk hal yang sama, sehingga siswa dengan lebih dari satu rombel bisa menjangkau materi yang bukan miliknya. Terpisah dari itu, `is_aktif` wajib diperiksa pada `show`/`kumpul`, bukan hanya di `index`: penyaringan index bisa dilewati dengan menebak URL.
