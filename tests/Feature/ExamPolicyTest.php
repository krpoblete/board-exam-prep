<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamPolicyTest extends TestCase
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

    public function test_examinee_sees_only_published_exams(): void
    {
        $draft = Exam::factory()->create();
        $published = Exam::factory()->published()->create();

        $this->assertTrue($this->examinee->can('viewAny', Exam::class));
        $this->assertTrue($this->examinee->can('view', $published));
        $this->assertFalse($this->examinee->can('view', $draft));
    }

    public function test_staff_can_view_draft_and_published_exams(): void
    {
        $draft = Exam::factory()->create();
        $published = Exam::factory()->published()->create();

        $this->assertTrue($this->admin->can('view', $draft));
        $this->assertTrue($this->instructor->can('view', $draft));
        $this->assertTrue($this->admin->can('view', $published));
        $this->assertTrue($this->instructor->can('view', $published));
    }

    public function test_staff_can_create_exams(): void
    {
        $this->assertTrue($this->admin->can('create', Exam::class));
        $this->assertTrue($this->instructor->can('create', Exam::class));
        $this->assertFalse($this->examinee->can('create', Exam::class));
    }

    public function test_staff_can_edit_a_draft_exam_but_examinee_cannot(): void
    {
        $draft = Exam::factory()->create();

        $this->assertTrue($this->admin->can('update', $draft));
        $this->assertTrue($this->instructor->can('update', $draft));
        $this->assertFalse($this->examinee->can('update', $draft));
    }

    public function test_published_exams_are_locked_for_everyone(): void
    {
        $published = Exam::factory()->published()->create();

        $this->assertFalse($this->admin->can('update', $published));
        $this->assertFalse($this->instructor->can('update', $published));
        $this->assertFalse($this->examinee->can('update', $published));
    }

    public function test_only_admin_can_publish_or_unpublish(): void
    {
        $exam = Exam::factory()->create();

        $this->assertTrue($this->admin->can('publish', $exam));
        $this->assertFalse($this->instructor->can('publish', $exam));
        $this->assertFalse($this->examinee->can('publish', $exam));
    }

    public function test_only_admin_can_delete_and_only_without_attempts(): void
    {
        $unused = Exam::factory()->create();
        $withAttempts = Exam::factory()->published()->create();
        Attempt::factory()->create(['exam_id' => $withAttempts->id]);

        $this->assertTrue($this->admin->can('delete', $unused));
        $this->assertFalse($this->admin->can('delete', $withAttempts));
        $this->assertFalse($this->instructor->can('delete', $unused));
        $this->assertFalse($this->examinee->can('delete', $unused));
    }

    public function test_exams_can_be_taken_only_when_published(): void
    {
        $this->assertTrue($this->examinee->can('take', Exam::factory()->published()->create()));
        $this->assertFalse($this->examinee->can('take', Exam::factory()->create()));
    }
}
