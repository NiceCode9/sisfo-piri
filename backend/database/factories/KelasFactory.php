<?php

namespace Database\Factories;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 *
 * Prasyarat untuk factory Rombel, yang dipakai factory Exam.
 *
 * @method static Kelas tingkat(string $tingkat)
 */
class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_kelas' => fake()->unique()->lexify('10??'),
            'tingkat' => '10',
            'deskripsi' => null,
        ];
    }

    public function tingkat(string $tingkat): static
    {
        return $this->state(fn (array $attributes): array => ['tingkat' => $tingkat]);
    }
}
