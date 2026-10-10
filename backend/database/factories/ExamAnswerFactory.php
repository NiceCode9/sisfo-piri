<?php

namespace Database\Factories;

use App\Models\ExamAnswer;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAnswer>
 */
class ExamAnswerFactory extends Factory
{
    protected $model = ExamAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_session_id' => ExamSession::factory(),
            'exam_question_id' => ExamQuestion::factory(),
            'answer' => ['a'],
            'is_correct' => null,
            'score_obtained' => 0,
            'answered_at' => now(),
        ];
    }

    public function benar(float $poin = 5): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_correct' => true,
            'score_obtained' => $poin,
        ]);
    }

    public function salah(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_correct' => false,
            'score_obtained' => 0,
        ]);
    }

    public function uraian(string $teks = 'Jawaban uraian siswa'): static
    {
        return $this->state(fn (array $attributes): array => [
            'answer' => [$teks],
            'is_correct' => null,
            'score_obtained' => 0,
        ]);
    }

    /** Skor parsedial — hasil kompensasi parsial pilihan jamak. */
    public function parsial(float $poin): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_correct' => $poin > 0,
            'score_obtained' => $poin,
        ]);
    }
}
