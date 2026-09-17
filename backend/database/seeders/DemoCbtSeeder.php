<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamToken;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\QuestionBank;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoCbtSeeder extends Seeder
{
    public function run(): void
    {
        $tahun = TahunAjaran::aktif()->first();
        if (! $tahun) {
            $this->command?->warn('DemoCbtSeeder: TahunAjaran aktif tidak ditemukan, skip.');

            return;
        }

        $mtk = MataPelajaran::where('kode', 'MTK')->first() ?? MataPelajaran::aktif()->first();
        if (! $mtk) {
            $this->command?->warn('DemoCbtSeeder: MataPelajaran tidak ditemukan, skip.');

            return;
        }

        $rombels = Rombel::with('kelas')->where('tahun_ajaran_id', $tahun->id)->orderBy('id')->limit(2)->get();
        if ($rombels->count() < 2) {
            $rombels = Rombel::with('kelas')->orderBy('id')->limit(2)->get();
        }
        if ($rombels->isEmpty()) {
            $this->command?->warn('DemoCbtSeeder: Rombel tidak ditemukan, skip.');

            return;
        }

        $gurus = Guru::aktif()->orderBy('id')->limit(2)->get();
        if ($gurus->count() < 2) {
            $gurus = Guru::orderBy('id')->limit(2)->get();
        }

        // Pastikan tiap rombel ada pengampu MTK (pakai guru existing bila sudah ada, agar tidak langgar unique [mapel,rombel])
        foreach ($rombels as $idx => $rombel) {
            $existing = Pengampu::where('mata_pelajaran_id', $mtk->id)->where('rombel_id', $rombel->id)->first();
            if (! $existing) {
                $guru = $gurus[$idx % $gurus->count()];
                Pengampu::create(['guru_id' => $guru->id, 'mata_pelajaran_id' => $mtk->id, 'rombel_id' => $rombel->id]);
            }
        }

        // Bank per guru×mapel — lintas rombel/tahun
        $banks = [];
        foreach ($gurus->take(2) as $guru) {
            $bank = QuestionBank::firstOrCreate(
                ['guru_id' => $guru->id, 'mata_pelajaran_id' => $mtk->id, 'nama' => 'Bank MTK Demo '.$guru->nama],
                ['deskripsi' => 'Bank soal CBT demo — MTK lintas rombel/tahun', 'is_shared' => true, 'created_by' => $guru->user_id]
            );
            $banks[] = $bank;

            // 10 soal bank: 4 single, 3 multiple, 3 essay (1 dengan image)
            $templates = [
                ['type' => 'single_choice', 'text' => 'Hasil dari 7 × 8 adalah?', 'options' => [['key' => 'A', 'text' => '54'], ['key' => 'B', 'text' => '56'], ['key' => 'C', 'text' => '64'], ['key' => 'D', 'text' => '72']], 'correct' => ['B'], 'score' => 10],
                ['type' => 'single_choice', 'text' => 'Bentuk sederhana dari 2x + 3x adalah?', 'options' => [['key' => 'A', 'text' => '5x'], ['key' => 'B', 'text' => '6x'], ['key' => 'C', 'text' => '5x²'], ['key' => 'D', 'text' => '6x²']], 'correct' => ['A'], 'score' => 10],
                ['type' => 'single_choice', 'text' => 'Akar dari 144 adalah?', 'options' => [['key' => 'A', 'text' => '11'], ['key' => 'B', 'text' => '12'], ['key' => 'C', 'text' => '13'], ['key' => 'D', 'text' => '14']], 'correct' => ['B'], 'score' => 10],
                ['type' => 'single_choice', 'text' => 'Keliling persegi sisi 5 cm?', 'options' => [['key' => 'A', 'text' => '15 cm'], ['key' => 'B', 'text' => '20 cm'], ['key' => 'C', 'text' => '25 cm'], ['key' => 'D', 'text' => '10 cm']], 'correct' => ['B'], 'score' => 10],
                ['type' => 'multiple_choice', 'text' => 'Manakah bilangan prima? (pilih 2)', 'options' => [['key' => 'A', 'text' => '2'], ['key' => 'B', 'text' => '9'], ['key' => 'C', 'text' => '13'], ['key' => 'D', 'text' => '15']], 'correct' => ['A', 'C'], 'score' => 10],
                ['type' => 'multiple_choice', 'text' => 'Sifat komutatif berlaku untuk?', 'options' => [['key' => 'A', 'text' => 'Penjumlahan'], ['key' => 'B', 'text' => 'Pengurangan'], ['key' => 'C', 'text' => 'Perkalian'], ['key' => 'D', 'text' => 'Pembagian']], 'correct' => ['A', 'C'], 'score' => 10],
                ['type' => 'multiple_choice', 'text' => 'Bangun datar dengan 4 sisi sama?', 'options' => [['key' => 'A', 'text' => 'Persegi'], ['key' => 'B', 'text' => 'Segitiga'], ['key' => 'C', 'text' => 'Belah ketupat'], ['key' => 'D', 'text' => 'Lingkaran']], 'correct' => ['A', 'C'], 'score' => 10],
                ['type' => 'essay', 'text' => 'Jelaskan langkah menyelesaikan persamaan 2x + 5 = 15.', 'options' => null, 'correct' => null, 'score' => 15],
                ['type' => 'essay', 'text' => 'Sebuah toko memberi diskon 20%. Jika harga awal Rp100.000, berapa harga setelah diskon? Jelaskan.', 'options' => null, 'correct' => null, 'score' => 15],
                ['type' => 'essay', 'text' => 'Perhatikan gambar persegi berikut (sisi 8 cm). Hitung luasnya dan jelaskan rumus yang dipakai.', 'options' => null, 'correct' => null, 'score' => 15, 'with_image' => true],
            ];

            foreach ($templates as $order => $tpl) {
                $image = null;
                if (! empty($tpl['with_image'])) {
                    $path = 'cbt/questions/demo-'.$bank->id.'-'.($order + 1).'.txt';
                    if (! Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->put($path, 'Placeholder image soal '.$bank->nama.' #'.($order + 1));
                    }
                    $image = $path;
                }

                ExamQuestion::firstOrCreate(
                    ['question_bank_id' => $bank->id, 'question_text' => $tpl['text']],
                    ['exam_id' => null, 'source_question_id' => null, 'question_image' => $image, 'type' => $tpl['type'], 'options' => $tpl['options'], 'correct_answer' => $tpl['correct'], 'score' => $tpl['score'], 'order' => $order + 1]
                );
            }
        }

        // 2 Exam demo — per rombel, snapshot 10 soal dari bank masing-masing
        foreach ($rombels->take(2) as $idx => $rombel) {
            $guru = $gurus[$idx % $gurus->count()];
            $bank = $banks[$idx % count($banks)];
            $suffix = $rombel->kelas->nama_kelas ?? ('R'.$rombel->id);
            $exam = Exam::firstOrCreate(
                ['name' => 'UTS Matematika '.$suffix.' — Demo', 'rombel_id' => $rombel->id],
                ['mata_pelajaran_id' => $mtk->id, 'description' => 'Ujian demo CBT — copy-snapshot dari '.$bank->nama, 'duration_minutes' => 60, 'available_from' => now()->subDay(), 'available_until' => now()->addDays(7), 'max_violation_count' => 3, 'shuffle_questions' => true, 'shuffle_options' => true, 'status' => 'published', 'created_by' => $guru->user_id]
            );

            $bankQuestions = ExamQuestion::where('question_bank_id', $bank->id)->orderBy('order')->limit(10)->get();
            foreach ($bankQuestions as $bq) {
                ExamQuestion::firstOrCreate(
                    ['exam_id' => $exam->id, 'source_question_id' => $bq->id],
                    ['question_bank_id' => $bank->id, 'question_text' => $bq->question_text, 'question_image' => $bq->question_image, 'type' => $bq->type, 'options' => $bq->options, 'correct_answer' => $bq->correct_answer, 'score' => $bq->score, 'order' => $bq->order]
                );
            }

            ExamToken::firstOrCreate(
                ['exam_id' => $exam->id, 'token' => 'DEMOMTK'.strtoupper(substr($suffix, -2)).str_pad((string) $rombel->id, 2, '0', STR_PAD_LEFT)],
                ['active_from' => now()->subHour(), 'active_until' => now()->addDays(7), 'max_usage' => null, 'is_active' => true, 'created_by' => $guru->user_id]
            );
        }
    }
}
