<?php

/**
 * Regresi keamanan CBT: isolasi antar guru.
 *
 * Semua route admin CBT berada di bawah middleware('auth') saja; sebelumnya
 * tidak satu pun memanggil ExamPolicy, sehingga siapa pun pemegang
 * cbt.view bisa melihat nilai semua siswa dan cbt.manage bisa menghapus
 * ujian guru lain. Test ini mengunci perilaku itu.
 */

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

/**
 * Guru yang mengampu mapel $mapel di rombel $rombel, memakai role guru.
 */
function guruPengampu(Rombel $rombel, MataPelajaran $mapel): User
{
    $user = User::factory()->create();
    $user->assignRole('guru');

    $guru = Guru::create([
        'user_id' => $user->id,
        'nip' => 'NIP'.Str::upper(Str::random(6)),
        'nama' => $user->name,
        'jenis_kelamin' => 'L',
        'is_aktif' => true,
    ]);

    Pengampu::create([
        'guru_id' => $guru->id,
        'mata_pelajaran_id' => $mapel->id,
        'rombel_id' => $rombel->id,
    ]);

    return $user;
}

function rombelIsolasi(): Rombel
{
    $tahun = TahunAjaran::create([
        'nama_tahun_ajaran' => 'ISO-'.Str::random(3),
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => false,
    ]);
    $kelas = Kelas::create(['nama_kelas' => 'ISO-'.Str::random(3), 'tingkat' => '10']);

    return Rombel::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);
}

function mapelIsolasi(): MataPelajaran
{
    return MataPelajaran::create([
        'kode' => 'MP'.Str::upper(Str::random(4)),
        'nama' => 'Mapel '.Str::random(4),
        'kelompok' => 'A',
    ]);
}

function examMilik(Rombel $rombel, MataPelajaran $mapel): Exam
{
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    return Exam::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'name' => 'Ujian '.Str::random(4),
        'duration_minutes' => 60,
        'max_violation_count' => 3,
        'status' => 'published',
        'created_by' => $admin->id,
    ]);
}

function sesiMilik(Exam $exam): ExamSession
{
    $siswa = User::factory()->create();
    $siswa->assignRole('siswa');

    $token = ExamToken::create([
        'exam_id' => $exam->id,
        'token' => strtoupper(Str::random(12)),
        'active_from' => now()->subHour(),
        'active_until' => now()->addHour(),
        'created_by' => $exam->created_by,
    ]);

    return ExamSession::create([
        'exam_id' => $exam->id,
        'exam_token_id' => $token->id,
        'user_id' => $siswa->id,
        'started_at' => now(),
        'expected_end_at' => now()->addHour(),
        'status' => 'finished',
        'score' => 75,
    ]);
}

test('guru lain tidak bisa membuka halaman ujian yang bukan miliknya', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);

    // Guru yang mengampu rombel+mapel yang SAMA tidak otomatis boleh
    // resurrected bila rombel/mapel-nya berbeda.
    $rombelLain = rombelIsolasi();
    $mapelLain = mapelIsolasi();
    $penyusup = guruPengampu($rombelLain, $mapelLain);

    $this->actingAs($penyusup)
        ->get(route('admin.cbt.exams.show', $exam))
        ->assertForbidden();
});

test('guru yang mengampu boleh membuka ujiannya sendiri', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);
    $pemilik = guruPengampu($rombel, $mapel);

    $this->actingAs($pemilik)
        ->get(route('admin.cbt.exams.show', $exam))
        ->assertOk();
});

test('guru yang mengampu melihat ujiannya sendiri di daftar', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);
    $pemilik = guruPengampu($rombel, $mapel);

    // Regresi: ExamController::index() dulu menulis ulang daftar rombel sendiri;
    // sekarang lewat Rombel::terjangkauUser(). Guru yang sah harus tetap
    // melihat ujiannya.
    $this->actingAs($pemilik)
        ->get(route('admin.cbt.exams.index'))
        ->assertOk()
        ->assertSee($exam->name);
});

test('guru tanpa penugasan melihat nol ujian, bukan seluruh ujian', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);

    // Guru tanpa baris Guru sama sekali -> harus nol, bukan semua.
    $yatim = User::factory()->create();
    $yatim->assignRole('guru');

    $this->actingAs($yatim)
        ->get(route('admin.cbt.exams.index'))
        ->assertOk()
        ->assertDontSee($exam->name)
        ->assertSee('Belum ada ujian');
});

test('hasil ujian milik guru lain tidak bisa dibaca atau diekspor', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);
    sesiMilik($exam);

    $rombelLain = rombelIsolasi();
    $mapelLain = mapelIsolasi();
    $penyusup = guruPengampu($rombelLain, $mapelLain);

    $this->actingAs($penyusup)
        ->get(route('admin.cbt.results.index', $exam))
        ->assertForbidden();

    $this->actingAs($penyusup)
        ->get(route('admin.cbt.results.export', $exam))
        ->assertForbidden();
});

test('monitoring dan csv pelanggaran milik guru lain tertutup', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);
    sesiMilik($exam);

    $rombelLain = rombelIsolasi();
    $mapelLain = mapelIsolasi();
    $penyusup = guruPengampu($rombelLain, $mapelLain);

    $this->actingAs($penyusup)->get(route('admin.cbt.monitoring.index', $exam))->assertForbidden();
    $this->actingAs($penyusup)->get(route('admin.cbt.monitoring.data', $exam))->assertForbidden();
    $this->actingAs($penyusup)->get(route('admin.cbt.violations.csv', $exam))->assertForbidden();
});

test('guru lain tidak bisa menghapus soal ujian lewat route bank', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);

    // Soal ujian: question_bank_id NULL, persis kasus yang dulu lolos tanpa
    // authorize() sama sekali di QuestionBankQuestionController::destroy.
    $soal = ExamQuestion::create([
        'exam_id' => $exam->id,
        'question_text' => 'Soal ujian',
        'type' => 'single_choice',
        'options' => [['key' => 'a', 'text' => 'A']],
        'correct_answer' => ['a'],
        'score' => 5,
        'order' => 1,
    ]);

    $rombelLain = rombelIsolasi();
    $mapelLain = mapelIsolasi();
    $penyusup = guruPengampu($rombelLain, $mapelLain);

    $this->actingAs($penyusup)
        ->delete(route('admin.cbt.banks.questions.destroy', $soal))
        ->assertNotFound();

    expect(ExamQuestion::whereKey($soal->id)->exists())->toBeTrue();
});

test('guru lain tidak bisa menghapus soal ujian lewat route ujian', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);
    $soal = ExamQuestion::create([
        'exam_id' => $exam->id,
        'question_text' => 'Soal ujian',
        'type' => 'single_choice',
        'options' => [['key' => 'a', 'text' => 'A']],
        'correct_answer' => ['a'],
        'score' => 5,
        'order' => 1,
    ]);

    $rombelLain = rombelIsolasi();
    $mapelLain = mapelIsolasi();
    $penyusup = guruPengampu($rombelLain, $mapelLain);

    $this->actingAs($penyusup)
        ->delete(route('admin.cbt.questions.destroy', $soal))
        ->assertForbidden();

    expect(ExamQuestion::whereKey($soal->id)->exists())->toBeTrue();
});

test('guru lain tidak bisa menghapus ujian milik orang lain', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);

    $rombelLain = rombelIsolasi();
    $mapelLain = mapelIsolasi();
    $penyusup = guruPengampu($rombelLain, $mapelLain);

    $this->actingAs($penyusup)
        ->delete(route('admin.cbt.exams.destroy', $exam))
        ->assertForbidden();

    expect(Exam::whereKey($exam->id)->exists())->toBeTrue();
});

test('guru lain tidak bisa membuat token untuk ujian yang bukan miliknya', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);

    $rombelLain = rombelIsolasi();
    $mapelLain = mapelIsolasi();
    $penyusup = guruPengampu($rombelLain, $mapelLain);

    $this->actingAs($penyusup)
        ->post(route('admin.cbt.exams.tokens.store', $exam), [
            'active_from' => now()->toDateTimeString(),
            'active_until' => now()->addHour()->toDateTimeString(),
        ])
        ->assertForbidden();

    expect(ExamToken::where('exam_id', $exam->id)->exists())->toBeFalse();
});

test('guru lain tidak bisa mengoreksi nilai siswa ujian yang bukan miliknya', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);
    $sesi = sesiMilik($exam);

    $soal = ExamQuestion::create([
        'exam_id' => $exam->id,
        'question_text' => 'Uraian',
        'type' => 'essay',
        'score' => 10,
        'order' => 1,
    ]);

    $jawaban = ExamAnswer::create([
        'exam_session_id' => $sesi->id,
        'exam_question_id' => $soal->id,
        'answer' => ['teks'],
        'answered_at' => now(),
    ]);

    $rombelLain = rombelIsolasi();
    $mapelLain = mapelIsolasi();
    $penyusup = guruPengampu($rombelLain, $mapelLain);

    $this->actingAs($penyusup)
        ->post(route('admin.cbt.answers.nilai', $jawaban), ['score_obtained' => 100])
        ->assertForbidden();

    expect((float) $jawaban->fresh()->score_obtained)->toBe(0.0);
});

test('nilai koreksi tidak boleh melebihi bobot soal', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);
    $sesi = sesiMilik($exam);

    $soal = ExamQuestion::create([
        'exam_id' => $exam->id,
        'question_text' => 'Bobot lima',
        'type' => 'essay',
        'score' => 5,
        'order' => 1,
    ]);

    $jawaban = ExamAnswer::create([
        'exam_session_id' => $sesi->id,
        'exam_question_id' => $soal->id,
        'answer' => ['teks'],
        'answered_at' => now(),
    ]);

    $pemilik = guruPengampu($rombel, $mapel);

    $this->actingAs($pemilik)
        ->post(route('admin.cbt.answers.nilai', $jawaban), ['score_obtained' => 100])
        ->assertSessionHasErrors('score_obtained');

    expect((float) $jawaban->fresh()->score_obtained)->toBe(0.0);
});

test('guru lain tidak bisa menghentikan sesi ujian yang bukan miliknya', function () {
    $rombel = rombelIsolasi();
    $mapel = mapelIsolasi();
    $exam = examMilik($rombel, $mapel);
    $sesi = sesiMilik($exam);
    $sesi->update(['status' => 'ongoing']);

    $rombelLain = rombelIsolasi();
    $mapelLain = mapelIsolasi();
    $penyusup = guruPengampu($rombelLain, $mapelLain);

    $this->actingAs($penyusup)
        ->post(route('admin.cbt.sessions.force', ['exam' => $exam->id, 'session' => $sesi->id]))
        ->assertForbidden();

    expect($sesi->fresh()->status)->toBe('ongoing');
});
