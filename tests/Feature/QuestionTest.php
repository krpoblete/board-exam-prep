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

    private function questionWithChoices(array $attrs = []): Question
    {
        $question = Question::factory()->create([
            'status' => 'draft',
            ...$attrs,
        ]);
        $question->choices()->createMany([
            [
                'body' => 'A',
                'is_correct' => true,
            ],
            [
                'body' => 'B',
                'is_correct' => false,
            ],
        ]);

        return $question;
    }

    public function test_bulk_publish_skips_questions_without_valid_choices(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $ready = $this->questionWithChoices();
        $broken = Question::factory()->create(['status' => 'draft']);

        $this->actingAs($user)
            ->post('/questions/bulk-publish', ['ids' => [
                $ready->id,
                $broken->id,
            ]])
            ->assertRedirect()
            ->assertSessionHas('bulk', [
                'action' => 'published',
                'count' => 1,
                'skipped' => 1,
            ]);

        $this->assertSame('published', $ready->fresh()->status->value);
        $this->assertSame('draft', $broken->fresh()->status->value);
    }

    public function test_publish_all_matching_only_touches_drafts_in_the_filter(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $topicA = Topic::factory()->create();
        $topicB = Topic::factory()->create();
        $inFilter = $this->questionWithChoices(['topic_id' => $topicA->id]);
        $otherTopic = $this->questionWithChoices(['topic_id' => $topicB->id]);

        $this->actingAs($user)
            ->post('/questions/bulk-publish', [
                'all_matching' => true,
                'status' => 'draft',
                'topic_id' => $topicA->id,
            ])
            ->assertSessionHas('bulk', [
                'action' => 'published',
                'count' => 1,
                'skipped' => 0,
            ]);

        $this->assertSame('published', $inFilter->fresh()->status->value);
        $this->assertSame('draft', $otherTopic->fresh()->status->value);
    }

    public function test_examinee_cannot_bulk_publish(): void
    {
        $user = User::factory()->create(['role' => 'examinee']);
        $question = $this->questionWithChoices();

        $this->actingAs($user)
            ->post('/questions/bulk-publish', ['ids' => [$question->id]])
            ->assertForbidden();
    }
}
