<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Enums\QuizStatus;

class StoreCheatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function resolveQuiz(): Quiz
    {
        $quiz = $this->route('quiz');

        if (!$quiz instanceof Quiz) {
            $quiz = Quiz::query()->find($quiz);
            abort_if($quiz === null, 404, 'Quiz not found.');
        }

        abort_if($quiz->status !== QuizStatus::ACTIVE, 403, 'This quiz is not found.');

        return $quiz;
    }
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'participant_id' => [
                'required',
                'integer',
                Rule::exists('participants', 'id'),
            ],
        ];
    }

    /**
     * @return array{name: string, participant_id: int, quiz_id: int}
     */
    public function validated($key = null, $default = null): array
    {
        return array_merge(parent::validated($key, $default), [
            'quiz_id' => $this->resolveQuiz()->id,
        ]);
    }
}
