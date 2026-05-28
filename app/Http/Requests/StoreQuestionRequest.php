<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $incomingQuestions = $this->input('questions');

        $questions = is_array($incomingQuestions)
            ? $incomingQuestions
            : [$this->all()];

        $quizRouteParam = $this->route('quiz_id');
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
            'questions.*.quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'questions.*.content' => ['required', 'string'],
            'questions.*.score' => ['nullable', 'numeric', 'min:0'],
            'questions.*.question_type' => ['required', 'string', Rule::exists('question_types', 'code')],
            'questions.*.option_answers' => ['sometimes', 'array', 'min:1'],
            'questions.*.option_answers.*.content' => ['required_with:questions.*.option_answers', 'string'],
            'questions.*.option_answers.*.is_correct' => ['required_with:questions.*.option_answers', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $questions = $this->input('questions', []);

                if (! is_array($questions)) {
                    return;
                }

                foreach ($questions as $index => $question) {
                    if (! is_array($question)) {
                        continue;
                    }

                    if (! $this->requiresAtLeastTwoOptions($question['question_type'] ?? null)) {
                        continue;
                    }

                    $optionAnswers = $question['option_answers'] ?? [];

                    if (! is_array($optionAnswers)) {
                        $validator->errors()->add(
                            "questions.{$index}.option_answers",
                            'This question type requires at least two option answers.'
                        );

                        continue;
                    }

                    $optionAnswerCount = count(array_filter(
                        $optionAnswers,
                        fn (mixed $optionAnswer): bool => is_array($optionAnswer) && filled($optionAnswer['content'] ?? null)
                    ));

                    if ($optionAnswerCount < 2) {
                        $validator->errors()->add(
                            "questions.{$index}.option_answers",
                            'This question type requires at least two option answers.'
                        );
                    }
                }
            },
        ];
    }

    /**
     * @return array<int, array{content: string, score?: float|int|null, quiz_id?: int|null, question_type: string, option_answers?: array<int, array{content: string, is_correct: bool}>}>
     */
    public function questionPayloads(): array
    {
        /** @var array{questions: array<int, array{content: string, score?: float|int|null, quiz_id?: int|null, question_type: string, option_answers?: array<int, array{content: string, is_correct: bool}>}>} $validated */
        $validated = $this->validated();

        return $validated['questions'];
    }

    private function requiresAtLeastTwoOptions(mixed $questionType): bool
    {
        if (! is_string($questionType)) {
            return false;
        }

        $normalizedQuestionType = strtoupper(trim($questionType));

        return in_array($normalizedQuestionType, ['MC', 'TF'], true);
    }
}
