<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'set_time_limit' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:active,draft,closed'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'show_results_immediately' => ['nullable', 'boolean'],
            'allow_answer_review' => ['nullable', 'boolean'],
            'enable_anti_cheat' => ['nullable', 'boolean'],
            'max_violations' => ['nullable', 'integer', 'in:2,3,5'],
        ];
    }
}
