<?php

namespace Database\Factories;

use App\Models\BoardExam;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_exam_id' => BoardExam::factory(),
            'title' => fake()->sentence(3),
            'level' => 'Professional',
            'duration_minutes' => 180,
            'passing_score' => 80,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
        ]);
    }
}
