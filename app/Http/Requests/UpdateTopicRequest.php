<?php

namespace App\Http\Requests;

use App\Models\Topic;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTopicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('topic'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $topic = $this->route('topic');

        assert($topic instanceof Topic);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('topics')->where('subject_id', $topic->subject_id)->ignore($topic),
            ],
        ];
    }
}
