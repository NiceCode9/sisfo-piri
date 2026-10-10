<?php

/**
 * Regresi M4 & M5: validasi kunci jawaban dan perhitungan skor.
 *
 * Sebelum diperbaiki, `correct_answer` hanya divalidasi sebagai "array" tanpa
 * diperiksa terhadap `options`/`type`. Akibatnya soal dengan kunci yang tidak
 * ada di opsi tersimpan dan bernilai 0 untuk semua siswa tanpa pesan error.
 */

use App\Http\Controllers\Api\Cbt\ExamSessionController;
use App\Models\Exam;
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

function waliKunci(): array
{
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $tahun = TahunAjaran::create([
        'nama_tahun_ajaran' => 'KUNCI-'.Str::random(3),
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => false,
    ]);
    $kelas = Kelas::create(['nama_kelas' => 'K-'.Str::random(3), 'tingkat' => '10']);
    $rombel = Rombel::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);
    $mapel = MataPelajaran::create([
        'kode' => 'MK'.Str::upper(Str::random(4)),
        'nama' => 'Mapel '.Str::random(4),
        'kelompok' => 'A',
    ]);

    $guru = Guru::create([
        'user_id' => $admin->id,
        'nip' => 'NIP'.Str::upper(Str::random(6)),
        'nama' => 'Guru Kunci',
        'jenis_kelamin' => 'L',
        'is_aktif' => true,
    ]);
    Pengampu::create([
        'guru_id' => $guru->id,
        'mata_pelajaran_id' => $mapel->id,
        'rombel_id' => $rombel->id,
    ]);

    $exam = Exam::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'name' => 'Ujian Kunci',
        'duration_minutes' => 60,
        'max_violation_count' => 3,
        'status' => 'published',
        'created_by' => $admin->id,
    ]);

    return [$admin, $exam];
}

function opsiEmpat(): array
{
    return [
        ['key' => 'a', 'text' => 'A'],
        ['key' => 'b', 'text' => 'B'],
        ['key' => 'c', 'text' => 'C'],
        ['key' => 'd', 'text' => 'D'],
    ];
}

test('kunci jawaban di luar daftar opsi ditolak', function () {
    [$admin, $exam] = waliKunci();

    $this->actingAs($admin)->post(route('admin.cbt.exams.questions.store', $exam), [
        'question_text' => 'Pilih satu',
        'type' => 'single_choice',
        'options' => opsiEmpat(),
        'correct_answer' => ['z'],
        'score' => 10,
    ])->assertSessionHasErrors('correct_answer');

    expect(ExamQuestion::where('exam_id', $exam->id)->exists())->toBeFalse();
});

test('pilihan tunggal dengan dua kunci ditolak', function () {
    [$admin, $exam] = waliKunci();

    $this->actingAs($admin)->post(route('admin.cbt.exams.questions.store', $exam), [
        'question_text' => 'Pilih satu',
        'type' => 'single_choice',
        'options' => opsiEmpat(),
        'correct_answer' => ['a', 'b'],
        'score' => 10,
    ])->assertSessionHasErrors('correct_answer');
});

test('pilihan jamak dengan satu kunci ditolak', function () {
    [$admin, $exam] = waliKunci();

    $this->actingAs($admin)->post(route('admin.cbt.exams.questions.store', $exam), [
        'question_text' => 'Pilih jamak',
        'type' => 'multiple_choice',
        'options' => opsiEmpat(),
        'correct_answer' => ['a'],
        'score' => 10,
    ])->assertSessionHasErrors('correct_answer');
});

test('kunci ganda yang berulang ditolak', function () {
    [$admin, $exam] = waliKunci();

    $this->actingAs($admin)->post(route('admin.cbt.exams.questions.store', $exam), [
        'question_text' => 'Pilih jamak',
        'type' => 'multiple_choice',
        'options' => opsiEmpat(),
        'correct_answer' => ['a', 'a'],
        'score' => 10,
    ])->assertSessionHasErrors('correct_answer');
});

test('soal pilihan tanpa opsi ditolak', function () {
    [$admin, $exam] = waliKunci();

    $this->actingAs($admin)->post(route('admin.cbt.exams.questions.store', $exam), [
        'question_text' => 'Tanpa opsi',
        'type' => 'single_choice',
        'correct_answer' => ['a'],
        'score' => 10,
    ])->assertSessionHasErrors('options');
});

test('soal uraian tidak butuh kunci jawaban', function () {
    [$admin, $exam] = waliKunci();

    $this->actingAs($admin)->post(route('admin.cbt.exams.questions.store', $exam), [
        'question_text' => 'Jelaskan proses',
        'type' => 'essay',
        'score' => 10,
    ])->assertSessionHasNoErrors();

    expect(ExamQuestion::where('exam_id', $exam->id)->count())->toBe(1);
});

test('kunci jawaban valid disimpan sebagai string', function () {
    [$admin, $exam] = waliKunci();

    $this->actingAs($admin)->post(route('admin.cbt.exams.questions.store', $exam), [
        'question_text' => 'Pilih satu',
        'type' => 'single_choice',
        'options' => [['key' => '1', 'text' => 'Satu'], ['key' => '2', 'text' => 'Dua']],
        'correct_answer' => ['1'],
        'score' => 10,
    ])->assertSessionHasNoErrors();

    $soal = ExamQuestion::where('exam_id', $exam->id)->first();
    expect($soal->correct_answer)->toBe(['1']);
});

/**
 * Menjalankan sesi sampai finalisasi untuk memeriksa skor yang tersimpan.
 */
function skorUntuk(array $opsi, array $kunci, string $type, array $jawaban, float $bobot = 10.0): float
{
    [$admin, $exam] = waliKunci();

    $soal = ExamQuestion::create([
        'exam_id' => $exam->id,
        'question_text' => 'Soal uji',
        'type' => $type,
        'options' => $opsi,
        'correct_answer' => $kunci,
        'score' => $bobot,
        'order' => 1,
    ]);

    $siswa = User::factory()->create();
    $siswa->assignRole('siswa');

    $token = ExamToken::create([
        'exam_id' => $exam->id,
        'token' => strtoupper(Str::random(12)),
        'active_from' => now()->subHour(),
        'active_until' => now()->addHour(),
        'created_by' => $admin->id,
    ]);

    $sesi = ExamSession::create([
        'exam_id' => $exam->id,
        'exam_token_id' => $token->id,
        'user_id' => $siswa->id,
        'started_at' => now(),
        'expected_end_at' => now()->addHour(),
        'status' => 'ongoing',
    ]);

    $sesi->answers()->create([
        'exam_question_id' => $soal->id,
        'answer' => $jawaban,
        'answered_at' => now(),
    ]);

    $controller = app(ExamSessionController::class);
    $controller->finalizeSession($sesi->fresh(), 'manual');

    return (float) $sesi->fresh()->score;
}

test('pilihan tunggal benar dapat skor penuh', function () {
    expect(skorUntuk(opsiEmpat(), ['b'], 'single_choice', ['b']))->toBe(10.0);
});

test('pilihan tunggal salah dapat nol', function () {
    expect(skorUntuk(opsiEmpat(), ['b'], 'single_choice', ['a']))->toBe(0.0);
});

test('pilihan tunggal dengan dua jawaban dianggap salah', function () {
    // Kunci "b" tapi siswa mencentang a dan b sekaligus.
    expect(skorUntuk(opsiEmpat(), ['b'], 'single_choice', ['a', 'b']))->toBe(0.0);
});

test('kunci integer dari json tetap dicocokkan', function () {
    // Opsi DB menyimpan "1"; jawaban siswa mengirim integer 1.
    $opsi = [['key' => '1', 'text' => 'Satu'], ['key' => '2', 'text' => 'Dua']];
    expect(skorUntuk($opsi, ['1'], 'single_choice', [1]))->toBe(10.0);
});

test('pilihan jamak dapat kompensasi parsial', function () {
    // Kunci a+b+c, siswa jawab a+b (2 dari 3 benar, tidak ada tebakan salah).
    expect(skorUntuk(opsiEmpat(), ['a', 'b', 'c'], 'multiple_choice', ['a', 'b'], 9.0))->toBe(6.0);
});

test('menjawab semua opsi termasuk yang salah menurunkan skor', function () {
    // Kunci a+b, siswa jawab a+b+c — tebakan c tidak menambah, dan rasio turun.
    expect(skorUntuk(opsiEmpat(), ['a', 'b'], 'multiple_choice', ['a', 'b', 'c'], 10.0))->toBe(6.67);
});

test('kunci jawaban kosong menghasilkan nol, bukan error', function () {
    expect(skorUntuk(opsiEmpat(), [], 'single_choice', ['a']))->toBe(0.0);
});
