<?php

namespace App\Http\Requests;

use App\Models\Question;

class StoreQuestionRequest extends SaveQuestionRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Question::class);
    }
}
