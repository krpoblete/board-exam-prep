<?php

namespace App\Http\Requests;

use App\Enums\Difficulty;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveQuestionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'topic_id' => [
                'required',
                'integer',
                'exists:topics,id',
            ],
            'body' => [
                'required',
                'string',
            ],
            'explanation' => [
                'nullable',
                'string',
            ],
            'difficulty' => [
                'required',
                Rule::enum(Difficulty::class),
            ],
            'tags' => [
                'array',
                'max:10',
            ],
            'tags.*' => [
                'string',
                'max:50',
            ],
            'choices' => [
                'required',
                'array',
                'min:2',
                'max:6',
            ],
            'choices.*.id' => [
                'nullable',
                'integer',
            ],
            'choices.*.body' => [
                'required',
                'string',
            ],
            'choices.*.is_correct' => [
                'required',
                'boolean',
            ],
        ];
    }

    /** @return array<int, Closure(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $choices = $this->input('choices', []);
                $correct = 0;

                if (is_array($choices)) {
                    foreach ($choices as $choice) {
                        if (is_array($choice) && filter_var($choice['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                            $correct++;
                        }
                    }
                }

                if ($correct !== 1) {
                    $validator->errors()->add('choices', 'Exactly one choice must be marked correct.');
                }
            },
        ];
    }
}
