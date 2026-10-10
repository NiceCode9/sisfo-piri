<?php

/**
 * L2: factory CBT harus bisa dipakai apa adanya di test, dan harus menghasilkan
 * data yang VALID. Factory ExamQuestion sebelumnya tidak ada sama sekali,
 * sehingga test menuliskan soal pilihan tunggal tanpa opsi dan tanpa kunci —
 * persis bentuk data rusak yang dulu lolos tanpa validasi.
 */

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\ExamViolation;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\QuestionBank;
use App\Models\Rombel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('exam factory membuat ujian lengkap dan published', function () {
    $exam = Exam::factory()->create();

    expect($exam->exists)->toBeTrue()
        ->and($exam->rombel)->not->toBeNull()
        ->and($exam->mataPelajaran)->not->toBeNull()
        ->and($exam->status)->toBe('published')
        ->and($exam->duration_minutes)->toBe(60);
});

test('exam factory punya state draft dan published', function () {
    expect(Exam::factory()->draft()->create()->status)->toBe('draft')
        ->and(Exam::factory()->published()->create()->status)->toBe('published');
});

test('exam factory punya state jendela waktu', function () {
    expect(Exam::factory()->belumMulai()->create()->available_from->isFuture())->toBeTrue()
        ->and(Exam::factory()->sudahBerakhir()->create()->available_until->isPast())->toBeTrue()
        ->and(Exam::factory()->tanpaJendela()->create()->available_from)->toBeNull();
});

test('exam question factory menghasilkan kunci yang benar-benar ada di opsi', function () {
    $soal = ExamQuestion::factory()->create();

    $optionKeys = collect($soal->options)->pluck('key');

    expect($soal->type)->toBe('single_choice')
        ->and($soal->correct_answer)->toHaveCount(1)
        ->and($optionKeys)->toContain($soal->correct_answer[0]);
});

test('exam question factory bisa membuat pilihan jamak yang sah', function () {
    $soal = ExamQuestion::factory()->pilihanJamak(2)->create();

    expect($soal->type)->toBe('multiple_choice')
        ->and($soal->correct_answer)->toHaveCount(2);

    foreach ($soal->correct_answer as $kunci) {
        expect(collect($soal->options)->pluck('key'))->toContain($kunci);
    }
});

test('exam question factory bisa membuat soal uraian tanpa kunci', function () {
    $soal = ExamQuestion::factory()->uraian()->create();

    expect($soal->type)->toBe('essay')
        ->and($soal->correct_answer)->toBeNull()
        ->and($soal->options)->toBeNull();
});

test('exam question factory bisa membuat purposefully rusak untuk test penolakan', function () {
    $rusak = ExamQuestion::factory()->kunciTidakAdaDiOpsi()->create();
    expect(collect($rusak->options)->pluck('key'))->not->toContain($rusak->correct_answer[0]);

    $ganda = ExamQuestion::factory()->kunciGanda()->create();
    expect($ganda->type)->toBe('single_choice')->and($ganda->correct_answer)->toHaveCount(2);
});

test('exam question bisa dibuat milik ujian maupun milik bank', function () {
    $exam = Exam::factory()->create();
    $bank = QuestionBank::factory()->create();

    $soalUjian = ExamQuestion::factory()->milikUjian($exam->id)->create();
    $soalBank = ExamQuestion::factory()->milikBank($bank->id)->create();

    expect($soalUjian->bank)->toBeNull()
        ->and($soalBank->exam)->toBeNull()
        ->and($exam->questions()->count())->toBe(1);
});

test('token factory punya seluruh state jendela dan kuota', function () {
    expect(ExamToken::factory()->create()->is_active)->toBeTrue()
        ->and(ExamToken::factory()->tidakAktif()->create()->is_active)->toBeFalse()
        ->and(ExamToken::factory()->belumMulai()->create()->active_from->isFuture())->toBeTrue()
        ->and(ExamToken::factory()->sudahLewat()->create()->active_until->isPast())->toBeTrue();

    $kuota = ExamToken::factory()->kuota(3, 3)->create();
    expect($kuota->max_usage)->toBe(3)->and($kuota->used_count)->toBe(3);
});

test('session factory punya state waktu dan status', function () {
    $berjalan = ExamSession::factory()->create();
    expect($berjalan->status)->toBe('ongoing')
        ->and($berjalan->expected_end_at->isFuture())->toBeTrue();

    $lewat = ExamSession::factory()->lewatWaktu()->create();
    expect($lewat->expected_end_at->isPast())->toBeTrue();

    expect(ExamSession::factory()->selesai(80)->create()->status)->toBe('finished')
        ->and(ExamSession::factory()->selesai(80)->create()->finish_reason)->toBe('manual')
        ->and(ExamSession::factory()->tidakLolos(3)->create()->status)->toBe('disqualified')
        ->and(ExamSession::factory()->kedaluwarsa()->create()->status)->toBe('expired');
});

test('session factory bisa dibangun dari token tanpa melanggar unique index', function () {
    $token = ExamToken::factory()->create();
    $user = User::factory()->create();

    $sesi = ExamSession::factory()->denganToken($token, $user)->create();

    expect($sesi->exam_token_id)->toBe($token->id)
        ->and($sesi->user_id)->toBe($user->id);
});

test('heartbeat factory membedakan online dan offline', function () {
    expect(ExamSession::factory()->baruPing()->create()
        ->last_heartbeat_at->gt(now()->subSeconds(30)))->toBeTrue()
        ->and(ExamSession::factory()->heartbeatBasi()->create()
            ->last_heartbeat_at->gt(now()->subSeconds(30)))->toBeFalse();
});

test('answer factory punya state penilaian', function () {
    expect((float) ExamAnswer::factory()->benar(10)->create()->score_obtained)->toBe(10.0)
        ->and(ExamAnswer::factory()->benar(10)->create()->is_correct)->toBeTrue()
        ->and((float) ExamAnswer::factory()->salah()->create()->score_obtained)->toBe(0.0)
        ->and(ExamAnswer::factory()->salah()->create()->is_correct)->toBeFalse()
        ->and(ExamAnswer::factory()->uraian('Teks')->create()->answer)->toBe(['Teks'])
        ->and((float) ExamAnswer::factory()->parsial(3.5)->create()->score_obtained)->toBe(3.5);
});

test('violation factory hanya memuat tipe yang diizinkan enum', function () {
    $diizinkan = ['fullscreen_exit', 'tab_blur', 'visibility_hidden', 'devtools_suspected', 'copy_paste_attempt', 'connection_lost'];

    foreach ($diizinkan as $tipe) {
        $v = ExamViolation::factory()->jenis($tipe)->create();
        expect($v->type)->toBe($tipe);
    }

    expect(ExamViolation::factory()->koneksiPutus()->create()->type)->toBe('connection_lost');
});

test('question bank factory punya guru dan mapel', function () {
    $bank = QuestionBank::factory()->create();

    expect($bank->guru)->toBeInstanceOf(Guru::class)
        ->and($bank->mataPelajaran)->toBeInstanceOf(MataPelajaran::class)
        ->and($bank->is_shared)->toBeFalse()
        ->and(QuestionBank::factory()->publik()->create()->is_shared)->toBeTrue();
});

test('faktori CBT bisa menyusun skenario ujian lengkap tanpa boilerplate', function () {
    $guru = Guru::factory()->create();
    $rombel = Rombel::factory()->create();
    $mapel = MataPelajaran::factory()->create();

    Pengampu::create([
        'guru_id' => $guru->id,
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
    ]);

    $exam = Exam::factory()
        ->create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id]);

    ExamQuestion::factory()->milikUjian($exam->id)->urutan(1)->create();
    ExamQuestion::factory()->milikUjian($exam->id)->urutan(2)->uraian()->create();

    $token = ExamToken::factory()->create(['exam_id' => $exam->id]);
    $sesi = ExamSession::factory()->denganToken($token)->lewatWaktu()->create();

    expect($exam->questions()->count())->toBe(2)
        ->and($sesi->status)->toBe('ongoing')
        ->and($sesi->exam_id)->toBe($exam->id);
});
