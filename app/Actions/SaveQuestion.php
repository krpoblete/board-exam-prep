<?php

namespace App\Actions;

use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaveQuestion
{
    /** @param array<string, mixed> $data */
    public function handle(array $data, User $user, ?Question $question = null): Question
    {
        return DB::transaction(function () use ($data, $user, $question): Question {
            $attrs = Arr::only($data, [
                'topic_id',
                'body',
                'explanation',
                'difficulty',
            ]);

            if ($question === null) {
                $question = Question::create([
                    ...$attrs,
                    'status' => QuestionStatus::Draft,
                    'created_by' => $user->id,
                ]);
            } else {
                $question->update($attrs);
            }

            $this->syncChoices($question, $data['choices']);
            $this->syncTags($question, $data['tags'] ?? []);

            return $question;
        });
    }

    /** @param array<string, mixed> $choices */
    private function syncChoices(Question $question, array $choices): void
    {
        // Clear flags first: the partial unique index allows only one correct
        // choice at a time, so moving the answer A -> B would otherwise collide.
        $question->choices()->update(['is_correct' => false]);

        $keep = [];
        foreach ($choices as $row) {
            $payload = [
                'body' => $row['body'],
                'is_correct' => (bool) $row['is_correct'],
            ];
            $choice = isset($row['id'])
                ? $question->choices()->whereKey($row['id'])->first()
                : null;

            if ($choice) {
                $choice->update($payload);
            } else {
                $choice = $question->choices()->create($payload);
            }

            $keep[] = $choice->id;
        }

        $question->choices()->whereNotIn('id', $keep)->delete();
    }

    /** @param array<int, string> $names */
    private function syncTags(Question $question, array $names): void
    {
        $ids = collect($names)
            ->map(fn (string $n): string => trim($n))
            ->filter(fn (string $n): bool => Str::slug($n) !== '')
            ->unique(fn (string $n): string => Str::slug($n))
            ->map(fn (string $n): int => Tag::firstOrCreate(
                ['slug' => Str::slug($n)],
                ['name' => $n],
            )->id)
            ->all();

        $question->tags()->sync($ids);
    }
}
