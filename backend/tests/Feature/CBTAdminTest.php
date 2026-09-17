<?php

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

if (! function_exists('buatRombelCbt')) {
    function buatRombelCbt(): Rombel
    {
        $tahun = TahunAjaran::create(['nama_tahun_ajaran' => 'CBT-'.Str::random(3), 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'status_aktif' => false]);
        $kelas = Kelas::create(['nama_kelas' => 'CBT-'.Str::random(3), 'tingkat' => '10']);

        return Rombel::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);
    }
}

if (! function_exists('buatMapelCbt')) {
    function buatMapelCbt(): MataPelajaran
    {
        return MataPelajaran::create(['kode' => 'MP'.Str::upper(Str::random(4)), 'nama' => 'Mapel '.Str::random(4), 'kelompok' => 'A']);
    }
}

if (! function_exists('buatExamCbt')) {
    function buatExamCbt(array $overrides = []): Exam
    {
        $rombel = $overrides['rombel_id'] ?? buatRombelCbt()->id;
        $mapel = $overrides['mata_pelajaran_id'] ?? buatMapelCbt()->id;
        $admin = $overrides['created_by'] ?? superAdmin()->id;

        return Exam::create(array_merge([
            'rombel_id' => is_object($rombel) ? $rombel->id : $rombel,
            'mata_pelajaran_id' => is_object($mapel) ? $mapel->id : $mapel,
            'name' => 'Ujian CBT Test '.Str::random(4),
            'duration_minutes' => 60,
            'max_violation_count' => 3,
            'status' => 'draft',
            'created_by' => $admin,
        ], $overrides));
    }
}

test('super-admin dapat membuat ujian', function () {
    $rombel = buatRombelCbt();
    $mapel = buatMapelCbt();
    $response = $this->actingAs(superAdmin())->post(route('admin.cbt.exams.store'), [
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'name' => 'Ujian CBT Test',
        'duration_minutes' => 60,
        'max_violation_count' => 3,
        'status' => 'draft',
    ]);
    $response->assertRedirect(route('admin.cbt.exams.index'));
    expect(Exam::where('name', 'Ujian CBT Test')->exists())->toBeTrue();
});

test('update soal menghapus cache', function () {
    $exam = buatExamCbt();
    Cache::put("exam:{$exam->id}:questions", collect([1, 2, 3]), 3600);
    $this->actingAs(superAdmin())->put(route('admin.cbt.exams.update', $exam), [
        'rombel_id' => $exam->rombel_id,
        'mata_pelajaran_id' => $exam->mata_pelajaran_id,
        'name' => $exam->name,
        'duration_minutes' => $exam->duration_minutes,
        'max_violation_count' => $exam->max_violation_count,
        'status' => $exam->status,
    ]);
    expect(Cache::has("exam:{$exam->id}:questions"))->toBeFalse();
});

test('admin_force menghentikan sesi ongoing', function () {
    $exam = buatExamCbt(['status' => 'published']);
    $token = ExamToken::create([
        'exam_id' => $exam->id,
        'token' => strtoupper(Str::random(12)),
        'active_from' => now()->subHour(),
        'active_until' => now()->addHour(),
        'created_by' => $exam->created_by,
    ]);
    $user = User::factory()->create();
    $user->assignRole('siswa');
    $session = ExamSession::create([
        'exam_id' => $exam->id,
        'exam_token_id' => $token->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'expected_end_at' => now()->addHour(),
        'status' => 'ongoing',
    ]);

    $this->actingAs(superAdmin())->post(route('admin.cbt.sessions.force', ['exam' => $exam->id, 'session' => $session->id]))
        ->assertRedirect();
    expect($session->fresh()->status)->not->toBe('ongoing')
        ->and($session->fresh()->finish_reason)->toBe('admin_force');
});

test('koreksi uraian per soal', function () {
    $exam = buatExamCbt();
    $q = ExamQuestion::create(['exam_id' => $exam->id, 'question_text' => 'Jelaskan', 'type' => 'essay', 'score' => 10, 'order' => 1]);
    $user = User::factory()->create();
    $user->assignRole('siswa');
    $token = ExamToken::create(['exam_id' => $exam->id, 'token' => strtoupper(Str::random(12)), 'active_from' => now()->subHour(), 'active_until' => now()->addHour(), 'created_by' => $exam->created_by]);
    $session = ExamSession::create(['exam_id' => $exam->id, 'exam_token_id' => $token->id, 'user_id' => $user->id, 'started_at' => now(), 'expected_end_at' => now()->addHour(), 'status' => 'finished']);
    $answer = ExamAnswer::create(['exam_session_id' => $session->id, 'exam_question_id' => $q->id, 'answer' => ['text' => 'Jawaban essay'], 'answered_at' => now()]);

    $this->actingAs(superAdmin())->post(route('admin.cbt.answers.nilai', $answer), ['score_obtained' => 8])
        ->assertRedirect();
    expect((float) $answer->fresh()->score_obtained)->toBe(8.0);
});

test('export violations csv', function () {
    $exam = buatExamCbt();
    $this->actingAs(superAdmin())->get(route('admin.cbt.violations.csv', $exam))
        ->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
