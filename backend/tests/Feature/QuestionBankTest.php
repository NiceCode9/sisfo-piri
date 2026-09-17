<?php

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\QuestionBank;
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

function guruUser(array $overrides = []): User
{
    $user = User::factory()->create($overrides);
    $user->assignRole('guru');
    Guru::create(['user_id' => $user->id, 'nama' => 'Guru '.Str::random(4), 'jenis_kelamin' => 'L', 'is_aktif' => true]);

    return $user;
}

function mapelAktif(): MataPelajaran
{
    return MataPelajaran::create(['kode' => 'MP'.Str::upper(Str::random(4)), 'nama' => 'Mapel '.Str::random(4), 'kelompok' => 'A']);
}

function rombelBaru(): Rombel
{
    $tahun = TahunAjaran::create(['nama_tahun_ajaran' => 'QB-'.Str::random(3), 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'status_aktif' => false]);
    $kelas = Kelas::create(['nama_kelas' => 'QB-'.Str::random(3), 'tingkat' => '10']);

    return Rombel::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);
}

test('guru pengampu dapat membuat bank untuk mapel ampuan', function () {
    $user = guruUser();
    $guru = Guru::where('user_id', $user->id)->first();
    $mapel = mapelAktif();
    $rombel = rombelBaru();
    Pengampu::create(['guru_id' => $guru->id, 'mata_pelajaran_id' => $mapel->id, 'rombel_id' => $rombel->id]);

    $response = $this->actingAs($user)->post(route('admin.cbt.banks.store'), [
        'mata_pelajaran_id' => $mapel->id,
        'nama' => 'Bank UTS',
    ]);
    $response->assertRedirect(route('admin.cbt.banks.index'));
    expect(QuestionBank::where('nama', 'Bank UTS')->exists())->toBeTrue();
});

test('guru tidak bisa membuat bank untuk mapel yang tidak diampu', function () {
    $user = guruUser();
    $mapel = mapelAktif();

    $this->actingAs($user)->post(route('admin.cbt.banks.store'), [
        'mata_pelajaran_id' => $mapel->id,
        'nama' => 'Bank Ilegal',
    ])->assertForbidden();
});

test('guru hanya melihat bank miliknya', function () {
    $guruA = guruUser();
    $guruB = guruUser();
    $ga = Guru::where('user_id', $guruA->id)->first();
    $gb = Guru::where('user_id', $guruB->id)->first();
    $mapelA = mapelAktif();
    $mapelB = mapelAktif();
    $rombel = rombelBaru();
    Pengampu::create(['guru_id' => $ga->id, 'mata_pelajaran_id' => $mapelA->id, 'rombel_id' => $rombel->id]);
    Pengampu::create(['guru_id' => $gb->id, 'mata_pelajaran_id' => $mapelB->id, 'rombel_id' => $rombel->id]);

    QuestionBank::create(['mata_pelajaran_id' => $mapelA->id, 'guru_id' => $ga->id, 'nama' => 'Bank A', 'created_by' => $guruA->id]);
    QuestionBank::create(['mata_pelajaran_id' => $mapelB->id, 'guru_id' => $gb->id, 'nama' => 'Bank B', 'created_by' => $guruB->id]);

    $response = $this->actingAs($guruA)->get(route('admin.cbt.banks.index'));
    $response->assertOk();
    $response->assertSee('Bank A');
    $response->assertDontSee('Bank B');
});

test('impor soal dari bank membuat snapshot copy', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $mapel = mapelAktif();
    $guru = Guru::create(['user_id' => $admin->id, 'nama' => 'Admin Guru', 'jenis_kelamin' => 'L', 'is_aktif' => true]);
    $bank = QuestionBank::create(['mata_pelajaran_id' => $mapel->id, 'guru_id' => $guru->id, 'nama' => 'Bank Snap', 'created_by' => $admin->id]);
    $q1 = ExamQuestion::create(['question_bank_id' => $bank->id, 'question_text' => 'Soal 1', 'type' => 'single_choice', 'score' => 10, 'order' => 1]);
    $q2 = ExamQuestion::create(['question_bank_id' => $bank->id, 'question_text' => 'Soal 2', 'type' => 'single_choice', 'score' => 10, 'order' => 2]);

    $rombel = rombelBaru();
    $exam = Exam::create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'name' => 'Ujian Snap', 'duration_minutes' => 60, 'max_violation_count' => 3, 'status' => 'draft', 'created_by' => $admin->id]);

    $this->actingAs($admin)->post(route('admin.cbt.exams.banks.import', [$exam, $bank]), [
        'question_ids' => [$q1->id, $q2->id],
    ])->assertRedirect();

    expect($exam->fresh()->questions()->count())->toBe(2)
        ->and($exam->questions()->where('source_question_id', $q1->id)->exists())->toBeTrue();
});

test('hapus bank tidak menghapus snapshot ujian', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $mapel = mapelAktif();
    $guru = Guru::create(['user_id' => $admin->id, 'nama' => 'Guru H', 'jenis_kelamin' => 'L', 'is_aktif' => true]);
    $bank = QuestionBank::create(['mata_pelajaran_id' => $mapel->id, 'guru_id' => $guru->id, 'nama' => 'Bank Hapus', 'created_by' => $admin->id]);
    $q = ExamQuestion::create(['question_bank_id' => $bank->id, 'question_text' => 'Hapus me', 'type' => 'essay', 'score' => 10, 'order' => 1]);
    $rombel = rombelBaru();
    $exam = Exam::create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'name' => 'Ujian Keep', 'duration_minutes' => 60, 'max_violation_count' => 3, 'status' => 'draft', 'created_by' => $admin->id]);
    $this->actingAs($admin)->post(route('admin.cbt.exams.banks.import', [$exam, $bank]), ['question_ids' => [$q->id]]);

    $this->actingAs($admin)->delete(route('admin.cbt.banks.destroy', $bank))->assertRedirect();
    expect($exam->fresh()->questions()->count())->toBe(1)
        ->and(QuestionBank::where('id', $bank->id)->exists())->toBeFalse();
});

test('nama bank unik per guru mapel', function () {
    $user = guruUser();
    $guru = Guru::where('user_id', $user->id)->first();
    $mapel = mapelAktif();
    $rombel = rombelBaru();
    Pengampu::create(['guru_id' => $guru->id, 'mata_pelajaran_id' => $mapel->id, 'rombel_id' => $rombel->id]);
    QuestionBank::create(['mata_pelajaran_id' => $mapel->id, 'guru_id' => $guru->id, 'nama' => 'Duplikat', 'created_by' => $user->id]);

    $this->actingAs($user)->post(route('admin.cbt.banks.store'), [
        'mata_pelajaran_id' => $mapel->id,
        'nama' => 'Duplikat',
    ])->assertSessionHasErrors('nama');
});
