<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionBank>
 */
class QuestionBankFactory extends Factory
{
    protected $model = QuestionBank::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $guru = Guru::factory();

        return [
            'mata_pelajaran_id' => MataPelajaran::factory(),
            'guru_id' => $guru,
            'nama' => 'Bank '.fake()->unique()->words(2, true),
            'deskripsi' => fake()->sentence(),
            'is_shared' => false,
            'created_by' => User::factory(),
        ];
    }

    public function publik(): static
    {
        return $this->state(fn (array $attributes): array => ['is_shared' => true]);
    }
}
