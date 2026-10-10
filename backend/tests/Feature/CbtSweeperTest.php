<?php

/**
 * Regresi M6: sesi yang ditinggalkan siswa harus ikut ditutup.
 *
 * Sebelumnya status `expired` ada di enum tapi tidak pernah ditulis, dan tidak
 * ada sweeper. Baris `ongoing` dari siswa yang menutup laptop menggantung
 * selamanya: monitoring menampilkan sisa waktu negatif dan
 * `GET /exam/active` menawarkan ujian yang sudah lewat.
 */

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Kelas;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

function sesiLama(): ExamSession
{
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $tahun = TahunAjaran::create([
        'nama_tahun_ajaran' => 'SWP-'.Str::random(3),
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => false,
    ]);
    $kelas = Kelas::create(['nama_kelas' => 'SW-'.Str::random(3), 'tingkat' => '10']);
    $rombel = Rombel::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);

    $exam = Exam::create([
        'rombel_id' => $rombel->id,
        'name' => 'Ujian Sweep '.Str::random(3),
        'duration_minutes' => 60,
        'max_violation_count' => 3,
        'status' => 'published',
        'created_by' => $admin->id,
    ]);

    $token = ExamToken::create([
        'exam_id' => $exam->id,
        'token' => strtoupper(Str::random(12)),
        'active_from' => now()->subHours(3),
        'active_until' => now()->addHour(),
        'created_by' => $admin->id,
    ]);

    $siswa = User::factory()->create();
    $siswa->assignRole('siswa');

    return ExamSession::create([
        'exam_id' => $exam->id,
        'exam_token_id' => $token->id,
        'user_id' => $siswa->id,
        'started_at' => now()->subHours(2),
        'expected_end_at' => now()->subHour(),
        'status' => 'ongoing',
    ]);
}

test('sweeper menutup sesi yang sudah lewat', function () {
    $sesi = sesiLama();

    $this->artisan('cbt:expire-sessions')->assertSuccessful();

    $sesi->refresh();
    expect($sesi->status)->toBe('expired')
        ->and($sesi->finish_reason)->toBe('time_up')
        ->and($sesi->finished_at)->not->toBeNull();
});

test('sweeper tidak menyentuh sesi yang masih berjalan', function () {
    $sesi = sesiLama();
    $sesi->update(['expected_end_at' => now()->addMinutes(30)]);

    $this->artisan('cbt:expire-sessions')->assertSuccessful();

    expect($sesi->fresh()->status)->toBe('ongoing');
});

test('dry-run tidak menulis apa pun', function () {
    $sesi = sesiLama();

    $this->artisan('cbt:expire-sessions --dry-run')->assertSuccessful();

    expect($sesi->fresh()->status)->toBe('ongoing');
});

test('sweeper dijadwalkan tiap menit', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains($e->command ?? '', 'cbt:expire-sessions'));

    expect($events)->not->toBeEmpty();
});

test('exam active tidak menawarkan sesi yang sudah lewat', function () {
    $sesi = sesiLama();

    $response = $this->actingAs($sesi->user, 'sanctum')->getJson('/api/cbt/exam/active');

    $response->assertOk()->assertJson(['active' => false]);
    expect($sesi->fresh()->status)->not->toBe('ongoing');
});
