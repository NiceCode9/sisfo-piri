<?php

// database/seeders/LoadTestSeeder.php
//
// Seeder KHUSUS untuk keperluan load test. JANGAN dijalankan di database
// produksi yang sudah berisi data siswa/ujian asli — jalankan di environment
// staging yang terpisah, atau di production HANYA menjelang hari-H dengan
// exam/token yang jelas ditandai sebagai "load test" dan dihapus sesudahnya.
//
// Cara pakai:
//   php artisan db:seed --class=LoadTestSeeder
//
// Setelah dijalankan, seeder ini juga generate file students.json yang
// dipakai oleh skrip k6 (load-test-cbt.js) — taruh file itu satu folder
// dengan skrip k6-nya.

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamToken;
use App\Models\Rombel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoadTestSeeder extends Seeder
{
    public function run(): void
    {
        $studentCount = 500;
        $questionCount = 20;

        $this->command->info("Membuat {$studentCount} akun siswa dummy...");

        $students = [];
        $plainPassword = 'password123';

        for ($i = 1; $i <= $studentCount; $i++) {
            $identifier = sprintf('loadtest_siswa%04d@test.local', $i);
            $username = sprintf('loadtest_siswa%04d', $i);

            $user = User::updateOrCreate(
                ['email' => $identifier],
                [
                    'username' => $username,
                    'name' => "Load Test Siswa {$i}",
                    'email' => $identifier,
                    'password' => Hash::make($plainPassword),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('siswa');

            $students[] = [
                'identifier' => $identifier,
                'password' => $plainPassword,
            ];
        }

        file_put_contents(
            base_path('../students.json'), // sesuaikan path taruh file ini
            json_encode($students, JSON_PRETTY_PRINT)
        );
        $this->command->info('students.json berhasil dibuat.');

        $this->command->info('Membuat exam dummy + soal...');

        $teacher = User::role('guru')->first() ?? User::role('super-admin')->first();
        if (! $teacher) {
            $this->command->error('Tidak ada user guru — buat dulu sebelum jalankan seeder ini.');

            return;
        }

        $rombel = Rombel::first();
        if (! $rombel) {
            $this->command->error('Tidak ada rombel — seed akademik dulu.');

            return;
        }

        $exam = Exam::create([
            'rombel_id' => $rombel->id,
            'name' => 'LOAD TEST — Jangan Dipakai Ujian Asli',
            'description' => 'Exam khusus load testing, hapus setelah selesai.',
            'duration_minutes' => 60,
            'available_from' => now()->subDay(),
            'available_until' => now()->addDays(7),
            'max_violation_count' => 999,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'status' => 'published',
            'created_by' => $teacher->id,
        ]);

        for ($i = 1; $i <= $questionCount; $i++) {
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'question_text' => "[LOAD TEST] Ini soal dummy nomor {$i}. Pilih salah satu jawaban di bawah.",
                'type' => 'single_choice',
                'options' => [
                    ['key' => 'A', 'text' => 'Opsi A'],
                    ['key' => 'B', 'text' => 'Opsi B'],
                    ['key' => 'C', 'text' => 'Opsi C'],
                    ['key' => 'D', 'text' => 'Opsi D'],
                ],
                'correct_answer' => ['A'],
                'score' => 5,
                'order' => $i,
            ]);
        }

        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'token' => 'LOADTEST'.strtoupper(Str::random(6)),
            'active_from' => now()->subHour(),
            'active_until' => now()->addDays(7),
            'max_usage' => null,
            'is_active' => true,
            'created_by' => $teacher->id,
        ]);

        $this->command->info("Selesai. Token ujian untuk load test: {$token->token}");
        $this->command->info("Jalankan k6 dengan: k6 run -e EXAM_TOKEN={$token->token} -e VUS=500 load-test-cbt.js");
    }
}
