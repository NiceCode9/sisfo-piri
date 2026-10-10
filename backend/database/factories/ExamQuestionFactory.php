<?php

namespace Database\Factories;

use App\Models\ExamQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamQuestion>
 */
class ExamQuestionFactory extends Factory
{
    protected $model = ExamQuestion::class;

    /**
     * Default-nya soal pilian tunggal yang KUNCINYA BENAR — kunci di luar
     * daftar opsi adalah salah konfigurasi yang dulu lolos tanpa validasi dan
     * membuat semua siswa dapat 0. Kalau memang butuh soal rusak, pakai
     * state()->kunciTidakAdaDiOpsi().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_text' => fake()->sentence(8),
            'question_image' => null,
            'type' => 'single_choice',
            'options' => [
                ['key' => 'a', 'text' => 'Opsi A'],
                ['key' => 'b', 'text' => 'Opsi B'],
                ['key' => 'c', 'text' => 'Opsi C'],
                ['key' => 'd', 'text' => 'Opsi D'],
            ],
            'correct_answer' => ['a'],
            'score' => 5,
            'order' => 1,
        ];
    }

    public function pilihanJamak(int $jumlahKunci = 2): static
    {
        return $this->state(function (array $attributes) use ($jumlahKunci): array {
            $semua = ['a', 'b', 'c', 'd'];

            return [
                'type' => 'multiple_choice',
                'correct_answer' => array_slice($semua, 0, max(2, $jumlahKunci)),
            ];
        });
    }

    public function uraian(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'essay',
            'options' => null,
            'correct_answer' => null,
        ]);
    }

    public function kunci(string ...$kunci): static
    {
        return $this->state(fn (array $attributes): array => ['correct_answer' => array_values($kunci)]);
    }

    /** Kunci yang tidak menunjuk opsi mana pun — harus ditolak validasi. */
    public function kunciTidakAdaDiOpsi(string $kunci = 'z'): static
    {
        return $this->state(fn (array $attributes): array => ['correct_answer' => [$kunci]]);
    }

    /** Pilihan tunggal dengan dua kunci — mustahil dijawab benar. */
    public function kunciGanda(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'single_choice',
            'correct_answer' => ['a', 'b'],
        ]);
    }

    public function milikUjian(int $examId): static
    {
        return $this->state(fn (array $attributes): array => [
            'exam_id' => $examId,
            'question_bank_id' => null,
        ]);
    }

    public function milikBank(int $bankId): static
    {
        return $this->state(fn (array $attributes): array => [
            'exam_id' => null,
            'question_bank_id' => $bankId,
        ]);
    }

    public function poin(int $poin): static
    {
        return $this->state(fn (array $attributes): array => ['score' => $poin]);
    }

    public function urutan(int $urut): static
    {
        return $this->state(fn (array $attributes): array => ['order' => $urut]);
    }
}
