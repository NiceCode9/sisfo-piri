# Catatan Izin/Sakit di Jam Pertama — Rencana Implementasi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Guru menandai siswa `izin`/`sakit` + foto surat langsung dari halaman QR Absen, tanpa menyentuh jalur admin.

**Architecture:** Satu controller guru baru (`AbsensiIzinController`) menulis baris absensi dengan jalur grid manual yang sudah ada (`Absensi::firstOrNew` + `PencatatanAbsensi::simpan`), dikawal penjaga sempit (rombel terjangkau + siswa anggota aktif + hanya baris baru). Kartu Blade ditambah di `guru/absensi-qr.blade.php`; cakupan rombel memakai `Rombel::terjangkauUser(..., ROLE_ABSENSI_UNIVERSAL)` — tanpa perubahan seeder, tanpa dependensi baru.

**Tech Stack:** Laravel 13, Blade, Pest 4. Tidak ada dependensi baru.

**Spec:** `docs/superpowers/specs/2026-10-23-absensi-izin-sakit-jam-pertama-design.md`

## Global Constraints

- UI dan pesan dalam bahasa Indonesia. Gaya file sekitar diikuti.
- Tanpa dependensi baru; tanpa migrasi baru.
- Jangan ubah: notifikasi WA, rekap/ekspor, grid manual/impor, `admin.absensis.*`, `Rombel` scope yang ada.
- Jangan pakai `PencatatanAbsensi::catat` di jalur guru ini (jalur scan); tulis baris via `firstOrNew` + `PencatatanAbsensi::simpan`.
- Status kartu hanya `izin` | `sakit`. `jam_datang` TIDAK diisi (konsisten `storeBatch` yang hanya mengisi jam untuk `hadir`/`terlambat`).
- Validasi gambar mengikuti `StoreBuktiAbsensiRequest`: `image, mimes:jpg,jpeg,png,webp, max:10240`.
- Disk bukti privat `berkas`, path `store('absensi', 'berkas')`.
- Flash: sukses `with('success', ...)`; error koreksi `with('error', ...)` (alert partial menampilkannya).
- Format: `vendor/bin/pint --dirty --format agent` setelah setiap edit PHP.

## Review Focus

- Siswa di luar daftar rombel (rombel_id pasangan siswa_id asal) → harus 403, bukan baris tercatat. Test: Task 1 langkah `siswa di luar daftar rombel ditolak`.
- Penimpaan baris yang sudah ada (koreksi diam-diam) → ditolak dengan pesan grid, baris tak berubah. Test: Task 1 `menandai siswa yang sudah tercatat`.
- Race insert dua guru menandai siswa sama → pengunggah kalah diarahkan pesan grid, tidak menimpa (galat `simpan()` mengembalikan baris pesaing). Test: Task 1 `menandai siswa yang sudah tercatat` (mengenai cabang `!$tersimpan->is($absensi)` sama kokohnya).
- Berkas bukan gambar / ukuran besar → validasi menolak, baris tidak terbentuk. Test: Task 1 `berkas bukan gambar ditolak`.
- Guru memberi status `hadir`/`alpa` via endpoint → ditolak validasi. Test: Task 1 `status selain izin dan sakit ditolak`.

---

### Task 1: Endpoint pencatatan izin/sakit (controller + route + request)

**Files:**
- Create: `backend/app/Http/Controllers/Guru/AbsensiIzinController.php`
- Create: `backend/app/Http/Requests/Guru/StoreAbsensiIzinRequest.php`
- Modify: `backend/routes/web.php` (grup guru, setelah baris `absensi-qr.refresh` ~line 142)
- Test: `backend/tests/Feature/AbsensiIzinGuruTest.php` (baru)

**Interfaces:**
- Consumes: `Rombel::terjangkauOleh(User, ?int $rombelId, array $universal)`, `$rombel->anggotaIdsAktif(): array`, `Rombel::ROLE_ABSENSI_UNIVERSAL`, `PencatatanAbsensi::simpan(Absensi $absensi, array $kunci): Absensi`, `App\Http\Requests\Admin\StoreBuktiAbsensiRequest` (untuk `storeBukti`).
- Produces: route `guru.absensi-qr.izin` → `POST /guru/absensi-qr/izin` (`store`), route `guru.absensi-qr.izin.bukti` → `POST /guru/absensi-qr/izin/{absensi}/bukti` (`storeBukti`). Task 2 memakai kedua nama ini di Blade.

- [ ] **Step 1: Tulis test file + fixtures** — `tests/Feature/AbsensiIzinGuruTest.php`

Fixture mengikuti pola `AbsensiQrMandiriTest` (seed sama: `RoleSeeder`, `PermissionSeeder`, `PpdbSeeder`, `AkademikSeeder`; rombel 7A dari seeder). Nama helper harus unik dan diguard `if (! function_exists(...))` karena Pest memuat semua file test dalam satu proses:

```php
uses(RefreshDatabase::class);

beforeEach(fn () => foreach ([RoleSeeder::class, PermissionSeeder::class, PpdbSeeder::class, AkademikSeeder::class] as $s) $this->seed($s));

// guruKartuIzin(?Rombel $rombel = null): User — role `guru` + baris Guru + Pengampu mapel di 7A (rombelQrMandiri()).
// rombelIzinLain(): Rombel — Rombel::factory()->create([... 'wali_guru_id' => null]) rombel asing guru lain.
// Catatan: helper TIDAK boleh menimpa nama rombelQrMandiri/siswaQrMandiri yang sudah diguard file lain.
```

- [ ] **Step 2: Tulis test-case berikut (Pest, nama bahasa Indonesia)**

```php
Storage::fake('berkas') di awal test yang menyentuh berkas.

test('guru menandai izin dengan bukti dan barisnya tersimpan manual', function () {
    $rombel = rombelQrMandiri(); $siswa = siswaQrMandiri('77', 'Siswa Izin Uji'); $guru = guruKartuIzin($rombel);

    $this->actingAs($guru)->post(route('guru.absensi-qr.izin'), [
        'rombel_id' => $rombel->id, 'siswa_id' => $siswa->id, 'status' => 'izin',
        'berkas' => UploadedFile::fake()->image('surat.png'),
    ])->assertRedirect(route('guru.absensi-qr', ['rombel_id' => $rombel->id]))->assertSessionHas('success');

    $baris = Absensi::where(['siswa_id' => $siswa->id, 'rombel_id' => $rombel->id])->firstOrFail();
    expect($baris->status)->toBe('izin')->and($baris->metode)->toBe('manual')
        ->and($baris->dicatat_oleh)->toBe($guru->id)->and($baris->jam_datang)->toBeNull()
        ->and($baris->berkas_path)->not->toBeNull();
    Storage::disk('berkas')->assertExists($baris->berkas_path);
});

test('menandai sakit tanpa bukti tetap tercatat tanpa bukti', function () { ..._baris dibuat, berkas_path null, success... });

test('menandai siswa yang sudah tercatat mengembalikan pesan dan tidak mengubah baris', function () {
    // Absensi::create([status 'hadir', metode 'qr', tanggal today()]) → POST status izin
    // → assertSessionHas('error'), row tetap 'hadir', berkas_path masih null, jumlah baris 1.
});

test('siswa di luar daftar rombel ditolak', function () {
    // $guru ampu rombelQrMandiri (7A); $siswaLain = siswaQrMandiri(..., rombelIzinLain()).
    // POST pakai rombel_id=7A + siswa_id=$siswaLain → assertForbidden(), tidak ada baris.
});

test('rombel milik guru lain ditolak', function () {
    // guruKartuIzin($rombel7A); POST rombel_id=rombelIzinLain()->id → assertForbidden(), baris 0.
});

test('berkas bukan gambar ditolak dan baris tidak terbentuk', function () {
    // UploadedFile::fake()->create('surat.pdf', 10) → assertSessionHasErrors('berkas'); Absensi count 0.
});

test('status selain izin dan sakit ditolak', function () {
    // status 'alpa' → assertSessionHasErrors('status'); count 0.
});

test('guru biasa tanpa izin grid tetap bisa menandai izin', function () {
    // guruKartuIzin (role guru saja, tanpa absensis.create) → 302 success + baris manual ada.
});

test('guru bisa mengunggah bukti menyusul dan menggantinya', function () {
    // Baris izin tanpa bukti dibuat langsung (Absensi::create, metode 'manual').
    // POST route('guru.absensi-qr.izin.bukti', $baris) berkas A → berkas_path A ada di disk.
    // POST lagi berkas B → disk missing A, exists B.
});
```

- [ ] **Step 3: Jalankan, pastikan semua FAIL (route belum ada)**

Run: `vendor/bin/pest tests/Feature/AbsensiIzinGuruTest.php`
Expected: FAIL (404/`RouteNotFoundException`) — tidak ada PASS dini.

- [ ] **Step 4: Implement request** — `app/Http/Requests/Guru/StoreAbsensiIzinRequest.php`

```php
public function authorize(): bool { return true; } // penjaga rombel & baris di controller

public function rules(): array {
    return [
        'rombel_id' => ['required', 'integer', 'exists:rombels,id'],
        'siswa_id' => ['required', 'integer', 'exists:siswas,id'],
        'status' => ['required', 'in:izin,sakit'],
        'berkas' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
    ];
}
```
Pesan bahasa Indonesia mengikuti gaya `StoreBuktiAbsensiRequest` (copy 4 pesan `berkas`, sisanya bawaan).

- [ ] **Step 5: Implement controller** — `app/Http/Controllers/Guru/AbsensiIzinController.php`

```php
/** Penandaan izin/sakit milik kartu QR guru (spek 2026-10-23). */
class AbsensiIzinController extends Controller
{
    public function store(StoreAbsensiIzinRequest $request): RedirectResponse
    {
        // $data = validated(); $rombel = Rombel::findOrFail($data['rombel_id']);
        // $this->evaluasiAkses($rombel, (int) $data['siswa_id']);   // lihat bawah
        // $kunci = ['siswa_id' => ..., 'rombel_id' => $rombel->id, 'tanggal' => today()->toDateString()];
        // $absensi = Absensi::firstOrNew($kunci);
        // if ($absensi->exists) → redirect guru.absensi-qr + with('error', 'Absensi siswa ini sudah
        //     tercatat — koreksinya lewat grid absensi.')
        // isi: status, metode='manual', dicatat_oleh=auth()->id(), rombel_id; tanpa jam_datang
        // $tersimpan = PencatatanAbsensi::simpan($absensi, $kunci);
        // if (!$tersimpan->is($absensi)) → redirect + with('error', ...) (balapan: baris sudah ada)
        // if ($request->hasFile('berkas')) { $absensi->berkas_path = $request->file('berkas')->store('absensi', 'berkas'); $absensi->save(); }
        // success: sprintf('%s ditandai %s.', nama siswa (user?->name), $absensi->status)
    }

    public function storeBukti(StoreBuktiAbsensiRequest $request, Absensi $absensi): RedirectResponse
    {
        // meniru Admin\AbsensiController::storeBukti:
        // $this->evaluasiAkses($absensi->rombel, $absensi->siswa_id);
        // abort_unless(in_array($absensi->status, ['sakit','izin'], true), 422, 'Bukti hanya ... sakit atau izin.');
        // hapus berkas lama bila ada; simpan baru; save; back()->with('success', 'Bukti berhasil diunggah.');
    }

    /** Penjaga sempit (spek §Model izin): 403 bila rombel tidak terjangkau ROLE_ABSENSI_UNIVERSAL
     *  atau siswa bukan anggota aktif rombel itu. */
    private function evaluasiAkses(Rombel $rombel, int $siswaId): void
    {
        // Rombel::terjangkauOleh(user, $rombel->id, Rombel::ROLE_ABSENSI_UNIVERSAL)
        //     || abort(403, 'Rombel ini bukan cakupan Anda.');
        // in_array($siswaId, $rombel->anggotaIdsAktif(), true) || abort(403, 'Siswa bukan anggota rombel ini.');
    }
}
```

- [ ] **Step 6: Daftarkan route** — `routes/web.php` di grup guru (setelah `absensi-qr.refresh`):

```php
Route::post('/absensi-qr/izin', [AbsensiIzinController::class, 'store'])->name('absensi-qr.izin');
Route::post('/absensi-qr/izin/{absensi}/bukti', [AbsensiIzinController::class, 'storeBukti'])->name('absensi-qr.izin.bukti');
```
(+ use `AbsensiIzinController` di import list atas file yang sudah berisi `AbsensiQrController`.)

- [ ] **Step 7: Jalankan test file → PASS**

Run: `vendor/bin/pest tests/Feature/AbsensiIzinGuruTest.php`
Expected: 9 test PASS.

- [ ] **Step 8: Pint dan commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A backend/app/Http/Controllers/Guru backend/app/Http/Requests/Guru backend/routes/web.php backend/tests/Feature/AbsensiIzinGuruTest.php
git commit -m "feat: endpoint izin/sakit kartu QR guru dengan penjaga sempit"
```

### Task 2: Kartu Izin/Sakit di halaman QR Absen

**Files:**
- Modify: `backend/app/Http/Controllers/Guru/AbsensiQrController.php` (`show()` menambah data kartu)
- Modify: `backend/resources/views/guru/absensi-qr.blade.php` (kartu setelah `div.row` QR/jendela)
- Test: `backend/tests/Feature/AbsensiIzinGuruTest.php` (tambah 4 test view)

**Interfaces:**
- Consumes: route Task 1 (`guru.absensi-qr.izin`, `guru.absensi-qr.izin.bukti`), `Rombel::terjangkauUser`, `anggotaIdsAktif`, `dokumen.absensi` (view bukti).
- Produces: data view `$rombelsIzin`, `$rombelIzinTerpilih`, `$siswaKartu`, `$statusHariIni` (map `siswa_id => status`).

- [ ] **Step 1: Tulis 4 test view (Gabungkan ke file Task 1)**

```php
test('kartu izin tampil dengan daftar siswa rombel terpilih', function () {
    $rombel = rombelQrMandiri(); $siswa = siswaQrMandiri('78', 'Siswa Kartu Uji'); $guru = guruKartuIzin($rombel);

    $this->actingAs($guru)->get(route('guru.absensi-qr'))->assertOk()
        ->assertSee('Catatan Izin/Sakit')->assertSee('Siswa Kartu Uji')
        ->assertSee((string) route('guru.absensi-qr.izin'));
});

test('rombel guru lain tidak muncul sebagai pilihan kartu', function () {
    // $guru ampu 7A; rombelIzinLain() dibuat dengan kelas bernama '7B'.
    // GET guru.absensi-qr → assertDontSee('7B'), assertSee('7A').
    // Penjelasan: daftar pilihan hanya rombel terjangkau guru; rombel default = milik guru.
});

test('baris yang sudah tercatat tampil sebagai badge dan tanpa form', function () {
    // 1 siswa di 7A; Absensi::create status 'izin', metode 'manual', tanggal today().
    // GET → assertSee('Izin') (badge) dan assertDontSee('name="status"') (form per baris tidak dirender).
});

test('guru tanpa penugasan melihat keadaan kosong kartu', function () {
    // guruKartuIzin(null) → GET → assertOk + assertSee('Tidak ada rombel yang diampu').
});
```

- [ ] **Step 2: Jalankan → FAIL**

Run: `vendor/bin/pest tests/Feature/AbsensiIzinGuruTest.php`
Expected: 4 test baru FAIL (teks kartu tidak ada). 9 test Task 1 tetap PASS.

- [ ] **Step 3: Tambah data kartu di `AbsensiQrController::show()`**

```php
// setelah array jendela yang ada, sebelum return view(...):
$rombelsIzin = Rombel::query()->with('kelas')
    ->terjangkauUser(auth()->user(), Rombel::ROLE_ABSENSI_UNIVERSAL)->get();
$rombelIzinId = (int) request('rombel_id') ?: $rombelsIzin->first()?->id;
$rombelIzinTerpilih = $rombelsIzin->firstWhere('id', $rombelIzinId);
$siswaKartu = $rombelIzinTerpilih
    ? Siswa::with('user')->whereIn('id', $rombelIzinTerpilih->anggotaIdsAktif())->get()
        ->sortBy(fn ($s) => $s->user?->name ?? '')->values()
    : collect();
$statusHariIni = Absensi::where('rombel_id', $rombelIzinTerpilih?->id)
    ->where('tanggal', today()->toDateString())->pluck('status', 'siswa_id');
```
Return view ditambah `+ ['rombelsIzin' => ..., 'rombelIzinTerpilih' => ..., 'siswaKartu' => ..., 'statusHariIni' => ...]` (import `Absensi`, `Rombel`, `Siswa` ditambah di use controller).

- [ ] **Step 4: Tambah kartu di `guru/absensi-qr.blade.php`** (setelah tutup `</div>` baris `<div class="row g-3">` QR/jendela, sebelum `@endsection`)

Struktur dan nilai wajib:

```blade
{{-- Kartu Izin/Sakit: penandaan + bukti satu-satu (spek 2026-10-23). --}}
<div class="card-nexus mt-3" id="kartu-izin">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Catatan Izin/Sakit</h5>
            <p class="card-subtitle mb-0">Tandai siswa yang tidak memindai QR karena bawa surat; bukti bisa menyusul</p>
        </div>
        @if ($rombelsIzin->count() > 1)
            <form method="GET" action="{{ route('guru.absensi-qr') }}" class="d-flex gap-1">
                <select name="rombel_id" class="form-select form-select-sm" aria-label="Pilih kelas">
                    @foreach ($rombelsIzin as $r)
                        <option value="{{ $r->id }}" @selected($r->is($rombelIzinTerpilih))>{{ $r->kelas?->nama_kelas ?? $r->id }}</option>
                    @endforeach
                </select>
                <button class="btn btn-nexus-outline btn-sm">Tampilkan</button>
            </form>
        @endif
    </div>
    {{-- bila $rombelsIzin kosong: pesan 'Tidak ada rombel yang diampu.' --}}
    {{-- tabel: kolom Siswa (user?->name) / Status hari ini (badge statusHariIni[siswa->id]) / Form --}}
    {{-- baris belum tercatat: form POST guru.absensi-qr.izin (enctype multipart, @csrf,
         hidden rombel_id + siswa_id, select name="status" opsi izin/sakit, input berkas image accept, tombol Simpan) --}}
    {{-- baris izin/sakit tanpa bukti: badge + form POST guru.absensi-qr.izin.bukti (input berkas required, tombol Unggah) --}}
    {{-- baris izin/sakit berbukti: badge + <a href="{{ route('dokumen.absensi', $baris) }}">Lihat bukti</a> --}}
</div>
```
Untuk baris yang `statusHariIni`-nya `hadir`/`alpa`/`terlambat` → render badge saja;
form penandaan HANYA untuk siswa tanpa catatan. Jenis badge / aria-label mengikuti grid admin.

- [ ] **Step 5: Jalankan file test → semua PASS**

Run: `vendor/bin/pest tests/Feature/AbsensiIzinGuruTest.php`
Expected: 13 test PASS.

- [ ] **Step 6: Alur manual smoke-test** (opsional, bila dev server jalan): login guru → `/guru/absensi-qr` → tandai izin tanpa bukti → tag ulang → pesan grid muncul.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A backend/app/Http/Controllers/Guru backend/resources/views/guru backend/tests/Feature/AbsensiIzinGuruTest.php
git commit -m "feat: kartu catatan izin/sakit di halaman QR Absen guru"
```

### Task 3: Regresi & verifikasi akhir

**Files:**
- Modify: `docs/ROADMAP_ABSENSI.md` (tandai fase izin/sakit selesai — satu baris checklist, pola Fase A5)

- [ ] **Step 1: Jalankan test absensi + otorisasi terkait**

Run: `php artisan test --compact --filter='Absensi'`
Expected: FAIL 0 — termasuk `AbsensiOtorisasiTest`, `AbsensiFondasiTest`, `AbsensiQrMandiriTest`, `AbsensiInputTest` (jalur admin grid/impor tidak berubah).

- [ ] **Step 2: Suite penuh**

Run: `php artisan test --compact`
Expected: semua PASS (899 + 13 baru).

- [ ] **Step 3: Roadmap checklist + commit**

Perbarui `docs/ROADMAP_ABSENSI.md` (pola penandaan Fase A5), commit:
```bash
git add docs/ROADMAP_ABSENSI.md
git commit -m "docs: tandai fase izin/sakit jam pertama selesai"
```
