<?php

namespace Database\Factories;

use App\Models\MataPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MataPelajaran>
 *
 * @method static MataPelajaran aktif()
 */
class MataPelajaranFactory extends Factory
{
    protected $model = MataPelajaran::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->lexify('??##')),
            'nama' => 'Mapel '.fake()->unique()->words(2, true),
            'kelompok' => 'A',
            'is_aktif' => true,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes): array => ['is_aktif' => false]);
    }
}
