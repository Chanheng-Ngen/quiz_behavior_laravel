<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Question $question */
        $question = $this->route('question');

        return $question->quiz->creator_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'required', 'string'],
            'score' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'quiz_id' => ['sometimes', 'required', Rule::exists('quizzes', 'id')],
            'question_type_id' => ['sometimes', 'required', Rule::exists('question_types', 'id')],
        ];
    }
}
