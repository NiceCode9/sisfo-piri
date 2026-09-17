<?php

use App\Models\Exam;
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
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

function apiSiswa(): User
{
    $u = User::factory()->create();
    $u->assignRole('siswa');

    return $u;
}

function buatRombelApi(): Rombel
{
    $tahun = TahunAjaran::create(['nama_tahun_ajaran' => 'API-'.Str::random(3), 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'status_aktif' => false]);
    $kelas = Kelas::create(['nama_kelas' => 'API-'.Str::random(3), 'tingkat' => '10']);

    return Rombel::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);
}

function buatExamApi(array $overrides = []): array
{
    $rombel = $overrides['rombel_id'] ?? buatRombelApi();
    $mapel = $overrides['mata_pelajaran_id'] ?? MataPelajaran::create(['kode' => 'AP'.Str::upper(Str::random(4)), 'nama' => 'Mapel '.Str::random(4), 'kelompok' => 'A']);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $exam = Exam::create(array_merge([
        'rombel_id' => $rombel instanceof Rombel ? $rombel->id : $rombel,
        'mata_pelajaran_id' => $mapel instanceof MataPelajaran ? $mapel->id : $mapel,
        'name' => 'API Exam', 'duration_minutes' => 60, 'max_violation_count' => 3, 'status' => 'published', 'created_by' => $admin->id,
    ], $overrides));
    $token = ExamToken::create(['exam_id' => $exam->id, 'token' => strtoupper(Str::random(12)), 'active_from' => now()->subHour(), 'active_until' => now()->addHour(), 'created_by' => $admin->id]);

    return [$exam, $token, $rombel];
}

test('login cbt mengembalikan token', function () {
    $user = apiSiswa();
    $user->update(['username' => 'siswa_login', 'password' => bcrypt('secret123')]);
    $res = $this->postJson('/api/cbt/login', ['identifier' => 'siswa_login', 'password' => 'secret123']);
    $res->assertOk()->assertJsonStructure(['token', 'user']);
});

test('join dan ambil soal tanpa bocor correct_answer', function () {
    $user = apiSiswa();
    [$exam, $token] = buatExamApi();
    ExamQuestion::create(['exam_id' => $exam->id, 'question_text' => 'Apa?', 'type' => 'single_choice', 'options' => [['key' => 'A', 'text' => 'Ya']], 'correct_answer' => ['A'], 'score' => 10, 'order' => 1]);
    $login = $this->postJson('/api/cbt/login', ['identifier' => $user->username, 'password' => 'password']);
    $bearer = $login->json('token');
    $join = $this->withHeader('Authorization', "Bearer $bearer")->postJson('/api/cbt/exam/join', ['token' => $token->token]);
    $join->assertOk();
    $sid = $join->json('exam_session_id');
    $q = $this->withHeader('Authorization', "Bearer $bearer")->getJson("/api/cbt/exam/questions?exam_session_id=$sid");
    $q->assertOk();
    expect(json_encode($q->json()))->not->toContain('correct_answer');
});

test('heartbeat throttled 6 per menit', function () {
    $user = apiSiswa();
    [$exam, $token] = buatExamApi();
    ExamQuestion::create(['exam_id' => $exam->id, 'question_text' => 'Q', 'type' => 'single_choice', 'score' => 1, 'order' => 1]);
    $bearer = $this->postJson('/api/cbt/login', ['identifier' => $user->username, 'password' => 'password'])->json('token');
    $sid = $this->withHeader('Authorization', "Bearer $bearer")->postJson('/api/cbt/exam/join', ['token' => $token->token])->json('exam_session_id');
    for ($i = 0; $i < 6; $i++) {
        $this->withHeader('Authorization', "Bearer $bearer")->postJson('/api/cbt/exam/heartbeat', ['exam_session_id' => $sid])->assertOk();
    }
    $this->withHeader('Authorization', "Bearer $bearer")->postJson('/api/cbt/exam/heartbeat', ['exam_session_id' => $sid])->assertStatus(429);
});

test('connection_lost tidak menambah violation_count', function () {
    $user = apiSiswa();
    [$exam, $token] = buatExamApi();
    $bearer = $this->postJson('/api/cbt/login', ['identifier' => $user->username, 'password' => 'password'])->json('token');
    $sid = $this->withHeader('Authorization', "Bearer $bearer")->postJson('/api/cbt/exam/join', ['token' => $token->token])->json('exam_session_id');
    $this->withHeader('Authorization', "Bearer $bearer")->postJson('/api/cbt/exam/violation', ['exam_session_id' => $sid, 'type' => 'connection_lost'])->assertOk();
    expect(ExamSession::find($sid)->violation_count)->toBe(0);
    $this->withHeader('Authorization', "Bearer $bearer")->postJson('/api/cbt/exam/violation', ['exam_session_id' => $sid, 'type' => 'tab_blur'])->assertOk();
    expect(ExamSession::find($sid)->violation_count)->toBe(1);
});

test('admin hasil export csv', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    [$exam] = buatExamApi();
    $this->actingAs($admin)->get(route('admin.cbt.results.export', $exam))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
