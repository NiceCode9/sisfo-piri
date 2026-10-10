<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\MataPelajaran;
use App\Models\Rombel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 *
 * Default-nya membuat ujian `published` yang sudah punya rombel, mapel, dan
 * pembuat — cukup untuk dipakai langsung di test tanpa boilerplate. Keadaan
 * lain biasanya dibutuhkan pindah lewat state().
 *
 * @method static Exam published()
 * @method static Exam draft()
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rombel_id' => Rombel::factory(),
            'mata_pelajaran_id' => MataPelajaran::factory(),
            'name' => 'Ujian '.fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'duration_minutes' => 60,
            'max_violation_count' => 3,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'status' => 'published',
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'draft']);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'published']);
    }

    /**
     * Ujian dengan jendela waktu yang belum dibuka — dipakai untuk menguji
     * gate available_from di ExamSessionController::join().
     */
    public function belumMulai(): static
    {
        return $this->state(fn (array $attributes): array => [
            'available_from' => now()->addDay(),
            'available_until' => now()->addDays(2),
        ]);
    }

    public function sudahBerakhir(): static
    {
        return $this->state(fn (array $attributes): array => [
            'available_from' => now()->subDays(2),
            'available_until' => now()->subDay(),
        ]);
    }

    public function tanpaJendela(): static
    {
        return $this->state(fn (array $attributes): array => [
            'available_from' => null,
            'available_until' => null,
        ]);
    }

    public function durasi(int $menit): static
    {
        return $this->state(fn (array $attributes): array => ['duration_minutes' => $menit]);
    }

    public function Violasi(int $maks): static
    {
        return $this->state(fn (array $attributes): array => ['max_violation_count' => $maks]);
    }
}
