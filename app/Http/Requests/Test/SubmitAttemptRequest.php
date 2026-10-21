<?php

namespace App\Http\Requests\Test;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers.*.selected' => ['nullable', 'array'],
            'answers.*.selected.*' => ['integer', 'min:0'],
            'answers.*.text_answer' => ['nullable', 'string', 'max:1000'],
            'answers.*.time_spent_seconds' => ['nullable', 'integer', 'min:0'],
            'app_switches' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
