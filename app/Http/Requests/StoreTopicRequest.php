<?php

namespace App\Http\Requests;

use App\Models\Topic;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTopicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Topic::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('topics')->where('subject_id', $this->integer('subject_id')),
            ],
        ];
    }
}
