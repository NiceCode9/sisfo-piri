<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guru>
 *
 * @method static Guru untukUser(User $user)
 */
class GuruFactory extends Factory
{
    protected $model = Guru::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nip' => fake()->unique()->numerify('19#########'),
            'nama' => fake()->name(),
            'jenis_kelamin' => 'L',
            'telp' => fake()->numerify('08##########'),
            'alamat' => fake()->address(),
            'is_aktif' => true,
        ];
    }

    /** Baris Guru yang menempel ke user tertentu — dipakai test otorisasi. */
    public function untukUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
            'nama' => $user->name,
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes): array => ['is_aktif' => false]);
    }
}
