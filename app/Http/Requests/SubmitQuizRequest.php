<?php

namespace App\Http\Requests;

use App\Enums\QuizStatus;
use App\Models\OptionAnswer;
use App\Models\Quiz;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Quiz $quiz */
        $quiz = $this->route('quiz');

        return [
            'quiz_password'              => ['required', 'string', 'size:6'],
            'participant'                => ['required', 'array'],
            'participant.full_name'      => ['required', 'string', 'max:255'],
            'participant.email'          => ['required', 'email', 'max:255'],
            'answers'                    => ['required', 'array', 'min:1'],
            'answers.*.question_id'      => [
                'required',
                'integer',
                Rule::exists('questions', 'id')->where('quiz_id', $quiz->id),
            ],
            'answers.*.option_answer_id' => ['nullable', 'integer', Rule::exists('option_answers', 'id')],
            'answers.*.text_answer'      => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Quiz $quiz */
                $quiz = $this->route('quiz');

                // Check quiz status
                if ($quiz->status !== QuizStatus::ACTIVE) {
                    $validator->errors()->add('quiz', 'This quiz is not available for submission.');
                    return;
                }

                // Check quiz password
                $submittedPassword = strtoupper(trim((string) $this->input('quiz_password')));
                if ($submittedPassword !== strtoupper(trim((string) $quiz->password))) {
                    $validator->errors()->add('quiz_password', 'The quiz password is incorrect.');
                    return;
                }

                $answers = $this->input('answers', []);
                if (!is_array($answers)) {
                    return;
                }

                foreach ($answers as $index => $answer) {
                    if (!is_array($answer)) {
                        continue;
                    }

                    $optionAnswerId = $answer['option_answer_id'] ?? null;
                    $textAnswer     = trim((string) ($answer['text_answer'] ?? ''));

                    if (!is_numeric($optionAnswerId) && $textAnswer === '') {
                        $validator->errors()->add(
                            "answers.{$index}",
                            'Each answer must include option_answer_id or text_answer.'
                        );
                    }

                    if (!is_numeric($optionAnswerId) || !isset($answer['question_id']) || !is_numeric($answer['question_id'])) {
                        continue;
                    }

                    $belongsToQuestion = OptionAnswer::query()
                        ->whereKey((int) $optionAnswerId)
                        ->where('question_id', (int) $answer['question_id'])
                        ->exists();

                    if (!$belongsToQuestion) {
                        $validator->errors()->add(
                            "answers.{$index}.option_answer_id",
                            'The selected option answer does not belong to the provided question.'
                        );
                    }
                }
            },
        ];
    }

    /**
     * @return array{full_name: string, email: string}
     */
    public function participantPayload(): array
    {
        /** @var array{participant: array{full_name: string, email: string}} $validated */
        $validated = $this->validated();

        return $validated['participant'];
    }

    /**
     * @return array<int, array{question_id: int, option_answer_id?: int|null, text_answer?: string|null}>
     */
    public function answerPayloads(): array
    {
        /** @var array{answers: array<int, array{question_id: int, option_answer_id?: int|null, text_answer?: string|null}>} $validated */
        $validated = $this->validated();

        return $validated['answers'];
    }
}