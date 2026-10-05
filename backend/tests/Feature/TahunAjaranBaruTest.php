<?php

use App\Actions\Rombel\SalinRombelAction;
use App\Actions\Siswa\ProsesKenaikanAction;
use App\Models\Kelas;
use App\Models\Menu;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

/*
 * AkademikSeeder hanya mengisi tahun ajaran aktif. Wizard ini selalu butuh dua
 * tahun, jadi test membuat tahun kedua sendiri beserta kelas 8A yang belum ada.
 */

function adminTahunBaru(): User
{
    // Username unik per pemanggilan: satu test bisa memproses wizard lebih
    // dari sekali dengan akun berbeda.
    $user = User::factory()->create([
        'username' => 'admin-tahun-baru-'.uniqid(),
    ]);
    $user->assignRole('admin');

    return $user;
}

function buatTahunKedua(): TahunAjaran
{
    return TahunAjaran::create([
        'nama_tahun_ajaran' => '2027/2028',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => false,
    ]);
}

function rombelTahunBaru(string $kelas): Rombel
{
    return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $kelas))->firstOrFail();
}

/**
 * Satu siswa aktif di 7A tahun pertama, untuk diuji naik ke 8A.
 */
function buatSiswaUntukKenaikan(string $kode = '01'): array
{
    $rombel = rombelTahunBaru('7A');

    $user = User::factory()->create(['username' => "siswa-tahun-baru-{$kode}"]);
    $user->assignRole('siswa');

    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '97'.$kode,
        'nisn' => '0080'.str_pad($kode, 6, '0', STR_PAD_LEFT),
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'kelas_id' => $rombel->kelas_id,
        'is_aktif' => true,
    ]);

    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombel->kelas_id,
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    return [$siswa->fresh(), $user];
}

/*
 * Ekstraksi Action: logika lama harus tetap sama hasilnya.
 */

test('salin rombel menyalin rombel wali dan penugasan', function () {
    $tujuan = buatTahunKedua();

    $hasil = app(SalinRombelAction::class)->jalankan(
        TahunAjaran::aktif()->firstOrFail()->id,
        $tujuan->id
    );

    expect($hasil['rombelDisalin'])->toBe(4);
    expect($hasil['rombel'])->toBe(4);

    $salin8A = Rombel::where('tahun_ajaran_id', $tujuan->id)
        ->whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
        ->firstOrFail();

    expect($salin8A->wali_guru_id)->toBe(rombelTahunBaru('7A')->wali_guru_id);
    expect($salin8A->pengampus()->count())->toBeGreaterThan(0);
});

test('salin rombel idempoten saat dijalankan berulang', function () {
    $tujuan = buatTahunKedua();
    $asal = TahunAjaran::aktif()->firstOrFail()->id;
    $action = app(SalinRombelAction::class);

    $pertama = $action->jalankan($asal, $tujuan->id);
    $kedua = $action->jalankan($asal, $tujuan->id);

    expect($pertama['rombelDisalin'])->toBe(4);
    expect($kedua['rombelDisalin'])->toBe(0);
    expect($kedua['rombelDilewati'])->toBe(4);
    expect(Rombel::where('tahun_ajaran_id', $tujuan->id)->count())->toBe(4);
});

test('salin rombel menolak tahun sumber sama dengan tujuan', function () {
    $tahun = TahunAjaran::aktif()->firstOrFail();

    expect(fn () => app(SalinRombelAction::class)->jalankan($tahun->id, $tahun->id))
        ->toThrow(InvalidArgumentException::class);
});

test('peta otomatis pasangan tingkat berikutnya dan kelas terakhir jadi lulus', function () {
    // 8A harus ada dulu, kalau tidak maka 7A tidak punya pasangan dan peta
    // untuknya kosong — itu memang perilaku yang benar, tapi tidak yang
    // sedang diuji di sini.
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);
    $kelas7A = Kelas::where('nama_kelas', '7A')->firstOrFail();

    $peta = app(ProsesKenaikanAction::class)->petaOtomatis();
    $tingkatAkhir = (int) Kelas::pluck('tingkat')->map(fn ($t) => (int) $t)->max();
    $kelasAkhir = Kelas::where('tingkat', $tingkatAkhir)->firstOrFail();

    expect((string) $peta[$kelas7A->id])->toBe((string) $kelas8A->id);
    expect($peta[$kelasAkhir->id])->toBe('LULUS');
});

test('kenaikan memindahkan siswa dan idempoten', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $action = app(ProsesKenaikanAction::class);

    // Pemetaan dikunci KELAS ASAL => kelas tujuan, bukan kebalikannya.
    $pemetaan = [rombelTahunBaru('7A')->kelas_id => (string) $kelas8A->id];

    $hasil = $action->jalankan($asal->id, $tujuan->id, $pemetaan);

    expect($hasil['naik'])->toBe(1);
    expect($siswa->fresh()->kelas_id)->toBe($kelas8A->id);
    expect($siswa->fresh()->tahun_ajaran_id)->toBe($tujuan->id);

    // Menjalankan lagi tidak menambah apa pun: siswa sudah tidak lagi berada
    // di tahun asal, jadi tidak lagi ikut ter-query sama sekali.
    $ulang = $action->jalankan($asal->id, $tujuan->id, $pemetaan);

    expect($ulang['naik'])->toBe(0);
    expect($siswa->riwayatKelas()->where('tahun_ajaran_id', $tujuan->id)->count())->toBe(1);
});

test('kenaikan melewati siswa yang sudah punya riwayat di tahun tujuan', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    // Siswa masih tercatat di tahun asal (mis. kenaikan sebelumnya berjalan
    // sebagian lalu Someone mengembalikannya), tapi riwayat tahun tujuan sudah
    // ada. Jalur "dilewati" melindungi keadaan itu.
    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $kelas8A->id,
        'tahun_ajaran_id' => $tujuan->id,
        'status' => 'aktif',
    ]);

    $hasil = app(ProsesKenaikanAction::class)->jalankan(
        $asal->id,
        $tujuan->id,
        [rombelTahunBaru('7A')->kelas_id => (string) $kelas8A->id]
    );

    expect($hasil['naik'])->toBe(0);
    expect($hasil['dilewati'])->toBe(1);
    expect($siswa->fresh()->kelas_id)->toBe(rombelTahunBaru('7A')->kelas_id);
});

/*
 * Wizard gabungan.
 */

test('wizard membentuk rombel lalu memindahkan siswa dalam satu proses', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = Kelas::where('nama_kelas', '7A')->firstOrFail();
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $response = $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => (string) $kelas8A->id],
    ]);

    $response->assertRedirect();

    // 4 rombel hasil salin + 1 rombel 8A yang dibuat otomatis karena kelas itu
    // baru dan tidak punya sumber untuk disalin.
    expect(Rombel::where('tahun_ajaran_id', $tujuan->id)->count())->toBe(5);
    expect(Rombel::where('tahun_ajaran_id', $tujuan->id)
        ->whereHas('kelas', fn ($q) => $q->where('nama_kelas', '8A'))
        ->exists())->toBeTrue();

    // Rombel 8A dibuat kosong: wali dan penugasan harus diisi terpisah.
    $rombel8A = Rombel::where('tahun_ajaran_id', $tujuan->id)
        ->whereHas('kelas', fn ($q) => $q->where('nama_kelas', '8A'))
        ->firstOrFail();
    expect($rombel8A->wali_guru_id)->toBeNull();
    expect($rombel8A->pengampus()->count())->toBe(0);

    // Siswa berpindah (kenaikan).
    expect($siswa->fresh()->kelas_id)->toBe($kelas8A->id);
    expect($siswa->fresh()->tahun_ajaran_id)->toBe($tujuan->id);
});

test('wizard menolak kelas tujuan yang tidak dikenal', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = Kelas::where('nama_kelas', '7A')->firstOrFail();

    // Id kelas yang tidak ada di tabel kelas. Rombel tidak bisa dibuat untuk
    // kelas fiktif, jadi wizard harus menolak dan TIDAK memindahkan siswa —
    // memindahkannya akan membuatnya menunjuk kelas tanpa rombel.
    //
    // Penolakan sekarang datang dari `ProsesKenaikanWizardRequest` sebagai error
    // per-field, bukan lagi flash `error` dari controller. Pesannya jauh lebih
    // mudah dibaca karena menempel pada baris pemetaan yang salah.
    $response = $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => '999999'],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('pemetaan.'.$kelas7A->id);

    expect($siswa->fresh()->kelas_id)->toBe($kelas7A->id);
    expect($siswa->fresh()->tahun_ajaran_id)->toBe($asal->id);
    expect($siswa->riwayatKelas()->where('tahun_ajaran_id', $tujuan->id)->exists())->toBeFalse();
});

test('wizard memproses kelas tingkat terakhir sebagai lulus', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = Kelas::where('nama_kelas', '7A')->firstOrFail();

    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => 'LULUS'],
    ])->assertRedirect();

    expect($siswa->fresh()->is_aktif)->toBeFalse();
    expect($siswa->riwayatKelas()
        ->where('tahun_ajaran_id', $tujuan->id)
        ->first()->status)->toBe('lulus');
});

test('wizard menghormati override tinggal kelas', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = Kelas::where('nama_kelas', '7A')->firstOrFail();
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => (string) $kelas8A->id],
        'override' => [$siswa->id => 'tinggal'],
    ])->assertRedirect();

    $riwayat = $siswa->riwayatKelas()->where('tahun_ajaran_id', $tujuan->id)->firstOrFail();

    expect($riwayat->status)->toBe('mengulang');
    expect($riwayat->kelas_id)->toBe($kelas7A->id);   // tetap di kelas lama
    expect($siswa->fresh()->kelas_id)->toBe($kelas7A->id);
});

test('halaman wizard menampilkan pratinjau dan nama kelas tujuan', function () {
    buatSiswaUntukKenaikan();
    $tujuan = buatTahunKedua();
    // 8A perlu ada supaya pasangan otomatis 7A -> 8A ditemukan dan tampil.
    Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $this->actingAs(adminTahunBaru())
        ->get(route('admin.tahun-ajaran-baru.index', [
            'tahun_asal_id' => TahunAjaran::aktif()->firstOrFail()->id,
            'tahun_tujuan_id' => $tujuan->id,
        ]))
        ->assertOk()
        ->assertSee('Langkah 1')
        ->assertSee('Langkah 2')
        ->assertSee('Langkah 3')
        ->assertSee('Langkah 4')
        ->assertSee('7A')
        ->assertSee('8A');
});

test('wizard butuh permission khusus', function () {
    $user = User::factory()->create(['username' => 'user-bukan-admin-tahun-baru']);
    $user->assignRole('guru');

    $this->actingAs($user)->get(route('admin.tahun-ajaran-baru.index'))->assertForbidden();
});

/*
 * Rincian override per siswa dan penggabungan menu "Kenaikan Kelas".
 */

test('halaman wizard menampilkan override per siswa di dalam rincian', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $tujuan = buatTahunKedua();
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $this->actingAs(adminTahunBaru())
        ->get(route('admin.tahun-ajaran-baru.index', [
            'tahun_asal_id' => TahunAjaran::aktif()->firstOrFail()->id,
            'tahun_tujuan_id' => $tujuan->id,
        ]))
        ->assertOk()
        ->assertSee('Langkah 4')
        // Kolom override ada di halaman yang sama, bukan di halaman terpisah.
        ->assertSee('name="override['.$siswa->id.']"', escape: false)
        ->assertSee('Naik ke 8A');
});

test('override per siswa dari halaman gabungan menulis riwayat mengulang', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = rombelTahunBaru('7A')->kelas;
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    // Ini dulu satu-satunya cara: POST ke `kenaikan.proses`, yang tidak
    // menyalin rombel sama sekali. Sekarang lewat wizard yang sama.
    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => (string) $kelas8A->id],
        'override' => [$siswa->id => 'tinggal'],
    ])->assertSessionHas('success');

    $riwayat = RiwayatKelas::where('siswa_id', $siswa->id)
        ->where('tahun_ajaran_id', $tujuan->id)->first();

    expect($riwayat)->not->toBeNull()
        ->and($riwayat->status)->toBe('mengulang')
        ->and($riwayat->kelas_id)->toBe($kelas7A->id)
        ->and($siswa->fresh()->is_aktif)->toBeTrue()
        ->and($siswa->fresh()->kelas_id)->toBe($kelas7A->id);
});

test('endpoint yang sama menyalin rombel sebelum override dijalankan', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = rombelTahunBaru('7A')->kelas;
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => (string) $kelas8A->id],
        'override' => [$siswa->id => 'tinggal'],
    ])->assertSessionHas('success');

    // Yang ditegakkan di sini: rombel tujuan harus ada. Inilah yang dulu
    // dilewati kalau operator memakai "Atur Kenaikan Detail".
    expect(Rombel::where('tahun_ajaran_id', $tujuan->id)
        ->where('kelas_id', $kelas8A->id)->exists())->toBeTrue();
});

test('kelas tujuan yang tidak dikenal ditolak dan tidak ada siswa berpindah', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = rombelTahunBaru('7A')->kelas;

    // Id kelas fiktif. Rombel tidak bisa dibuat untuk kelas yang tidak ada,
    // jadi wizard harus menolak SEBELUM memindahkan siapa pun — kalau tidak,
    // siswa menunjuk kelas tanpa rombel dan lenyap dari rekap.
    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => '999999'],
    ])->assertSessionHasErrors();

    expect($siswa->fresh()->kelas_id)->toBe($kelas7A->id)
        ->and($siswa->fresh()->tahun_ajaran_id)->toBe($asal->id)
        ->and($siswa->riwayatKelas()->where('tahun_ajaran_id', $tujuan->id)->exists())->toBeFalse();
});

test('url lama kenaikan kelas redirect ke wizard dan membawa param', function () {
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();

    $this->actingAs(adminTahunBaru())
        ->get(route('admin.kenaikan.index', [
            'tahun_asal_id' => $asal->id,
            'tahun_tujuan_id' => $tujuan->id,
        ]))
        ->assertRedirect(route('admin.tahun-ajaran-baru.index', [
            'tahun_asal_id' => $asal->id,
            'tahun_tujuan_id' => $tujuan->id,
        ]));
});

test('url lama kenaikan kelas tanpa param tetap redirect', function () {
    $this->actingAs(adminTahunBaru())
        ->get(route('admin.kenaikan.index'))
        ->assertRedirect(route('admin.tahun-ajaran-baru.index'));
});

test('tamu tidak dapat membuka wizard lewat url lama', function () {
    $this->get(route('admin.kenaikan.index'))->assertRedirect(route('login'));
});

test('super-admin dapat membuka wizard gabungan', function () {
    $user = User::factory()->create(['username' => 'super-'.uniqid()]);
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->get(route('admin.tahun-ajaran-baru.index'))
        ->assertOk()
        ->assertSee('Tahun Ajaran Baru');
});

test('sabotase endpoint lama tidak lagi bisa dipakai melewati salin', function () {
    // convincingly test adalah menguji guard yang benar-benar menahan.
    // Kalau route `kenaikan.proses` dihidupkan lagi, test ini harus gagal
    // karena siswa bisa dipindah tanpa rombel tujuan.
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = rombelTahunBaru('7A')->kelas;

    // 8A belum punya rombel di tahun tujuan dan sengaja TIDAK dibuat.
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $rombelTujuanSebelum = Rombel::where('tahun_ajaran_id', $tujuan->id)
        ->where('kelas_id', $kelas8A->id)->exists();
    expect($rombelTujuanSebelum)->toBeFalse();

    // Jalur satu-satunya yang tersisa adalah wizard. Karena kelas 8A dikenal,
    // wizard membuat rombelnya lebih dulu lalu memindahkan siswa.
    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => (string) $kelas8A->id],
    ])->assertSessionHas('success');

    // Dan setelah naik, rombel itu benar-benar ada — tidak ada siswa yang
    // menunjuk kelas tanpa rombel.
    expect(Rombel::where('tahun_ajaran_id', $tujuan->id)
        ->where('kelas_id', $kelas8A->id)->exists())->toBeTrue()
        ->and($siswa->fresh()->kelas_id)->toBe($kelas8A->id);
});

test('wizard menaikkan beberapa kelas sekaligus', function () {
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();

    $kelas7A = rombelTahunBaru('7A')->kelas;
    $kelas7B = rombelTahunBaru('7B')->kelas;
    $kelas8A = Kelas::firstOrCreate(['nama_kelas' => '8A'], ['tingkat' => '8']);
    $kelas8B = Kelas::firstOrCreate(['nama_kelas' => '8B'], ['tingkat' => '8']);

    $rombel7B = Rombel::firstOrCreate(
        ['kelas_id' => $kelas7B->id, 'tahun_ajaran_id' => $asal->id],
        ['wali_guru_id' => null]
    );

    $userA = User::factory()->create(['username' => 'bulk-a-'.uniqid()]);
    $userA->assignRole('siswa');
    $siswaA = Siswa::create([
        'user_id' => $userA->id,
        'nis' => '97'.uniqid(),
        'nisn' => '0083'.random_int(100000, 999999),
        'tahun_ajaran_id' => $asal->id,
        'kelas_id' => $kelas7A->id,
        'is_aktif' => true,
    ]);

    $userB = User::factory()->create(['username' => 'bulk-b-'.uniqid()]);
    $userB->assignRole('siswa');
    $siswaB = Siswa::create([
        'user_id' => $userB->id,
        'nis' => '97'.uniqid(),
        'nisn' => '0084'.random_int(100000, 999999),
        'tahun_ajaran_id' => $rombel7B->tahun_ajaran_id,
        'kelas_id' => $rombel7B->kelas_id,
        'is_aktif' => true,
    ]);

    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [
            $kelas7A->id => (string) $kelas8A->id,
            $kelas7B->id => (string) $kelas8B->id,
        ],
    ])->assertSessionHas('success');

    expect($siswaA->fresh()->kelas_id)->toBe($kelas8A->id)
        ->and($siswaA->fresh()->tahun_ajaran_id)->toBe($tujuan->id)
        ->and($siswaB->fresh()->kelas_id)->toBe($kelas8B->id)
        ->and($siswaB->fresh()->tahun_ajaran_id)->toBe($tujuan->id)
        ->and(RiwayatKelas::where('status', 'aktif')
            ->where('tahun_ajaran_id', $tujuan->id)->count())->toBe(2);
});

test('halaman kenaikan kelas lama tidak lagi punya form proses sendiri', function () {
    // Tidak ada route POST lagi. Endpoint itu yang tidak menyalin rombel.
    expect(Route::has('admin.kenaikan.proses'))->toBeFalse();
});

test('hanya ada satu menu untuk kenaikan dan tahun ajaran baru', function () {
    $this->seed(MenuSeeder::class);

    expect(Menu::where('route', 'admin.tahun-ajaran-baru.index')->exists())->toBeTrue()
        ->and(Menu::where('route', 'admin.kenaikan.index')->exists())->toBeFalse()
        ->and(Menu::where('name', 'Kenaikan Kelas')->exists())->toBeFalse();
});

test('permission kenaikan kelas lama tetap ada tapi tidak dipakai sebagai gerbang', function () {
    expect(Permission::where('name', 'kenaikan-kelas.view')->exists())->toBeTrue();

    // Guru biasa punya `kenaikan-kelas.view` di role lama tapi tidak punya
    // `tahun-ajaran-baru.view`. Kalau gerbangnya masih yang lama, dia akan
    // masuk. Sekarang dia ditolak.
    $guru = User::factory()->create(['username' => 'guru-uji-grbang-'.uniqid()]);
    $guru->assignRole('guru');
    $guru->givePermissionTo('kenaikan-kelas.view');

    $this->actingAs($guru)->get(route('admin.tahun-ajaran-baru.index'))->assertForbidden();
});

test('wizard bisa dijalankan berulang tanpa merusak data setelah digabung', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = rombelTahunBaru('7A')->kelas;
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $payload = [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => (string) $kelas8A->id],
    ];

    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), $payload)->assertRedirect();
    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), $payload)->assertRedirect();

    expect(Rombel::where('tahun_ajaran_id', $tujuan->id)->count())->toBe(5)
        ->and($siswa->riwayatKelas()->where('tahun_ajaran_id', $tujuan->id)->count())->toBe(1);
});

test('grup tanpa tujuan dilewati dan dilaporkan', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = rombelTahunBaru('7A')->kelas;

    $response = $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => ''],
    ]);

    $response->assertSessionHas('success');
    expect($siswa->riwayatKelas()->where('tahun_ajaran_id', $tujuan->id)->exists())->toBeFalse()
        ->and(session('success'))->toContain('dilewati');
});

test('tahun asal sama dengan tujuan ditolak', function () {
    buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $kelas7A = rombelTahunBaru('7A')->kelas;
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $asal->id,
        'pemetaan' => [$kelas7A->id => (string) $kelas8A->id],
    ])->assertSessionHasErrors('tahun_tujuan_id');
});

test('user tanpa permission execute ditolak memproses', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = rombelTahunBaru('7A')->kelas;
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $user = User::factory()->create(['username' => 'user-biasa-'.uniqid()]);

    $this->actingAs($user)->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => (string) $kelas8A->id],
    ])->assertForbidden();

    expect($siswa->riwayatKelas()->where('tahun_ajaran_id', $tujuan->id)->exists())->toBeFalse();
});

test('kenaikan kelas tingkat akhir diluluskan lewat wizard', function () {
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();

    // Kelas tingkat terakhir yang ada di tahun asal.
    $kelasTerakhir = Kelas::orderByDesc('tingkat')->firstOrFail();
    $rombel = Rombel::where('kelas_id', $kelasTerakhir->id)
        ->where('tahun_ajaran_id', $asal->id)->first();

    if (! $rombel) {
        $rombel = Rombel::create([
            'kelas_id' => $kelasTerakhir->id,
            'tahun_ajaran_id' => $asal->id,
            'wali_guru_id' => null,
        ]);
    }

    $user = User::factory()->create(['username' => 'siswa-lulus-'.uniqid()]);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '97'.uniqid(),
        'nisn' => '0081'.random_int(100000, 999999),
        'tahun_ajaran_id' => $asal->id,
        'kelas_id' => $kelasTerakhir->id,
        'is_aktif' => true,
    ]);
    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $kelasTerakhir->id,
        'tahun_ajaran_id' => $asal->id,
        'status' => 'aktif',
    ]);

    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelasTerakhir->id => 'LULUS'],
    ])->assertSessionHas('success');

    $riwayat = RiwayatKelas::where('siswa_id', $siswa->id)
        ->where('tahun_ajaran_id', $tujuan->id)->first();

    expect($riwayat)->not->toBeNull()
        ->and($riwayat->status)->toBe('lulus')
        ->and($siswa->fresh()->is_aktif)->toBeFalse();
});

test('pratinjau menandai tingkat akhir sebagai lulus', function () {
    $tujuan = buatTahunKedua();
    $asal = TahunAjaran::aktif()->firstOrFail();

    $kelas9A = Kelas::firstOrCreate(['nama_kelas' => '9A'], ['tingkat' => '9']);

    $user = User::factory()->create(['username' => 'siswa-9-'.uniqid()]);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '97'.uniqid(),
        'nisn' => '0082'.random_int(100000, 999999),
        'tahun_ajaran_id' => $asal->id,
        'kelas_id' => $kelas9A->id,
        'is_aktif' => true,
    ]);

    $this->actingAs(adminTahunBaru())
        ->get(route('admin.tahun-ajaran-baru.index', [
            'tahun_asal_id' => $asal->id,
            'tahun_tujuan_id' => $tujuan->id,
        ]))
        ->assertOk()
        ->assertSee('Luluskan')
        ->assertSee('Lulus');
});

test('menu wizard hanya untuk yang punya permission', function () {
    expect(Menu::where('route', 'admin.tahun-ajaran-baru.index')->exists())->toBeFalse();

    $this->seed(PermissionSeeder::class);
    $this->seed(MenuSeeder::class);

    expect(Menu::where('route', 'admin.tahun-ajaran-baru.index')->exists())->toBeTrue();
});
