<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    public const TOPICS = [
        'Verbal Ability' => [
            'Vocabulary',
            'Grammar and Correct Usage',
            'Reading Comprehension',
        ],
        'Numerical Ability' => [
            'Fractions and Decimals',
            'Percentages',
            'Word Problems',
        ],
        'Analytical Ability' => [
            'Number Series',
            'Logic and Reasoning',
            'Analogies',
        ],
        'General Information' => [
            'Philippine Constitution',
            'Philippine History',
            'Science and Environment',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::TOPICS as $subjectName => $topics) {
            // Fails loudly if the subjects were not seeded first.
            $subject = Subject::where('name', $subjectName)->firstOrFail();

            foreach ($topics as $name) {
                Topic::firstOrCreate([
                    'subject_id' => $subject->id,
                    'name' => $name,
                ]);
            }
        }
    }
}
