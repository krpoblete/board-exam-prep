<?php

namespace App\Http\Requests;

use App\Models\Question;
use Illuminate\Validation\Validator;

class UpdateQuestionRequest extends SaveQuestionRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('question'));
    }

    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                /** @var Question $question */
                $question = $this->route('question');

                if (! $question->hasAttempts()) {
                    return;
                }

                /** @var array<int, array<string, mixed>> $choices */
                $choices = $this->input('choices', []);

                $submitted = collect($choices);
                $existing = $question->choices;

                $sameSet = $submitted->pluck('id')->filter()->map(fn ($id): int => (int) $id)->sort()->values()->all()
                    === $existing->pluck('id')->sort()->values()->all();

                $correctId = $submitted->first(fn ($c) => filter_var($c['is_correct']
                    ?? false, FILTER_VALIDATE_BOOLEAN))['id']
                    ?? null;
                $sameAnswer = (int) $correctId === $existing->firstWhere('is_correct', true)?->id;

                if (! $sameSet || ! $sameAnswer) {
                    $validator->errors()->add('choices', 'This question already has attempts: you can fix wording, but do not add, remove choices, or change the correct answer.');
                }
            },
        ];
    }
}
