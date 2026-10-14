<?php

/**
 * Regresi: validasi yang gagal harus terlihat di kolomnya.
 *
 * Dua bug yang dikunci di sini:
 *
 * 1. "Buat Ujian" dan "Edit Ujian" selalu gagal menyimpan karena
 *    `max_violation_count` (dan `duration_minutes` di form Edit) tidak pernah
 *    punya kolom, padahal `ExamController` memvalidasinya sebagai `required`.
 *    Semua test lama lolos karena mengirim field itu manual di payload POST,
 *    bukan lewat form — jadi tidak ada yang menguji apa yang benar-benar
 *    dirender ke layar.
 *
 * 2. Kegagalan itu tidak pernah terlihat. `layouts/partials/alert` hanya
 *    merender `session('success')`/`session('error')`, bukan `$errors`, dan
 *    tidak ada satu pun `@error` di views CBT. Guru menekan Simpan, form
 *    kembali ke posisi semula, dan tidak ada pesan apa pun.
 */

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

if (! function_exists('buatRombelTampilanUji')) {
    function buatRombelTampilanUji(): Rombel
    {
        $tahun = TahunAjaran::create(['nama_tahun_ajaran' => 'CBT-'.Str::random(3), 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'status_aktif' => false]);
        $kelas = Kelas::create(['nama_kelas' => 'CBT-'.Str::random(3), 'tingkat' => '10']);

        return Rombel::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);
    }
}

if (! function_exists('buatExamTampilanUji')) {
    function buatExamTampilanUji(array $overrides = []): Exam
    {
        $rombel = buatRombelTampilanUji();
        $mapel = MataPelajaran::create(['kode' => 'MP'.Str::upper(Str::random(4)), 'nama' => 'Mapel '.Str::random(4), 'kelompok' => 'A']);

        return Exam::create(array_merge([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => $mapel->id,
            'name' => 'Ujian CBT Test '.Str::random(4),
            'duration_minutes' => 60,
            'max_violation_count' => 3,
            'status' => 'draft',
            'created_by' => superAdmin()->id,
        ], $overrides));
    }
}

if (! function_exists('buatSesiSiswaTampilanUji')) {
    function buatSesiSiswaTampilanUji(Exam $exam): ExamSession
    {
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'token' => strtoupper(Str::random(12)),
            'active_from' => now()->subHour(),
            'active_until' => now()->addHour(),
            'created_by' => $exam->created_by,
        ]);

        $user = User::factory()->create();
        $user->assignRole('siswa');

        return ExamSession::create([
            'exam_id' => $exam->id,
            'exam_token_id' => $token->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'expected_end_at' => now()->addHour(),
            'status' => 'finished',
        ]);
    }
}

/**
 * Nama field yang wajib ada di form HTML karena controller memvalidasinya
 * sebagai `required`. Daftar ini diturunkan dari aturan controller, bukan
 * disalin dari form — jadi test gagal kalau form kehilangan kolom, bukan
 * kalau daftar ini ikut dicopy.
 */
function fieldUjianWajib(): array
{
    return [
        'rombel_id',
        'mata_pelajaran_id',
        'name',
        'description',
        'duration_minutes',
        'available_from',
        'available_until',
        'max_violation_count',
        'status',
        'is_remedial',
        'remedial_of_id',
    ];
}

/**
 * Isi form sesuai kolom yang benar-benar dirender di
 * `admin/cbt/exams/_form.blade.php`.
 *
 * @param  array<string, mixed>  $ubah
 * @return array<string, mixed>
 */
function isianFormUjian(Exam $exam, array $ubah = []): array
{
    return array_merge([
        'rombel_id' => $exam->rombel_id,
        'mata_pelajaran_id' => $exam->mata_pelajaran_id,
        'name' => $exam->name,
        'description' => $exam->description,
        'duration_minutes' => $exam->duration_minutes,
        'available_from' => '2026-09-01 08:00',
        'available_until' => '2026-09-01 10:00',
        'max_violation_count' => $exam->max_violation_count,
        'shuffle_questions' => '1',
        'shuffle_options' => '1',
        'status' => 'draft',
        'is_remedial' => '0',
        'remedial_of_id' => '',
    ], $ubah);
}

test('form buat ujian memuat seluruh field yang divalidasi controller', function () {
    $html = $this->actingAs(superAdmin())->get(route('admin.cbt.exams.create'))
        ->assertOk()
        ->getContent();

    foreach (fieldUjianWajib() as $field) {
        expect($html)->toContain('name="'.$field.'"');
    }
});

test('form edit ujian punya kolom yang sama dengan form buat ujian', function () {
    $html = $this->actingAs(superAdmin())
        ->get(route('admin.cbt.exams.edit', buatExamTampilanUji()))
        ->assertOk()
        ->getContent();

    foreach (fieldUjianWajib() as $field) {
        expect($html)->toContain('name="'.$field.'"');
    }
});

test('ujian tersimpan dari isi form, termasuk batas pelanggaran', function () {
    $exam = buatExamTampilanUji();

    $this->actingAs(superAdmin())
        ->post(route('admin.cbt.exams.store'), isianFormUjian($exam, ['name' => 'Ujian Dari Form', 'max_violation_count' => 4]))
        ->assertRedirect(route('admin.cbt.exams.index'))
        ->assertSessionHasNoErrors();

    $tersimpan = Exam::where('name', 'Ujian Dari Form')->firstOrFail();

    expect((int) $tersimpan->max_violation_count)->toBe(4)
        ->and((int) $tersimpan->duration_minutes)->toBe((int) $exam->duration_minutes);
});

test('edit ujian tidak menghapus deskripsi, durasi, dan batas pelanggaran', function () {
    $exam = buatExamTampilanUji([
        'description' => 'Deskripsi asli',
        'duration_minutes' => 75,
        'max_violation_count' => 7,
    ]);

    $this->actingAs(superAdmin())
        ->put(route('admin.cbt.exams.update', $exam), isianFormUjian($exam))
        ->assertRedirect(route('admin.cbt.exams.index'))
        ->assertSessionHasNoErrors();

    $exam->refresh();

    expect($exam->description)->toBe('Deskripsi asli')
        ->and((int) $exam->duration_minutes)->toBe(75)
        ->and((int) $exam->max_violation_count)->toBe(7);
});

test('tanpa batas pelanggaran, simpan ditolak dan pesannya muncul di field', function () {
    $exam = buatExamTampilanUji();

    // Nama dibuat berbeda dari fixture supaya "tidak tersimpan" benar-benar
    // terukur — `where('name', $exam->name)` akan menemukan exam yang sudah
    // ada jauh sebelum POST.
    $isian = isianFormUjian($exam, ['name' => 'Ujian Tanpa Batas']);
    unset($isian['max_violation_count']);

    $this->actingAs(superAdmin())
        ->post(route('admin.cbt.exams.store'), $isian)
        ->assertRedirect();

    expect(Exam::where('name', 'Ujian Tanpa Batas')->exists())->toBeFalse();

    // Pesan diuji dari HTML yang benar-benar dirender, bukan dari
    // `assertSessionHasErrors()`: assertion itu membaca session lebih dulu sehingga
    // pesan yang justru ingin dibuktikan ikut terhapus sebelum halaman dibuka.
    $html = $this->get(route('admin.cbt.exams.create'))->assertOk()->getContent();

    expect($html)->toContain('Batas pelanggaran wajib diisi.')
        ->and($html)->toContain('is-invalid');
});

test('halaman hasil menampilkan form koreksi uraian berbatas bobot soal', function () {
    $exam = buatExamTampilanUji();
    $soal = ExamQuestion::create([
        'exam_id' => $exam->id,
        'question_text' => 'Jelaskan',
        'type' => 'essay',
        'score' => 10,
        'order' => 1,
    ]);
    ExamAnswer::create([
        'exam_session_id' => buatSesiSiswaTampilanUji($exam)->id,
        'exam_question_id' => $soal->id,
        'answer' => ['text' => 'Jawaban essay'],
        'answered_at' => now(),
    ]);

    $this->actingAs(superAdmin())->get(route('admin.cbt.results.index', $exam))
        ->assertOk()
        ->assertSee('name="score_obtained"', escape: false)
        ->assertSee('max="10"', escape: false);
});

test('nilai uraian melebihi bobot soal ditolak dan pesan hanya muncul di cell yang gagal', function () {
    $exam = buatExamTampilanUji();

    $soalA = ExamQuestion::create(['exam_id' => $exam->id, 'question_text' => 'Uraian A', 'type' => 'essay', 'score' => 10, 'order' => 1]);
    $soalB = ExamQuestion::create(['exam_id' => $exam->id, 'question_text' => 'Uraian B', 'type' => 'essay', 'score' => 5, 'order' => 2]);

    ExamAnswer::create([
        'exam_session_id' => buatSesiSiswaTampilanUji($exam)->id,
        'exam_question_id' => $soalA->id,
        'answer' => ['text' => 'A'],
        'answered_at' => now(),
    ]);
    $jawabanB = ExamAnswer::create([
        'exam_session_id' => buatSesiSiswaTampilanUji($exam)->id,
        'exam_question_id' => $soalB->id,
        'answer' => ['text' => 'B'],
        'answered_at' => now(),
    ]);

    // Soal B hanya bernilai 5, jadi 50 harus ditolak.
    $this->actingAs(superAdmin())
        ->post(route('admin.cbt.answers.nilai', $jawabanB), ['score_obtained' => 50]);

    expect((float) $jawabanB->fresh()->score_obtained)->toBe(0.0);

    $html = $this->get(route('admin.cbt.results.index', $exam))
        ->assertOk()
        ->getContent();

    // Semua cell memakai nama field yang sama; kalau penandanya hilang, pesan
    // akan muncul di setiap kolom sekaligus.
    expect(substr_count($html, 'data-cell-gagal'))->toBe(1)
        ->and($html)->toContain('data-cell-gagal="'.$jawabanB->id.'"');
});

test('nilai uraian yang sah tersimpan dan skor sesi dihitung ulang', function () {
    $exam = buatExamTampilanUji();

    $soal = ExamQuestion::create(['exam_id' => $exam->id, 'question_text' => 'Uraian', 'type' => 'essay', 'score' => 10, 'order' => 1]);
    $sesi = buatSesiSiswaTampilanUji($exam);
    $jawaban = ExamAnswer::create([
        'exam_session_id' => $sesi->id,
        'exam_question_id' => $soal->id,
        'answer' => ['text' => 'Jawaban essay'],
        'answered_at' => now(),
    ]);

    $this->actingAs(superAdmin())
        ->post(route('admin.cbt.answers.nilai', $jawaban), ['score_obtained' => 8])
        ->assertSessionHasNoErrors();

    expect((float) $jawaban->fresh()->score_obtained)->toBe(8.0)
        ->and((float) $sesi->fresh()->score)->toBe(8.0);
});
