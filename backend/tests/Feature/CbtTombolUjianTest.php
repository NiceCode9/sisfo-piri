<?php

/**
 * Regresi: status ujian harus bisa diubah dari daftar dan detail.
 *
 * Halaman `index` dan `show` dulunya tidak punya tombol Edit maupun Hapus, dan
 * `show` tidak menampilkan status sama sekali. Field `status` sendiri sudah ada
 * di form, jadi satu-satunya cara mengubahnya adalah mengetik URL edit manual.
 * Test ini mengunci bahwa tombolnya ada, dan hanya bagi yang berhak.
 *
 * Gate-nya `ExamPolicy`, bukan `@can('exams.edit')`: permission per-resource itu
 * tidak pernah ada di `PermissionSeeder` (yang tersedia hanya `cbt.view` dan
 * `cbt.manage`), jadi menyalin pola `@can('...edit')` dari modul lain membuat
 * tombol tidak pernah muncul sama sekali.
 */

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Rombel;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

function adminUjian(): User
{
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    return $user;
}

/** Pemegang `cbt.view` saja: boleh melihat daftar, tidak boleh mengubah. */
function pemihatUjian(): User
{
    $role = Role::firstOrCreate(['name' => 'pemihat-ujian', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::findOrCreate('cbt.view', 'web'));

    $user = User::factory()->create();
    $user->assignRole('pemihat-ujian');

    return $user;
}

/** Guru yang mengampu mapel dan rombel yang sama persis dengan ujiannya. */
function guruPemilik(Exam $exam): User
{
    $user = User::factory()->create();
    $user->assignRole('guru');

    $guru = Guru::factory()->create([
        'user_id' => $user->id,
        'nama' => $user->name,
    ]);

    Pengampu::create([
        'guru_id' => $guru->id,
        'mata_pelajaran_id' => $exam->mata_pelajaran_id,
        'rombel_id' => $exam->rombel_id,
    ]);

    return $user;
}

/** Guru role-nya sudah benar, tapi mengampu rombel dan mapel yang lain. */
function guruAsing(Exam $exam): User
{
    $user = guruPemilik($exam);
    $guru = Guru::where('user_id', $user->id)->firstOrFail();

    Pengampu::where('guru_id', $guru->id)->delete();
    Pengampu::create([
        'guru_id' => $guru->id,
        'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
        'rombel_id' => Rombel::factory()->create()->id,
    ]);

    return $user;
}

/**
 * URL hapusDicek lewat `action="..."`, bukan URL polos: `.../exams/1` adalah
 * awalan dari `.../exams/1/edit`, jadi pengecekan substring biasa selalu cocok
 * dan test "hapus tidak tampil" akan selalu lulus.
 */
function actionHapus(Exam $exam): string
{
    return 'action="'.route('admin.cbt.exams.destroy', $exam).'"';
}

test('daftar ujian menautkan ke halaman edit', function () {
    $exam = Exam::factory()->create();

    $html = $this->actingAs(adminUjian())
        ->get(route('admin.cbt.exams.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain(route('admin.cbt.exams.edit', $exam));
});

test('detail ujian menautkan ke halaman edit', function () {
    $exam = Exam::factory()->create();

    $html = $this->actingAs(adminUjian())
        ->get(route('admin.cbt.exams.show', $exam))
        ->assertOk()
        ->getContent();

    expect($html)->toContain(route('admin.cbt.exams.edit', $exam));
});

test('tombol edit disembunyikan dari pemegang cbt.view saja', function () {
    $exam = Exam::factory()->create();

    $html = $this->actingAs(pemihatUjian())
        ->get(route('admin.cbt.exams.index'))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain(route('admin.cbt.exams.edit', $exam));
});

test('tombol edit disembunyikan dari guru yang mengampu rombel dan mapel lain', function () {
    $exam = Exam::factory()->create();
    $asing = guruAsing($exam);

    $html = $this->actingAs($asing)
        ->get(route('admin.cbt.exams.index'))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain(route('admin.cbt.exams.edit', $exam));
});

test('detail ujian menampilkan status, durasi, dan jendela waktu', function () {
    $exam = Exam::factory()->create([
        'status' => 'published',
        'duration_minutes' => 90,
        'max_violation_count' => 5,
    ]);

    $html = $this->actingAs(adminUjian())
        ->get(route('admin.cbt.exams.show', $exam))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Diterbitkan')
        ->and($html)->toContain('90 menit')
        ->and($html)->toContain('5 kali')
        ->and($html)->toContain('Tanpa batas waktu');
});

test('badge status memakai label Indonesia, bukan nilai enum mentah', function () {
    $pairs = [
        'draft' => 'Draf',
        'published' => 'Diterbitkan',
        'archived' => 'Diarsipkan',
    ];

    foreach ($pairs as $nilai => $label) {
        $exam = Exam::factory()->create(['status' => $nilai]);

        $html = $this->actingAs(adminUjian())
            ->get(route('admin.cbt.exams.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain($label)
            ->and($html)->not->toContain('>'.$nilai.'<');
    }
});

test('dropdown status di form edit memakai label yang sama dengan badge', function () {
    $exam = Exam::factory()->create(['status' => 'archived']);

    $html = $this->actingAs(adminUjian())
        ->get(route('admin.cbt.exams.edit', $exam))
        ->assertOk()
        ->getContent();

    foreach (Exam::STATUS as $meta) {
        expect($html)->toContain($meta['label']);
    }
});

test('status tersimpan dari form edit lalu guru dikembalikan ke detail', function () {
    $exam = Exam::factory()->create(['status' => 'draft']);

    $this->actingAs(adminUjian())
        ->put(route('admin.cbt.exams.update', $exam), [
            'rombel_id' => $exam->rombel_id,
            'mata_pelajaran_id' => $exam->mata_pelajaran_id,
            'name' => $exam->name,
            'description' => $exam->description,
            'duration_minutes' => 60,
            'max_violation_count' => 3,
            'status' => 'published',
            'shuffle_questions' => '1',
            'shuffle_options' => '1',
            'is_remedial' => '0',
            'remedial_of_id' => '',
        ])
        ->assertRedirect(route('admin.cbt.exams.show', $exam))
        ->assertSessionHasNoErrors();

    expect($exam->fresh()->status)->toBe('published');
});

test('tombol hapus tersedia untuk ujian tanpa sesi', function () {
    $exam = Exam::factory()->create();

    $html = $this->actingAs(adminUjian())
        ->get(route('admin.cbt.exams.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain(actionHapus($exam));
});

test('hapus ujian tanpa sesi menghapus soal dan tokennya', function () {
    $exam = Exam::factory()->create();
    $soalId = ExamQuestion::factory()->create(['exam_id' => $exam->id])->id;
    $tokenId = ExamToken::factory()->create(['exam_id' => $exam->id])->id;

    $this->actingAs(adminUjian())
        ->delete(route('admin.cbt.exams.destroy', $exam))
        ->assertRedirect(route('admin.cbt.exams.index'));

    expect(Exam::whereKey($exam->id)->exists())->toBeFalse()
        ->and(ExamQuestion::whereKey($soalId)->exists())->toBeFalse()
        ->and(ExamToken::whereKey($tokenId)->exists())->toBeFalse();
});

test('ujian yang sudah punya sesi tidak bisa dihapus', function () {
    $exam = Exam::factory()->create();
    $sesi = ExamSession::factory()->create(['exam_id' => $exam->id]);

    $html = $this->actingAs(adminUjian())
        ->get(route('admin.cbt.exams.index'))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain(actionHapus($exam));

    // Dicek lewat DELETE langsung juga, bukan hanya lewat tombol yang hilang:
    // `@can` di view protecting UI saja tidak berlaku untuk request yang dibuat
    // tangan.
    $this->actingAs(adminUjian())
        ->delete(route('admin.cbt.exams.destroy', $exam))
        ->assertForbidden();

    expect(Exam::whereKey($exam->id)->exists())->toBeTrue()
        ->and(ExamSession::whereKey($sesi->id)->exists())->toBeTrue();
});

test('guru pemilik boleh menghapus ujiannya sendiri yang belum ada sesinya', function () {
    $exam = Exam::factory()->create();
    $guru = guruPemilik($exam);

    $html = $this->actingAs($guru)
        ->get(route('admin.cbt.exams.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain(actionHapus($exam));
});
