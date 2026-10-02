---
paths:
  - database/seeders/PpdbSeeder.php
---

# Seeders

## Tahap gelombang adalah sumber tanggal tunggal di seeder
`seedJadwal()` membaca tahap Gelombang 1, jadi `seedGelombang()` **wajib** jalan lebih dulu di `run()`. Kalau menambah sumber tanggal baru, jangan tulis tanggal terpisah di `seedJadwal()`. Tahap diturunkan dari tanggal **tutup** pendaftaran (bukan buka) supaya mustahil ada tahap yang jatuh sebelum pendaftaran ditutup. Tahap satu hari ditulis `tanggal_selesai => null`.
