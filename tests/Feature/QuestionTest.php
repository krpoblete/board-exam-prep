<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Tests\TestCase;

class QuestionTest extends TestCase
{
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
}
