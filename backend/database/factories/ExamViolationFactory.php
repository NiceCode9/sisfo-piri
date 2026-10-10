<?php

namespace Database\Factories;

use App\Models\ExamSession;
use App\Models\ExamViolation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamViolation>
 */
class ExamViolationFactory extends Factory
{
    protected $model = ExamViolation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_session_id' => ExamSession::factory(),
            'type' => 'tab_blur',
            'meta' => null,
            'occurred_at' => now(),
        ];
    }

    public function jenis(string $type): static
    {
        return $this->state(fn (array $attributes): array => ['type' => $type]);
    }

    public function keluarFullscreen(): static
    {
        return $this->jenis('fullscreen_exit');
    }

    public function pindahTab(): static
    {
        return $this->jenis('tab_blur');
    }

    public function paste(): static
    {
        return $this->jenis('copy_paste_attempt');
    }

    /** Murni masalah jaringan — tidak dihitung sebagai curang. */
    public function koneksiPutus(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => 'connection_lost']);
    }
}
