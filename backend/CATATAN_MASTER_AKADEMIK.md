# Catatan Master Akademik — Sisfo Ngaglik

> **Tujuan:** Handover fase data master akademik (fondasi Absensi & E-Learning). PPDB dianggap selesai — arsipnya di `CATATAN_PENGERJAAN_PPDB.md` (jangan diubah).
> **Stack:** Laravel 13 / PHP 8.3 / Pest 4 / Spatie Permission / Bootstrap 5.3 (admin).
> **DB:** MySQL lokal, SQLite :memory: untuk test. Suite saat ini: **250 test / 91 permission**.

---

## 1. Skema (kasus sekolah)

Satu guru bisa mengajar banyak mapel di banyak kelas (Guru A → MTK 7A+7B; Guru D → MTK 7C+7D), dan bisa bertukar tahun depan. Riwayat nilai/materi/tugas harus utuh walau siswa naik kelas/lulus.

```
gurus ─┐ (user_id login + role guru, nip unique, nama, jk, telp, alamat, is_aktif)
       │
mata_pelajarans (kode unique MTK, nama, kelompok A/B/C, kkm, aktif)
       │
kelas (timeless: 7A…, tingkat 7/8/9)          tahun_ajarans
       │                                            │
       └──────────────┬─────────────────────────────┘
                      ▼
              pengampus  ← TABEL KUNCI
              guru + mapel + kelas + tahun
              unique(mapel, kelas, tahun): 1 guru per mapel per kelas per tahun
              Tahun baru = baris baru → guru boleh bertukar, histori utuh

wali_kelas: guru + kelas + tahun, unique(kelas, tahun)
siswas: kelas_id berjalan + riwayat_kelas per tahun (aktif/lulus/pindah/dropout)
```

**Jangkar modul berikutnya:** `materi/tugas/nilai/absensi` wajib merujuk `pengampu_id` (+ `siswa_id` untuk per siswa) — bukan guru langsung.

## 2. Keputusan yang direkam (jangan diubah tanpa diskusi)

1. **Kelas timeless** — 7A satu baris permanen; penugasan & wali di-scope per tahun.
2. **Wali tabel sendiri** — historis per tahun tercatat.
3. **Akun guru** seperti siswa (`guru.user_id` + role `guru`).
4. **Naik/lulus per siswa + massal** — naikkan tulis riwayat aktif + pindah kelas (idempoten, lewati yang sudah ada); luluskan tulis riwayat lulus + `is_aktif=false` (akun tetap).
5. **Tingkat 7/8/9** (SMP) — komentar migrasi lama `10/11/12` sudah dibetulkan.
6. **RULE MIGRASI (berlaku permanen):** satu tabel = satu file `create_*`. Konsolidasi 9 file alter adalah **kejadian satu kali** yang sudah selesai. Ke depan kolom baru **wajib** file migrasi alter baru (`add_x_to_ys_table`) — dilarang mengedit file `create_*` yang sudah committed. Pengecualian: `modify_pembayarans` dipertahankan hanya untuk FK `detail_angsuran_id` (tabel detail dibuat setelah pembayarans — alasan urutan timestamp), dan migrasi data/backfill. Setelah ubah skema: wajib `migrate:fresh --seed`.

## 3. Inventaris kode

- **CRUD** (pola baku: HasMiddleware `permission:*`, FormRequest, view Nexus, `@can`): Guru (buat akun otomatis, blokir hapus bila bertugas), MataPelajaran, Kelas (filter tingkat, blokir hapus berelasi), Pengampu (filter tahun/kelas/guru, cegah duplikat mapel+kelas+tahun, histori beda tahun boleh), WaliKelas.
- **Kenaikan** (`admin.kenaikan.*`, perm `kenaikan-kelas.view/execute`): filter tahun/kelas, checkbox + bulk naikkan/luluskan.
- **Menu** header Akademik order 21–27 (Guru 22, Mapel 23, Kelas 24, Pengampu 25, Wali 26, Kenaikan 27).
- **Seeder `AkademikSeeder`:** MTK/IPA/BIN/BIG, kelas 7A–7D, Guru A & D (+wali 7A/7C), akun `guru-a`/`guru-d` password `password`.
- **Tests:** Guru 8, MataPelajaran 8, Kelas 8, Pengampu 7, WaliKelas 7, KenaikanKelas 7. Pattern: `superAdmin()` guard `function_exists`, seed Role+Permission+Ppdb.

## 4. Pending (rencana disepakati, belum eksekusi)

- **CRUD Siswa** — full + tambah manual beserta akun (kasus mutasi; tolak bila NISN sudah dipakai akun), show profil + riwayat + tagihan (read-only + link kelola), edit kelas otomatis catat riwayat, destroy blokir bila berelasi (akun ikut terhapus bila jadi dihapus). Permission `siswas.create/edit/delete` + menu Siswa order 28.

---

*Dibuat sesi rumah pasca-PPDB — ganti bila ada commit baru.*
