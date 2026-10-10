<?php

/**
 * Regresi M7: ujian berulang lewat token, dan kebijakan nilai remedial.
 *
 * SEBELUM: `exam_sessions` punya `unique(['exam_token_id','user_id'])`, sehingga
 * "sudah menyelesaikan ujian ini" hanya berlaku per token. Token berikutnya
 * untuk ujian yang sama membuka akses ulang.
 *
 * SESUDAH: uniqueness per `exam_id`. Remedial dibuat lewat baris `exams` baru
 * berpenanda `is_remedial` + `remedial_of_id`, dan rapor memakai skor terbaik
 * dalam satu rantai — tanpa pernah menggabungkan UH-1 dengan UH-2.
 */

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\RiwayatSiswa;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

function rombelM7(string $kelas = '7A'): Rombel
{
    $tahun = TahunAjaran::create([
        'nama_tahun_ajaran' => 'M7-'.Str::random(3),
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => false,
    ]);
    $k = Kelas::create(['nama_kelas' => $kelas.'-'.Str::random(3), 'tingkat' => '7']);

    return Rombel::create(['kelas_id' => $k->id, 'tahun_ajaran_id' => $tahun->id]);
}

function siswaM7(Rombel $rombel): array
{
    $user = User::factory()->create();
    $user->assignRole('siswa');

    // `ExamSessionController::join()` membandingkan kelas + tahun ajaran siswa
    // dengan rombel ujian, jadi keduanya wajib diisi di fixture.
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '9'.Str::random(6),
        'nisn' => '00'.Str::random(8),
        'kelas_id' => $rombel->kelas_id,
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'is_aktif' => true,
    ]);

    return [$siswa, $user];
}

function tokenM7(Exam $exam, string $token): ExamToken
{
    return ExamToken::create([
        'exam_id' => $exam->id,
        'token' => $token,
        'active_from' => now()->subHour(),
        'active_until' => now()->addHour(),
        'created_by' => $exam->created_by,
    ]);
}

function sesiM7(Exam $exam, ExamToken $token, User $user, string $status = 'finished', ?float $skor = 70): ExamSession
{
    return ExamSession::create([
        'exam_id' => $exam->id,
        'exam_token_id' => $token->id,
        'user_id' => $user->id,
        'started_at' => now()->subHour(),
        'expected_end_at' => now()->addHour(),
        'finished_at' => $status === 'ongoing' ? null : now(),
        'status' => $status,
        'score' => $skor,
    ]);
}

/*
 * Bagian 1 — batas unik & aturan join
 */

test('unik sesi sekarang per ujian, bukan per token', function () {
    $columns = collect(Schema::getIndexes('exam_sessions'))
        ->filter(fn ($i) => $i['unique'] === true)
        ->map(fn ($i) => $i['columns']);

    $adaExam = $columns->contains(fn ($c) => $c === ['exam_id', 'user_id']);
    $adaToken = $columns->contains(fn ($c) => $c === ['exam_token_id', 'user_id']);

    expect($adaExam)->toBeTrue()
        ->and($adaToken)->toBeFalse();
});

test('siswa yang sudah selesai tidak bisa join lewat token lain', function () {
    $rombel = rombelM7();
    $exam = Exam::factory()->create(['rombel_id' => $rombel->id]);
    [$siswa, $user] = siswaM7($rombel);

    $tokenA = tokenM7($exam, 'TOKENA1');
    $tokenB = tokenM7($exam, 'TOKENB1');

    sesiM7($exam, $tokenA, $user, 'finished', 70);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/cbt/exam/join', ['token' => $tokenB->token])
        ->assertStatus(422)
        ->assertJsonValidationErrors('token');
});

test('sesi expired juga memblokir — laptop mati bukan akses ulang', function () {
    $rombel = rombelM7();
    $exam = Exam::factory()->create(['rombel_id' => $rombel->id]);
    [, $user] = siswaM7($rombel);

    $tokenA = tokenM7($exam, 'EXPIRED1');
    $tokenB = tokenM7($exam, 'EXPIRED2');

    sesiM7($exam, $tokenA, $user, 'expired', null);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/cbt/exam/join', ['token' => $tokenB->token])
        ->assertStatus(422)
        ->assertJsonValidationErrors('token');
});

test('sesi disqualified juga memblokir', function () {
    $rombel = rombelM7();
    $exam = Exam::factory()->create(['rombel_id' => $rombel->id]);
    [, $user] = siswaM7($rombel);

    $tokenA = tokenM7($exam, 'DISQ0001');
    $tokenB = tokenM7($exam, 'DISQ0002');

    sesiM7($exam, $tokenA, $user, 'disqualified', 0);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/cbt/exam/join', ['token' => $tokenB->token])
        ->assertStatus(422);
});

test('sesi ongoing di-resume lewat token lain tanpa kehilangan waktu', function () {
    $rombel = rombelM7();
    $exam = Exam::factory()->create(['rombel_id' => $rombel->id]);
    [, $user] = siswaM7($rombel);

    $tokenA = tokenM7($exam, 'RESUME01');
    $tokenB = tokenM7($exam, 'RESUME02');

    $sesi = sesiM7($exam, $tokenA, $user, 'ongoing', null);
    $sesi->update(['expected_end_at' => now()->addMinutes(30)]);

    $respons = $this->actingAs($user, 'sanctum')
        ->postJson('/api/cbt/exam/join', ['token' => $tokenB->token])
        ->assertOk();

    // Sesi yang dikembalikan harus yang lama, bukan dibuat baru.
    expect($respons->json('exam_session_id'))->toBe($sesi->id)
        ->and(ExamSession::where('user_id', $user->id)->count())->toBe(1);
});

test('siswa berbeda tetap bisa memakai token yang sama', function () {
    $rombel = rombelM7();
    $exam = Exam::factory()->create(['rombel_id' => $rombel->id]);
    [, $userA] = siswaM7($rombel);
    [, $userB] = siswaM7($rombel);

    $token = tokenM7($exam, 'BERSAMA1');

    sesiM7($exam, $token, $userA, 'finished', 70);

    $this->actingAs($userB, 'sanctum')
        ->postJson('/api/cbt/exam/join', ['token' => $token->token])
        ->assertOk();

    expect(ExamSession::where('exam_id', $exam->id)->count())->toBe(2);
});

/*
 * Bagian 2 — validasi penanda remedial
 */

function adminM7(): User
{
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    return $user;
}

function remedialPayload(Exam $asal, array $extra = []): array
{
    return array_merge([
        'rombel_id' => $asal->rombel_id,
        'mata_pelajaran_id' => $asal->mata_pelajaran_id,
        'name' => 'Ujian Remedial',
        'duration_minutes' => 60,
        'max_violation_count' => 3,
        'status' => 'draft',
        'is_remedial' => '1',
        'remedial_of_id' => $asal->id,
    ], $extra);
}

test('remedial disimpan dengan penanda yang benar', function () {
    $admin = adminM7();
    $rombel = rombelM7();
    $mapel = MataPelajaran::factory()->create();
    $asal = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id]);

    $this->actingAs($admin)->post(route('admin.cbt.exams.store'), remedialPayload($asal))
        ->assertRedirect();

    $remedial = Exam::where('name', 'Ujian Remedial')->first();

    expect($remedial->is_remedial)->toBeTrue()
        ->and($remedial->remedial_of_id)->toBe($asal->id)
        ->and($remedial->remedialOf->id)->toBe($asal->id)
        ->and($asal->remidals()->count())->toBe(1);
});

test('remedial wajib menunjuk ujian asal', function () {
    $admin = adminM7();
    $rombel = rombelM7();
    $asal = Exam::factory()->create(['rombel_id' => $rombel->id]);

    $this->actingAs($admin)->post(route('admin.cbt.exams.store'), remedialPayload($asal, ['remedial_of_id' => null]))
        ->assertSessionHasErrors('remedial_of_id');
});

test('remedial dari mapel lain ditolak', function () {
    $admin = adminM7();
    $rombel = rombelM7();
    $asal = Exam::factory()->create(['rombel_id' => $rombel->id]);

    $mapelLain = MataPelajaran::factory()->create();

    $this->actingAs($admin)->post(route('admin.cbt.exams.store'), remedialPayload($asal, [
        'mata_pelajaran_id' => $mapelLain->id,
    ]))->assertSessionHasErrors('remedial_of_id');
});

test('remedial dari rombel lain ditolak', function () {
    $admin = adminM7();
    $asal = Exam::factory()->create();

    $rombelLain = rombelM7('7B');

    $this->actingAs($admin)->post(route('admin.cbt.exams.store'), remedialPayload($asal, [
        'rombel_id' => $rombelLain->id,
    ]))->assertSessionHasErrors('remedial_of_id');
});

test('ujian tidak bisa menjadi remedial dirinya sendiri', function () {
    $admin = adminM7();
    $exam = Exam::factory()->create();

    $this->actingAs($admin)->put(route('admin.cbt.exams.update', $exam), [
        'rombel_id' => $exam->rombel_id,
        'mata_pelajaran_id' => $exam->mata_pelajaran_id,
        'name' => $exam->name,
        'duration_minutes' => 60,
        'max_violation_count' => 3,
        'status' => 'draft',
        'is_remedial' => '1',
        'remedial_of_id' => $exam->id,
    ])->assertSessionHasErrors('remedial_of_id');
});

test('remedial tidak boleh menunjuk remedial lain (rantai bercabang)', function () {
    $admin = adminM7();
    $rombel = rombelM7();
    $mapel = MataPelajaran::factory()->create();
    $asal = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id]);
    $remedial1 = Exam::factory()->create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'is_remedial' => true,
        'remedial_of_id' => $asal->id,
    ]);

    $this->actingAs($admin)->post(route('admin.cbt.exams.store'), remedialPayload($remedial1))
        ->assertSessionHasErrors('remedial_of_id');
});

test('tanpa centang remedial, penanda dibuang bersih', function () {
    $admin = adminM7();
    $rombel = rombelM7();
    $mapel = MataPelajaran::factory()->create();
    $asal = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id]);

    // Checkbox yang tidak dicentang tidak mengirim key sama sekali — itu yang
    // dilakukan browser. Mengirim `is_remedial => null` akan gagal validasi
    // `boolean`, jadi key-nya dibuang seluruhnya di sini.
    $payload = remedialPayload($asal);
    unset($payload['is_remedial'], $payload['remedial_of_id']);

    $this->actingAs($admin)->post(route('admin.cbt.exams.store'), $payload)
        ->assertSessionHasNoErrors();

    $examBaru = Exam::where('name', 'Ujian Remedial')->first();

    expect($examBaru->is_remedial)->toBeFalse()
        ->and($examBaru->remedial_of_id)->toBeNull();
});

/*
 * Bagian 3 — rekap nilai: remedial digabung, UH-1/UH-2 tidak
 */

function nilaiM7(Exam $exam, ExamToken $token, User $user, float $skor): void
{
    sesiM7($exam, $token, $user, 'finished', $skor);
}

test('rapor menggabungkan remedial ke ujian asal dan memakai skor terbaik', function () {
    $rombel = rombelM7();
    $mapel = MataPelajaran::factory()->create();
    [$siswa, $user] = siswaM7($rombel);

    $asal = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'name' => 'Ujian Awal']);
    $remedial = Exam::factory()->create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'name' => 'Ujian Remedial',
        'is_remedial' => true,
        'remedial_of_id' => $asal->id,
    ]);

    nilaiM7($asal, tokenM7($asal, 'AWAL0001'), $user, 55);
    nilaiM7($remedial, tokenM7($remedial, 'REM00001'), $user, 88);

    $baris = collect(RiwayatSiswa::untukSiswa($siswa))->first();
    $cbt = $baris['cbt'];

    expect($cbt)->toHaveCount(1)
        ->and($cbt[0]['skor'])->toBe(88.0)
        ->and($cbt[0]['jumlahPercobaan'])->toBe(2)
        ->and($cbt[0]['adalahRemedial'])->toBeTrue()
        ->and($cbt[0]['semuaPercobaan'])->toHaveCount(2)
        // Nilai gagal tidak boleh ikut meracuni rata-rata.
        ->and($baris['cbtRata'])->toBe(88.0);
});

test('remedial yang nilainya lebih buruk tidak menurunkan nilai rapor', function () {
    $rombel = rombelM7();
    $mapel = MataPelajaran::factory()->create();
    [$siswa, $user] = siswaM7($rombel);

    $asal = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id]);
    $remedial = Exam::factory()->create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'is_remedial' => true,
        'remedial_of_id' => $asal->id,
    ]);

    nilaiM7($asal, tokenM7($asal, 'AWAL0002'), $user, 90);
    nilaiM7($remedial, tokenM7($remedial, 'REM00002'), $user, 60);

    $baris = collect(RiwayatSiswa::untukSiswa($siswa))->first();

    expect($baris['cbt'][0]['skor'])->toBe(90.0)
        ->and($baris['cbtRata'])->toBe(90.0);
});

test('UH-1 dan UH-2 pada mapel sama TIDAK digabung', function () {
    $rombel = rombelM7();
    $mapel = MataPelajaran::factory()->create();
    [$siswa, $user] = siswaM7($rombel);

    // Dua penilaian biasa pada mapel yang sama. Mengelompokkan per mapel saja
    // akan membuang salah satunya dan menaikkan rata-rata.
    $uh1 = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'name' => 'UH 1']);
    $uh2 = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'name' => 'UH 2']);

    nilaiM7($uh1, tokenM7($uh1, 'UH1X0001'), $user, 55);
    nilaiM7($uh2, tokenM7($uh2, 'UH2X0001'), $user, 88);

    $baris = collect(RiwayatSiswa::untukSiswa($siswa))->first();
    $cbt = $baris['cbt'];

    expect($cbt)->toHaveCount(2)
        ->and($cbt[0]['jumlahPercobaan'])->toBe(1)
        ->and($cbt[1]['jumlahPercobaan'])->toBe(1)
        ->and($cbt[0]['adalahRemedial'])->toBeFalse()
        ->and($cbt[1]['adalahRemedial'])->toBeFalse()
        // Keduanya dihitung: (55 + 88) / 2 = 71.5
        ->and($baris['cbtRata'])->toBe(71.5);
});

test('dua remedial dari ujian yang sama tetap satu kelompok', function () {
    $rombel = rombelM7();
    $mapel = MataPelajaran::factory()->create();
    [$siswa, $user] = siswaM7($rombel);

    $asal = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id]);
    $rem1 = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'is_remedial' => true, 'remedial_of_id' => $asal->id]);
    $rem2 = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'is_remedial' => true, 'remedial_of_id' => $asal->id]);

    nilaiM7($asal, tokenM7($asal, 'AWAL0003'), $user, 40);
    nilaiM7($rem1, tokenM7($rem1, 'REM1X001'), $user, 70);
    nilaiM7($rem2, tokenM7($rem2, 'REM2X001'), $user, 60);

    $baris = collect(RiwayatSiswa::untukSiswa($siswa))->first();

    expect($baris['cbt'])->toHaveCount(1)
        ->and($baris['cbt'][0]['skor'])->toBe(70.0)
        ->and($baris['cbt'][0]['jumlahPercobaan'])->toBe(3);
});

test('kelompok yang semua skornya null tetap tampil sebagai baris', function () {
    $rombel = rombelM7();
    $mapel = MataPelajaran::factory()->create();
    [$siswa, $user] = siswaM7($rombel);

    $exam = Exam::factory()->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id]);

    // Diskualifikasi: skor null, tidak pernah mengalah dari angka.
    nilaiM7($exam, tokenM7($exam, 'DISQ0003'), $user, 0);
    ExamSession::where('exam_id', $exam->id)->update(['status' => 'disqualified', 'score' => null]);

    $baris = collect(RiwayatSiswa::untukSiswa($siswa))->first();

    expect($baris['cbt'])->toHaveCount(1)
        ->and($baris['cbt'][0]['skor'])->toBeNull()
        ->and($baris['cbt'][0]['status'])->toBe('disqualified')
        ->and($baris['cbtRata'])->toBeNull();
});

test('remedial lintas rombel tidak tercampur karena rekap per rombel', function () {
    $rombelA = rombelM7('8A');
    $rombelB = rombelM7('8B');
    $mapel = MataPelajaran::factory()->create();
    [$siswa, $user] = siswaM7($rombelA);

    // Ujian asal tetap di 8A, remedial di 8B. Admin sebenarnya tidak bisa
    // membuat begini (ada guard rombel sama), tapi rekap tetap tidak boleh
    // menggabungkannya.
    $asal = Exam::factory()->create(['rombel_id' => $rombelA->id, 'mata_pelajaran_id' => $mapel->id]);
    $remedial = Exam::factory()->create([
        'rombel_id' => $rombelB->id,
        'mata_pelajaran_id' => $mapel->id,
        'is_remedial' => true,
        'remedial_of_id' => $asal->id,
    ]);

    nilaiM7($asal, tokenM7($asal, 'RCA00001'), $user, 60);
    nilaiM7($remedial, tokenM7($remedial, 'RCB00001'), $user, 90);

    $baris = collect(RiwayatSiswa::untukSiswa($siswa));

    // Masing-masing masuk ke baris rombelnya sendiri.
    expect($baris)->toHaveCount(2);
});
