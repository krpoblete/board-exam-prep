<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_create_topic(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $subject = Subject::factory()->create();

        $this->actingAs($user)
            ->post('/topics', [
                'subject_id' => $subject->id,
                'name' => 'Analogies',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('topics', [
            'name' => 'Analogies',
            'subject_id' => $subject->id,
        ]);
    }

    public function test_examinee_cannot_create_topic(): void
    {
        $user = User::factory()->create(['role' => 'examinee']);
        $subject = Subject::factory()->create();

        $this->actingAs($user)
            ->post('/topics', [
                'subject_id' => $subject->id,
                'name' => 'Analogies',
            ])
            ->assertForbidden();
    }

    public function test_topic_with_questions_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $topic = Topic::factory()->has(Question::factory())->create();

        $this->actingAs($user)
            ->delete("/topics/{$topic->id}")
            ->assertForbidden();
    }

    public function test_instructor_can_rename_topic(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $topic = Topic::factory()->create(['name' => 'English']);

        $this->actingAs($user)
            ->put("topics/{$topic->id}", ['name' => 'English Grammar'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('topics', ['id' => $topic->id, 'name' => 'English Grammar']);
    }
}
