<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_create_question_with_one_correct_choice(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $topic = Topic::factory()->create();

        $this->actingAs($user)->post('/questions', [
            'topic_id' => $topic->id,
            'body' => 'Choose the synonym of "brisk".',
            'difficulty' => 'easy',
            'tags' => ['vocabulary'],
            'choices' => [
                [
                    'body' => 'Quick',
                    'is_correct' => true,
                ],
                [
                    'body' => 'Slow',
                    'is_correct' => false,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas(
            'questions',
            [
                'topic_id' => $topic->id,
                'status' => 'draft',
            ]
        );
        $this->assertDatabaseHas(
            'tags',
            [
                'slug' => 'vocabulary',
            ]
        );
    }

    public function test_question_with_two_correct_choices_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $topic = Topic::factory()->create();

        $this->actingAs($user)->post('/questions', [
            'topic_id' => $topic->id,
            'body' => 'Pick one.',
            'difficulty' => 'easy',
            'choices' => [
                [
                    'body' => 'A',
                    'is_correct' => true,
                ],
                [
                    'body' => 'B',
                    'is_correct' => true,
                ],
            ],
        ])->assertSessionHasErrors('choices');
    }

    public function test_question_without_correct_choice_cannot_be_published(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $question = Question::factory()->create();
        $question->choices()->createMany([
            [
                'body' => 'A',
                'is_correct' => false,
            ],
            [
                'body' => 'B',
                'is_correct' => false,
            ],
        ]);

        $this->actingAs($user)->post("/questions/{$question->id}/publish")
            ->assertSessionHasErrors('status');
    }

    public function test_instructor_can_open_question_list(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        Question::factory()->count(3)->create();

        $this->actingAs($user)->get('/questions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('questions/index')
                ->has('questions.data', 3));
    }

    public function test_examinee_cannot_open_question_list(): void
    {
        $user = User::factory()->create(['role' => 'examinee']);

        $this->actingAs($user)->get('/questions')->assertForbidden();
    }

    public function test_edit_page_loads_choices_and_tags(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $question = Question::factory()->create();
        $question->choices()->create([
            'body' => 'A',
            'is_correct' => true,
        ]);

        $this->actingAs($user)->get("/questions/{$question->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('questions/form')
                ->where('question.locked', false)
                ->has('question.choices', 1));
    }
}
