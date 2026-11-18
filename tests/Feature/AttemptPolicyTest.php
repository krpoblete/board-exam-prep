<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttemptPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $instructor;

    private User $owner;

    private User $examinee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->instructor = User::factory()->instructor()->create();
        $this->owner = User::factory()->create();
        $this->examinee = User::factory()->create();
    }

    public function test_owner_and_admin_can_view_an_attempt(): void
    {
        $attempt = Attempt::factory()->create(['user_id' => $this->owner->id]);

        $this->assertTrue($this->owner->can('view', $attempt));
        $this->assertTrue($this->admin->can('view', $attempt));
        $this->assertFalse($this->instructor->can('view', $attempt));
        $this->assertFalse($this->examinee->can('view', $attempt));
    }

    public function test_examinees_and_instructors_cannot_view_it(): void
    {
        $attempt = Attempt::factory()->create(['user_id' => $this->owner->id]);

        $this->assertFalse($this->examinee->can('view', $attempt));
        $this->assertFalse($this->instructor->can('view', $attempt));
    }

    public function test_owner_can_update_an_open_attempt(): void
    {
        $mock = Attempt::factory()->create(['user_id' => $this->owner->id]);
        $practice = Attempt::factory()->practice()->create(['user_id' => $this->owner->id]);

        $this->assertTrue($this->owner->can('update', $mock));
        // untimed
        $this->assertTrue($this->owner->can('update', $practice));
    }

    public function test_nobody_but_the_owner_can_update_an_attempt(): void
    {
        $mock = Attempt::factory()->create(['user_id' => $this->owner->id]);
        $practice = Attempt::factory()->practice()->create(['user_id' => $this->owner->id]);

        $this->assertFalse($this->examinee->can('update', $mock));
        $this->assertFalse($this->instructor->can('update', $mock));
        $this->assertFalse($this->admin->can('update', $mock));
        $this->assertFalse($this->examinee->can('update', $practice));
        $this->assertFalse($this->instructor->can('update', $practice));
        $this->assertFalse($this->admin->can('update', $practice));
    }

    public function test_a_submitted_attempt_is_read_only(): void
    {
        $submittedAttempt = Attempt::factory()->submitted()->create(['user_id' => $this->owner->id]);

        $this->assertFalse($this->owner->can('update', $submittedAttempt));
    }

    public function test_an_expired_attempt_is_read_only(): void
    {
        $expiredAttempt = Attempt::factory()->expired()->create(['user_id' => $this->owner->id]);

        $this->assertFalse($this->owner->can('update', $expiredAttempt));
    }
}
