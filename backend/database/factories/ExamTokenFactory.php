<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamToken>
 */
class ExamTokenFactory extends Factory
{
    protected $model = ExamToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'token' => strtoupper(Str::random(12)),
            'active_from' => now()->subHour(),
            'active_until' => now()->addHour(),
            'max_usage' => null,
            'used_count' => 0,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function tidakAktif(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }

    public function sudahLewat(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active_from' => now()->subDays(2),
            'active_until' => now()->subDay(),
        ]);
    }

    public function belumMulai(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active_from' => now()->addDay(),
            'active_until' => now()->addDays(2),
        ]);
    }

    public function kuota(int $maks, int $terpakai = 0): static
    {
        return $this->state(fn (array $attributes): array => [
            'max_usage' => $maks,
            'used_count' => $terpakai,
        ]);
    }

    public function denganNilai(string $token): static
    {
        return $this->state(fn (array $attributes): array => ['token' => $token]);
    }
}
