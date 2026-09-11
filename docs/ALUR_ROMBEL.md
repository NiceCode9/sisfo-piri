# Alur Rombongan Belajar (Rombel)

> Dokumen penjelasan untuk client — Bahasa Indonesia non-teknis.
> Sistem: Sisfo Ngaglik (SMK). Berlaku mulai tahun ajaran berjalan.

---

## 1. Apa itu rombel?

Rombel (rombongan belajar) adalah **satu kelas pada satu tahun ajaran** sebagai satu kesatuan. Contoh:

- Rombel "7A – 2026/2027" = kelas 7A yang berjalan pada tahun ajaran 2026/2027, beserta wali kelasnya, guru-guru yang mengajar di sana, dan daftar siswanya.
- Tahun depan, kelas 7A yang sama akan punya rombel baru: "7A – 2027/2028", bisa dengan wali dan guru yang berbeda.

Sebelum ada rombel, data seperti "siapa mengajar apa di mana" dicatat terpisah per kolom (kelas di satu tempat, tahun di tempat lain) sehingga histori mudah tercecer. Dengan rombel, **semua riwayat (penugasan guru, wali, nilai, absensi, materi) menempel pada satu baris rombel** — histori tiap kelas-berjalan utuh dan mudah dilihat kembali kapan pun.

## 2. Alur kerja tahunan (yang dilakukan admin)

```
1. Aktifkan tahun ajaran baru
   Menu: Tahun Ajaran → tandai 2027/2028 sebagai aktif
        ↓
2. Bentuk rombel dari tahun lalu
   Menu: Rombel → tombol "Salin Tahun"
   Pilih tahun sumber (2026/2027) dan tahun tujuan (2027/2028) → Simpan
   Sistem menduplikasi rombel + penugasan + wali sekaligus.
   Yang sudah ada dilewati dan dilaporkan, jadi aman diulang.
        ↓
3. Sesuaikan pengecualian
   Menu: Rombel → ubah wali yang berganti.
   Menu: Pengampu → ubah guru yang bertukar (pilih rombel "7A — 2027/2028", bukan kelas+tahun terpisah).
        ↓
4. Berjalan harian
   Absensi, nilai, dan materi tercatat per rombel tahun berjalan.
        ↓
5. Lihat riwayat kapan pun
   Menu: Rombel → tombol Histori: penugasan + wali + daftar siswa
   satu rombel tampil lengkap dalam satu halaman.
```

## 3. Peran dan menu terkait

| Peran | Menu | Kegunaan |
|-------|------|----------|
| Admin akademik | Rombel | Membentuk, mengubah wali, melihat histori, salin tahun |
| Admin akademik | Pengampu | Menetapkan guru per mapel per rombel |
| Admin akademik | Kenaikan Kelas | Menaikkan/meluluskan siswa (menulis riwayat) |
| Wali/Guru | (modul menyusul) | Melihat rombel yang diampu |

## 4. Pertanyaan yang sering ditanyakan

**Apakah data lama hilang saat pindah ke rombel?**
Tidak. Data penugasan dan wali lama otomatis dipindahkan ke baris rombel yang sesuai. Tabel wali lama disimpan sebagai arsip read-only dan tidak lagi dipakai untuk input baru.

**Apakah tiap tahun harus input ulang satu per satu?**
Tidak. Cukup sekali klik "Salin Tahun", lalu ubah yang berbeda saja (misalnya hanya 2 guru bertukar).

**Bagaimana bila satu kelas tidak dibuka tahun depan?**
Hapus rombel tahun berjalan tersebut (atau jangan bentuk saat salin). Histori tahun-tahun sebelumnya tetap tersimpan.

**Apakah nomor/identitas rombel berubah tiap tahun?**
Ya — "7A – 2026/2027" dan "7A – 2027/2028" adalah dua rombel berbeda dengan riwayat masing-masing. Nama kelas (7A) tetap sama.
