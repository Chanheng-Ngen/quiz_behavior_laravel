<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $incomingQuestions = $this->input('questions');

        $questions = is_array($incomingQuestions)
            ? $incomingQuestions
            : [$this->all()];

        $quizRouteParam = $this->route('quiz');
        $quizIdFromRoute = is_object($quizRouteParam)
            ? (int) $quizRouteParam->id
            : (is_numeric($quizRouteParam) ? (int) $quizRouteParam : null);

        $normalizedQuestions = array_map(function (mixed $question) use ($quizIdFromRoute): array {
            if (! is_array($question)) {
                $question = [];
            }

            $normalizedQuestion = [
                'content' => $question['content'] ?? $question['title'] ?? null,
                'score' => $question['score'] ?? null,
                'quiz_id' => $quizIdFromRoute,
                'question_type' => $question['question_type'] ?? $question['questionType'] ?? null,
            ];

            if (array_key_exists('option_answers', $question) || array_key_exists('options', $question)) {
                $incomingOptionAnswers = $question['option_answers'] ?? $question['options'];

                $normalizedQuestion['option_answers'] = is_array($incomingOptionAnswers)
                    ? array_map(function (mixed $optionAnswer): array {
                        if (! is_array($optionAnswer)) {
                            $optionAnswer = [];
                        }

                        return [
                            'content' => $optionAnswer['content'] ?? $optionAnswer['text'] ?? null,
                            'is_correct' => $optionAnswer['is_correct'] ?? false,
                        ];
                    }, $incomingOptionAnswers)
                    : [];
            }

            return $normalizedQuestion;
        }, $questions);

        $this->merge([
            'questions' => $normalizedQuestions,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.content' => ['required', 'string'],
            'questions.*.score' => ['nullable', 'numeric', 'min:0'],
            'questions.*.quiz_id' => ['required', 'integer', Rule::exists('quizzes', 'id')],
            'questions.*.question_type' => ['required', 'string', Rule::exists('question_types', 'name')],
            'questions.*.option_answers' => ['sometimes', 'array', 'min:1'],
            'questions.*.option_answers.*.content' => ['required_with:questions.*.option_answers', 'string'],
            'questions.*.option_answers.*.is_correct' => ['required_with:questions.*.option_answers', 'boolean'],
        ];
    }

    /**
     * @return array<int, array{content: string, score?: float|int|null, quiz_id: int, question_type: string, option_answers?: array<int, array{content: string, is_correct: bool}>}>
     */
    public function questionPayloads(): array
    {
        /** @var array{questions: array<int, array{content: string, score?: float|int|null, quiz_id: int, question_type: string, option_answers?: array<int, array{content: string, is_correct: bool}>}>} $validated */
        $validated = $this->validated();

        return $validated['questions'];
    }
}
