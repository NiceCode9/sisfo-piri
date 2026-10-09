<?php

use App\Models\Absensi;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use App\Models\WaliMurid;
use App\Services\RiwayatSiswa;
use Database\Seeders\AkademikSeeder;
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
 * Dev DB hanya punya satu tahun ajaran dengan data contoh, sehingga tidak ada
 * apa pun untuk membuktikan rekonstruksi lintas tahun. Test di sini membangun
 * dua tahun sendiri supaya agregasi teruji pada kasus yang sebenarnya penting.
 */

function rombelRiwayat(string $kelas): Rombel
{
    return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $kelas))->firstOrFail();
}

function guruRiwayat(string $nama): Guru
{
    return Guru::where('nama', $nama)->firstOrFail();
}

function adminRiwayat(): User
{
    $user = User::factory()->create(['username' => 'admin-riwayat']);
    $user->assignRole('admin');

    return $user;
}

/**
 * Satu siswa dengan riwayat di dua tahun ajaran, tiga domain terisi.
 *
 * @return array{Siswa, User}
 */
function buatSiswaDuaTahun(): array
{
    $rombelAwal = rombelRiwayat('7A');
    $tahunAwal = $rombelAwal->tahun_ajaran_id;

    $user = User::factory()->create(['username' => 'siswa-riwayat', 'name' => 'Murid Riwayat']);
    $user->assignRole('siswa');

    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '9801',
        'nisn' => '008009801',
        'tahun_ajaran_id' => $tahunAwal,
        'kelas_id' => $rombelAwal->kelas_id,
        'is_aktif' => true,
        'no_hp_orang_tua' => '0812888001',
    ]);

    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombelAwal->kelas_id,
        'tahun_ajaran_id' => $tahunAwal,
        'status' => 'pindah',
    ]);

    // Tahun kedua. Dimulai Juli 2026 agar semester genap-nya jatuh di
    // Januari–Juni 2027, supaya bisa diisi absensi pada bulan yang wajar.
    $tahunKedua = TahunAjaran::create([
        'nama_tahun_ajaran' => '2027/2028',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => false,
    ]);
    $kelasKedua = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);
    $rombelKedua = Rombel::create([
        'kelas_id' => $kelasKedua->id,
        'tahun_ajaran_id' => $tahunKedua->id,
    ]);

    $siswa->update(['kelas_id' => $kelasKedua->id, 'tahun_ajaran_id' => $tahunKedua->id]);
    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $kelasKedua->id,
        'tahun_ajaran_id' => $tahunKedua->id,
        'status' => 'aktif',
    ]);

    // Domain 1 — absensi.
    //
    // Semester dihitung dari TAHUN tahun_mulai, bukan dari nama tahun ajaran:
    // tahun ajaran "2026/2027" dimulai Juli 2025, jadi semester ganjilnya
    // adalah Juli–Desember 2025 dan genapnya Januari–Juni 2026. Tahun kedua
    // dimulai Juli 2026, jadi genapnya Januari–Juni 2027.
    $tanggalAwal = [
        ['2025-09-01', 'hadir'],
        ['2025-09-02', 'hadir'],
        ['2025-09-03', 'alpa'],
    ];
    foreach ($tanggalAwal as [$tanggal, $status]) {
        Absensi::create([
            'rombel_id' => $rombelAwal->id,
            'siswa_id' => $siswa->id,
            'tanggal' => $tanggal,
            'status' => $status,
        ]);
    }

    $tanggalKedua = [
        ['2027-03-01', 'hadir'],
        ['2027-03-02', 'hadir'],
        ['2027-03-03', 'hadir'],
        ['2027-03-04', 'terlambat'],
    ];
    foreach ($tanggalKedua as [$tanggal, $status]) {
        Absensi::create([
            'rombel_id' => $rombelKedua->id,
            'siswa_id' => $siswa->id,
            'tanggal' => $tanggal,
            'status' => $status,
        ]);
    }

    // Domain 2 — e-learning: dua tugas dinilai di tahun pertama, tiga di tahun
    // kedua dengan satu yang sengaja tidak dikumpulkan (array kosong).
    $mapel = MataPelajaran::aktif()->firstOrFail();

    foreach ([78, 82] as $i => $nilai) {
        $tugas = Tugas::create([
            'rombel_id' => $rombelAwal->id,
            'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Tugas Ganjil '.($i + 1),
            'is_aktif' => true,
        ]);
        PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $siswa->id,
            'nilai' => $nilai,
        ]);
    }

    foreach ([[75], [85], []] as $i => $pasangan) {
        $tugas = Tugas::create([
            'rombel_id' => $rombelKedua->id,
            'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Tugas Genap '.($i + 1),
            'is_aktif' => true,
        ]);

        if ($pasangan) {
            PengumpulanTugas::create([
                'tugas_id' => $tugas->id,
                'siswa_id' => $siswa->id,
                'nilai' => $pasangan[0],
            ]);
        }
    }

    // Domain 3 — CBT: satu ujian per tahun.
    foreach ([[$rombelAwal, 'Ujian Ganjil', 71.5], [$rombelKedua, 'Ujian Genap', 88.0]] as [$rombel, $nama, $skor]) {
        $exam = Exam::create([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => $mapel->id,
            'name' => $nama,
            'duration_minutes' => 60,
            'status' => 'published',
            'created_by' => $user->id,
        ]);

        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'token' => strtolower(str_replace(' ', '', $nama)).'-'.$exam->id,
            'active_from' => now()->subDay(),
            'active_until' => now()->addDay(),
            'max_usage' => 50,
            'used_count' => 1,
            'is_active' => false,
            'created_by' => $user->id,
        ]);

        ExamSession::create([
            'exam_id' => $exam->id,
            'exam_token_id' => $token->id,
            'user_id' => $user->id,
            'status' => 'finished',
            'score' => $skor,
            'started_at' => now()->subHour(),
            'expected_end_at' => now()->addHour(),
            'finished_at' => now(),
        ]);
    }

    return [$siswa->fresh(), $user];
}

/*
 * Agregasi tiga domain.
 */

test('riwayat mencakup ketiga domain dan dikelompokkan per rombel', function () {
    [$siswa] = buatSiswaDuaTahun();

    $baris = RiwayatSiswa::untukSiswa($siswa);

    expect($baris)->toHaveCount(2);

    // Tahun terbaru dulu.
    expect($baris[0]['kelas'])->toBe('8A');
    expect($baris[1]['kelas'])->toBe('7A');

    foreach ($baris as $r) {
        expect($r['absensi']['total'])->toBeGreaterThan(0);
        expect($r['elearning'])->not->toBeEmpty();
        expect($r['cbt'])->not->toBeEmpty();
    }
});

test('rekap absensi menghitung hari dan bukan baris', function () {
    [$siswa] = buatSiswaDuaTahun();

    // Rombel kedua pada tahun yang sama. Students punya riwayat `aktif` di sana
    // juga, sehingga satu tanggal bisa punya baris di dua rombel — hanya itu
    // yang diizinkan constraint (siswa, rombel, tanggal), bukan dua baris di
    // rombel yang sama.
    $rombelKedua = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '8A'))->firstOrFail();
    $rombelKetiga = Rombel::create([
        'kelas_id' => Kelas::create(['nama_kelas' => '8B', 'tingkat' => '8'])->id,
        'tahun_ajaran_id' => $rombelKedua->tahun_ajaran_id,
    ]);

    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombelKetiga->kelas_id,
        'tahun_ajaran_id' => $rombelKetiga->tahun_ajaran_id,
        'status' => 'pindah',
    ]);

    Absensi::create([
        'rombel_id' => $rombelKetiga->id,
        'siswa_id' => $siswa->id,
        'tanggal' => '2027-03-01',
        'status' => 'izin',
    ]);

    $baris = RiwayatSiswa::untukSiswa($siswa);
    $tahunKedua = collect($baris)->firstWhere('kelas', '8A');

    // 8A tetap 4 hari: baris di 8B tidak boleh menambah hitungan 8A.
    expect($tahunKedua['absensi']['total'])->toBe(4);
    expect($tahunKedua['absensi']['hadir'])->toBe(3);
    expect($tahunKedua['absensi']['izin'])->toBe(0);
});

test('tugas yang belum dikumpulkan tetap terhitung sebagai beban', function () {
    [$siswa] = buatSiswaDuaTahun();
    $baris = RiwayatSiswa::untukSiswa($siswa);
    $tahunKedua = collect($baris)->firstWhere('kelas', '8A');
    $mapel = collect($tahunKedua['elearning'])->first();

    expect($mapel['ditugas'])->toBe(3);
    expect($mapel['dikumpul'])->toBe(2);
    expect($mapel['belumDikumpul'])->toBe(1);
    expect($mapel['rata'])->toBe(80.0);   // (75+85)/2
});

test('rata-rata nilai tugas dan skor ujian dihitung', function () {
    [$siswa] = buatSiswaDuaTahun();
    $baris = RiwayatSiswa::untukSiswa($siswa);

    $tahunPertama = collect($baris)->firstWhere('kelas', '7A');
    $tahunKedua = collect($baris)->firstWhere('kelas', '8A');

    expect($tahunPertama['elearning'][0]['rata'])->toBe(80.0);   // (78+82)/2
    expect($tahunPertama['cbtRata'])->toBe(71.5);
    expect($tahunKedua['cbtRata'])->toBe(88.0);
});

test('perAbsensi memakai pemecahan semester', function () {
    [$siswa] = buatSiswaDuaTahun();
    $baris = RiwayatSiswa::untukSiswa($siswa);

    $tahunPertama = collect($baris)->firstWhere('kelas', '7A');
    $tahunKedua = collect($baris)->firstWhere('kelas', '8A');

    // September 2025 -> semester ganjil 2026/2027; Maret 2027 -> genap 2027/2028.
    expect($tahunPertama['periode']['ganjil']['total'])->toBe(3);
    expect($tahunPertama['periode']['genap']['total'])->toBe(0);
    expect($tahunKedua['periode']['genap']['total'])->toBe(4);
    expect($tahunKedua['periode']['ganjil']['total'])->toBe(0);
});

test('siswa tanpa catatan tetap melihat rombel berjalan dengan angka nol', function () {
    $rombel = rombelRiwayat('7D');
    $user = User::factory()->create(['username' => 'siswa-kosong-riwayat']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '9899',
        'nisn' => '008009899',
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'kelas_id' => $rombel->kelas_id,
        'is_aktif' => true,
    ]);

    $baris = RiwayatSiswa::untukSiswa($siswa);

    // Bukan array kosong: rombel tempat siswa berada tetap ditampilkan, hanya
    // angkanya nol. Halaman kosong total akan disalahpahami sebagai error.
    expect($baris)->toHaveCount(1);
    expect($baris[0]['absensi']['total'])->toBe(0);
    expect($baris[0]['absensi']['persen'])->toBeNull();
    expect($baris[0]['cbt'])->toBe([]);
    expect($baris[0]['cbtRata'])->toBeNull();

    expect(RiwayatSiswa::ringkas($baris))->toMatchArray([
        'rombel' => 1,
        'hariAbsen' => 0,
        'persenAbsen' => null,
        'tugas' => 0,
    ]);
});

/*
 * Akses: siswa, guru, admin, orang tua.
 */

test('siswa melihat riwayat sendiri', function () {
    [$siswa, $user] = buatSiswaDuaTahun();

    // Tampilan meringkas per mapel, bukan daftar judul tugas.
    $respons = $this->actingAs($user)->get(route('siswa.riwayat'));
    $respons->assertOk();

    // `8A` dan `7A` diperiksa lewat htmlTanpaToken(): jarum dua karakter
    // berisiko menabrak token CSRF acak pada halaman.
    $html = htmlTanpaToken($respons->getContent());

    expect($html)->toContain('8A');
    expect($html)->toContain('7A');

    $respons->assertSee('Ujian Genap')
        ->assertSee('Ujian Ganjil')
        ->assertSee('Kehadiran')
        ->assertSee('E-Learning')
        ->assertSee('Nilai CBT');
});

test('guru yang mengampu rombel boleh melihat riwayat siswa', function () {
    [$siswa] = buatSiswaDuaTahun();
    $guru = guruRiwayat('Guru A'); // wali 7A + pengampu MTK di 7A/7B
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->get(route('admin.siswas.riwayat', $siswa))
        ->assertOk()
        ->assertSee('Ujian Genap');
});

test('guru yang tidak mengampu rombel ditolak', function () {
    [$siswa] = buatSiswaDuaTahun();
    $guru = guruRiwayat('Guru D'); // hanya 7C/7D
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->get(route('admin.siswas.riwayat', $siswa))->assertForbidden();
});

test('guru tanpa penugasan ditolak', function () {
    [$siswa] = buatSiswaDuaTahun();
    $user = User::factory()->create(['username' => 'guru-kosong-riwayat']);
    $user->assignRole('guru');
    Guru::create([
        'user_id' => $user->id,
        'nip' => '197002021990022009',
        'nama' => 'Guru Kosong',
        'jenis_kelamin' => 'P',
        'is_aktif' => true,
    ]);

    $this->actingAs($user)->get(route('admin.siswas.riwayat', $siswa))->assertForbidden();
});

test('admin boleh melihat riwayat siswa mana pun', function () {
    [$siswa] = buatSiswaDuaTahun();

    $this->actingAs(adminRiwayat())->get(route('admin.siswas.riwayat', $siswa))
        ->assertOk()
        ->assertSee('Ujian Ganjil');
});

test('orang tua melihat riwayat anaknya sendiri', function () {
    [$siswa] = buatSiswaDuaTahun();
    $wali = WaliMurid::where('siswa_id', $siswa->id)->firstOrFail();

    $this->actingAs($wali->user)->get(route('ortu.anak.riwayat', $wali))
        ->assertOk()
        ->assertSee('Ujian Genap');
});

test('orang tua ditolak melihat riwayat anak orang lain', function () {
    [$siswa] = buatSiswaDuaTahun();

    $rombelLain = rombelRiwayat('7D');
    $userLain = User::factory()->create(['username' => 'ortu-lain-riwayat']);
    $userLain->assignRole('orang-tua');
    $siswaLain = Siswa::create([
        'user_id' => $userLain->id,
        'nis' => '9799',
        'nisn' => '008009799',
        'tahun_ajaran_id' => $rombelLain->tahun_ajaran_id,
        'kelas_id' => $rombelLain->kelas_id,
        'is_aktif' => true,
    ]);
    $waliLain = WaliMurid::where('siswa_id', $siswaLain->id)->firstOrFail();

    $wali = WaliMurid::where('siswa_id', $siswa->id)->firstOrFail();

    $this->actingAs($wali->user)->get(route('ortu.anak.riwayat', $waliLain))->assertForbidden();
});

test('siswa tanpa peran tidak bisa membuka halaman riwayat siswa lain', function () {
    [$siswa] = buatSiswaDuaTahun();

    $this->get(route('admin.siswas.riwayat', $siswa))->assertRedirect(route('login'));
});

test('halaman riwayat tidak memuat nilai rapor', function () {
    [$siswa, $user] = buatSiswaDuaTahun();

    $html = $this->actingAs($user)->get(route('siswa.riwayat'))->assertOk()->getContent();

    // Batas yang disepakati: read-only, tanpa predikat/peringkatan rapor.
    expect($html)->not->toContain('Predikat');
    expect($html)->not->toContain('Peringkat');
    expect($html)->not->toContain('Rata-rata Rapor');
});
