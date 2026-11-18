<?php

namespace Database\Seeders;

use App\Models\BoardExam;
use App\Models\Exam;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class CscExamSeeder extends Seeder
{
    // The CSC publishes no per-subject item counts. Admins set the real
    // values in the app, so the seed starts at 0 rather than guessing.
    private const PLACEHOLDER_QUESTION_COUNT = 0;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csc = BoardExam::firstOrCreate(
            ['slug' => 'csc'],
            ['name' => 'CSC'],
        );

        $exam = Exam::firstOrCreate(
            [
                'board_exam_id' => $csc->id,
                'title' => 'Professional',
            ],
            [
                'level' => 'Professional',
                'duration_minutes' => 190,
                'passing_score' => 80,
                'is_published' => false,
            ],
        );

        $names = [
            'Verbal Ability',
            'Numerical Ability',
            'Analytical Ability',
            'General Information',
        ];

        foreach ($names as $name) {
            $subject = Subject::firstOrCreate([
                'board_exam_id' => $csc->id,
                'name' => $name,
            ]);

            // Attach only when missing, so re-seeding never overwrites
            // a question_count an admin has already tuned.
            if (! $exam->subjects()->where('subjects.id', $subject->id)->exists()) {
                $exam->subjects()->attach($subject->id, [
                    'question_count' => self::PLACEHOLDER_QUESTION_COUNT,
                ]);
            }
        }
    }
}
