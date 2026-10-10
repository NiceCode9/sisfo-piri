<?php

namespace Database\Factories;

use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahunAjaran>
 *
 * @method static TahunAjaran aktif()
 */
class TahunAjaranFactory extends Factory
{
    protected $model = TahunAjaran::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_tahun_ajaran' => fake()->unique()->numerify('20##/20##'),
            'tanggal_mulai' => now()->startOfYear()->toDateString(),
            'tanggal_selesai' => now()->endOfYear()->toDateString(),
            'status_aktif' => false,
        ];
    }

    public function aktif(): static
    {
        return $this->state(fn (array $attributes): array => ['status_aktif' => true]);
    }
}
