<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamParticipant>
 */
class ExamParticipantFactory extends Factory
{
    protected $model = ExamParticipant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'user_id' => User::factory(),
        ];
    }
}
