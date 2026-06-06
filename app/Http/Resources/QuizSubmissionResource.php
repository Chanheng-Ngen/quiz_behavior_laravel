<?php

namespace App\Http\Resources;

use App\Models\Participant;
use App\Models\Quiz;
use App\Models\SubmissionAnswers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class QuizSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{participant: Participant, quiz: Quiz, answers: Collection<int, SubmissionAnswers>} $payload */
        $payload = $this->resource;

        $participant = $payload['participant'];
        $quiz = $payload['quiz'];
        $answers = $payload['answers'];

        $questions = $quiz->questions;
        $totalScore = (float) $questions->sum('score');
        $earnedScore = 0.0;

        $answerDetails = $questions->map(function ($question) use ($answers, &$earnedScore): array {
            /** @var SubmissionAnswers|null $answer */
            $answer = $answers->get($question->id);

            $questionScore = (float) ($question->score ?? 0);
            $isCorrect = $answer?->optionAnswer?->is_correct;
            
            // Use earned_points if manually graded, otherwise use automatic calculation
            $earned = $answer?->earned_points !== null 
                ? (float) $answer->earned_points 
                : ($isCorrect === true ? $questionScore : 0.0);

            $earnedScore += $earned;

            $options = $question->optionAnswers->map(fn ($optionAnswer) => [
                'id' => $optionAnswer->id,
                'content' => $optionAnswer->content,
                'is_correct' => $optionAnswer->is_correct,
            ])->values();

            $correctAnswer = $question->optionAnswers
                ->firstWhere('is_correct', true);

            return [
                'id' => $answer?->id,
                'question_id' => $question->id,
                'content' => $question->content,
                'question_type' => $question->questionType?->name,
                'score' => $questionScore,
                'option_answer' => $answer?->optionAnswer ? [
                    'id' => $answer->optionAnswer->id,
                    'content' => $answer->optionAnswer->content,
                ] : null,
                'text_answer' => $answer?->text_answer,
                'options' => $options,
                'correct_option_answer' => $correctAnswer === null ? null : [
                    'id' => $correctAnswer->id,
                    'content' => $correctAnswer->content,
                ],
                'is_correct' => $isCorrect,
                'earned_points' => $answer?->earned_points,
                'feedback' => $answer?->feedback,
                'is_graded' => $answer?->is_graded,
            ];
        })->values();

        // Recalculate final earned score
        $finalEarnedScore = 0.0;
        foreach ($answerDetails as $answer) {
            if ($answer['earned_points'] !== null) {
                $finalEarnedScore += (float)$answer['earned_points'];
            } else {
                $finalEarnedScore += $answer['is_correct'] ? (float)$answer['score'] : 0.0;
            }
        }

        return [
            'participant' => [
                'id' => $participant->id,
                'full_name' => $participant->full_name,
                'email' => $participant->email,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'questions' => $answerDetails,
            ],
            'score' => [
                'earned' => $finalEarnedScore,
                'total' => $totalScore,
                'percentage' => $totalScore > 0 ? round(($finalEarnedScore / $totalScore) * 100, 2) : 0.0,
            ],
            'answers' => $answerDetails,
        ];
    }
}
