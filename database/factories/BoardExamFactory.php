<?php

namespace Database\Factories;

use App\Models\BoardExam;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BoardExam>
 */
class BoardExamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fn (array $attrs) => Str::slug($attrs['name']),
        ];
    }
}
