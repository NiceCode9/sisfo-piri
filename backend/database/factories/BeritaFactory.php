<?php

namespace Database\Factories;

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Berita>
 */
class BeritaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judul = fake()->unique()->sentence(4);

        return [
            'user_id' => User::factory(),
            'kategori_berita_id' => KategoriBerita::factory(),
            'judul' => rtrim($judul, '.'),
            'slug' => Str::slug(rtrim($judul, '.')),
            'ringkasan' => fake()->sentence(),
            'konten' => fake()->paragraphs(3, true),
            'status' => 'draft',
            'published_at' => null,
            'views_count' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
