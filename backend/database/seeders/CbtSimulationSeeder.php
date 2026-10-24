<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamToken;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\QuestionBank;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CbtSimulationSeeder extends Seeder
{
    public function run(): void
    {
        $tahun = TahunAjaran::firstOrCreate(
            ['nama_tahun_ajaran' => '2026/2027'],
            ['tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'status_aktif' => true]
        );

        $kelas = Kelas::firstOrCreate(['nama_kelas' => 'X-RPL-1', 'tingkat' => '10']);
        $rombel = Rombel::firstOrCreate(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);

        $mapel = MataPelajaran::firstOrCreate(
            ['kode' => 'MTK'],
            ['nama' => 'Matematika', 'kelompok' => 'A']
        );

        $guruUser = User::firstOrCreate(
            ['email' => 'guru.simulasi@sekolah.test'],
            ['name' => 'Guru Simulasi', 'username' => 'guru_simulasi', 'password' => Hash::make('password')]
        );
        $guruUser->assignRole('guru');
        $guru = Guru::firstOrCreate(
            ['user_id' => $guruUser->id],
            ['nip' => 'SIM001', 'nama' => 'Guru Simulasi', 'jenis_kelamin' => 'L', 'is_aktif' => true]
        );
        Pengampu::firstOrCreate([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'rombel_id' => $rombel->id,
        ]);

        foreach (['siswa1', 'siswa2'] as $name) {
            $u = User::firstOrCreate(
                ['email' => "{$name}.simulasi@sekolah.test"],
                ['name' => ucfirst($name).' Simulasi', 'username' => $name.'_simulasi', 'password' => Hash::make('password')]
            );
            $u->assignRole('siswa');
        }

        $bank = QuestionBank::firstOrCreate(
            ['guru_id' => $guru->id, 'mata_pelajaran_id' => $mapel->id, 'nama' => 'Bank Simulasi MTK'],
            ['deskripsi' => 'Bank soal untuk simulasi CBT', 'is_shared' => true, 'created_by' => $guruUser->id]
        );

        $templates = [
            ['type' => 'single_choice', 'text' => 'Hasil dari 7 × 8 adalah?', 'options' => [['key' => 'A', 'text' => '54'], ['key' => 'B', 'text' => '56'], ['key' => 'C', 'text' => '64'], ['key' => 'D', 'text' => '72']], 'correct' => ['B'], 'score' => 10],
            ['type' => 'single_choice', 'text' => 'Bentuk sederhana dari 2x + 3x adalah?', 'options' => [['key' => 'A', 'text' => '5x'], ['key' => 'B', 'text' => '6x'], ['key' => 'C', 'text' => '5x²'], ['key' => 'D', 'text' => '6x²']], 'correct' => ['A'], 'score' => 10],
            ['type' => 'single_choice', 'text' => 'Akar dari 144 adalah?', 'options' => [['key' => 'A', 'text' => '11'], ['key' => 'B', 'text' => '12'], ['key' => 'C', 'text' => '13'], ['key' => 'D', 'text' => '14']], 'correct' => ['B'], 'score' => 10],
            ['type' => 'single_choice', 'text' => 'Keliling persegi sisi 5 cm?', 'options' => [['key' => 'A', 'text' => '15 cm'], ['key' => 'B', 'text' => '20 cm'], ['key' => 'C', 'text' => '25 cm'], ['key' => 'D', 'text' => '10 cm']], 'correct' => ['B'], 'score' => 10],
            ['type' => 'multiple_choice', 'text' => 'Manakah bilangan prima? (pilih 2)', 'options' => [['key' => 'A', 'text' => '2'], ['key' => 'B', 'text' => '9'], ['key' => 'C', 'text' => '13'], ['key' => 'D', 'text' => '15']], 'correct' => ['A', 'C'], 'score' => 10],
            ['type' => 'multiple_choice', 'text' => 'Sifat komutatif berlaku untuk?', 'options' => [['key' => 'A', 'text' => 'Penjumlahan'], ['key' => 'B', 'text' => 'Pengurangan'], ['key' => 'C', 'text' => 'Perkalian'], ['key' => 'D', 'text' => 'Pembagian']], 'correct' => ['A', 'C'], 'score' => 10],
            ['type' => 'multiple_choice', 'text' => 'Bangun datar dengan 4 sisi sama?', 'options' => [['key' => 'A', 'text' => 'Persegi'], ['key' => 'B', 'text' => 'Segitiga'], ['key' => 'C', 'text' => 'Belah ketupat'], ['key' => 'D', 'text' => 'Lingkaran']], 'correct' => ['A', 'C'], 'score' => 10],
            ['type' => 'essay', 'text' => 'Jelaskan langkah menyelesaikan persamaan 2x + 5 = 15.', 'options' => null, 'correct' => null, 'score' => 15],
            ['type' => 'essay', 'text' => 'Jika harga awal Rp100.000 didiskon 20%, berapa harga akhir? Jelaskan.', 'options' => null, 'correct' => null, 'score' => 15],
            ['type' => 'essay', 'text' => 'Hitung luas persegi 8 cm x 8 cm dan jelaskan rumusnya.', 'options' => null, 'correct' => null, 'score' => 15],
        ];

        foreach ($templates as $order => $tpl) {
            ExamQuestion::firstOrCreate(
                ['question_bank_id' => $bank->id, 'question_text' => $tpl['text']],
                ['exam_id' => null, 'source_question_id' => null, 'question_image' => null, 'type' => $tpl['type'], 'options' => $tpl['options'], 'correct_answer' => $tpl['correct'], 'score' => $tpl['score'], 'order' => $order + 1]
            );
        }

        $exam = Exam::firstOrCreate(
            ['name' => 'Simulasi CBT MTK', 'rombel_id' => $rombel->id],
            ['mata_pelajaran_id' => $mapel->id, 'description' => 'Ujian simulasi untuk perbaikan CBT', 'duration_minutes' => 30, 'available_from' => now()->subDay(), 'available_until' => now()->addDays(7), 'max_violation_count' => 3, 'shuffle_questions' => true, 'shuffle_options' => true, 'status' => 'published', 'created_by' => $guruUser->id]
        );

        $bankQuestions = ExamQuestion::where('question_bank_id', $bank->id)->orderBy('order')->limit(10)->get();
        foreach ($bankQuestions as $bq) {
            ExamQuestion::firstOrCreate(
                ['exam_id' => $exam->id, 'source_question_id' => $bq->id],
                ['question_bank_id' => $bank->id, 'question_text' => $bq->question_text, 'question_image' => $bq->question_image, 'type' => $bq->type, 'options' => $bq->options, 'correct_answer' => $bq->correct_answer, 'score' => $bq->score, 'order' => $bq->order]
            );
        }

        ExamToken::firstOrCreate(
            ['exam_id' => $exam->id, 'token' => 'SIMCBT2026'],
            ['active_from' => now()->subHour(), 'active_until' => now()->addDays(7), 'max_usage' => null, 'is_active' => true, 'created_by' => $guruUser->id]
        );
    }
}
