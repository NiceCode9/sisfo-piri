<?php

/**
 * Regresi upload gambar soal + form tambah soal.
 *
 * Tiga masalah yang ditutup di sini:
 *
 * 1. Gambar soal hidup di disk PUBLIK (`/storage/cbt/questions/...` tanpa
 *    autentikasi) padahal isinya bank soal ujian — hal yang justru dilindungi
 *    di audit H4.
 * 2. API mengirim path storage mentah, lalu SPA memakainya sebagai `src`,
 *    sehingga resolve ke `/cbt/questions/...` dan 404. Gambar tidak pernah
 *    muncul di ujian.
 * 3. Form tambah soal di bank TIDAK punya field opsi/kunci padahal validasi M4
 *    mewajibkannya — jadi setiap soal pilihan gagal tersimpan dan guru tidak
 *    punya kolom untuk memperbaikinya.
 */

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\QuestionBank;
use App\Models\Rombel;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);

    // Disk privat yang dipakai gambar soal; fake supaya tidak menyentuh disk nyata.
    Storage::fake(ExamQuestion::DISK);
});

function gambarUji(): string
{
    return UploadedFile::fake()->image('soal.png', 200, 200)->store(ExamQuestion::IMAGE_DIR, ExamQuestion::DISK);
}

/**
 * Mengembalikan: admin, guru pengampu ROMBEL+MAPEL yang sama, bank, ujian.
 * Baris Guru sengaja ditautkan ke User yang benar-benar bisa login, supaya
 * test otorisasi memakai identitas nyata.
 */
function bankUji(): array
{
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $userGuru = User::factory()->create();
    $userGuru->assignRole('guru');

    $rombel = Rombel::factory()->create();
    $mapel = MataPelajaran::factory()->create();

    $guru = Guru::factory()->create(['user_id' => $userGuru->id]);
    Pengampu::create([
        'guru_id' => $guru->id,
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
    ]);

    $bank = QuestionBank::create([
        'mata_pelajaran_id' => $mapel->id,
        'guru_id' => $guru->id,
        'nama' => 'Bank Gambar',
        'created_by' => $admin->id,
    ]);

    $exam = Exam::factory()->create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'created_by' => $admin->id,
    ]);

    return [$admin, $userGuru, $bank, $exam];
}

function sesiAktifUji(Exam $exam): array
{
    $user = User::factory()->create();
    $user->assignRole('siswa');

    $token = ExamToken::factory()->create(['exam_id' => $exam->id]);
    $sesi = ExamSession::factory()->denganToken($token, $user)->create([
        'exam_id' => $exam->id,
        'status' => 'ongoing',
        'expected_end_at' => now()->addHour(),
    ]);

    return [$user, $sesi];
}

/*
 * 1-4: route gambar untuk siswa
 */

test('gambar soal menolak permintaan tanpa token', function () {
    $soal = ExamQuestion::factory()->milikUjian(bankUji()[3]->id)->create(['question_image' => gambarUji()]);

    $this->get("/api/cbt/exam/question-image/{$soal->id}")->assertUnauthorized();
});

test('gambar soal bank tidak pernah disajikan ke siswa', function () {
    [, , $bank] = bankUji();
    $soal = ExamQuestion::factory()->milikBank($bank->id)->create(['question_image' => gambarUji()]);

    // Siswa sengaja dibuat punya sesi ongoing pada ujian lain, supaya 404-nya
    // benar-benar karena soal bank — bukan karena tidak punya sesi.
    $examLain = Exam::factory()->create();
    [$user] = sesiAktifUji($examLain);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/cbt/exam/question-image/{$soal->id}")
        ->assertNotFound();
});

test('siswa tidak bisa mengambil gambar soal ujian yang tidak sedang dikerjakan', function () {
    [, , , $exam] = bankUji();
    $soal = ExamQuestion::factory()->milikUjian($exam->id)->create(['question_image' => gambarUji()]);

    // Ujian yang SEDANG dikerjakan.
    $examAktif = Exam::factory()->create();
    [$user] = sesiAktifUji($examAktif);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/cbt/exam/question-image/{$soal->id}")
        ->assertNotFound();
});

test('siswa bisa mengambil gambar soal ujiannya yang sedang aktif', function () {
    [, , , $exam] = bankUji();
    $soal = ExamQuestion::factory()->milikUjian($exam->id)->create(['question_image' => gambarUji()]);
    [$user] = sesiAktifUji($exam);

    $this->actingAs($user, 'sanctum')
        ->get("/api/cbt/exam/question-image/{$soal->id}")
        ->assertOk();
});

test('sesi yang sudah selesai tidak lagi berhak atas gambar soal', function () {
    [, , , $exam] = bankUji();
    $soal = ExamQuestion::factory()->milikUjian($exam->id)->create(['question_image' => gambarUji()]);

    $user = User::factory()->create();
    $user->assignRole('siswa');
    ExamSession::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $user->id,
        'status' => 'finished',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/cbt/exam/question-image/{$soal->id}")
        ->assertNotFound();
});

/*
 * 5: route gambar untuk admin
 */

test('guru lain tidak bisa membuka gambar soal ujian yang bukan miliknya', function () {
    [, , , $exam] = bankUji();
    $soal = ExamQuestion::factory()->milikUjian($exam->id)->create(['question_image' => gambarUji()]);

    $rombelLain = Rombel::factory()->create();
    $mapelLain = MataPelajaran::factory()->create();
    $guruLain = User::factory()->create();
    $guruLain->assignRole('guru');
    $barisGuru = Guru::factory()->create(['user_id' => $guruLain->id]);
    Pengampu::create([
        'guru_id' => $barisGuru->id,
        'rombel_id' => $rombelLain->id,
        'mata_pelajaran_id' => $mapelLain->id,
    ]);

    $this->actingAs($guruLain)
        ->get(route('admin.cbt.questions.image', $soal))
        ->assertForbidden();
});

test('guru pemilik boleh membuka gambar soalnya sendiri', function () {
    [, $guruUser, , $exam] = bankUji();
    $soal = ExamQuestion::factory()->milikUjian($exam->id)->create(['question_image' => gambarUji()]);

    // $guruUser sudah punya baris Guru + Pengampu yang menunjuk rombel & mapel
    // ujian ini, jadi ExamPolicy::view mengizinkan.
    $this->actingAs($guruUser)
        ->get(route('admin.cbt.questions.image', $soal))
        ->assertOk();
});

/*
 * 6-7: penghapusan file aman
 */

test('hapus soal bank tidak menghapus gambar yang masih dipakai salinan ujian', function () {
    [$admin, , $bank, $exam] = bankUji();

    $path = gambarUji();
    $soalBank = ExamQuestion::factory()->milikBank($bank->id)->create(['question_image' => $path]);
    $salinan = ExamQuestion::factory()->milikUjian($exam->id)->create(['question_image' => $path]);

    $this->actingAs($admin)
        ->delete(route('admin.cbt.banks.questions.destroy', $soalBank))
        ->assertRedirect();

    // importFromBank menyalin path apa adanya, jadi salinan masih butuh file ini.
    expect(ExamQuestion::whereKey($soalBank->id)->exists())->toBeFalse()
        ->and(ExamQuestion::whereKey($salinan->id)->exists())->toBeTrue()
        ->and(Storage::disk(ExamQuestion::DISK)->exists($path))->toBeTrue();
});

test('hapus referensi terakhir benar-benar menghapus berkasnya', function () {
    [$admin, , $bank] = bankUji();

    $path = gambarUji();
    $soalBank = ExamQuestion::factory()->milikBank($bank->id)->create(['question_image' => $path]);

    $this->actingAs($admin)
        ->delete(route('admin.cbt.banks.questions.destroy', $soalBank))
        ->assertRedirect();

    expect(Storage::disk(ExamQuestion::DISK)->exists($path))->toBeFalse();
});

test('hapus soal ujian juga menghapus gambarnya', function () {
    [$admin, , , $exam] = bankUji();

    $path = gambarUji();
    $soal = ExamQuestion::factory()->milikUjian($exam->id)->create(['question_image' => $path]);

    $this->actingAs($admin)
        ->delete(route('admin.cbt.questions.destroy', $soal))
        ->assertRedirect();

    // Sebelumnya ExamQuestionController sama sekali tidak menghapus file.
    expect(Storage::disk(ExamQuestion::DISK)->exists($path))->toBeFalse();
});

/*
 * 8: kontrak API
 */

test('API mengirim url gambar, bukan path storage mentah', function () {
    [, , , $exam] = bankUji();
    ExamQuestion::factory()->milikUjian($exam->id)->create([
        'question_image' => gambarUji(),
        'order' => 1,
    ]);
    [$user, $sesi] = sesiAktifUji($exam);

    $respons = $this->actingAs($user, 'sanctum')
        ->getJson("/api/cbt/exam/questions?exam_session_id={$sesi->id}")
        ->assertOk();

    $gambar = $respons->json('questions.0.question_image');

    // Path storage mentah membocorkan struktur disk dan 404 di SPA.
    expect($gambar)->toContain('/api/cbt/exam/question-image/')
        ->and($gambar)->not->toContain('cbt/questions/');
});

test('API mengirim null untuk soal tanpa gambar', function () {
    [, , , $exam] = bankUji();
    ExamQuestion::factory()->milikUjian($exam->id)->create(['question_image' => null, 'order' => 1]);
    [$user, $sesi] = sesiAktifUji($exam);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/cbt/exam/questions?exam_session_id={$sesi->id}")
        ->assertOk()
        ->assertJsonPath('questions.0.question_image', null);
});

/*
 * 9-10: penjaga regresi form
 */

test('soal uraian tersimpan tanpa opsi dan tanpa kunci', function () {
    [$admin, , $bank] = bankUji();
    // Persis payload yang dikirim form saat tipe=Uraian: field opsi DISABLE
    // sehingga tidak ikut terkirim sama sekali.
    $this->actingAs($admin)
        ->post(route('admin.cbt.banks.questions.store', $bank), [
            'question_text' => 'Jelaskan proses fotosintesis.',
            'type' => 'essay',
            'score' => 15,
        ])
        ->assertSessionHasNoErrors();

    $soal = ExamQuestion::where('question_text', 'Jelaskan proses fotosintesis.')->first();

    expect($soal)->not->toBeNull()
        ->and($soal->type)->toBe('essay')
        ->and($soal->options)->toBeNull()
        ->and($soal->correct_answer)->toBeNull();
});

test('soal pilihan dari form tersimpan lengkap dengan opsi dan kunci', function () {
    [$admin, , $bank] = bankUji();
    $this->actingAs($admin)
        ->post(route('admin.cbt.banks.questions.store', $bank), [
            'question_text' => 'Hasil dari 2 + 2 adalah?',
            'type' => 'single_choice',
            'score' => 10,
            'options' => [
                ['key' => 'a', 'text' => '3'],
                ['key' => 'b', 'text' => '4'],
                ['key' => 'c', 'text' => '5'],
            ],
            'correct_answer' => ['b'],
        ])
        ->assertSessionHasNoErrors();

    $soal = ExamQuestion::where('question_text', 'Hasil dari 2 + 2 adalah?')->first();

    expect($soal)->not->toBeNull()
        ->and($soal->correct_answer)->toBe(['b'])
        ->and(collect($soal->options)->pluck('key')->all())->toBe(['a', 'b', 'c']);
});

test('pilihan jamak menerima kunci dari beberapa opsi', function () {
    [$admin, , $bank] = bankUji();
    $this->actingAs($admin)
        ->post(route('admin.cbt.banks.questions.store', $bank), [
            'question_text' => 'Pilih bilangan prima.',
            'type' => 'multiple_choice',
            'score' => 10,
            'options' => [
                ['key' => 'a', 'text' => '2'],
                ['key' => 'b', 'text' => '9'],
                ['key' => 'c', 'text' => '13'],
            ],
            'correct_answer' => ['a', 'c'],
        ])
        ->assertSessionHasNoErrors();

    expect(ExamQuestion::where('question_text', 'Pilih bilangan prima.')->first()->correct_answer)
        ->toBe(['a', 'c']);
});

test('kedua form memakai partial yang sama', function () {
    [$admin, , $bank, $exam] = bankUji();

    foreach ([route('admin.cbt.banks.show', $bank), route('admin.cbt.exams.show', $exam)] as $url) {
        $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

        // Kalau salah satu form keluar dari partial bersama, keduanya bisa
        // berbeda lagi — dan itulah akar regresi yang pernah terjadi.
        expect($html)->toContain('soal-daftar-opsi')
            ->and($html)->toContain('correct_answer[]');
    }
});

test('form bank punya opsi dan kunci — regresi M4', function () {
    [$admin, , $bank] = bankUji();

    // Tanpa field ini, validasi kunci menolak SETIAP soal pilihan dan guru
    // tidak punya kolom untuk memperbaikinya.
    $html = $this->actingAs($admin)->get(route('admin.cbt.banks.show', $bank))->assertOk()->getContent();

    expect($html)->toContain('soal-daftar-opsi')
        ->and($html)->toContain('data-kunci');
});
