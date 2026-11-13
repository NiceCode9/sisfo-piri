<?php

namespace Database\Factories;

use App\Models\KategoriBerita;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KategoriBerita>
 */
class KategoriBeritaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->unique()->words(2, true);

        return [
            'nama' => ucfirst($nama),
            'slug' => Str::slug($nama),
            'deskripsi' => fake()->sentence(),
        ];
    }
}
