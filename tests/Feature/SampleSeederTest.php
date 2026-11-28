<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Topic;
use Database\Seeders\CscExamSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\QuestionSeeder;
use Database\Seeders\TopicSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SampleSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_sample_data_is_valid_and_seeding_again_adds_nothing(): void
    {
        $chain = [
            UserSeeder::class,
            CscExamSeeder::class,
            TopicSeeder::class,
            QuestionSeeder::class,
        ];

        $this->seed($chain);
        $this->seed($chain);

        $this->assertSame(12, Topic::count());
        $this->assertSame(12, Question::count());

        Question::with('choices')->get()->each(function (Question $question): void {
            $this->assertTrue($question->hasValidChoices());
        });
    }

    public function test_database_seeder_runs_everything_in_a_working_order(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(12, Question::count());
    }
}
