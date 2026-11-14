<?php

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
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
    Storage::fake(ExamQuestion::DISK);
});

test('upload gambar soal ke exam', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $exam = Exam::factory()->create();

    $file = UploadedFile::fake()->image('test.png', 100, 100);

    $response = $this->actingAs($admin)
        ->post(route('admin.cbt.exams.questions.store', $exam), [
            'question_text' => 'Soal dengan gambar',
            'type' => 'single_choice',
            'score' => 5,
            'order' => 1,
            'options' => [['key' => 'a', 'text' => 'Opsi A'], ['key' => 'b', 'text' => 'Opsi B']],
            'correct_answer' => ['a'],
            'question_image' => $file,
        ]);

    dump('status:', $response->getStatusCode());
    dump('errors:', $response->getSession()->get('errors')?->all() ?? 'none');

    $q = ExamQuestion::latest()->first();
    dump('question_image:', $q->question_image);
    expect($q->question_image)->not->toBeNull();
});
