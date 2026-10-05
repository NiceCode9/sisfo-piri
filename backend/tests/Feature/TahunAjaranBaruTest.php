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
    $response = $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => '999999'],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

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

test('wizard bisa dijalankan berulang tanpa merusak data', function () {
    [$siswa] = buatSiswaUntukKenaikan();
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = buatTahunKedua();
    $kelas7A = Kelas::where('nama_kelas', '7A')->firstOrFail();
    $kelas8A = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);

    $payload = [
        'tahun_asal_id' => $asal->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$kelas7A->id => (string) $kelas8A->id],
    ];

    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), $payload)->assertRedirect();
    $this->actingAs(adminTahunBaru())->post(route('admin.tahun-ajaran-baru.proses'), $payload)->assertRedirect();

    // 4 hasil salin + 1 rombel 8A otomatis, dan tidak bertambah di run kedua.
    expect(Rombel::where('tahun_ajaran_id', $tujuan->id)->count())->toBe(5);
    expect($siswa->riwayatKelas()->where('tahun_ajaran_id', $tujuan->id)->count())->toBe(1);
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
        ->assertSee('7A')
        ->assertSee('8A');
});

test('wizard butuh permission khusus', function () {
    $user = User::factory()->create(['username' => 'user-bukan-admin-tahun-baru']);
    $user->assignRole('guru');

    $this->actingAs($user)->get(route('admin.tahun-ajaran-baru.index'))->assertForbidden();
});

test('menu wizard hanya untuk yang punya permission', function () {
    expect(Menu::where('route', 'admin.tahun-ajaran-baru.index')->exists())->toBeFalse();

    $this->seed(PermissionSeeder::class);
    $this->seed(MenuSeeder::class);

    expect(Menu::where('route', 'admin.tahun-ajaran-baru.index')->exists())->toBeTrue();
});
