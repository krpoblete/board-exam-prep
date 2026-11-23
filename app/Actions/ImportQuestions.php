<?php

namespace App\Actions;

use App\Enums\Difficulty;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Support\QuestionImport\ImportResult;
use App\Support\QuestionImport\ImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportQuestions
{
    public const COLUMNS = [
        'subject',
        'topic',
        'body',
        'explanation',
        'difficulty',
        'tags',
        'choice_a',
        'choice_b',
        'choice_c',
        'choice_d',
        'correct',
    ];

    public const MAX_ROWS = 500;

    private const LETTERS = [
        'A' => 'choice_a',
        'B' => 'choice_b',
        'C' => 'choice_c',
        'D' => 'choice_d',
    ];

    /**
     * Create a new class instance.
     */
    public function __construct(private readonly SaveQuestion $save) {}

    public function parse(string $path): ImportResult
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return ImportResult::fatal(
                'The uploaded file could not be read.'
            );
        }

        try {
            return $this->read($handle);
        } finally {
            fclose($handle);
        }
    }

    public function commit(string $path, User $user): int
    {
        $result = $this->parse($path);

        if ($result->fatal !== null) {
            throw ValidationException::withMessages([
                'file' => $result->fatal,
            ]);
        }

        if ($result->invalidCount() > 0) {
            throw ValidationException::withMessages([
                'file' => 'Fix the rows with errors and upload the file again.',
            ]);
        }

        return DB::transaction(function () use ($result, $user): int {
            foreach ($result->rows as $row) {
                $this->save->handle($row->data ?? [], $user);
            }

            return count($result->rows);
        });
    }

    /** @param resource $handle */
    private function read($handle): ImportResult
    {
        $header = fgetcsv($handle, 0, ',', '"', '');

        if ($header === false || $header === [null]) {
            return ImportResult::fatal(
                'The file is empty.'
            );
        }

        // Excel adds a UTF-8 byte order mark to the first header cell.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);

        $columns = array_map(fn ($cell): string => Str::lower(trim((string) $cell)), $header);
        $missing = array_values(array_diff(self::COLUMNS, $columns));

        if ($missing !== []) {
            return ImportResult::fatal(
                'Missing column(s): '.implode(', ', $missing).'. Download the template for the expected layout.'
            );
        }

        $index = array_flip($columns);
        $subjects = $this->subjectLookup();
        $pending = [];
        // the header is row 1, like in a spreadsheet
        $rowNumber = 1;

        while (($cells = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rowNumber++;

            if ($cells === [null] || collect($cells)->every(fn ($c): bool => trim((string) $c) === '')) {
                continue;
            }

            if (count($pending) >= self::MAX_ROWS) {
                return ImportResult::fatal(
                    'The file has more than '.self::MAX_ROWS.' rows. Split it into smaller files.'
                );
            }

            $raw = [];

            foreach (self::COLUMNS as $column) {
                $raw[$column] = trim((string) ($cells[$index[$column]] ?? ''));
            }

            [$errors, $data] = $this->validateRow($raw, $subjects);

            $pending[] = [
                'row' => $rowNumber,
                'raw' => $raw,
                'errors' => $errors,
                'data' => $data,
            ];
        }

        if ($pending === []) {
            return ImportResult::fatal(
                'The file has a header but no question rows.'
            );
        }

        $pending = $this->flagDuplicates($pending);

        return new ImportResult(null, array_map(
            fn (array $item): ImportRow => new ImportRow(
                $item['row'],
                $item['raw'],
                $item['data'],
                $item['errors'],
            ),
            $pending,
        ));
    }

    /**
     * @param  array<string, string>  $raw
     * @param  array<string, array{id: int, name: string, topics: array<string, int>}>  $subjects
     * @return array{0: list<string>, 1: array<string, mixed>|null}
     */
    private function validateRow(array $raw, array $subjects): array
    {
        $errors = [];
        $topicId = null;

        if ($raw['subject'] === '') {
            $errors[] = 'Subject is required.';
        } else {
            $subject = $subjects[Str::lower($raw['subject'])] ?? null;

            if ($subject === null) {
                $errors[] = "Unknown subject \"{$raw['subject']}\".";
            } elseif ($raw['topic'] === '') {
                $errors[] = 'Topic is required.';
            } else {
                $topicId = $subject['topics'][Str::lower($raw['topic'])] ?? null;

                if ($topicId === null) {
                    $errors[] = "Topic \"{$raw['topic']}\" does not exist under {$subject['name']}. Add it on the Topics page first.";
                }
            }
        }

        if ($raw['body'] === '') {
            $errors[] = 'Question text is required.';
        }

        $difficulty = Difficulty::Medium;

        if ($raw['difficulty'] !== '') {
            $parsed = Difficulty::tryFrom(Str::lower($raw['difficulty']));

            if ($parsed === null) {
                $errors[] = 'Difficulty must be easy, medium or hard.';
            } else {
                $difficulty = $parsed;
            }
        }

        $tags = collect(explode(';', $raw['tags']))
            ->map(fn (string $tag): string => trim($tag))
            ->filter(fn (string $tag): bool => Str::slug($tag) !== '')
            ->unique(fn (string $tag): string => Str::slug($tag))
            ->values();

        if ($tags->count() > 10) {
            $errors[] = 'Use at most 10 tags.';
        }

        if ($tags->contains(fn (string $tag): bool => mb_strlen($tag) > 50)) {
            $errors[] = 'Eact tag must be 50 characters or fewer.';
        }

        $filled = [];

        foreach (self::LETTERS as $letter => $column) {
            if ($raw[$column] !== '') {
                $filled[$letter] = $raw[$column];
            }
        }

        if (count($filled) < 2) {
            $errors[] = 'At least two choices are required.';
        }

        $correct = Str::upper($raw['correct']);

        if ($raw['correct'] == '') {
            $errors[] = 'The correct column is required (A, B, C, or D).';
        } elseif (! array_key_exists($correct, self::LETTERS)) {
            $errors[] = 'The correct column must be A, B, C, or D.';
        } elseif (! isset($filled[$correct])) {
            $errors[] = "Correct answer {$correct} points to an empty choice.";
        }

        if ($errors !== []) {
            return [$errors, null];
        }

        return [[], [
            'topic_id' => $topicId,
            'body' => $raw['body'],
            'explanation' => $raw['explanation'] !== '' ? $raw['explanation'] : null,
            'difficulty' => $difficulty->value,
            'tags' => $tags->all(),
            'choices' => collect($filled)
                ->map(fn (string $body, string $letter): array => [
                    'body' => $body,
                    'is_correct' => $letter === $correct,
                ])
                ->values()
                ->all(),
        ]];
    }

    /**
     * @param  list<array{row: int, raw: array<string, string>, errors: list<string>, data: array<string, mixed>|null}>  $pending
     * @return list<array{row: int, raw: array<string, string>, errors: list<string>, data: array<string, mixed>|null}>
     */
    private function flagDuplicates(array $pending): array
    {
        $topicIds = collect($pending)
            ->pluck('data.topic_id')
            ->filter()
            ->unique()
            ->all();

        $existing = [];

        foreach (Question::whereIn('topic_id', $topicIds)->get(['id', 'topic_id', 'body']) as $question) {
            $existing[$question->topic_id.'|'.$this->fingerprint($question->body)] = $question->id;
        }

        $seen = [];

        foreach ($pending as $i => $item) {
            if ($item['data'] === null) {
                continue;
            }

            $key = $item['data']['topic_id'].'|'.$this->fingerprint((string) $item['data']['body']);

            if (isset($existing[$key])) {
                $pending[$i]['errors'][] = "Duplicate of existing question #{$existing[$key]}.";
            } elseif (isset($seen[$key])) {
                $pending[$i]['errors'][] = "Duplicate of row #{$seen[$key]} in this file.";
            } else {
                $seen[$key] = $item['row'];
            }
        }

        return $pending;
    }

    private function fingerprint(string $body): string
    {
        $collapsed = preg_replace('/\s+/u', '', trim($body)) ?? $body;

        return md5(Str::lower($collapsed));
    }

    /** @return array<string, array{id: int, name: string, topics: array<string, int>}> */
    private function subjectLookup(): array
    {
        $lookup = [];

        foreach (Subject::with('topics:id,subject_id,name')->get() as $subject) {
            $lookup[Str::lower($subject->name)] = [
                'id' => $subject->id,
                'name' => $subject->name,
                'topics' => $subject->topics
                    ->mapWithKeys(fn (Topic $topic): array => [Str::lower($topic->name) => $topic->id])
                    ->all(),
            ];
        }

        return $lookup;
    }
}
