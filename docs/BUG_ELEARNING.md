# Analisa & Temuan Bug — Modul E-Learning

**Tanggal audit:** 2026-10-03
**Ruang lingkup:** modul materi dan tugas untuk tiga area (admin/guru, siswa, orang tua): cakupan rombel, pembatasan data, penyimpanan berkas, status aktif, navigasi, dan throttle.
**Metode:** pembacaan kode menyeluruh (controller, policy, model, view, route, seeder, test), verifikasi data pada DB dev, dan sabotage verification terhadap test baru.
**Referensi kode:** `backend/app/Models/{Rombel,Materi,Tugas,PengumpulanTugas}.php`, `backend/app/Policies/{Materi,Tugas}Policy.php`, `backend/app/Http/Controllers/{Admin,Siswa,Ortu}/**`, `backend/app/Http/Controllers/Elearning/BerkasController.php`, `backend/resources/views/{admin,siswa,ortu}/**`, `backend/tests/Feature/**`.
**Status:** seluruh 10 temuan sudah diperbaiki di `2f141c6` (branch `feat/elearning-audit`).

## Ringkasan

| Severity | Jumlah | Karakteristik |
|---|---|---|
| **Critical** | 2 | Kebocoran data pribadi ke orang tua, akses berkas tanpa autentikasi |
| **High** | 2 | Guru dapat mengelola kelas milik guru lain |
| **Medium** | 4 | Guard tidak simetris, atribusi hilang, unggah tanpa throttle |
| **Low** | 2 | Permission buta role, navigasi tidak terjangkau |
| **TOTAL** | **10** | Semua sudah diperbaiki |

Dua temuan struktural, keduanya soal **cakupan data**: halaman tugas milik orang tua menampilkan pengumpulan seluruh teman sekelas (C1), dan lampiran E-Learning tersimpan di disk publik sehingga URL-nya bisa diambil tanpa login (C2).

Temuan H2 adalah akar yang sama: tidak ada satu pun tempat yang membedakan "tidak punya akses" dari "boleh melihat semua".

---

# Status Perbaikan

| ID | Severity | Perbaikan inti | Lokasi |
|---|---|---|---|
| C1 | Critical | Pengumpulan difilter ke anak sendiri | `app/Http/Controllers/Ortu/TugasController.php:54` |
| C2 | Critical | Pindah ke disk privat plus route terotorisasi | `app/Http/Controllers/Elearning/BerkasController.php:33` |
| H1 | High | `MateriPolicy` dan `TugasPolicy` di semua action admin | `app/Policies/{Materi,Tugas}Policy.php` |
| H2 | High | `Rombel::terjangkauUser()` fail-closed, wali atau pengampu | `app/Models/Rombel.php:83` |
| M1 | Medium | `is_aktif` diperiksa di `show` dan `kumpul` | `Siswa/{Materi,Tugas}Controller.php`, `Ortu/MateriController.php` |
| M2 | Medium | `guru_id` null untuk admin, disengaja dan dikunci test | `Admin/MateriController.php`, `Admin/TugasController.php` |
| M3 | Medium | Rate limiter 12 per menit per user | `bootstrap/app.php:42` |
| M4 | Medium | Resolusi rombel dipusatkan | `app/Models/Rombel.php:125` |
| L1 | Low | Permission didokumentasikan, tidak dipecah | `app/Policies/MateriPolicy.php` |
| L2 | Low | Tab navigasi area orang tua | `resources/views/ortu/_nav.blade.php` |

Suite penuh: **551 test / 1931 assertion** hijau, sebelumnya 537 test / 1867 assertion.

---

# CRITICAL

## C1. Halaman tugas orang tua menampilkan pengumpulan seluruh teman sekelas

**Lokasi (sebelum):** `app/Http/Controllers/Ortu/TugasController.php` — `show()`
**Dampak:** kebocoran nama, berkas, nilai, dan catatan guru milik anak lain.

Controller memuat relasi tanpa filter:

```php
$tugas->load(['mataPelajaran', 'rombel.kelas', 'pengumpulans.siswa.user']);
```

View `ortu/tugas/show.blade.php` lalu melakukan loop atas seluruh `$tugas->pengumpulans`:

```blade
@forelse($tugas->pengumpulans as $p)
    <strong>{{ $p->siswa->user->name ?? '-' }}</strong>
    Nilai: {{ $p->nilai === null ? '—' : $p->nilai }}
    @if($p->catatan_guru)Catatan: {{ $p->catatan_guru }}@endif
```

Jadi begitu wali murid membuka tugas anak, ia melihat nilai dan catatan guru untuk seluruh kelas, termasuk anak yang bukan anaknya. Guard yang ada hanya memastikan tugas itu milik rombel anak, bukan memastikan pengumpulan di dalamnya milik anak.

**Perbaikan:** relasi dirombel tidak lagi dipakai untuk data pengumpulan. Pengumpulan diambil terpisah dan dibatasi:

```php
$pengumpulans = PengumpulanTugas::where('tugas_id', $tugas->id)
    ->whereIn('siswa_id', $this->anakIds())
    ->with('siswa.user')
    ->latest()
    ->get();
```

`anakIds()` membaca lewat `WaliMurid::where('user_id', auth()->id())`, jadi daftar anak selalu mengikuti sesi login dan tidak bisa dipalsukan lewat parameter.

**Verifikasi:** test `orang tua hanya melihat pengumpulan anak sendiri` membuat dua siswa di rombel sama, lalu memastikan halaman memuat nilai anak sendiri dan tidak memuat nilai teman, catatan guru untuk teman, maupun nama teman. Test ini sabotage-verified: mengembalikan filter `whereIn('siswa_id', ...)` langsung memunculkan kebocoran dan test gagal.

---

## C2. Lampiran materi dan tugas siswa dapat diambil tanpa login

**Lokasi (sebelum):** `Admin/MateriController` (tiga titik), `Siswa/TugasController:79`, dan tujuh view
**Dampak:** berkas jawaban siswa dan materi kelas terbuka untuk siapa pun yang punya URL.

Kedua jenis berkas ditulis ke disk `public`:

```php
$request->file('file')->store('materi', 'public');   // Admin/MateriController
$request->file('file')->store('tugas', 'public');    // Siswa/TugasController
```

View memakainya sebagai URL langsung:

```blade
<a href="{{ Storage::disk('public')->url($p->file_path) }}" target="_blank">Download</a>
```

Karena `storage:link` memetakan `public/storage` ke `storage/app/public`, siapa pun yang mengetahui path-nya bisa mengunduh tanpa sesi. Nama berkas memang acak, tetapi path tersebut bocor lewat halaman mana pun yang menautkannya, dan tidak ada mekanisme pencabutan akses ketika perangkat hilang.

Yang terparah adalah berkas tugas: berisi jawaban pribadi siswa yang belum dinilai dan belum diumumkan.

**Perbaikan:** dipindahkan ke disk privat dan disajikan lewat route yang memeriksa hak akses.

```php
// app/Models/Materi.php
public const DISK = 'berkas';

// app/Models/PengumpulanTugas.php
public const DISK = 'berkas';
```

`Elearning\BerkasController` menjadi satu-satunya jalan membaca, dengan aturan berbeda untuk dua jenis berkas:

| Jenis | Boleh dibaca oleh |
|---|---|
| Lampiran materi | admin atau super-admin dengan `materis.view`, guru pengampu rombel, siswa dan wali murid anggota rombel, tetapi hanya bila `is_aktif` |
| Berkas tugas | admin atau super-admin dengan `tugas.view`, guru pengampu rombel, siswa penyusun, wali murid penyusun |

Berkas tugas sengaja lebih ketat. Teman sekelas dan wali murid lain tidak termasuk, meskipun masing-masing bisa membuka halaman rekap miliknya sendiri.

Route-nya berada di grup `auth` yang sama dengan `DokumenController` PPDB, sehingga konsisten dengan pola yang sudah terbukti di repo ini.

**Verifikasi:** empat test menutup hal ini — `berkas tugas hanya dapat diunduh oleh pemilik, wali, atau guru pengampu`, `tamu tidak dapat mengunduh berkas tugas`, `berkas materi hanya dapat diunduh pihak yang berhak`, serta `halaman tugas tidak lagi menautkan storage publik` yang memastikan HTML tidak mengandung `/storage/`. Yang terakhir sabotage-verified: mengembalikan tautan ke `Storage::disk('public')->url(...)` langsung gagal.

---

# HIGH

## H1. Guru dapat melihat, mengubah, dan menilai tugas guru lain

**Lokasi (sebelum):** lima belas action di `Admin/MateriController` dan `Admin/TugasController`
**Dampak:** guru bisa menghapus materi guru lain dan menulis nilai di kelas yang bukan miliknya.

Tidak satu pun action memanggil `$this->authorize()`, padahal repo ini sudah punya policy (`QuestionBankPolicy`, `ExamPolicy`) yang tidak dipakai di sini. Route hanya dijaga middleware `permission:*`, yang memeriksa apakah pengguna punya izin, bukan apakah isi yang diakses miliknya. Semua guru punya `tugas.edit` dan `tugas.nilai`, sehingga dua guru di sekolah yang sama saling bisa mengoreksi pekerjaan.

Pada `store()`, FormRequest juga tidak membatasi `rombel_id`, dan dropdown menampilkan seluruh rombel, sehingga guru bisa membuat tugas di kelas orang lain.

**Perbaikan:** `MateriPolicy` dan `TugasPolicy`, mengikuti bentuk `QuestionBankPolicy` dengan method `viewAny`, `view`, `create`, `update`, `delete`, ditambah `nilai` terpisah pada `TugasPolicy`:

```php
public function view(User $user, Tugas $tugas): bool
{
    if ($user->hasRole(Rombel::ROLE_UNIVERSAL)) {
        return true;
    }

    return Rombel::terjangkauOleh($user, $tugas->rombel_id);
}
```

Langkah lain yang menyertainya:

- `index()` memakai `whereHas('rombel', fn ($qq) => $qq->terjangkauUser(request()->user()))` sehingga daftar ikut ter-scope, bukan hanya detail.
- Dropdown rombel di `formData()` memakai scope yang sama, jadi guru tidak lagi ditawarkan kelas yang tidak boleh ia sentuh.
- `TugasPolicy::nilai()` dipisah dari `update()` supaya permission `tugas.nilai` tidak otomatis berlaku saat guru boleh menyunting.

**Verifikasi:** test `guru ditolak pada rombel milik guru lain` menguji `show`, `edit`, `nilai`, `update`, dan `destroy` sekaligus, lalu memastikan judul tugas tidak berubah setelah percobaan menulis.

## H2. Cakupan rombel fail-open: guru tanpa kelas melihat seluruh rombel

**Lokasi (sebelum):** `Admin/TugasController::rombelTerjangkau()`
**Dampak:** rekap nilai dan dropdown kelas terbuka untuk seluruh sekolah.

Fungsi lama hanya menghitung rombel yang diwali:

```php
$guruId = Guru::where('user_id', auth()->id())->first()?->id;
$kemampuan = $guruId ? $semua->where('wali_guru_id', $guruId)->values() : collect();

if ($kemampuan->isNotEmpty()) {
    return ['semua' => $kemampuan, 'terkunci' => true];
}

return ['semua' => $semua, 'terkunci' => false];
```

Dua cacat sekaligus:

1. **Guru pengampu mapel kehilangan akses.** Kunci `wali_guru_id` mengabaikan tabel `pengampus`, padahal itu justru sumber penugasan mapel yang sesungguhnya.
2. **Fail-open.** Kalau tidak punya kelas wali, hasilnya seluruh rombel. Guru yang baru dibuat, guru yang penugasannya belum diisi, atau pengguna yang bukan guru sama sekali langsung mendapat akses ke semua rombel.

**Perbaikan:** satu sumber kebenaran di `Rombel`, dengan dua bentuk:

```php
public function scopeTerjangkauUser(Builder $query, User $user): Builder
{
    if ($user->hasRole(self::ROLE_UNIVERSAL)) {
        return $query;
    }

    $guru = Guru::where('user_id', $user->id)->first();

    if ($guru === null) {
        return $query->whereRaw('1 = 0');
    }

    return $query->where(function (Builder $q) use ($guru) {
        $q->where('wali_guru_id', $guru->id)
            ->orWhereIn('id', Pengampu::where('guru_id', $guru->id)->select('rombel_id'));
    });
}
```

Ditambah `terjangkauOleh()` sebagai padanan untuk satu baris, dan `untukSiswa()` untuk resolusi rombel siswa.

Cakupan menjadi wali kelas **atau** pengampu mapel, dan yang paling penting: tidak punya penugasan berarti nol, bukan semua. Bypass hanya lewat `Rombel::ROLE_UNIVERSAL`, sehingga tidak mungkin lagi terjadi tanpa sengaja.

Data dev sudah menyediakan kasus uji untuk hal ini. `AkademikSeeder` membentuk Guru A sebagai wali 7A sekaligus pengampu MTK di 7A dan 7B, serta Guru D sebagai wali 7C sekaligus pengampu MTK di 7C dan 7D. Kelas 7B adalah kasus guru boleh menjangkau rombel karena mengampu mapel, bukan karena wali.

**Verifikasi:** dua test saling mengunci. `guru mencapai rombel yang diampu mapel walau bukan wali` menuntut 7B memberi 200, dan `guru ditolak pada rombel milik guru lain` menuntut 7C memberi 403. Test `guru tanpa penugasan tidak melihat rombel siapa pun` menutup jalur fail-open. Sabotage-verified: menghapus klausa `orWhereIn('id', Pengampu...)` langsung membuat Guru A kehilangan 7B dan daftar materinya kosong.

> **Pola fail-open yang sama ada di luar E-Learning.** Saat H2 diperbaiki, sapuan lanjutan menemukan `AbsensiController` menghitung rekap dengan cara berbeda — tanpa `terjangkauUser()`, sehingga guru tanpa kelas melihat seluruh rombel. Sudah diperbaiki terpisah dengan call site yang sama; verifikasi manual ada di `docs/PANDUAN_UJI_KEPUTUSAN_AKADEMIK.md`. Kalau menambah controller baru yang menyaring rombel, jangan menghitung jangkauan dengan cara sendiri — pakai `terjangkauUser()` supaya jalur fail-open tidak terbuka lagi.

---

# MEDIUM

## M1. `is_aktif` hanya dijaga di index

**Lokasi (sebelum):** `Siswa/{Materi,Tugas}Controller` dan `Ortu/{Materi,Tugas}Controller`

Empat controller menyaring `is_aktif` pada `index()`, tetapi `show()` dan `kumpul()` tidak memaksanya. Penyaringan index bukan kontrol akses, karena siswa bisa membuka `/siswa/tugas/{id}` langsung dengan menebak ID.

Untuk materi dampaknya kecil. Untuk tugas lebih serius: siswa bisa mengumpulkan tugas yang sudah ditutup guru.

**Perbaikan:** `abort_unless($tugas->is_aktif, 404)` ditambahkan pada `show()` dan `kumpul()` di area Siswa dan Ortu. Kode 404 dipilih, bukan 403, supaya halaman non-aktif tidak bisa dibedakan dari halaman yang tidak ada. `Elearning\BerkasController` menegakkan aturan yang sama, sehingga lampiran materi non-aktif tidak bisa diambil walaupun halaman yang menautkannya sudah 404.

## M2. Materi yang dibuat admin tidak punya atribusi guru

**Lokasi (sebelum):** `Admin/MateriController::store()` dan `Admin/TugasController::store()`

```php
$validated['guru_id'] = Guru::where('user_id', auth()->id())->first()?->id;
```

Untuk admin tidak ada baris di tabel `gurus`, sehingga hasilnya `null`.

**Keputusan: dibiarkan null.** Mengisinya dengan `rombel.wali_guru_id` memang membuat kolom tidak kosong, tetapi itu berbohong. Kolom itu berarti guru yang membuat materi ini, dan wali kelas yang tidak pernah menyentuhnya akan tercatat sebagai penulis. Materi milik sekolah tanpa nama penulis adalah catatan yang jujur; materi milik wali kelas padahal dibuat admin adalah data palsu yang lebih sulit dilacak daripada null.

Yang berubah adalah pencatatannya: perilaku ini kini punya komentar di kedua controller dan test yang mengunci, agar tidak dianggap bug lalu "diperbaiki" dengan cara yang keliru.

## M3. Unggah tugas dan materi tanpa rate limit

**Lokasi:** `Siswa\TugasController::kumpul()` dan `Admin\MateriController::store()`

Batas throttle bawaan adalah 60 per menit, sementara endpoint menerima berkas sampai 10 MB per percobaan. Satu akun bisa mengulang sampai sekitar 600 MB per menit. Ini denial of service yang murah: tidak membutuhkan alat khusus, hanya pengulangan.

**Perbaikan:** limiter khusus di `bootstrap/app.php`, dipasang lewat `HasMiddleware::middleware()` yang sudah dipakai kedua controller:

```php
RateLimiter::for('elearning-kumpul', function (Request $request) {
    return Limit::perMinute(12)->by($request->user()?->id ?: $request->ip());
});
```

Batas 12 per menit dipilih jauh di bawah bawaan. Unggah tugas normal mungkin satu atau dua per hari, sehingga 12 per menit memberi ruang sangat longgar untuk pemakaian nyata sambil tetap menahan penyalahgunaan. Dites oleh `unggah tugas dibatasi throttle` yang mengirim 13 permintaan dan mengharapkan 429 pada permintaan terakhir.

## M4. Resolusi rombel siswa tidak konsisten

**Lokasi (sebelum):** enam pemanggilan di empat controller

Untuk hal yang sama ada dua bentuk berbeda:

```php
// show dan kumpul, hanya satu baris:
abort_unless($tugas->rombel_id === Rombel::where(...)->first()?->id, 403);

// index, semua baris:
$rombels = Rombel::where(...)->pluck('id');
```

Perbedaannya nyata. `first()` hanya melihat satu baris, sedangkan `pluck()` melihat semua. Untuk siswa yang punya lebih dari satu baris rombel, keduanya menghitung hal berbeda: `index` menampilkan materi yang `show` menolak. Selain itu `first()?->id` menghasilkan `null` yang dibandingkan ketat dengan integer, sehingga perbedaan tipe ikut menjadi sumber penolakan yang tidak menentu.

**Perbaikan:** satu definisi di `Rombel::untukSiswa()` yang mengembalikan array integer, dipakai keenam pemanggilan.

---

# LOW

## L1. Permission view dan delete tidak dibedakan untuk guru

**Lokasi (sebelum):** `database/seeders/PermissionSeeder.php`

Sembilan permission E-Learning diberikan utuh kepada role `guru` dan `admin`, termasuk `delete`. Secara permission, guru memang boleh menghapus. Pada praktiknya itu tidak masalah setelah H1 diperbaiki, karena yang membatasi adalah rombelnya.

**Keputusan: tidak dipecah.** Pemecahan permission per guru menambah daftar permission tanpa menambah keamanan, karena yang tetap perlu dicek tetap apakah rombel ini miliknya, dan itu sudah ditangani policy. Slug `materis.*` yang jamak juga dibiarkan: menyelaraskan ke `materi.*` berarti migrasi dan reseed dengan nilai tambah nol. Ketidakkonsistenan slug dicatat di header policy supaya tidak disalahpahami sebagai bug.

## L2. Area orang tua tidak punya navigasi sama sekali

**Lokasi (sebelum):** tidak ada `resources/views/ortu/_nav.blade.php`

Siswa punya tab navigasi di `siswa/_nav.blade.php` lengkap dengan Materi dan Tugas. Area orang tua tidak punya padanannya: tidak ada partial, dan tidak ada view yang mengikutinya. Halaman `/ortu/materi` dan `/ortu/tugas` ada dan berfungsi, tetapi tidak ada tautan masuk ke sana dari mana pun. Satu-satunya cara mencapainya adalah mengetik URL atau bernavigasi lewat halaman dashboard.

**Perbaikan:** `ortu/_nav.blade.php` dibuat mengikuti bentuk `siswa/_nav.blade.php` dengan tab Dashboard, Profil Saya, Materi, dan Tugas, lalu diikutkan ke tujuh view ortu.

---

# Cakupan Test (setelah perbaikan)

`tests/Feature/ElearningIsolationTest.php` berisi 14 test dan 64 assertion:

| Test | Menutup |
|---|---|
| `orang tua hanya melihat pengumpulan anak sendiri` | C1 |
| `berkas tugas hanya dapat diunduh oleh pemilik, wali, atau guru pengampu` | C2 |
| `tamu tidak dapat mengunduh berkas tugas` | C2 |
| `halaman tugas tidak lagi menautkan storage publik` | C2 |
| `berkas materi hanya dapat diunduh pihak yang berhak` | C2 |
| `guru mencapai rombel yang diampu mapel walau bukan wali` | H2, sisi positif |
| `guru ditolak pada rombel milik guru lain` | H1 dan H2, lima endpoint |
| `guru tanpa penugasan tidak melihat rombel siapa pun` | H2, fail-closed |
| `daftar tugas dan materi dibatasi rombel yang boleh diakses` | H1 |
| `siswa tidak dapat membuka atau mengumpulkan tugas non-aktif` | M1 dan M4 |
| `orang tua tidak dapat membuka materi non-aktif` | M1 |
| `materi oleh admin tidak dikaitkan ke guru, oleh guru memakai dirinya sendiri` | M2 |
| `unggah tugas dibatasi throttle` | M3 |
| `area orang tua punya navigasi menuju materi dan tugas` | L2 |

## Sabotage verification

Empat perbaikan diuji dengan mengembalikan sabotage-nya satu per satu, untuk memastikan test benar-benar menangkap bug dan bukan hanya hijau:

| Sabotase | Hasil |
|---|---|
| Hapus `whereIn('siswa_id', ...)` di `Ortu\TugasController::show` | Nama teman, nilai 95, dan catatan guru untuk teman muncul; test gagal |
| Hapus klausa `orWhereIn('id', Pengampu...)` | Guru A kehilangan akses 7B dengan 403 dan daftar materi kosong; dua test gagal |
| Kembalikan tautan view ke `Storage::disk('public')->url(...)` | HTML mengandung `/storage/`; test gagal |
| Hapus guard `is_aktif` dan ganti limiter jadi `Limit::none()` | Dua test gagal |

Tiga test lain tidak di-sabotage karena tidak ada perilaku runtime yang bisa dibalik tanpa merusak kode yang lebih besar.

---

# Yang Masih Terbuka

| Item | Status |
|---|---|
| **Tabel `wali_kelas` kosong, tapi `rombels.wali_guru_id` berisi dua baris** | **Putusan: dibiarkan kosong, didokumentasikan.** Sumber wali yang dipakai sistem adalah kolom `rombels.wali_guru_id`; tabel `wali_kelas` tidak dibaca sebagai sumber resmi. `RombelSeeder` memang memakainya sebagai salah satu sumber pembentukan rombel, tapi pasangan dari `riwayat_kelas` sudah cukup. Mengisinya tanpa keputusan akan membuat dua sumber kebenaran yang bisa menyimpang. Lihat `docs/PANDUAN_UJI_KEPUTUSAN_AKADEMIK.md` bagian "Catatan penting soal data" |
| **Baris lama masih menunjuk path disk publik** | `materis` dan `pengumpulan_tugas` versi lama menyimpan path relatif `materi/...` dan `tugas/...` terhadap `storage/app/public`. Tidak ada file fisik di sana, sehingga `storage:link` tidak lagi membocorkan apa pun. Kalau ada data produksi, path itu perlu dipindahkan manual ke `storage/app/private/berkas` dan tidak bisa ditutup tes |
| **Foto profil siswa masih di disk publik** | `siswa/profil.blade.php` memakai `Storage::disk('public')->url($siswa->foto_path)`. Berbeda dari lampiran E-Learning, foto profil memang biasanya dimaksudkan publik, tetapi path-nya bisa ditebak bila nama berkas berpola. Di luar cakupan audit ini |
| **Filter mapel pada rekap dan ekspor tidak dibatasi** | `exportExcel()` dan `exportPdf()` memakai `rekapTerfilter()` yang sudah ter-scope ke rombel. Namun parameter `mapel_id` tidak dibatasi ke mapel yang benar-benar diampu guru, sehingga guru wali bisa melihat nilai mapel lain di kelasnya. Perlu keputusan produk |
| **Tidak ada pagination pada rekap dan ekspor** | `dataRekap()` memuat seluruh tugas dan siswa dalam satu array. Kelas yang besar akan melambat. Tidak diukur karena data dev masih kecil |
| **Tidak ada audit trail perubahan nilai** | `simpanNilai()` langsung melakukan `update()`. Nilai yang sudah diberikan bisa diubah tanpa jejak siapa yang mengubah dan kapan, dan untuk nilai rapor ini berisiko |

---

# Catatan Arsitektur

## Satu sumber cakupan, bukan daftar per controller

Sebelum audit ini, rombel mana yang boleh diakses pengguna dihitung ulang dengan cara berbeda di tiap controller, dan salah satunya fail-open. Sekarang hanya ada dua entry point, keduanya di `Rombel`:

| Kebutuhan | Pakai |
|---|---|
| Query daftar untuk index, dropdown, dan ekspor | `Rombel::terjangkauUser($user)` |
| Satu baris untuk policy dan controller | `Rombel::terjangkauOleh($user, $rombelId)` |

Aturan gagal tetap gagal ditegakkan di dua tempat sekaligus: `guru === null` menghasilkan `1 = 0`, dan `ROLE_UNIVERSAL` adalah satu-satunya jalan keluar. Artinya, menambahkan jenis pengguna baru tidak bisa diam-diam membuka akses ke seluruh rombel.

Tersimpan sebagai aturan bersama di `.ai/rules/` supaya pola fail-open ini tidak diulang.

## Pola berkas privat sekarang seragam

| Modul | Disk | Penyaji |
|---|---|---|
| PPDB untuk berkas, sertifikat, pembayaran | `berkas` | `DokumenController` |
| E-Learning untuk materi dan tugas | `berkas` | `Elearning\BerkasController` |

Keduanya memakai pola sama: simpan ke disk privat, lalu alirkan lewat `Storage::disk(...)->response()` setelah `exists()` diperiksa. Kalau modul baru butuh lampiran privat, pola yang benar adalah mengikuti kedua tabel ini, bukan menulis `store('x', 'public')`.

## Skema pengampu

Tabel `pengampus` dengan kolom `guru_id`, `mata_pelajaran_id`, dan `rombel_id` adalah sumber penugasan mapel yang sesungguhnya, dan sudah terisi di dev. Tabel inilah yang membuat cakupan wali atau pengampu mungkin. Tanpa tabel ini, opsi kedua tidak bisa dipenuhi.

Terverifikasi pada data dev: Guru A tercatat mengampu MTK di 7A dan 7B, Guru D di 7C dan 7D.