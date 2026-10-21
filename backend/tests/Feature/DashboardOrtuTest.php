<?php

/**
 * Dashboard orang tua — blok yang ditambahkan.
 *
 * Dua hal yang dijaga. Pertama, query-nya tidak boleh bertambah per anak; itu
 * sudah dijaga `OrtuViewTest`, tapi blok di sini tidak boleh menyelundupkan
 * query per anak lewat jalan lain. Kedua, angka harus milik keluarga sendiri —
 * satu akun orang tua bisa punya beberapa anak, dan itu bukan Opportunities
 * untuk melihat data keluarga orang lain.
 */

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Pengumuman;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use App\Models\WaliMurid;
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

function rombelOrtu(string $kelas): Rombel
{
    return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $kelas))
        ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
        ->firstOrFail();
}

/**
 * Anak yang tertaut ke orang tua. Kalau $ortu diisi, anak itu langsung
 * tertaut ke akun tersebut -- satu nomor HP bisa menaungi kakak-beradik.
 *
 * @return array{0: User, 1: Siswa}
 */
function anakOrtu(string $nis, string $nama, Rombel $rombel, ?User $ortu = null): array
{
    $ortu ??= tap(User::factory()->create(['username' => 'ortu-'.$nis, 'name' => 'Wali '.$nama]), function ($u) {
        $u->assignRole('orang-tua');
    });

    $siswaUser = User::factory()->create(['name' => $nama]);
    $siswaUser->assignRole('siswa');

    $siswa = Siswa::create([
        'user_id' => $siswaUser->id,
        'nis' => $nis,
        'nisn' => '0077'.$nis,
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

    WaliMurid::create(['user_id' => $ortu->id, 'siswa_id' => $siswa->id]);

    return [$ortu, $siswa];
}

test('rata-rata nilai tugas per anak dihitung dari yang sudah dinilai', function () {
    $rombel = rombelOrtu('7A');
    [$ortu, $anak] = anakOrtu('8201', 'Anak Nilai', $rombel);
    $mapel = MataPelajaran::factory()->create();

    // Dua tugas berbeda: `pengumpulan_tugas` unik pada (tugas_id, siswa_id), jadi
    // satu tugas tidak bisa menyimpan dua nilai untuk anak yang sama.
    foreach ([80, 100] as $nilai) {
        $tugas = Tugas::create([
            'rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Tugas Bernilai '.$nilai, 'is_aktif' => true,
        ]);

        PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $anak->id,
            'nilai' => $nilai,
        ]);
    }

    $this->actingAs($ortu)
        ->get(route('ortu.dashboard'))
        ->assertOk()
        ->assertSee('Rata-rata tugas')
        ->assertSee('90.0');
});

test('anak tanpa nilai tugas menampilkan tanda pisah bukan nol', function () {
    $rombel = rombelOrtu('7A');
    [$ortu] = anakOrtu('8202', 'Anak Tanpa Nilai', $rombel);

    $this->actingAs($ortu)
        ->get(route('ortu.dashboard'))
        ->assertOk()
        ->assertSee('Anak Tanpa Nilai')
        ->assertSee('—', false);
});

test('tugas yang sudah dikumpulkan tidak masuk daftar menunggu', function () {
    $rombel = rombelOrtu('7A');
    [$ortu, $anak] = anakOrtu('8203', 'Anak Tugas', $rombel);
    $mapel = MataPelajaran::factory()->create();

    $menunggu = Tugas::create([
        'rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id,
        'judul' => 'Tugas MenungguDikumpulkan', 'is_aktif' => true,
    ]);

    $selesai = Tugas::create([
        'rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id,
        'judul' => 'Tugas SudahDikumpulkan', 'is_aktif' => true,
    ]);

    PengumpulanTugas::create([
        'tugas_id' => $selesai->id,
        'siswa_id' => $anak->id,
        'nilai' => 90,
    ]);

    $html = $this->actingAs($ortu)->get(route('ortu.dashboard'))->assertOk()->getContent();

    expect($html)->toContain('Tugas MenungguDikumpulkan')
        ->and($html)->not->toContain('Tugas SudahDikumpulkan');
});

test('pengumpulan salah anak tidak menutupi tugas anak sendiri', function () {
    // Dua anak di kelas sama, tugas sama. Anak B sudah mengumpulkan, anak A
    // belum. Kalau penyaringan hanya melihat "ada pengumpulan untuk tugas ini",
    // keduanya akan hilang.
    $rombel = rombelOrtu('7A');
    [$ortu] = anakOrtu('8204', 'Anak Belum', $rombel);
    [, $anakB] = anakOrtu('8205', 'Anak Sudah', $rombel, $ortu);

    expect($ortu->waliMurids()->count())->toBe(2);

    // Dua tugas. Anak B mengumpulkan hanya yang pertama; anak A tidak
    // mengumpulkan keduanya.
    //
    // Jumlahnya sengaja berbeda antar anak (2 vs 1) supaya pencacah di kartu
    // bisa membedakan. Kalau penyaringan salah dan pengumpulan anak B tidak
    // terbaca, keduanya jadi 2 dan angka anak B hilang begitu saja — dan itu
    // tidak akan terlihat kalau kedua anak punya jumlah yang sama.
    foreach ([1, 2] as $urut => $urutan) {
        $tugas = Tugas::create([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'judul' => 'Tugas '.$urutan.' Dipakai Dua Anak', 'is_aktif' => true,
        ]);

        if ($urut === 1) {
            PengumpulanTugas::create([
                'tugas_id' => $tugas->id, 'siswa_id' => $anakB->id, 'nilai' => 88,
            ]);
        }
    }

    $html = $this->actingAs($ortu)->get(route('ortu.dashboard'))->assertOk()->getContent();

    expect($html)->toContain('Anak Belum')->toContain('Anak Sudah')
        // Benar: anak A punya 2 menunggu, anak B punya 1.
        ->and($html)->toContain('2 tugas belum dikerjakan')
        ->and($html)->toContain('1 tugas belum dikerjakan')
        // Salah kalau anak B ikut dapat 2.
        ->and($html)->not->toContain('4 tugas belum dikerjakan')
        // Rata-rata anak B hanya muncul kalau pengumulannya terbaca.
        ->and($html)->toContain('88.0');
});

test('tugas kelas lain tidak muncul untuk orang tua', function () {
    $rombelAnak = rombelOrtu('7A');
    [$ortu] = anakOrtu('8206', 'Anak Tetangga', $rombelAnak);
    $rombelLain = rombelOrtu('7B');

    Tugas::create([
        'rombel_id' => $rombelLain->id,
        'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
        'judul' => 'Tugas Kelas Tetangga', 'is_aktif' => true,
    ]);

    $this->actingAs($ortu)
        ->get(route('ortu.dashboard'))
        ->assertOk()
        ->assertDontSee('Tugas Kelas Tetangga');
});

test('pengumuman aktif tampil, yang nonaktif tidak', function () {
    $rombel = rombelOrtu('7A');
    [$ortu, $anak] = anakOrtu('8207', 'Anak Pengumuman', $rombel);
    $tahunId = $anak->tahun_ajaran_id;

    Pengumuman::create([
        'tahun_ajaran_id' => $tahunId, 'judul' => 'Info Untuk Orang Tua',
        'isi' => 'Isi pengumuman.', 'tanggal_pengumuman' => now(), 'status_aktif' => true,
    ]);

    Pengumuman::create([
        'tahun_ajaran_id' => $tahunId, 'judul' => 'Info Lama Mati',
        'isi' => 'Jangan tampil.', 'tanggal_pengumuman' => now(), 'status_aktif' => false,
    ]);

    $this->actingAs($ortu)
        ->get(route('ortu.dashboard'))
        ->assertOk()
        ->assertSee('Info Untuk Orang Tua')
        ->assertDontSee('Info Lama Mati');
});

test('nilai CBT dilabeli terakhir, bukan nilai rapor', function () {
    // Rapor memakai skor terbaik dalam satu rantai remedial; angka di sini cuma
    // sesi terakhir. Kalau labelnya sama, orang tua akan mengira keduanya sama.
    $rombel = rombelOrtu('7A');
    [$ortu, $anak] = anakOrtu('8208', 'Anak Ujian', $rombel);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $exam = Exam::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
        'name' => 'Ujian Selesai',
        'duration_minutes' => 60,
        'max_violation_count' => 3,
        'status' => 'published',
        'created_by' => $admin->id,
    ]);

    $token = ExamToken::create([
        'exam_id' => $exam->id, 'token' => 'TOK-'.uniqid(),
        'active_from' => now()->subHour(), 'active_until' => now()->addHour(),
        'created_by' => $admin->id,
    ]);

    ExamSession::create([
        'exam_id' => $exam->id, 'exam_token_id' => $token->id,
        'user_id' => $anak->user_id, 'started_at' => now()->subHour(),
        'expected_end_at' => now()->addHour(), 'finished_at' => now()->subHour(),
        'status' => 'finished', 'score' => 72,
    ]);

    $this->actingAs($ortu)
        ->get(route('ortu.dashboard'))
        ->assertOk()
        ->assertSee('Nilai CBT terakhir')
        ->assertSee('72');
});

test('anak keluarga lain tidak pernah muncul', function () {
    $rombel = rombelOrtu('7A');
    [$ortu] = anakOrtu('8209', 'Anak Sendiri', $rombel);

    // Anak keluarga lain di kelas yang sama -- dengan orang tua yang berbeda.
    [$ortuLain] = anakOrtu('8210', 'Anak Orang Lain', $rombel);

    $this->actingAs($ortu)
        ->get(route('ortu.dashboard'))
        ->assertOk()
        ->assertSee('Anak Sendiri')
        ->assertDontSee('Anak Orang Lain');
});

test('akun tanpa anak mendapat dashboard yang tidak error', function () {
    $ortu = User::factory()->create(['username' => 'ortu-kosong']);
    $ortu->assignRole('orang-tua');

    $this->actingAs($ortu)
        ->get(route('ortu.dashboard'))
        ->assertOk()
        ->assertSee('Belum ada anak tertaut ke akun ini');
});
