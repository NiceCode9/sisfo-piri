---
paths:
  - 'app/Services/RiwayatSiswa.php'
  - 'app/Http/Controllers/Admin/RiwayatSiswaController.php'
  - 'app/Http/Controllers/Ortu/RiwayatController.php'
---

# Riwayat Terpadu Siswa

## Satu service, tiga area

`App\Services\RiwayatSiswa` adalah satu-satunya tempat yang merangkai
kehadiran, e-learning, dan nilai CBT untuk seorang siswa. Tiga area memakainya:
`/siswa/riwayat`, `/admin/siswas/{siswa}/riwayat`, dan
`/ortu/anak/{waliMurid}/riwayat`. View-nya satu partial yang dipakai ketiganya.

Jangan membangun query agregasi ulang di controller atau view. Kalau ada
domain baru, tambahkan di service supaya ketiganya ikut dapat.

## Kunci pengelompokan adalah rombel_id

Baris riwayat dikelompokkan per `rombel_id`, bukan per pasangan kelas + tahun,
karena `rombels` sudah membawa keduanya. Domain mana pun bisa dijembatani ke
`rombel_id`:

| Domain | Jalur |
|---|---|
| Absensi | `absensis.rombel_id` langsung |
| E-Learning | `pengumpulan_tugas` → `tugas_id` → `tugas.rombel_id` |
| CBT | `exam_sessions` → `exam_id` → `exams.rombel_id` |

`exam_sessions` menyimpan `user_id`, bukan `siswa_id`, jadi identitas siswa
dijembatan lewat `siswas.user_id`. Jangan menambahkan kolom `siswa_id` ke
`exam_sessions` — itu membuat dua sumber kebenaran untuk "siapa siswa ini".

## Batasnya read-only

Halaman ini sengaja tidak memuat nilai rapor, predikat, peringkat, atau
cetak. Itu di luar lingkup (`docs/PRD.md`) dan butuh keputusan kurikulum
terlebih dulu. Kalau nanti ditambahkan, itu modul terpisah — bukan tambahan
di service ini.

## Akses guru

`RiwayatSiswa::bolehAkses()` menjembatan ke `Rombel::terjangkauUser()`, sumber
cakupan yang sama dengan absensi, tugas, dan materi. Guru yang tidak mengampu
rombel siswa tersebut mendapat 403 — bukan halaman kosong, supaya tidak
disalahpahami sebagai "siswa ini tidak punya riwayat".