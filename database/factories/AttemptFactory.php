<?php

namespace Database\Factories;

use App\Enums\AttemptMode;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exam_id' => Exam::factory()->published(),
            'mode' => AttemptMode::Mock,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(180),
            'submitted_at' => null,
            'score' => null,
        ];
    }

    public function practice(): static
    {
        return $this->state(fn () => [
            'exam_id' => null,
            'mode' => AttemptMode::Practice,
            'expires_at' => null,
        ]);
    }

    public function submitted(float $score = 85): static
    {
        return $this->state(fn () => [
            'submitted_at' => now(), 'score' => $score,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'started_at' => now()->subHours(4),
            'expires_at' => now()->subHour(),
        ]);
    }
}
