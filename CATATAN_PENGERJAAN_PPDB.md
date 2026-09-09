# Catatan Pengerjaan — Sisfo Ngaglik (PPDB / SPMB)

> **Tujuan:** Handover untuk AI agent berikutnya agar langsung paham konteks tanpa baca ulang semua commit.  
> **Stack:** Laravel 13 / PHP 8.3 / Pest 4 / Spatie Permission 8.3 / Bootstrap 5.3 (admin) + Tailwind v4/Vite 8 (publik)  
> **DB:** MySQL lokal, SQLite :memory: untuk test. `RefreshDatabase` di-comment di `tests/Pest.php` — pakai `uses(RefreshDatabase::class)` per file.

---

## 1. Ringkasan Proyek (AGENTS.md)

- **Sisfo Ngaglik** — Sistem Informasi Sekolah SMKN Ngaglik. 4 modul:
  | Modul | Status |
  |-------|--------|
  | **PPDB (SPMB)** | **Active** — landing + pendaftaran + admin CRUD calon siswa, gelombang, biaya, pengumuman |
  | Absensi | Planned |
  | E-Learning | Planned |
  | CBT | Planned — akan expose JSON API (`routes/api.php` belum ada) |
- **Bahasa UI:** Indonesian `lang="id"`, route `spmb.*`, `admin.*`, `siswa.*`
- **Layout:** `resources/views/layouts/spmb.blade.php` (Tailwind, Poppins, `lang="id"`) untuk publik, `resources/views/layouts/app.blade.php` (Bootstrap 5.3 + Nexus `assets/style.css`) untuk admin
- **Konvensi Blade:** `resources/views/{module}/` per modul, `@yield('content')`, `@can()` untuk permission

---

## 2. Perintah Penting

```bash
composer run setup   # install, key, migrate, npm, build
composer run dev     # artisan serve + vite
composer run test    # clear config + php artisan test
npm run dev          # vite HMR
npm run build        # production

vendor/bin/pint --dirty --format agent   # WAJIB setelah edit PHP
php artisan test --compact               # semua test (150 saat ini)
php artisan test --compact --filter=nama # satu test
php artisan migrate:fresh --seed         # reset DB lokal (seed Role+Permission+Menu+Ppdb+Gelombang)
php artisan route:list
php artisan storage:link                 # sudah dijalankan — berkas di storage/app/public/berkas
```

---

## 3. Timeline Git (10 commit terakhir → HEAD)

```
99bd302 Initial commit + 25 migrasi PPDB
3f3165b fix: struktur table (menu_permission pivot, dll)
5062d5a feat: template layouting + menu dinamis (Nexus, AppServiceProvider composer $adminMenus)
b99d316 feat: autentikasi username + CRUD pengguna + permission users.* (19 test)
4abcfab feat: role & menu CRUD + permission roles.*, menus.* (39 test)
e079af6 feat: PPDB inti — CalonSiswa admin CRUD + pendaftaran publik + kuota lockForUpdate + generate PPDB-YYYY-XXXX
56bc5aa fix: rapikan form calon siswa 5 section + upload berkas admin nullable (agama select, dashed upload)
6a4208f feat: test PPDB inti (CalonSiswa/Spmb/Berkas 21 test) + Biaya/Pengumuman CRUD + publik dinamis (60 test)
5bef942 feat: gelombang terpisah dari jadwal_ppdbs dengan CRUD admin (3 gelombang template Early Bird 80/70/30)
213440b feat: auto-akun siswa saat daftar (nisn) + dashboard pantau status + Siswa expand (87 test)
9b6b7e6 feat: sertifikat prestasi olahraga multi-file per jalur (127 test)
a0dd198 feat: berkas KRM & KIP opsional di pendaftaran (kantor)
8888aaf feat: halaman about sekolah di frontend (kantor)
1ca208a feat: pembayaran manual + auto-create saat diterima + upload bukti (kantor) ← HEAD
```

**Working tree bersih** setelah `1ca208a`.

---

## 4. Apa yang SUDAH Selesai (PPDB)

### 4.1 Auth & Sistem (commit b99d316 + 4abcfab)
- **Login username** `LoginController` `throttle:5,1`, `session regenerate`, `logout POST`, middleware `guest`/`auth`
- **Role:** `super-admin, admin, guru, siswa, orang-tua` (RoleSeeder) — `superadmin/superadmin123`
- **Permission 55:** 13 resource ×4 (`users, roles, menus, calon-siswas, biaya-pendaftarans, pengumumans, gelombangs, tahun-ajarans, jalur-pendaftarans, jadwal-ppdbs, kuota-pendaftarans, pembayarans, rencana-angsurans`) + `berkas-calon-siswas` view/edit + `siswas.view`
  - `super-admin → all`, `admin → view/create/edit` tanpa `delete` (+ `pembayarans.*` tanpa delete, `rencana-angsurans.*` tanpa delete), `siswa → siswas.view + pembayarans.view/create`
- **CRUD:** `UserController` (HasMiddleware, assignableRoles, cegah hapus diri & super-admin terakhir), `RoleController`, `MenuController` (dual permission `permission` string + pivot `menu_permission`), `GelombangController`, `BiayaPendaftaranController`, `PengumumanController`, `TahunAjaranController` (aktivasi = transaksi nonaktifkan lainnya, blokir hapus berelasi), `JalurPendaftaranController`, `JadwalPpdbController`, `KuotaPendaftaranController` (unique tahun+jalur, `terisi lte:kuota`, blokir hapus bila terisi>0)
- **Views admin:** `admin/users, roles, menus, gelombangs, biaya-pendaftarans, pengumumans` — `card-nexus`, `table-nexus`, `form-floating`, `pagination::bootstrap-5`
- **Tests:** `UsersTest 9, RolesTest 10, MenusTest 10, GelombangTest 9, BiayaPendaftaranTest 8, PengumumanTest 10` (pattern `superAdmin()` guard `function_exists`)

### 4.2 PPDB Inti (e079af6 + 56bc5aa)
- **Model fix:** `TahunAjaran HAsMany typo`, `CalonSiswa hasMany rencanaAngsuran`, `RencanaAngsuran belongsTo calonSiswa`, `siswas` expand (`calon_siswa_id, user_id, nis, nisn, tahun_ajaran_id, kelas_id, tanggal_diterima, is_aktif`)
- **CalonSiswa:** `CalonSiswaController` 7 route resource + `PATCH status` (kuota `lockForUpdate` increment/decrement) + `PATCH berkas` (Storage delete old + store baru). `index` search `no_pendaftaran|nama|nik` + filter `jalur/status` paginate 10.
- **Form admin:** `admin/calon-siswas/_form` 5 section (Jalur&Tahun, Data Pribadi, Kontak&Alamat, Orang Tua, Berkas) — `agama` select 6 opsi, alamat `height:80px`, 5 file dashed `PDF 5MB / JPG 2MB` + preview `Lihat` + `@push('scripts')` file-name JS, `create/edit` `enctype multipart`
- **Request:** `StoreCalonSiswaRequest`/`UpdateCalonSiswaRequest` (`nik 16 unique`, `nisn 10 nullable unique`, `agama in:Islam...Khonghucu`, 5 berkas `nullable mimes pdf/jpg max`), `StorePendaftaranRequest` (publik `required` 5 berkas)
- **Publik:** `SpmbController home()` kirim `tahunAjaranAktif, jadwalPpdbs, biayas, pengumumans limit3, gelombangs`, `create()` kirim `jalurPendaftarans, kuotaMap`, `store()` transaksi kuota + `PPDB-YYYY-XXXX` + 5 berkas `store('berkas','public')` + `LogStatusPendaftaran`
- **Routes:** `GET / → spmb.home`, `GET /pendaftaran → spmb.pendaftaran`, `POST /pendaftaran → spmb.store throttle:5,1`, `GET /pengumuman` + `/{pengumuman}` + admin `calon-siswas` resource
- **Tests:** `CalonSiswaTest 10, SpmbPendaftaranTest 6, BerkasVerificationTest 5` (`Storage::fake('public')`, `UploadedFile::fake`)

### 4.3 Biaya & Pengumuman Publik (6a4208f)
- **Biaya:** 5 rows seed (`Biaya Pendaftaran 100k, Uang Pangkal 2.5M dapat_diangsur, Seragam 500k, Buku 750k, Ekstrakurikuler 200k opsional`), `BiayaPendaftaranController`, `StoreBiayaRequest` (`jumlah numeric`, `wajib_bayar boolean`), `spmb/partials/biaya.blade.php` dinamis `@forelse($biayas)` + total `Rp 3.850.000` hanya `wajib_bayar=true`
- **Pengumuman:** 3 rows seed `status_aktif=true`, `PengumumanController`, `StorePengumumanRequest` (`judul, isi, tanggal_pengumuman date, status_aktif boolean, tahun_ajaran_id`), `spmb/partials/pengumuman-home.blade.php` limit 3, `spmb/pengumuman/index` paginate 9 (semua `status_aktif`) + `show` 404 jika nonaktif, `spmb/home` sisip setelah `biaya` sebelum `ekstrakurikuler`, `navbar` link Pengumuman
- **Menu:** `Biaya Pendaftaran` order17, `Pengumuman` order18

### 4.4 Gelombang Terpisah (5bef942)
- **Kenapa terpisah:** `jadwal_ppdbs` = fase linier (Pendaftaran→Verifikasi→Tes→Pengumuman→Daftar Ulang Mei-Juni), `gelombangs` = batch marketing (3 card Okt-Mar dengan 4 tanggal + kuota + diskon + keuntungan) — tidak kompatibel 1 tabel.
- **Tabel baru:** `gelombangs` (`tahun_ajaran_id FK, nama_gelombang, nomor_urut unique[tahun,urut], badge EARLY BIRD, tanggal_buka/tutup/tes/pengumuman, kuota, terisi default0, diskon_persen, keuntungan json, keterangan, warna_border primary/secondary/accent, is_aktif`)
- **Model:** `Gelombang fillable+casts keuntungan=>array, date, is_aktif=>boolean, belongsTo TahunAjaran, scopeAktif, getPersentaseAttribute = terisi/kuota*100`
- **Seed:** 3 gelombang template 80(28 terisi 35% diskon15 primary), 70(14 20% diskon10 secondary), 30(3 10% diskon5 accent)
- **Admin CRUD:** `GelombangController` HasMiddleware `gelombangs.*`, `_form` 5 section (tanggal buka/tutup/tes/umum, kuota/terisi/diskon, keuntungan 3 input), `index` `nomor_urut` + `badge` + `persentase`
- **Publik:** `SpmbController home` kirim `gelombangs where is_aktif order nomor_urut`, `spmb/partials/gelombang.blade.php` dinamis `@forelse` gradient `match warna_border`, kuota progress, keuntungan loop
- **Menu:** `Gelombang` order15 `fa-layer-group` `admin.gelombangs.index` (sisakan `Jalur 12, Jadwal 13, Kuota 14` masih `route=null` — sengaja, CRUD master ditunda sesuai keputusan user)

### 4.5 Auto-Akun Siswa (213440b)

- **Spec user:** `username = nisn`, generate otomatis `Str::random(8)`, role `siswa` saja, langsung buat `Siswa` saat `diterima`, login langsung `menunggu` untuk pantau.
- **Siswa expand:** `siswas` tambah 8 kolom (sudah di atas), `User hasOne calonSiswa/siswa`, `CalonSiswa belongsTo user`
- **Spmb store:** dalam transaksi cek `User where username nisn exists → error`, `User::create(username:nisn, name, email, password plain)` `assignRole('siswa')`, `CalonSiswa create user_id`, flash `Username: nisn | Password: xxx (simpan!) No: PPDB-...`
- **Login:** `LoginController store` cabang `if hasRole('siswa') → siswa.dashboard else admin.dashboard`
- **Dashboard siswa:** `Siswa/DashboardController __invoke` `CalonSiswa where user_id auth` + `berkas, log, tahun, jalur` + `siswa`, view `siswa/dashboard.blade.php` (`layouts/app` card status badge + berkas `perlu perbaikan` + log timeline + pembayaran placeholder), route `GET /siswa/dashboard role:siswa siswa.dashboard`
- **Promosi:** `CalonSiswaController updateStatus` setelah `diterima` → `Siswa::firstOrCreate(calon_siswa_id, user_id, nisn, tahun_ajaran_id, tanggal_diterima)` + auto-create 4 tagihan `Pembayaran` wajib_bayar (lihat 4.7)

### 4.7 Pembayaran (1ca208a, kantor) ← terbaru
- **Admin CRUD:** `Admin/PembayaranController` (HasMiddleware `pembayarans.*`) — kode `PAY-YYYY-XXXX`, `status menunggu/berhasil/gagal` via `PATCH pembayarans/{pembayaran}/status`, `metode transfer/tunai`, `bukti_pembayaran_path` store disk `public/bukti`, views `admin/pembayarans/*` (index/create/edit/show/_form). Menu `Pembayaran` order16 → `admin.pembayarans.index` (orphan terakhir tersambung).
- **Auto-create tagihan:** `CalonSiswaController updateStatus` saat `diterima` → buat `Pembayaran` untuk tiap `BiayaPendaftaran wajib_bayar=true` tahun aktif (`Biaya Pendaftaran, Uang Pangkal, Seragam, Buku`), status `menunggu`.
- **Sisi siswa:** `Siswa/PembayaranController@store` — upload bukti untuk tagihan miliknya (`POST /pembayarans/upload`, nama rute `siswa.pembayarans.store`, grup `siswa.` + `role:siswa`); dashboard eager `pembayaran` + card tabel + upload per tagihan `menunggu`; show calon admin ada card Pembayaran + tombol verifikasi berhasil/gagal + placeholder Rencana Angsuran.
- **Tests:** `PembayaranTest` 11 (CRUD + validasi + auto-create 4 tagihan), `RencanaAngsuranTest` 12 (generate, guard, bayar, lunas, denda, batal, hapus).
- **Upload siswa DICABUT total** (keputusan user): route `siswa.pembayarans.store` + `Siswa/PembayaranController` + card dashboard + eager dihapus; role siswa kembali hanya `siswas.view`. Pembayaran murni admin.

### 4.9 Angsuran Admin-Only
- **Buat rencana dari tagihan** (`POST pembayarans/{pembayaran}/rencana`, perm `rencana-angsurans.create`): syarat biaya `dapat_diangsur` + tagihan `menunggu` + belum ada rencana aktif; validasi DP ≥ `min_dp`, DP ≤ total, cicilan ≤ `max_cicilan`; transaksi buat DP (`dp_angsuran`, berhasil) + `RencanaAngsuran` (`ANG-YYYY-XXXX`, FK `pembayaran_id` induk) + N detail (jatuh tempo bulanan `addMonthsNoOverflow`, nominal rata + selisih di terakhir).
- **Bayar cicilan** via form inline per baris → `PembayaranController@store` (`cicilan_angsuran` + `detail_angsuran_id`, cek milik calon sama + belum dibayar); verifikasi `berhasil` → hook `tutupCicilan`: detail `dibayar`, `sisa_hutang = total−dp−Σterbayar`, semua lunas → rencana `lunas` + induk `berhasil`.
- **Aturan:** denda manual per detail (`PATCH detail-angsuran/{detail}/denda`, blokir bila dibayar); status `terlambat` hanya badge tampilan; `batal` (`PATCH rencana-angsuran/{rencana}/batal`) hanya bila belum ada dibayar; hapus pembayaran cicilan terverifikasi diblokir; hapus induk aktif diblokir.
- **Views:** card rencana di show pembayaran (form buat + tabel detail + bayar inline + denda + batal); show calon link kode → show tagihan + kolom sisa.

### 4.8 KRM/KIP & About (a0dd198 + 8888aaf, kantor)
- **Berkas KRM & KIP opsional:** migrasi tambah `krm_path` + `kip_path` nullable di `berkas_calon_siswas`; fillable jadi 7 berkas; request admin + publik `nullable mimes pdf/jpg max 5MB`; loop 7 berkas di `CalonSiswaController` store/update/verify + `SpmbController@store` (+ delete old); view publik card opsional (`required false`); admin Section E/show/perbaikan/dashboard siswa 7 baris.
- **About frontend:** `spmb/about.blade.php` + rute + link navbar (`spmb.home` tetap kirim data lama).

---

## 5. Struktur File Penting (absolute `D:\Web Personal\sisfo-piri\backend\`)

```
app/Http/Controllers/
  Auth/LoginController.php (username, throttle, regenerate, redirect cabang siswa)
  Admin/UserController.php, RoleController.php, MenuController.php, GelombangController.php,
         BiayaPendaftaranController.php, PengumumanController.php, CalonSiswaController.php,
         TahunAjaran/JalurPendaftaran/JadwalPpdb/KuotaPendaftaranController.php, PembayaranController.php (hook tutupCicilan),
         RencanaAngsuranController.php (store rencana + DP, denda, batal)
  SpmbController.php (home, create, store, pengumumanIndex/Show, about)
  Siswa/DashboardController.php (tanpa pembayaran)
app/Models/ CalonSiswa (hasMany sertifikatPrestasis), BerkasCalonSiswa (7 berkas: +krm/kip), SertifikatPrestasi, LogStatusPendaftaran, TahunAjaran (scopeAktif), Gelombang (persentase), BiayaPendaftaran, Pengumuman (table pengumuman), KuotaPendaftaran, JadwalPpdb, JalurPendaftaran (wajib_sertifikat), Siswa (expand), Pembayaran, User (hasOne calonSiswa/siswa, HasRoles), Menu (parent/children, scopeUserCanAccess)
app/Http/Requests/Admin/ StoreCalonSiswa/UpdateCalonSiswa, StoreGelombang/UpdateGelombang, StoreBiaya/UpdateBiaya, StorePengumuman/UpdatePengumuman, StoreTahunAjaran/UpdateTahunAjaran, StoreJalurPendaftaran/UpdateJalurPendaftaran, StoreJadwalPpdb/UpdateJadwalPpdb, StoreKuotaPendaftaran/UpdateKuotaPendaftaran (unique tahun+jalur via Rule::unique where, terisi lte:kuota), StorePembayaran/UpdatePembayaran (+detail_angsuran_id), StoreRencanaAngsuranRequest (dp/cicilan/tanggal) + Spmb/StorePendaftaranRequest (sertifikat kondisional via withValidator)
database/migrations/ 25 migrasi awal + gelombangs + expand siswas + username unique + sertifikat_prestasis + wajib_sertifikat di jalur + krm/kip di berkas + pembayaran_id di rencana_angsurans
database/seeders/ RoleSeeder (5 role), PermissionSeeder (55 perms), MenuSeeder (14 menus: +Tahun Ajaran, Pembayaran tersambung), PpdbSeeder (4 TA, 5 jalur: +Prestasi Olahraga wajib_sertifikat, 5 kuota, 5 biaya, 5 jadwal, 3 pengumuman, 3 gelombang), DatabaseSeeder call order
resources/views/layouts/app.blade.php (Nexus), spmb.blade.php (Tailwind), admin/calon-siswas/* (+Section F sertifikat, card Pembayaran di show), admin/gelombangs/*, admin/biaya-pendaftarans/*, admin/pengumumans/*, admin/tahun-ajarans/*, admin/jalur-pendaftarans/* (checkbox wajib_sertifikat), admin/jadwal-ppdbs/*, admin/kuota-pendaftarans/*, admin/pembayarans/* (index/create/edit/show/_form), spmb/pendaftaran.blade.php (7 berkas + section sertifikat dinamis step 4), spmb/home.blade.php, spmb/about.blade.php, siswa/dashboard.blade.php (card Pembayaran + upload)
routes/web.php (±60 baris, ±93 route): / (spmb.home), /about, /pendaftaran (spmb.*), /pengumuman/*, siswa/dashboard (tanpa upload), /login, admin/* 12 resources (+pembayarans full + status patch + rencana.store/batal/denda)
tests/Feature/ 16 file 150 test: Auth 6, AdminDashboard 2, Example 1, Users 9, Roles 10, Menus 10, CalonSiswa 10, SpmbPendaftaran 6, BerkasVerification 5, Gelombang 9, Biaya 8, Pengumuman 10, TahunAjaran 9, JalurPendaftaran 8, JadwalPpdb 8, KuotaPendaftaran 8, SertifikatPrestasi 7, Pembayaran 11, RencanaAngsuran 12
```

---

## 6. Yang BELUM / Akan Dikerjakan (PPDB)

**Disepakati ditunda (jawaban user):**
- **Master CRUD SELESAI (sesi rumah):** `TahunAjaran, JalurPendaftaran, JadwalPpdb, KuotaPendaftaran` + menu baru `Tahun Ajaran` order 11 + orphan `Jalur 12, Jadwal 13, Kuota 14` dibuka (route + permission `*.view`). Keputusan: aktivasi tahun otomatis nonaktifkan lainnya (transaksi), hapus diblokir bila berelasi (kecuali Jadwal), `terisi` editable ≤ kuota, kombinasi tahun+jalur unique di validasi. `JadwalPpdb` 5 fase Mei-Juni tetap dipakai untuk timeline pendaftaran (bukan gelombang).
- **Keuangan sebagian SELESAI (kantor, 1ca208a):** `Pembayaran` CRUD + upload bukti + auto-create 4 tagihan saat diterima + upload sisi siswa. Menu order16 tersambung. Sisa: `RencanaAngsuran` (`kode_angsuran ANG-YYYY-XXXX`), `DetailAngsuran` per cicilan, `PembayaranLainnya` — permission `rencana-angsurans.*` sudah di-seed tapi belum ada controller/route/view.
- **Kuota seeder:** biarkan dummy (tidak reset ke 0).

**Belum ada sama sekali (butuh bila lanjut):**
- Email notifikasi setelah pendaftaran (sekarang hanya flash password), cek jendela `JadwalPpdb` sebelum `store`
- `PembayaranLainnya` (tabel + model ada, belum ada fitur)

**Modul lain (AGENTS.md):** Absensi, E-Learning, CBT (JSON API `routes/api.php` belum ada) — belum disentuh.

---

## 7. Konvensi untuk Agent Berikutnya

- **Ikuti pola Sistem:** `HasMiddleware` dengan `permission:*.view/create/edit/delete` per aksi (contoh `UserController.php:22`), `StoreRequest` `authorize:true` + `rules` (`alpha_dash`, `unique:table,field,{$id}`, `nullable array`), view `card-nexus` + `table-nexus` + `form-floating` + `old()` + `@error is-invalid` + `@can()` + `pagination::bootstrap-5`, `redirect()->route()->with('success')` + `layouts/partials/alert` untuk `error`.
- **Test:** Pest 4, copy boilerplate `uses(RefreshDatabase::class); beforeEach seed PermissionSeeder + PpdbSeeder; if (!function_exists('superAdmin')) guard; Storage::fake('public')` untuk file. Nama test Indonesia `test('tamu tidak dapat...')`.
- **Pint:** wajib `vendor/bin/pint --dirty --format agent` setelah edit PHP.
- **Storage:** `disk public` `storage/app/public/berkas`, `public/storage` symlink, `Storage::fake('public')` di test. Hapus file lama `Storage::disk('public')->delete(old)` saat update (sudah di `CalonSiswaController`).
- **Kuota:** selalu `lockForUpdate()` + `increment/decrement terisi` dalam `DB::transaction` saat `updateStatus`/`store`.
- **Gelombang terpisah:** jangan gabung ke `jadwal_ppdbs` — `gelombangs` untuk 3 card marketing, `jadwal_ppdbs` untuk 5 fase timeline.
- **Auto-akun:** `username = nisn` (10 digit unique), password `Str::random(8)` auto-hash via `casts hashed`, flash sekali, `siswa` login langsung `menunggu`.

---

## 8. Cara Verifikasi Cepat

```bash
php artisan migrate:fresh --seed  # 3 gelombang 80/70/30, 3 pengumuman, 5 biaya, kuota 200/50/30/20/20(olahraga)
php artisan test --compact         # harus 150/150
php artisan route:list | findstr spmb
# Publik: buka /pendaftaran → isi 5 berkas → submit → flash Username: nisn Password: xxx No: PPDB-...
# Login nisn/password → redirect /siswa/dashboard (role siswa) → pantau status, berkas, log
# Admin: POST /login superadmin/superadmin123 → /admin → Calon Siswa → ubah status diterima → Siswa terbuat
```

---

## 9. Next Priority (disarankan)

1. **Keuangan selesai penuh (150 test)** — pembayaran + angsuran admin-only, upload siswa dicabut. Semua menu orphan tersambung. Next bila perlu: email notifikasi, cek jendela `JadwalPpdb`, `PembayaranLainnya`, atau modul Absensi.

**Sertifikat prestasi (sudah selesai sesi rumah, 9b6b7e6):**
- **Tabel `sertifikat_prestasis`** (`calon_siswa_id` cascade, `nama_sertifikat`, `file_path` di `berkas/sertifikat`); `CalonSiswa hasMany sertifikatPrestasis`. Tabel berkas 5 kolom tidak diubah.
- **Flag `wajib_sertifikat`** di `jalur_pendaftarans` (dikelola CRUD Jalur) + seed `Jalur Prestasi Olahraga` + kuota 20.
- **Publik:** validasi kondisional di `StorePendaftaranRequest::withValidator` (wajib min 1 bila flag), `sertifikat` array max 5 (nama required, file pdf/jpg 5MB); section dinamis di step 4 (toggle via `data-wajib-sertifikat`, tambah/hapus baris JS); simpan dalam transaksi `SpmbController@store`.
- **Admin:** Section F di form calon (list existing + hapus per file via `DELETE calon-siswas/sertifikat/{sertifikat}` + tambah dinamis); tampil di show + dashboard siswa; enum `berkas_perlu_perbaikan` terima nilai `sertifikat`; `destroy` hapus file sertifikat.
- **Tests:** `SertifikatPrestasiTest` 7 (wajib/min-1, sukses 2 file, reguler lolos, max 5, mimes, hapus file, admin create).
2. User pilih: `Biaya/Pengumuman` sudah dinamis `hanya wajib_bayar=true` total `Rp 3.850.000`, pengumuman `semua status_aktif` — sudah sesuai.
3. Jangan ubah `gelombangs` `kuota` sinkron ke `kuota_pendaftarans` (terpisah) sesuai rekomendasi — biarkan manual.

---

*File ini dibuat 2026-09-08 setelah commit `213440b`, diperbarui sesi rumah (master CRUD + sertifikat, 127 test) lalu sinkron kerja kantor per `1ca208a` pada 2026-09-09 — ganti bila ada commit baru.*
