---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Keep uploads that are not yet public off the public disk
Berkas yang isinya belum boleh dipublikasikan tidak boleh ditulis ke disk `public`: URL `/storage/...` bisa diambil tanpa login. Dasarnya bukan siapa yang mengunggah, melainkan **kapan isinya boleh terlihat** — gambar soal diunggah guru, bukan siswa, tapi isinya justru bank soal ujian yang belum boleh bocor.

Yang memakai disk privat 'berkas': `Materi::DISK`, `PengumpulanTugas::DISK` (keduanya 'berkas'), `ExamQuestion::DISK`. Semuanya disajikan lewat route yang memeriksa hak akses — `Elearning\BerkasController`, `DokumenController` untuk PPDB, dan dua route CBT: `api.cbt.exam.question-image` (siswa dengan sesi ongoing) serta `admin.cbt.questions.image` (guru, lewat `ExamPolicy`).

Nama file berhash 40 karakter tetap hanya *obscurity*, bukan kontrol akses: URL bocor lewat `Referer`, cache browser, atau log server.

Satu aturan yang berlaku dua arah juga penting: **satu aturan jangan punya dua implementasi**. Kalau dua endpoint menguji "apakah pengguna berhak atas sumber daya ini", keduanya harus memanggil helper yang sama — `ExamSessionController::ownsOngoingExam()` dipakai bersama `resolveOngoingSession()` justru supaya gambar tidak bisa lolos lewat jalur longgar sementara menjawab soal lewat jalur ketat.

## Form bersama lebih baik daripada form kembar
Field yang sama di dua halaman harus berasal dari satu partial. Form tambah soal CBT pernah berbeda: form ujian punya opsi+kunci, form bank tidak — lalu validasi yang sama menolak semua soal pilihan di bank, dan tidak ada kolom untuk memperbaikinya. Test yang memuat kedua halaman dan.assert partial yang sama menahan jenis regresi ini.

## Input yang disembunyikan harus di-disable
Menyembunyikan `<input>` tidak mencegah pengiriman. Field yang tidak aktif harus `disabled`, kalau tidak nilainya tetap ikut terkirim — `ConvertEmptyStringsToNull` mengubah string kosong jadi `null`, lalu aturan `required_with:*` menolaknya.