<?php

namespace Tests\Feature;

use App\Models\Choice;
use App\Models\Question;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionChoiceRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_question_factory_gives_four_choices_with_one_correct(): void
    {
        $question = Question::factory()->published()->create();

        $this->assertSame(4, $question->choices()->count());
        $this->assertSame(1, $question->choices()->where('is_correct', true)->count());
    }

    public function test_each_question_may_have_its_own_correct_choice(): void
    {
        Question::factory()->published()->count(2)->create();

        $this->assertSame(2, Choice::where('is_correct', true)->count());
    }

    public function test_a_second_correct_choice_is_rejected_by_the_database(): void
    {
        $question = Question::factory()->published()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Choice::factory()->correct()->for($question)->create();
    }

    public function test_the_correct_answer_can_be_changed_by_clearing_first(): void
    {
        $question = Question::factory()->published()->create();
        $newCorrect = $question->choices()->where('is_correct', false)->first();

        DB::transaction(function () use ($question, $newCorrect) {
            $question->choices()->update(['is_correct' => false]);
            $newCorrect->update(['is_correct' => true]);
        });

        $this->assertSame(1, $question->choices()->where('is_correct', true)->count());
        $this->assertTrue($newCorrect->fresh()->is_correct);
    }
}
