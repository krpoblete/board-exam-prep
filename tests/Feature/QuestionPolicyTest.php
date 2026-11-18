<?php

namespace Tests\Feature;

use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $instructor;

    private User $examinee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->instructor = User::factory()->instructor()->create();
        $this->examinee = User::factory()->create();
    }

    /** @return array<string, User> */
    private function staff(): array
    {
        return [
            'admin' => $this->admin,
            'instructor' => $this->instructor,
        ];
    }

    public function test_staff_can_manage_the_question_bank(): void
    {
        $question = Question::factory()->create();

        foreach ($this->staff() as $role => $user) {
            $this->assertTrue($user->can('viewAny', Question::class), "$role viewAny");
            $this->assertTrue($user->can('view', $question), "$role view");
            $this->assertTrue($user->can('create', Question::class), "$role create");
            $this->assertTrue($user->can('update', $question), "$role update");
            $this->assertTrue($user->can('publish', $question), "$role publish");
        }
    }

    public function test_examinee_has_no_question_bank_access(): void
    {
        $question = Question::factory()->published()->create();

        foreach (['viewAny' => Question::class, 'create' => Question::class] as $ability => $arg) {
            $this->assertFalse($this->examinee->can($ability, $arg), $ability);
        }

        foreach (['view', 'update', 'publish', 'delete'] as $ability) {
            $this->assertFalse($this->examinee->can($ability, $question), $ability);
        }
    }

    public function test_staff_can_delete_an_unused_draft(): void
    {
        $question = Question::factory()->create();

        foreach ($this->staff() as $role => $user) {
            $this->assertTrue($user->can('delete', $question), $role);
        }
    }

    public function test_nobody_can_delete_a_published_question(): void
    {
        $question = Question::factory()->published()->create();

        foreach ($this->staff() as $role => $user) {
            $this->assertFalse($user->can('delete', $question), $role);
        }
    }

    public function test_nobody_can_delete_a_question_used_in_an_exam(): void
    {
        $question = Question::factory()->create();
        Exam::factory()->create()->questions()->attach($question->id, ['position' => 1]);

        foreach ($this->staff() as $role => $user) {
            $this->assertFalse($user->can('delete', $question), $role);
        }
    }

    public function test_nobody_can_delete_a_question_with_attempt_history(): void
    {
        $question = Question::factory()->create();
        AttemptAnswer::factory()->create(['question_id' => $question->id]);

        foreach ($this->staff() as $role => $user) {
            $this->assertFalse($user->can('delete', $question), $role);
        }
    }
}
