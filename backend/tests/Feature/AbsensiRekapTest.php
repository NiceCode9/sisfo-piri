<?php

use App\Http\Controllers\Admin\AbsensiController;
use App\Models\Absensi;
use App\Models\Guru;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliMurid;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

if (! function_exists('rombelRekapUji')) {
    function rombelRekapUji(string $namaKelas = '7A'): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $namaKelas))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }
}

if (! function_exists('siswaRekapUji')) {
    function siswaRekapUji(string $nis, string $nama, Rombel $rombel): Siswa
    {
        static $n = 0;
        $n++;

        $user = User::factory()->create(['username' => 'siswa-rekap-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008030%04d', $n),
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

        return $siswa;
    }
}

if (! function_exists('catatRekapUji')) {
    function catatRekapUji(Rombel $rombel, Siswa $siswa, string $tanggal, string $status): void
    {
        Absensi::create([
            'rombel_id' => $rombel->id,
            'siswa_id' => $siswa->id,
            'tanggal' => $tanggal,
            'status' => $status,
            'metode' => 'manual',
            'dicatat_oleh' => superAdmin()->id,
        ]);
    }
}

if (! function_exists('guruWaliUji')) {
    function guruWaliUji(Rombel $rombel): User
    {
        $user = User::factory()->create(['username' => 'wali-uji']);
        $user->assignRole('guru');
        $guru = Guru::create(['user_id' => $user->id, 'nama' => 'Wali Uji', 'jenis_kelamin' => 'L', 'is_aktif' => true]);
        $rombel->update(['wali_guru_id' => $guru->id]);

        return $user;
    }
}

test('tamu tidak dapat membuka rekap', function () {
    $this->get(route('admin.absensis.rekap'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka rekap', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.absensis.rekap'))->assertForbidden();
});

test('rekap mingguan menampilkan matriks dan total', function () {
    $rombel = rombelRekapUji();
    $s1 = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    $s2 = siswaRekapUji('6002', 'Anak Rekap Dua', $rombel);
    catatRekapUji($rombel, $s1, '2026-09-07', 'hadir');
    catatRekapUji($rombel, $s1, '2026-09-08', 'sakit');
    catatRekapUji($rombel, $s2, '2026-09-07', 'alpa');

    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap', [
        'rombel_id' => $rombel->id,
        'periode' => 'minggu',
        'acuan' => '2026-09-09',
    ]))->assertOk()
        ->assertSee('Anak Rekap Satu')
        ->assertSee('H 1')
        ->assertSee('S 1')
        ->assertSee('A 1');
});

test('jalur rinci dan ringkasan menghitung angka yang sama', function () {
    $rombel = rombelRekapUji();
    $penghitung = app(AbsensiController::class);

    $s1 = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    $s2 = siswaRekapUji('6002', 'Anak Rekap Dua', $rombel);
    $s3 = siswaRekapUji('6003', 'Anak Rekap Tiga', $rombel);
    // Murid tanpa catatan sama sekali. Dibuat sebelum rekap dihitung supaya
    // benar-benar ikut masuk roster yang dihitung.
    $tanpa = siswaRekapUji('6004', 'Anak Tanpa Catatan', $rombel);

    // Sebar di dua bulan supaya rentang pendek dan panjang bisa dipisahkan,
    // dan keduanya dihitung oleh jalur kode yang berbeda.
    foreach (range(1, 12) as $hari) {
        catatRekapUji($rombel, $s1, '2026-01-'.str_pad((string) $hari, 2, '0', STR_PAD_LEFT), 'hadir');
    }
    catatRekapUji($rombel, $s2, '2026-01-05', 'alpa');
    catatRekapUji($rombel, $s1, '2026-02-05', 'sakit');
    catatRekapUji($rombel, $s2, '2026-02-06', 'izin');
    catatRekapUji($rombel, $s3, '2026-02-07', 'terlambat');

    // Januari saja: 30 hari, jadi lewat jalur rinci (baris per tanggal).
    $januari = $penghitung->dataRekap($rombel->id, '2026-01-01', '2026-01-30');
    // Januari sampai Maret: 90 hari, lewat jalur ringkasan (agregat SQL).
    $semester = $penghitung->dataRekap($rombel->id, '2026-01-01', '2026-03-31');

    expect($januari['rinci'])->toBeTrue()
        ->and($semester['rinci'])->toBeFalse();

    // Ekspektasi dihitung tangan dari data di atas, bukan dari kode yang
    // sedang diuji. Jalur ringkasan menggabungkan hitungan bulan ketiga, jadi
    // angkanya lebih besar; yang dijaga adalah bahwa keduanya benar dan
    // tidak ada status yang hilang.
    expect($januari['total'])->toBe(['hadir' => 12, 'sakit' => 0, 'izin' => 0, 'alpa' => 1, 'terlambat' => 0]);
    expect($semester['total'])->toBe(['hadir' => 12, 'sakit' => 1, 'izin' => 1, 'alpa' => 1, 'terlambat' => 1]);

    // Siswa yang tidak punya catatan di rentang itu tetap punya hitungan nol
    // penuh dan persen kosong, bukan kunci yang hilang. Di rentang semester
    // siswa ini justru punya satu catatan, jadi diuji terpisah.
    expect($januari['matriks'][$s3->id]['hitung'])->toBe(['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'terlambat' => 0]);
    expect($januari['matriks'][$s3->id]['persen'])->toBeNull();

    // Murid yang benar-benar tanpa catatan sama sekali di rentang ini.
    foreach ([$januari, $semester] as $rekap) {
        expect($rekap['matriks'][$tanpa->id]['hitung'])->toBe(['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'terlambat' => 0])
            ->and($rekap['matriks'][$tanpa->id]['persen'])->toBeNull();
    }

    // Perentase dihitung dari jumlah yang terisi, bukan dari panjang rentang.
    // `round()` mengembalikan float, jadi ekspektasinya float juga supaya
    // perbandingan tetap ketat.
    expect($januari['matriks'][$s1->id]['persen'])->toBe(100.0);
    expect($semester['matriks'][$s1->id]['persen'])->toBe(92.0); // 12 dari 13 hari, dibulatkan
    expect($semester['matriks'][$s2->id]['persen'])->toBe(0.0);
    expect($semester['matriks'][$s3->id]['persen'])->toBe(100.0); // terlambat dihitung hadir

    // `perTanggal` hanya ada di jalur rinci. Di jalur ringkasan kuncinya
    // dihilangkan, bukan diisi kosong supaya guard yang hilang di view atau
    // export gagal keras.
    expect(array_key_exists('perTanggal', $januari['matriks'][$s1->id]))->toBeTrue()
        ->and(array_key_exists('perTanggal', $semester['matriks'][$s1->id]))->toBeFalse();

    // Bagian status pada rentang Januari harus utuh di jalur ringkasan.
    expect($semester['matriks'][$s1->id]['hitung']['hadir'])->toBe($januari['matriks'][$s1->id]['hitung']['hadir'])
        ->and($semester['matriks'][$s1->id]['hitung']['alpa'])->toBe($januari['matriks'][$s1->id]['hitung']['alpa'])
        ->and($semester['matriks'][$s2->id]['hitung']['alpa'])->toBe($januari['matriks'][$s2->id]['hitung']['alpa']);
});

test('rentang panjang memakai agregat SQL, bukan memuat semua baris', function () {
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    catatRekapUji($rombel, $siswa, '2026-01-05', 'hadir');
    catatRekapUji($rombel, $siswa, '2026-03-05', 'alpa');

    $sql = [];
    DB::listen(function ($query) use (&$sql) {
        $sql[] = $query->sql;
    });

    app(AbsensiController::class)->dataRekap($rombel->id, '2026-01-01', '2026-06-30');

    $absensi = array_values(array_filter($sql, fn ($q) => str_contains($q, '"absensis"')));

    // Untuk rentang panjang, rekap tidak boleh menarik satu baris per hari per
    // siswa. Cukup satu query agregat.
    expect($absensi)->toHaveCount(1)
        ->and($absensi[0])->toContain('group by "siswa_id", "status"')
        ->and($absensi[0])->not->toContain('"jam_datang"');
});

test('rentang pendek tetap memuat rincian per tanggal', function () {
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    catatRekapUji($rombel, $siswa, '2026-01-05', 'hadir');

    $sql = [];
    DB::listen(function ($query) use (&$sql) {
        $sql[] = $query->sql;
    });

    $rekap = app(AbsensiController::class)->dataRekap($rombel->id, '2026-01-01', '2026-01-30');

    $absensi = array_values(array_filter($sql, fn ($q) => str_contains($q, '"absensis"')));

    expect($rekap['rinci'])->toBeTrue()
        ->and($absensi)->toHaveCount(1)
        // Hanya kolom yang dipakai matriks, bukan seluruh tabel.
        ->and($absensi[0])->toContain('"siswa_id", "tanggal", "status"')
        ->and($absensi[0])->not->toContain('"keterangan"')
        ->and($rekap['matriks'][$siswa->id]['perTanggal']->get('2026-01-05')?->status)->toBe('hadir');
});

test('semester ganjil genap memakai konvensi kalender', function () {
    $tahun = TahunAjaran::aktif()->first();
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    catatRekapUji($rombel, $siswa, '2025-08-10', 'hadir');
    catatRekapUji($rombel, $siswa, '2026-02-10', 'izin');

    $dasar = ['rombel_id' => $rombel->id, 'tahun_ajaran_id' => $tahun->id];

    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap', $dasar + ['periode' => 'ganjil']))
        ->assertOk()->assertSee('2025-07-01 s.d. 2025-12-31')->assertSee('H 1')->assertDontSee('I 1');

    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap', $dasar + ['periode' => 'genap']))
        ->assertOk()->assertSee('2026-01-01 s.d. 2026-06-30')->assertSee('I 1')->assertDontSee('H 1');
});

test('periode ngawur ditolak, bukan diam-diam jadi tahun penuh', function () {
    $rombel = rombelRekapUji();
    siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);

    // Tanpa validasi, `rentangPeriode()` jatuh ke cabang `default` yang
    // bermakna "tahun penuh": filter ngawur dijawab dengan angka rekap satu
    // tahun seolah-olah itu yang diminta.
    $this->actingAs(superAdmin())
        ->get(route('admin.absensis.rekap', ['rombel_id' => $rombel->id, 'periode' => 'ngawur']))
        ->assertSessionHasErrors('periode');
});

test('acuan tidak valid ditolak tanpa membuat halaman 500', function () {
    $rombel = rombelRekapUji();

    // `Carbon::parse()` melempar exception pada input ngawur, jadi tanpa
    // validasi halaman rekap jadi 500.
    $this->actingAs(superAdmin())
        ->get(route('admin.absensis.rekap', [
            'rombel_id' => $rombel->id,
            'periode' => 'minggu',
            'acuan' => 'bukan-tanggal',
        ]))
        ->assertSessionHasErrors('acuan')
        ->assertStatus(302);
});

test('ekspor memakai filter yang sama dan menolak periode ngawur', function () {
    $rombel = rombelRekapUji();

    $this->actingAs(superAdmin())
        ->get(route('admin.absensis.rekap.excel', ['rombel_id' => $rombel->id, 'periode' => 'ngawur']))
        ->assertSessionHasErrors('periode');

    $this->actingAs(superAdmin())
        ->get(route('admin.absensis.rekap.pdf', ['rombel_id' => $rombel->id, 'periode' => 'ngawur']))
        ->assertSessionHasErrors('periode');
});

test('ekspor excel dan pdf rekap', function () {
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    catatRekapUji($rombel, $siswa, '2026-09-07', 'hadir');
    $filter = ['rombel_id' => $rombel->id, 'periode' => 'minggu', 'acuan' => '2026-09-09'];

    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap.excel', $filter))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap.pdf', $filter))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('wali dikunci ke rombel ampuan', function () {
    $rombel7A = rombelRekapUji('7A');
    $rombel7B = rombelRekapUji('7B');
    $wali = guruWaliUji($rombel7A);

    $respons = $this->actingAs($wali)->get(route('admin.absensis.rekap'));
    $respons->assertOk();
    expect(htmlTanpaToken($respons->getContent()))
        ->toContain('7A')
        ->not->toContain('7B');

    $this->actingAs($wali)->get(route('admin.absensis.rekap', ['rombel_id' => $rombel7B->id]))
        ->assertForbidden();
});

test('rekap tahun aktif menyembunyikan siswa yang sudah pindah kelas', function () {
    $rombel = rombelRekapUji('7A');
    $tetap = siswaRekapUji('6010', 'Anak Tetap', $rombel);
    $pindah = siswaRekapUji('6011', 'Anak Pindah', $rombel);

    RiwayatKelas::where('siswa_id', $pindah->id)->update(['status' => 'pindah']);
    catatRekapUji($rombel, $tetap, now()->toDateString(), 'hadir');
    catatRekapUji($rombel, $pindah, now()->toDateString(), 'hadir');

    // Grid hanya memuat anggota aktif, sehingga baris absensi siswa yang sudah
    // pindah mustahil dikoreksi. Menampilkan baris beku itu di rekap tahun
    // berjalan hanya menambah kebingungan.
    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap', [
        'rombel_id' => $rombel->id,
        'periode' => 'bulan',
        'acuan' => now()->format('Y-m-d'),
    ]))
        ->assertOk()
        ->assertSee('Anak Tetap')
        ->assertDontSee('Anak Pindah');
});

test('rekap tahun historis tetap menampilkan siswa yang waktu itu pindah', function () {
    $tahunHistoris = TahunAjaran::create([
        'nama_tahun_ajaran' => '2025/2026',
        'tanggal_mulai' => '2025-07-01',
        'tanggal_selesai' => '2026-06-30',
        'status_aktif' => false,
    ]);

    $rombel = Rombel::create([
        'kelas_id' => rombelRekapUji('7A')->kelas_id,
        'tahun_ajaran_id' => $tahunHistoris->id,
    ]);

    $siswa = siswaRekapUji('6012', 'Anak Historia', $rombel);
    RiwayatKelas::where('siswa_id', $siswa->id)->update(['status' => 'pindah']);
    catatRekapUji($rombel, $siswa, '2025-09-01', 'hadir');

    // Rekap lama adalah sumber kebenaran historis. Menghilanginya akan
    // menghapus jejaknya siswa dari kelas yang ia tinggalkan.
    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap', [
        'rombel_id' => $rombel->id,
        'periode' => 'tahun',
        'tahun_ajaran_id' => $tahunHistoris->id,
    ]))
        ->assertOk()
        ->assertSee('Anak Historia');
});

test('dashboard ortu menampilkan anak dan status hari ini', function () {
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();
    catatRekapUji($rombel, $siswa, now()->toDateString(), 'hadir');

    $this->actingAs($ortu)->get(route('ortu.dashboard'))
        ->assertOk()->assertSee('Anak Rekap Satu')->assertSee('Hadir');

    $tautan = WaliMurid::where('user_id', $ortu->id)->where('siswa_id', $siswa->id)->firstOrFail();
    $this->actingAs($ortu)->get(route('ortu.anak', $tautan))
        ->assertOk()->assertSee('Hadir: 1');
});

test('ortu tidak dapat melihat anak keluarga lain', function () {
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    $lain = siswaRekapUji('6002', 'Anak Rekap Dua', $rombel);
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();
    $tautanLain = WaliMurid::where('siswa_id', $lain->id)->firstOrFail();

    $this->actingAs($ortu)->get(route('ortu.anak', $tautanLain))->assertForbidden();
    $this->actingAs($ortu)->get(route('ortu.dashboard'))->assertOk()->assertDontSee('Anak Rekap Dua');
});

test('ortu tidak dapat membuka rekap admin', function () {
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();

    $this->actingAs($ortu)->get(route('admin.absensis.rekap'))->assertForbidden();
});
