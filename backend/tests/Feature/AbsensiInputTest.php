<?php

use App\Models\Absensi;
use App\Models\AbsensiRiwayat;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

if (! function_exists('buatAnggotaRombel')) {
    function buatAnggotaRombel(string $nis, string $nama, ?Rombel $rombel = null): Siswa
    {
        static $n = 0;
        $n++;

        $rombel ??= Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();

        $user = User::factory()->create(['username' => 'siswa-absen-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008010%04d', $n),
            'qr_token' => 'token-'.$n.'-'.str()->random(8),
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

if (! function_exists('rombelUjiAbsensi')) {
    function rombelUjiAbsensi(): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }
}

test('tamu tidak dapat membuka absensi', function () {
    $this->get(route('admin.absensis.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka absensi', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.absensis.index'))->assertForbidden();
});

test('grid menampilkan siswa rombel', function () {
    $rombel = rombelUjiAbsensi();
    buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    $this->actingAs(superAdmin())->get(route('admin.absensis.index', ['rombel_id' => $rombel->id]))
        ->assertOk()->assertSee('Anak Absen Satu');
});

test('siswa tanpa catatan tampil sebagai belum dicatat', function () {
    $rombel = rombelUjiAbsensi();
    buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    // Select tidak boleh terisi "hadir" secara diam-diam. Kalau iya, membuka
    // grid lalu menekan Simpan akan mencatat seluruh kelas hadir.
    $this->actingAs(superAdmin())->get(route('admin.absensis.index', [
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('Belum dicatat')
        ->assertSee('<option value="" selected', false);
});

test('siswa yang sudah punya catatan menampilkan status tercatatnya', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $hari = now()->toDateString();

    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => $hari,
        'status' => 'sakit',
        'metode' => 'manual',
    ]);

    // Memakai "Belum dicatat" sebagai nilai kosong tidak boleh ikut menimpa
    // pilihan yang memang sudah tercatat.
    $this->actingAs(superAdmin())->get(route('admin.absensis.index', [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
    ]))
        ->assertOk()
        ->assertSee('<option value="sakit" selected', false)
        ->assertDontSee('<option value="" selected', false);
});

test('simpan grid tanpa pilihan tidak membuat baris hadir', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $s2 = buatAnggotaRombel('4002', 'Anak Absen Dua', $rombel);
    $hari = now()->toDateString();

    // Semua dropdown masih "belum dicatat" dan form tetap mengirim nilainya
    // sebagai string kosong. Tidak boleh ada yang tersimpan sebagai hadir.
    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$s1->id => '', $s2->id => ''],
    ])->assertSessionHasErrors('status');

    expect(Absensi::where('tanggal', $hari)->count())->toBe(0);
});

test('siswa yang dibiarkan kosong tidak menghalangi siswa lain tersimpan', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $s2 = buatAnggotaRombel('4002', 'Anak Absen Dua', $rombel);
    $s3 = buatAnggotaRombel('4003', 'Anak Absen Tiga', $rombel);
    $hari = now()->toDateString();

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$s1->id => 'hadir', $s2->id => '', $s3->id => 'sakit'],
    ])->assertSessionHas('success');

    expect(Absensi::where('tanggal', $hari)->count())->toBe(2)
        ->and(Absensi::where('siswa_id', $s1->id)->exists())->toBeTrue()
        ->and(Absensi::where('siswa_id', $s3->id)->exists())->toBeTrue()
        ->and(Absensi::where('siswa_id', $s2->id)->exists())->toBeFalse();
});

test('tombol tandai semua hadir tersedia sebagai pintasan cepat', function () {
    $rombel = rombelUjiAbsensi();
    buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    // Jalur cepat "semua hadir" harus tetap ada, tapi sebagai aksi eksplisit.
    $this->actingAs(superAdmin())->get(route('admin.absensis.index', ['rombel_id' => $rombel->id]))
        ->assertOk()
        ->assertSee('Tandai semua hadir')
        ->assertSee('data-tandai-semua="hadir"', false);
});

test('tanggal pencatatan ngawur ditolak tanpa membuat halaman 500', function () {
    $rombel = rombelUjiAbsensi();
    buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    $this->actingAs(superAdmin())
        ->get(route('admin.absensis.index', ['rombel_id' => $rombel->id, 'tanggal' => 'bukan-tanggal']))
        ->assertSessionHasErrors('tanggal')
        ->assertStatus(302);
});

test('tanggal masa depan ditolak', function () {
    $rombel = rombelUjiAbsensi();

    $this->actingAs(superAdmin())
        ->get(route('admin.absensis.index', [
            'rombel_id' => $rombel->id,
            'tanggal' => now()->addDay()->toDateString(),
        ]))
        ->assertSessionHasErrors('tanggal');
});

test('scan menolak rombel_id ngawur', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.absensis.scan', ['rombel_id' => 'abc']))
        ->assertSessionHasErrors('rombel_id');
});

test('halaman scan menjelaskan saat tidak ada rombel terjangkau', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    // Guru tanpa penugasan. Dropdown rombel kosong, dan tanpa penjelasan
    // setiap scan berakhir dengan "Gagal mencatat." yang tidak menjelaskan
    // apa pun ke guru.
    $guru = User::factory()->create(['username' => 'guru-tanpa-rombel-scan']);
    $guru->assignRole('guru');

    $this->actingAs($guru)->get(route('admin.absensis.scan'))
        ->assertOk()
        ->assertSee('Belum ada rombel yang bisa dipindai')
        ->assertSee('wali');
});

test('balapan pada unique tidak berakhir sebagai 500', function () {
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '07:00']);
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $payload = ['token' => $siswa->qr_token, 'rombel_id' => $rombel->id];

    // Simulasikan balapan sungguhan: tepat sebelum baris pertama ditulis,
    // perangkat lain berhasil lebih dulu menyisipkan baris untuk kunci yang
    // sama. Tanpa penanganan unique, insert ini meledak jadi 500.
    $berkasLawan = false;

    Absensi::creating(function () use (&$berkasLawan, $rombel, $siswa) {
        if ($berkasLawan) {
            return;
        }

        $berkasLawan = true;

        DB::table('absensis')->insert([
            'rombel_id' => $rombel->id,
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_datang' => '06:30:00',
            'metode' => 'qr',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), $payload)
        ->assertOk()
        ->assertJson(['status' => 'hadir', 'baru' => false]);

    expect(Absensi::where('siswa_id', $siswa->id)->count())->toBe(1);

    // Arrival pertama milik perangkat lain harus tetap utuh.
    $absensi = Absensi::where('siswa_id', $siswa->id)->firstOrFail();
    expect(substr((string) $absensi->jam_datang, 0, 5))->toBe('06:30');

    Absensi::flushEventListeners();
});

test('scan dibatasi rate limit', function () {
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '07:00']);
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $admin = superAdmin();

    // Kamera memindai berulang selama kartu di dalam frame, jadi endpoint ini
    // harus punya batas sendiri, bukan throttle bawaan.
    for ($i = 0; $i < 120; $i++) {
        $this->actingAs($admin)->postJson(route('admin.absensis.scan.store'), [
            'token' => $siswa->qr_token,
            'rombel_id' => $rombel->id,
        ])->assertOk();
    }

    $this->actingAs($admin)->postJson(route('admin.absensis.scan.store'), [
        'token' => $siswa->qr_token,
        'rombel_id' => $rombel->id,
    ])->assertStatus(429);
});

test('batas terlambat dibaca dari cache dan dibuang saat pengaturan disimpan', function () {
    Cache::flush();
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '07:00']);
    Pengaturan::flushCache();

    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    // 09 Oktober 23:30 UTC = 10 Oktober 06:30 WIB, jadi masih dalam batas 07:00.
    $this->travelTo(Carbon::parse('2026-10-09 23:30:00', 'UTC'));
    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), [
        'token' => $siswa->qr_token,
        'rombel_id' => $rombel->id,
    ])->assertOk()->assertJson(['status' => 'hadir']);

    // Scan kedua harus memakai nilai yang sama tanpa query ulang. Yang dihitung
    // adalah nilai yang dikirim sebagai binding, bukan teks SQL: query-nya
    // berbentuk `select * from pengaturan where kunci = ? limit 1` sehingga
    // nama kuncinya tidak pernah muncul di SQL dan pemeriksaan berbasis
    // `str_contains($sql, ...)` akan selalu nol. `CheckMaintenance` juga
    // membaca `maintenance_mode` di setiap request, jadi filter kuncinya
    // wajib.
    $query = 0;
    DB::listen(function ($q) use (&$query) {
        if (str_contains($q->sql, 'pengaturans') && in_array('batas_terlambat', (array) $q->bindings, true)) {
            $query++;
        }
    });

    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), [
        'token' => $siswa->qr_token,
        'rombel_id' => $rombel->id,
    ])->assertOk();

    expect($query)->toBe(0);

    // Admin mengubah batas; nilai baru harus berlaku segera, bukan setelah
    // cache kedaluwarsa.
    $admin = superAdmin();
    $this->actingAs($admin)->put(route('admin.pengaturans.update'), [
        'batas_terlambat' => '05:00',
    ])->assertSessionHas('success');

    expect(Pengaturan::nilai('batas_terlambat'))->toBe('05:00');
});

test('penanda cek-belum-hadir tidak ikut di-cache', function () {
    Cache::flush();
    Pengaturan::updateOrCreate(['kunci' => 'cek_belum_hadir_terakhir'], ['nilai' => null]);

    // Perintah ini menulis penanda lalu membacanya di run yang sama sebagai
    // self-gating. Kalau `Pengaturan::nilai()` meng-cache semua kunci, penanda
    // itu jadi basi dan perintah jalan berulang.
    expect(Pengaturan::KUNCI_CACHE)->not->toContain('cek_belum_hadir_terakhir');

    Pengaturan::updateOrCreate(['kunci' => 'cek_belum_hadir_terakhir'], ['nilai' => '2026-10-10']);

    expect(Pengaturan::nilai('cek_belum_hadir_terakhir'))->toBe('2026-10-10');
});

test('batch manual tersimpan dan dapat diperbarui', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $s2 = buatAnggotaRombel('4002', 'Anak Absen Dua', $rombel);
    $hari = now()->toDateString();

    $payload = [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$s1->id => 'hadir', $s2->id => 'sakit'],
    ];

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), $payload)
        ->assertRedirect(route('admin.absensis.index', ['rombel_id' => $rombel->id, 'tanggal' => $hari]));

    expect(Absensi::where('tanggal', $hari)->count())->toBe(2)
        ->and(Absensi::where('siswa_id', $s2->id)->first()->status)->toBe('sakit')
        ->and(Absensi::where('siswa_id', $s1->id)->first()->metode)->toBe('manual');

    // Pencatatan pertama bukan koreksi, jadi tidak ada jejaknya.
    expect(AbsensiRiwayat::count())->toBe(0);

    // Memperbarui baris yang sudah ada adalah koreksi: butuh alasan dan
    // selalu meninggalkan jejak.
    $payload['status'] = [$s1->id => 'izin', $s2->id => 'sakit'];
    $payload['alasan'] = 'Salah input jam masuk';

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), $payload)->assertSessionHas('success');

    expect(Absensi::where('tanggal', $hari)->count())->toBe(2)
        ->and(Absensi::where('siswa_id', $s1->id)->first()->status)->toBe('izin');

    // Hanya s1 yang berubah; s2 disentuh tapi nilainya sama sehingga bukan koreksi.
    expect(AbsensiRiwayat::count())->toBe(1);

    $jejak = AbsensiRiwayat::firstOrFail();

    expect($jejak->siswa_id)->toBe($s1->id)
        ->and($jejak->status_sebelum)->toBe('hadir')
        ->and($jejak->status_sesudah)->toBe('izin')
        ->and($jejak->alasan)->toBe('Salah input jam masuk')
        ->and($jejak->dicatat_oleh)->not->toBeNull();
});

test('mengubah absensi yang sudah tercatat tanpa alasan ditolak', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $hari = now()->toDateString();

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'hadir'],
    ])->assertSessionHas('success');

    // Tanpa alasan, koreksi ditolak dan baris lama tidak boleh berubah.
    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'alpa'],
    ])->assertSessionHasErrors('alasan');

    expect(Absensi::where('siswa_id', $siswa->id)->firstOrFail()->status)->toBe('hadir')
        ->and(AbsensiRiwayat::count())->toBe(0);
});

test('koreksi butuh permission absensis.edit', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $hari = now()->toDateString();
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'hadir'],
    ])->assertSessionHas('success');

    // User yang boleh mencatat tapi tidak memegang `absensis.edit`. Permission
    // di Spatie bersifat aditif antara user dan role-nya, jadi revoke pada
    // `guru-piket` tidak cukup — role itu sudah memberi `absensis.edit`.
    // User dibuat tanpa role, dengan permission langsung dan sebuah baris Guru
    // supaya rombelnya tetap terjangkau; tanpa itu penolakan datang dari cek
    // jangkauan rombel, bukan dari permission yang sedang diuji.
    $setengah = User::factory()->create(['username' => 'pengguna-absensi-sebagian']);
    $guruSetengah = Guru::create([
        'user_id' => $setengah->id,
        'nama' => 'Guru Absensi Sebagian',
        'jenis_kelamin' => 'L',
        'is_aktif' => true,
    ]);
    $rombel->update(['wali_guru_id' => $guruSetengah->id]);

    $setengah->givePermissionTo('absensis.view', 'absensis.create');

    expect($setengah->can('absensis.create'))->toBeTrue()
        ->and($setengah->can('absensis.edit'))->toBeFalse();

    $this->actingAs($setengah)->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'alpa'],
        'alasan' => 'Mencoba menimpa tanpa izin',
    ])->assertForbidden();

    expect(Absensi::where('siswa_id', $siswa->id)->firstOrFail()->status)->toBe('hadir')
        ->and(AbsensiRiwayat::count())->toBe(0);
});

test('riwayat koreksi ditampilkan di grid hari yang sama', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $hari = now()->toDateString();
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'hadir'],
    ])->assertSessionHas('success');

    $this->actingAs($admin)->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'izin'],
        'alasan' => 'Surat dokter menyusul',
    ])->assertSessionHas('success');

    $this->actingAs($admin)->get(route('admin.absensis.index', [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
    ]))
        ->assertOk()
        ->assertSee('Riwayat Koreksi (1)')
        ->assertSee('Surat dokter menyusul')
        ->assertSee('Dari')
        ->assertSee('Ke');
});

test('batch menolak siswa luar rombel', function () {
    $rombel = rombelUjiAbsensi();
    $luar = buatAnggotaRombel('4009', 'Anak Luar');

    $kelas7B = Kelas::where('nama_kelas', '7B')->firstOrFail();
    $rombel7B = Rombel::where('kelas_id', $kelas7B->id)->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)->firstOrFail();
    $luar->update(['kelas_id' => $kelas7B->id]);
    $luar->riwayatKelas()->delete();
    RiwayatKelas::create(['siswa_id' => $luar->id, 'kelas_id' => $kelas7B->id, 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'status' => 'aktif']);

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
        'status' => [$luar->id => 'hadir'],
    ])->assertSessionHasErrors('status.'.$luar->id);

    expect(Absensi::count())->toBe(0);
    expect($rombel7B->id)->not->toBe($rombel->id);
});

test('scan sukses mencatat hadir dengan jam', function () {
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '23:59']);
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    $response = $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), [
        'token' => $siswa->qr_token,
        'rombel_id' => $rombel->id,
    ]);

    $response->assertOk()->assertJson(['status' => 'hadir', 'baru' => true]);
    expect(Absensi::where('siswa_id', $siswa->id)->first()->metode)->toBe('qr');
});

test('zona waktu aplikasi bukan UTC', function () {
    // Absensi membandingkan jam scan dengan batas jam dari `Pengaturan` dan
    // mengambil tanggal dari `now()`. Bila zona waktu aplikasi kembali ke UTC,
    // seluruh jendela 00:00-06:59 WIB masuk ke tanggal sebelumnya dan siswa
    // yang datang tepat waktu ditandai terlambat. `zona-waktu:audit` dipakai
    // untuk menghitung dampak pada data yang sudah ada.
    expect(config('app.timezone'))->not->toBe('UTC');
});

test('scan tengah malam dicatat pada tanggal sekolah yang benar', function () {
    // Instan dikunci dalam UTC secara absolut, bukan lewat `Carbon::parse`
    // tanpa zona — kalau begitu "sekarang" ikut zona aplikasi dan testnya
    // selalu lolos apa pun zonanya, yaitu tidak membuktikan apa pun.
    //
    // 09 Oktober 17:30 UTC = 10 Oktober 00:30 WIB. Dengan zona UTC, scan ini
    // tersimpan tanggal 9 Oktober dan jam 17:30, lalu `'17:30' > '07:00'`
    // membuat siswa yang datang tengah malam ditandai terlambat.
    $this->travelTo(Carbon::parse('2026-10-09 17:30:00', 'UTC'));

    expect(now()->toDateString())->toBe('2026-10-10');

    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '07:00']);
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), [
        'token' => $siswa->qr_token,
        'rombel_id' => $rombel->id,
    ])->assertOk()->assertJson(['status' => 'hadir']);

    $absensi = Absensi::where('siswa_id', $siswa->id)->firstOrFail();

    expect($absensi->tanggal)->toBe('2026-10-10')
        ->and(substr((string) $absensi->jam_datang, 0, 5))->toBe('00:30');
});

test('scan larut malam tetap pada hari yang sama dan ditandai terlambat', function () {
    // 10 Oktober 16:30 UTC = 10 Oktober 23:30 WIB. Lewat batas 07:00, jadi
    // `terlambat` itu benar; yang dijaga adalah tanggalnya tetap 10 Oktober.
    $this->travelTo(Carbon::parse('2026-10-10 16:30:00', 'UTC'));

    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '07:00']);
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4002', 'Anak Absen Malam', $rombel);

    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), [
        'token' => $siswa->qr_token,
        'rombel_id' => $rombel->id,
    ])->assertOk()->assertJson(['status' => 'terlambat']);

    expect(Absensi::where('siswa_id', $siswa->id)->firstOrFail()->tanggal)->toBe('2026-10-10');
});

test('scan lewat batas tercatat terlambat', function () {
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '00:00']);
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), [
        'token' => $siswa->qr_token,
        'rombel_id' => $rombel->id,
    ])->assertOk()->assertJson(['status' => 'terlambat']);
});

test('scan token asing ditolak', function () {
    $rombel = rombelUjiAbsensi();

    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), [
        'token' => 'token-tidak-ada',
        'rombel_id' => $rombel->id,
    ])->assertUnprocessable()->assertJson(['message' => 'QR tidak dikenal.']);
});

test('scan ulang hari sama tidak duplikat', function () {
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '23:59']);
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $payload = ['token' => $siswa->qr_token, 'rombel_id' => $rombel->id];

    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), $payload)->assertOk();
    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), $payload)
        ->assertOk()->assertJson(['baru' => false]);

    expect(Absensi::where('siswa_id', $siswa->id)->count())->toBe(1);
});

test('scan susulan tidak menimpa jam dan status kehadiran pertama', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4002', 'Anak Scan Ganda');
    $payload = ['token' => $siswa->qr_token, 'rombel_id' => $rombel->id];

    // Scan pertama tepat waktu.
    $this->travelTo(now()->setTime(6, 55));
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '07:00']);
    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), $payload)
        ->assertOk()->assertJson(['status' => 'hadir', 'baru' => true]);
    $pertama = Absensi::where('siswa_id', $siswa->id)->firstOrFail();

    // Scan kedua setelah batas. Absensi tepat waktunya tidak boleh hilang dan
    // siswa tidak boleh dianggap terlambat hanya karena scan susulan.
    $this->travelTo(now()->setTime(8, 5));
    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), $payload)
        ->assertOk()->assertJson(['status' => 'hadir', 'baru' => false]);

    $absensi = Absensi::where('siswa_id', $siswa->id)->firstOrFail();

    expect($absensi->jam_datang)->toBe($pertama->jam_datang)
        ->and($absensi->status)->toBe('hadir')
        ->and(Absensi::where('siswa_id', $siswa->id)->count())->toBe(1);
});

test('scan susulan tetap mencatat terlambat bila baris pertama belum punya jam', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4003', 'Anak Scan Tanpa Jam');
    $payload = ['token' => $siswa->qr_token, 'rombel_id' => $rombel->id];

    // Baris manual sudah ada (mis. diisi guru), belum ada jam datang.
    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => now()->toDateString(),
        'status' => 'izin',
        'metode' => 'manual',
    ]);

    $this->travelTo(now()->setTime(8, 5));
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '07:00']);
    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), $payload)->assertOk();

    $absensi = Absensi::where('siswa_id', $siswa->id)->firstOrFail();
    expect($absensi->jam_datang)->not->toBeNull()
        ->and(Absensi::where('siswa_id', $siswa->id)->count())->toBe(1);
});

test('generate ulang token QR menghanguskan token lama', function () {
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu');
    $lama = $siswa->qr_token;

    $this->actingAs(superAdmin())->post(route('admin.siswas.qr', $siswa))->assertSessionHas('success');

    expect($siswa->fresh()->qr_token)->not->toBe($lama);
});

test('kartu menampilkan QR dari token', function () {
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu');

    $this->actingAs(superAdmin())->get(route('admin.siswas.kartu', $siswa))
        ->assertOk()->assertSee('<svg', false);
});
