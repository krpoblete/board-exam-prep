<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Exam;
use App\Models\User;
use Database\Seeders\CscExamSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CscSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_one_user_per_role(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertSame(Role::Admin, User::where('email', 'admin@mail.com')->value('role'));
        $this->assertSame(Role::Instructor, User::where('email', 'instructor@mail.com')->value('role'));
        $this->assertSame(Role::Examinee, User::where('email', 'examinee@mail.com')->value('role'));
    }

    public function test_seeds_the_professional_exam_with_four_subjects(): void
    {
        $this->seed(CscExamSeeder::class);

        $exam = Exam::with('subjects')->where('title', 'Professional')->firstOrFail();

        $this->assertSame(190, $exam->duration_minutes);
        $this->assertSame(80, $exam->passing_score);
        $this->assertFalse($exam->is_published);
        $this->assertEqualsCanonicalizing(
            ['Verbal Ability', 'Numerical Ability', 'Analytical Ability', 'General Information'],
            $exam->subjects()->pluck('name')->all(),
        );
    }

    public function test_reseeding_is_idempotent_and_keeps_admin_tuned_counts(): void
    {
        $this->seed(CscExamSeeder::class);

        $exam = Exam::firstOrFail();
        $subject = $exam->subjects()->firstOrFail();
        $exam->subjects()->updateExistingPivot($subject->id, ['question_count' => 25]);

        $this->seed(CscExamSeeder::class);

        $this->assertSame(1, Exam::count());
        $this->assertSame(4, $exam->subjects()->count());
        $this->assertSame(
            25,
            $exam->subjects()->where('subjects.id', $subject->id)->first()->pivot->question_count,
        );
    }
}
