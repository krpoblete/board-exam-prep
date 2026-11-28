<?php

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample questions are for local development only.
        if (app()->isProduction()) {
            return;
        }

        $creator = User::query()->whereIn('role', ['admin', 'instructor'])->value('id');

        foreach ($this->questions() as $item) {
            $topic = Topic::where('name', $item['topic'])
                ->whereHas('subject', fn ($query) => $query->where('name', $item['subject']))
                ->firstOrFail();

            $question = Question::firstOrCreate(
                [
                    'topic_id' => $topic->id,
                    'body' => $item['body'],
                ],
                [
                    'explanation' => $item['explanation'],
                    'difficulty' => $item['difficulty'],
                    'status' => $item['status'],
                    'created_by' => $creator,
                ],
            );

            // Existing questions keep whatever an instructor has done to them.
            if (! $question->wasRecentlyCreated) {
                continue;
            }

            foreach ($item['choices'] as $index => $body) {
                $question->choices()->create([
                    'body' => $body,
                    'is_correct' => $index === $item['correct'],
                ]);
            }

            $question->tags()->sync(
                collect($item['tags'])
                    ->map(fn (string $name): int => Tag::firstOrCreate(
                        ['slug' => Str::slug($name)],
                        ['name' => $name],
                    )->id)
                    ->all()
            );
        }
    }

    /**
     * @return list<array{subject: string, topic: string, body: string, explanation: string, difficulty: Difficulty, status: QuestionStatus, tags: list<string>, choices: list<string>, correct: int}>
     */
    private function questions(): array
    {
        return [
            [
                'subject' => 'Verbal Ability',
                'topic' => 'Vocabulary',
                'body' => 'Choose the word closest in meaning to "brisk".',
                'explanation' => '"Brisk" describes something quick and energetic.',
                'difficulty' => Difficulty::Easy,
                'status' => QuestionStatus::Published,
                'tags' => [
                    'vocabulary',
                    'synonyms',
                ],
                'choices' => [
                    'Quick',
                    'Slow',
                    'Tired',
                    'Quiet',
                ],
                'correct' => 0,
            ],
            [
                'subject' => 'Verbal Ability',
                'topic' => 'Grammar and Correct Usage',
                'body' => 'Neither the manager nor the employees ___ aware of the change.',
                'explanation' => 'With neither/nor, the verb agrees with the nearer subject, "employees".',
                'difficulty' => Difficulty::Medium,
                'status' => QuestionStatus::Published,
                'tags' => [
                    'grammar',
                    'subject-verb agreement',
                ],
                'choices' => [
                    'was',
                    'were',
                    'is',
                    'has been',
                ],
                'correct' => 1,
            ],
            [
                'subject' => 'Verbal Ability',
                'topic' => 'Reading Comprehension',
                'body' => 'The library opens at 8 a.m. and closes at 6 p.m. on weekdays. On Saturdays it closes at noon, and it is closed on Sundays. At what time does the library close on Saturdays?',
                'explanation' => 'The passage states that the library closes at noon on Saturdays.',
                'difficulty' => Difficulty::Easy,
                'status' => QuestionStatus::Published,
                'tags' => [
                    'reading',
                ],
                'choices' => [
                    '6 p.m.',
                    'Noon',
                    '8 a.m.',
                    'It does not open on Saturdays',
                ],
                'correct' => 1,
            ],
            [
                'subject' => 'Numerical Ability',
                'topic' => 'Fractions and Decimals',
                'body' => 'What is 3/4 + 1/8?',
                'explanation' => '3/4 equals 6/8, and 6/8 + 1/8 = 7/8.',
                'difficulty' => Difficulty::Easy,
                'status' => QuestionStatus::Published,
                'tags' => [
                    'fractions',
                ],
                'choices' => [
                    '7/8',
                    '4/12',
                    '1',
                    '3/32',
                ],
                'correct' => 0,
            ],
            [
                'subject' => 'Numerical Ability',
                'topic' => 'Percentages',
                'body' => 'A shirt priced at ₱800 is sold at a 25% discount. What is the sale price?',
                'explanation' => '25% of 800 is 200, and 800 - 200 = 600.',
                'difficulty' => Difficulty::Medium,
                'status' => QuestionStatus::Published,
                'tags' => [
                    'percentages',
                ],
                'choices' => [
                    '₱200',
                    '₱600',
                    '₱650',
                    '₱775',
                ],
                'correct' => 1,
            ],
            [
                'subject' => 'Numerical Ability',
                'topic' => 'Word Problems',
                'body' => 'A car travels 150 kilometers in 2.5 hours. At the same speed, how many hours will it take to travel 360 kilometers?',
                'explanation' => 'The speed is 60 km/h, and 360 / 60 = 6 hours.',
                'difficulty' => Difficulty::Hard,
                'status' => QuestionStatus::Draft,
                'tags' => [
                    'word problems',
                    'rates',
                ],
                'choices' => [
                    '5',
                    '6',
                    '6.5',
                    '7',
                ],
                'correct' => 1,
            ],
            [
                'subject' => 'Analytical Ability',
                'topic' => 'Number Series',
                'body' => 'What number comes next in the series 2, 6, 18, 54, ...?',
                'explanation' => 'Each term is multiplied by 3, so 54 x 3 = 162.',
                'difficulty' => Difficulty::Medium,
                'status' => QuestionStatus::Published,
                'tags' => [
                    'number series',
                ],
                'choices' => [
                    '108',
                    '162',
                    '148',
                    '216',
                ],
                'correct' => 1,
            ],
            [
                'subject' => 'Analytical Ability',
                'topic' => 'Logic and Reasoning',
                'body' => 'All roses are flowers, and all flowers need water. Which statement must be true?',
                'explanation' => 'Roses are flowers and flowers need water, so all roses need water.',
                'difficulty' => Difficulty::Medium,
                'status' => QuestionStatus::Draft,
                'tags' => [
                    'logic',
                ],
                'choices' => [
                    'All flowers are roses.',
                    'Some roses do not need water.',
                    'All roses need water.',
                    'Water needs flowers.',
                ],
                'correct' => 2,
            ],
            [
                'subject' => 'Analytical Ability',
                'topic' => 'Analogies',
                'body' => 'Doctor is to hospital as teacher is to ____.',
                'explanation' => 'A doctor works in a hospital, and a teacher works in a school.',
                'difficulty' => Difficulty::Easy,
                'status' => QuestionStatus::Published,
                'tags' => [
                    'analogies',
                ],
                'choices' => [
                    'student',
                    'school',
                    'lesson',
                    'pencil',
                ],
                'correct' => 1,
            ],
            [
                'subject' => 'General Information',
                'topic' => 'Philippine Constitution',
                'body' => 'Under the 1987 Philippine Constitution, how long is the term of the President?',
                'explanation' => 'The President serves a single six-year term and may not be reelected.',
                'difficulty' => Difficulty::Medium,
                'status' => QuestionStatus::Draft,
                'tags' => [
                    'constitution',
                ],
                'choices' => [
                    'Four years',
                    'Six years, without reelection',
                    'Six years, with one reelection',
                    'Eight years',
                ],
                'correct' => 1,
            ],
            [
                'subject' => 'General Information',
                'topic' => 'Philippine History',
                'body' => 'On what date is Philippine Independence Day celebrated?',
                'explanation' => 'June 12 marks the 1898 proclamation of Philippine independence.',
                'difficulty' => Difficulty::Easy,
                'status' => QuestionStatus::Published,
                'tags' => [
                    'history',
                ],
                'choices' => [
                    'July 4',
                    'November 30',
                    'June 12',
                    'December 30',
                ],
                'correct' => 2,
            ],
            [
                'subject' => 'General Information',
                'topic' => 'Science and Environment',
                'body' => 'Which gas do plants take in from the air for photosynthesis?',
                'explanation' => 'Plants absorb carbon dioxide and release oxygen.',
                'difficulty' => Difficulty::Easy,
                'status' => QuestionStatus::Published,
                'tags' => [
                    'science',
                ],
                'choices' => [
                    'Oxygen',
                    'Nitrogen',
                    'Carbon dioxide',
                    'Hydrogen',
                ],
                'correct' => 2,
            ],
        ];
    }
}
