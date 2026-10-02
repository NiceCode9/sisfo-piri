---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Keep student/parent uploads off the public disk
Unggahan dan lampiran milik siswa/ortu tidak boleh ditulis ke disk `public`: URL `/storage/...` bisa diambil tanpa login. Materi memakai `Materi::DISK`, tugas siswa `PengumpulanTugas::DISK` (keduanya 'berkas'), dan disajikan lewat route yang memeriksa hak akses — `Elearning\BerkasController` untuk E-Learning, `DokumenController` untuk PPDB. Mengganti tautan view ke `Storage::disk('public')->url(...)` adalah regresi keamanan, bukan sekadar perubahan tampilan.
