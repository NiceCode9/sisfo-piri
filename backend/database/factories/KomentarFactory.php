<?php

namespace Database\Factories;

use App\Models\Berita;
use App\Models\Komentar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Komentar>
 */
class KomentarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'berita_id' => Berita::factory(),
            'user_id' => null,
            'nama' => fake()->name(),
            'email' => fake()->safeEmail(),
            'isi' => fake()->sentence(),
            'status' => 'pending',
            'ip_address' => fake()->ipv4(),
        ];
    }
}
