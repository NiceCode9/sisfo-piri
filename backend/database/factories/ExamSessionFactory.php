<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamSession>
 *
 * Default-nya sesi `ongoing` yang masih punya sisa waktu 2 jam.
 */
class ExamSessionFactory extends Factory
{
    protected $model = ExamSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = now();

        return [
            'exam_id' => Exam::factory(),
            // exam_token_id bertanda NOT NULL di DB. Token dibuat mengikuti
            // exam_id supaya keduanya selalu konsisten — kalau dibiarkan
            // terpisah, factory menghasilkan sesi yang menunjuk token milik
            // ujian lain, dan unique(['exam_token_id','user_id']) bisa bentrok.
            'exam_token_id' => function (array $attributes) {
                return ExamToken::factory()
                    ->create(['exam_id' => $attributes['exam_id']])
                    ->id;
            },
            'user_id' => User::factory(),
            'started_at' => $mulai,
            'expected_end_at' => $mulai->copy()->addHours(2),
            'finished_at' => null,
            'last_heartbeat_at' => null,
            'status' => 'ongoing',
            'finish_reason' => null,
            'violation_count' => 0,
            'score' => null,
            'client_ip' => '127.0.0.1',
            'user_agent' => 'Factory/Pest',
        ];
    }

    /**
     * Sesi yang punya token — memakai kolom unique(['exam_token_id','user_id']).
     */
    public function denganToken(ExamToken $token, ?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'exam_id' => $token->exam_id,
            'exam_token_id' => $token->id,
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    /** Sesi yang sudah lewat waktu — target dari sweeper cbt:expire-sessions. */
    public function lewatWaktu(): static
    {
        return $this->state(function (array $attributes): array {
            $mulai = now()->subHours(3);

            return [
                'started_at' => $mulai,
                'expected_end_at' => $mulai->copy()->addHours(2),
            ];
        });
    }

    public function selesai(float $skor = 75): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'finished',
            'finish_reason' => 'manual',
            'finished_at' => now(),
            'score' => $skor,
        ]);
    }

    public function tidakLolos(int $pelanggaran): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'disqualified',
            'finish_reason' => 'violation_limit',
            'finished_at' => now(),
            'violation_count' => $pelanggaran,
        ]);
    }

    public function kedaluwarsa(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'expired',
            'finish_reason' => 'time_up',
            'finished_at' => now(),
        ]);
    }

    public function denganPelanggaran(int $jumlah): static
    {
        return $this->state(fn (array $attributes): array => ['violation_count' => $jumlah]);
    }

    public function baruPing(int $detikLalu = 5): static
    {
        return $this->state(fn (array $attributes): array => [
            'last_heartbeat_at' => now()->subSeconds($detikLalu),
        ]);
    }

    /** Heartbeat lama — monitoring akan menandai siswa offline. */
    public function heartbeatBasi(): static
    {
        return $this->state(fn (array $attributes): array => [
            'last_heartbeat_at' => now()->subMinutes(5),
        ]);
    }
}
