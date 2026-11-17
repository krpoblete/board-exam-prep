<?php

namespace Database\Factories;

use App\Models\Choice;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Choice>
 */
class ChoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'body' => fake()->sentence(),
            'is_correct' => false,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn () => [
            'is_correct' => true,
        ]);
    }
}
