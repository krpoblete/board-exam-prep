<?php

namespace Database\Factories;

use App\Enums\QuestionStatus;
use App\Models\Choice;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'body' => rtrim(fake()->sentence(), '.').'?',
            'explanation' => fake()->paragraph(),
            'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
            'status' => QuestionStatus::Draft,
            'created_by' => User::factory()->instructor(),
        ];
    }

    // One correct choice plus (count - 1) wrong ones.
    public function withChoices(int $count = 4): static
    {
        return $this->afterCreating(function (Question $question) use ($count) {
            Choice::factory()->correct()->for($question)->create();
            Choice::factory()->count($count - 1)->for($question)->create();
        });
    }

    // A published question always has its choices.
    public function published(): static
    {
        return $this->state(fn () => [
            'status' => QuestionStatus::Published,
        ])->withChoices();
    }
}
