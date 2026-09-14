# Product Requirements Document (PRD)
# Sistem Manajemen Sekolah

**Versi:** 1.1
**Tanggal:** 8 September 2026
**Status:** Final (draft awal, siap untuk direview lebih lanjut oleh tim development)

---

## 1. Ringkasan Proyek

Sistem Manajemen Sekolah adalah aplikasi berbasis web yang dikembangkan untuk mendigitalisasi tiga proses inti operasional sekolah: pencatatan kehadiran siswa (Absensi), pengelolaan pembelajaran daring (E-Learning), dan pelaksanaan ujian berbasis komputer (Computer Based Test/CBT). Sistem ini dirancang untuk digunakan oleh guru, siswa, wali kelas, wali murid, dan pihak manajemen sekolah.

---

## 2. Latar Belakang & Tujuan

### 2.1 Latar Belakang
Proses absensi manual, distribusi materi pembelajaran secara konvensional, dan pelaksanaan ujian berbasis kertas menyulitkan pemantauan kehadiran, distribusi tugas, dan penilaian secara efisien. Sekolah membutuhkan satu sistem terintegrasi yang dapat menangani ketiga proses tersebut.

### 2.2 Tujuan
- Mempercepat dan mengotomasi pencatatan kehadiran siswa melalui QR code maupun input manual.
- Memberikan visibilitas kehadiran secara real-time kepada wali kelas, sekolah, dan orang tua/wali murid.
- Menyediakan platform pengelolaan materi dan tugas pembelajaran daring.
- Menyediakan platform ujian/kuis online yang efisien dengan koreksi dan rekap nilai otomatis untuk soal pilihan ganda.

---

## 3. Ruang Lingkup

### 3.1 Dalam Lingkup (In Scope)
- Modul Absensi Siswa (QR + manual, rekap, laporan, notifikasi WhatsApp)
- Modul E-Learning (materi, tugas, penilaian tugas harian)
- Modul CBT (bank soal, ujian dengan timer, koreksi otomatis PG, rekap nilai)
- WhatsApp Gateway custom menggunakan `whatsapp-web.js`
- REST API Laravel sebagai backend terpusat untuk seluruh modul, termasuk backend CBT (soal & hasil) yang dikonsumsi oleh frontend React

### 3.2 Di Luar Lingkup (Out of Scope) — perlu konfirmasi lebih lanjut
- Modul keuangan/pembayaran (SPP, dsb.)
- Modul kepegawaian/payroll guru
- Aplikasi mobile native (Android/iOS)
- Rapor/buku induk siswa (bila dibutuhkan, disarankan menjadi modul terpisah)
- Manajemen jadwal pelajaran otomatis (bila belum tersedia)

---

## 4. Pengguna & Peran (User Roles)

| Role | Deskripsi Akses Utama |
|---|---|
| **Admin/Superadmin** | Kelola user, master data (siswa, guru, kelas, mapel), konfigurasi sistem |
| **Guru Piket** | Input absensi manual, memantau kehadiran harian |
| **Guru Mapel** | Kelola materi ajar, tugas, penilaian tugas, buat soal CBT (jika role ini juga menyusun soal) |
| **Wali Kelas** | Melihat rekap & laporan kehadiran kelas yang diampu |
| **Siswa** | Scan QR untuk absensi, akses materi, mengumpulkan tugas, mengerjakan CBT |
| **Wali Murid** | Memiliki akun/login untuk memantau kehadiran, materi, tugas, dan nilai anak; juga menerima notifikasi WhatsApp terkait kehadiran |
| **Pihak Sekolah (Kepala Sekolah/TU)** | Melihat laporan kehadiran & rekap nilai tingkat sekolah |

*Catatan: peran di atas adalah asumsi awal berdasarkan deskripsi modul — perlu dikonfirmasi apakah ada peran tambahan seperti "Kaprodi" atau "Waka Kurikulum".*

---

## 5. Tech Stack

| Komponen | Teknologi |
|---|---|
| Backend (semua modul) | Laravel — REST API |
| Frontend Absensi & E-Learning | Blade (Laravel) |
| Frontend CBT (pengerjaan ujian) | React, mengonsumsi REST API Laravel |
| Manajemen soal CBT (bank soal, input soal) | Laravel (di sisi admin/guru) |
| QR Code Absensi | Generate QR per siswa, scan via web (kamera device) |
| WhatsApp Gateway | Custom, dibangun sendiri menggunakan `whatsapp-web.js` |

---

## 6. Kebutuhan Fungsional (Functional Requirements)

### 6.1 Modul Absensi Siswa

| ID | Kebutuhan |
|---|---|
| ABS-01 | Sistem dapat men-generate QR code unik untuk setiap siswa |
| ABS-02 | Siswa/petugas dapat melakukan absensi dengan scan QR code |
| ABS-03 | Guru piket dapat melakukan input absensi secara manual (untuk kasus QR tidak berfungsi/siswa tanpa akses) |
| ABS-04 | Sistem mencatat status kehadiran: Hadir, Sakit, Izin, Alpa (dan Terlambat, jika diperlukan) |
| ABS-05 | Sistem dapat menghasilkan rekap kehadiran mingguan |
| ABS-06 | Sistem dapat menghasilkan rekap kehadiran bulanan |
| ABS-07 | Sistem dapat menghasilkan rekap kehadiran semester |
| ABS-08 | Wali kelas dapat mengakses laporan kehadiran siswa di kelasnya |
| ABS-09 | Pihak sekolah dapat mengakses laporan kehadiran tingkat sekolah |
| ABS-10 | Sistem mengirim notifikasi otomatis ke WhatsApp wali murid saat terjadi event tertentu (mis. siswa belum absen di atas jam X, siswa tercatat alpa) |
| ABS-11 | Notifikasi WhatsApp dikirim melalui gateway custom (whatsapp-web.js) yang terintegrasi dengan sistem |
| ABS-12 | Laporan kehadiran dapat diekspor dalam format PDF dan Excel |
| ABS-13 | Wali murid memiliki akun/login untuk memantau kehadiran anak secara langsung di sistem (selain menerima notifikasi WhatsApp) |

### 6.2 Modul E-Learning

| ID | Kebutuhan |
|---|---|
| EL-01 | Guru dapat mengunggah materi pembelajaran dalam bentuk dokumen |
| EL-02 | Guru dapat mengunggah materi pembelajaran dalam bentuk video (upload atau link eksternal, mis. YouTube) |
| EL-03 | Guru dapat membagikan materi dalam bentuk link eksternal |
| EL-04 | Materi dikelompokkan berdasarkan mata pelajaran/kelas |
| EL-05 | Guru dapat membuat dan memberikan tugas online dengan tenggat waktu (deadline) |
| EL-06 | Siswa dapat mengumpulkan tugas secara online (upload file/isian) |
| EL-07 | Guru dapat memberikan nilai atas tugas yang dikumpulkan (penilaian tugas harian) |
| EL-08 | Siswa dapat melihat status pengumpulan tugas dan nilai yang diberikan |
| EL-09 | Sistem mencatat riwayat/rekap nilai tugas harian per siswa |
| EL-10 | Wali murid dapat memantau materi, status tugas, dan nilai anak melalui akun mereka |

### 6.3 Modul CBT (Computer Based Test)

| ID | Kebutuhan |
|---|---|
| CBT-01 | Guru mapel dapat membuat dan mengelola bank soal melalui panel Laravel (penyusunan soal menjadi tanggung jawab guru mapel, tidak ada tim penyusun soal terpisah) |
| CBT-02 | Bank soal mendukung tipe soal Pilihan Ganda |
| CBT-03 | Bank soal mendukung tipe soal Uraian (esai) |
| CBT-04 | Guru/admin dapat menyusun paket ujian/kuis dari bank soal |
| CBT-05 | Sistem menyediakan REST API (Laravel) untuk menyajikan soal ke aplikasi React |
| CBT-06 | Siswa mengerjakan ujian/kuis melalui aplikasi frontend React |
| CBT-07 | Ujian/kuis memiliki pembatasan waktu (timer) yang berjalan di sisi klien dan divalidasi di sisi server |
| CBT-08 | Sistem melakukan auto-submit saat waktu ujian habis |
| CBT-09 | Sistem melakukan koreksi otomatis untuk soal Pilihan Ganda |
| CBT-10 | Soal Uraian dikoreksi manual oleh guru melalui panel Laravel |
| CBT-11 | Sistem menghasilkan rekap nilai otomatis setelah ujian selesai (untuk komponen PG; nilai final setelah komponen Uraian dikoreksi) |
| CBT-12 | Sistem mencatat log pengerjaan (waktu mulai, waktu selesai, status submit) untuk keperluan audit |
| CBT-13 | Sistem menyediakan mekanisme proctoring/anti-kecurangan dasar, seperti deteksi perpindahan tab/window (visibility change) dan pencatatan event tersebut ke log ujian |
| CBT-14 | Sistem mampu menangani ± 500 siswa mengerjakan ujian secara bersamaan dalam satu sesi tanpa penurunan performa signifikan |

---

## 7. Kebutuhan Non-Fungsional

| Kategori | Kebutuhan |
|---|---|
| **Keamanan** | Autentikasi berbasis role, proteksi endpoint REST API (token/Sanctum/Passport), enkripsi data sensitif |
| **Performa** | API CBT harus tetap responsif saat diakses ± 500 siswa secara bersamaan dalam satu sesi ujian (concurrent load) |
| **Reliabilitas** | Timer ujian harus tetap konsisten meski terjadi refresh/koneksi terputus sementara (server-side time tracking) |
| **Skalabilitas** | WhatsApp gateway harus mampu menangani antrian pengiriman notifikasi dalam volume besar tanpa terblokir/banned oleh WhatsApp |
| **Auditability** | Semua aksi kritikal (absensi, submit ujian, input nilai) tercatat dengan timestamp dan user pelaku |
| **Usability** | Antarmuka guru piket untuk absensi manual harus cepat digunakan (minim klik) |

---

## 8. Integrasi Antar Komponen

- **Laravel REST API** bertindak sebagai backend tunggal yang diakses oleh:
  - Frontend Absensi & E-Learning
  - Frontend React untuk pengerjaan CBT
  - WhatsApp Gateway (whatsapp-web.js) sebagai service terpisah yang berkomunikasi dengan Laravel (mis. via webhook/queue) untuk mengirim notifikasi
- **Alur notifikasi WhatsApp** (usulan): Event absensi tercatat → Laravel menambahkan job ke queue → Worker memproses dan memanggil service whatsapp-web.js → Pesan terkirim ke wali murid.
- **Alur CBT**: Admin/guru membuat soal di Laravel → React mengambil data soal via REST API saat ujian dimulai → Jawaban siswa dikirim balik ke Laravel via API → Laravel melakukan koreksi otomatis (PG) dan menyimpan hasil.

---

## 9. Gambaran Awal Struktur Data (High-Level)

*Ini adalah gambaran awal entitas utama, bukan skema final.*

- **Siswa** (data induk, kode QR unik)
- **Guru**
- **Kelas**
- **Mapel**
- **Absensi** (siswa, tanggal, status, waktu, metode: QR/manual, dicatat oleh)
- **WaliMurid** (relasi ke siswa, nomor WhatsApp)
- **Materi** (mapel, kelas, tipe: dokumen/video/link, file/url)
- **Tugas** (materi/mapel, deadline, deskripsi)
- **PengumpulanTugas** (tugas, siswa, file/isian, nilai)
- **BankSoal** (mapel, tipe: PG/Uraian, konten, kunci jawaban/bobot)
- **PaketUjian** (kumpulan soal, durasi, jadwal)
- **HasilUjian** (siswa, paket ujian, jawaban, nilai PG otomatis, nilai uraian manual, waktu submit)

---

## 10. Asumsi & Batasan

- WhatsApp Gateway berbasis `whatsapp-web.js` bergantung pada sesi WhatsApp Web aktif (nomor WhatsApp khusus sekolah) — risiko downtime bila sesi terputus perlu mekanisme reconnect/monitoring.
- Frontend CBT (React) dan frontend modul lain diasumsikan terpisah secara teknis namun terhubung ke satu backend Laravel yang sama.
- Infrastruktur hosting, kapasitas server, dan jumlah pengguna simultan belum ditentukan — perlu dikonfirmasi untuk estimasi kapasitas.
- Kebijakan retensi data (berapa lama data absensi/nilai disimpan) belum ditentukan.

---

## 11. Catatan Klarifikasi (Sudah Terkonfirmasi)

Seluruh pertanyaan terbuka pada draft sebelumnya sudah terjawab:
- Frontend Absensi & E-Learning menggunakan Blade
- Wali murid memiliki akun/login sendiri
- Fitur ekspor laporan PDF/Excel dibutuhkan
- Kapasitas CBT ± 500 siswa mengerjakan ujian bersamaan
- Proctoring/anti-kecurangan dasar dibutuhkan (deteksi pindah tab)
- Penyusunan soal hanya oleh guru mapel
- **Sistem ini berdiri sendiri (standalone)**, tidak terintegrasi dengan sistem sekolah lain yang sudah ada

---

## 12. Metrik Keberhasilan (Success Metrics)

- Waktu pencatatan absensi per siswa berkurang dibanding proses manual sebelumnya.
- Notifikasi WhatsApp terkirim ke wali murid dengan tingkat keberhasilan (delivery rate) tinggi.
- Guru dapat mengelola materi dan tugas tanpa proses manual di luar sistem.
- Waktu koreksi ujian pilihan ganda menjadi instan (0 keterlambatan) dibanding koreksi manual.

---

*Dokumen ini adalah draft awal dan dapat direvisi seiring diskusi lebih lanjut dengan stakeholder sekolah.*
