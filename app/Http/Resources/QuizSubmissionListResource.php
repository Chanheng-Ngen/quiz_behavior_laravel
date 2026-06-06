<?php

namespace App\Http\Resources;

use App\Models\Question;
use App\Models\Submission;
use App\Models\SubmissionAnswers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizSubmissionListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Submission $submission */
        $submission = $this->resource;
        $quiz = $submission->quiz;
        $participant = $submission->participant;

        $questions = $quiz->questions;
        $totalScore = (float) $questions->sum('score');

        $submissionAnswers = SubmissionAnswers::where('participant_id', $participant->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->with('optionAnswer')
            ->get()
            ->keyBy('question_id');

        $earnedScore = 0.0;
        foreach ($questions as $question) {
            $answer = $submissionAnswers->get($question->id);
            if ($answer) {
                if ($answer->earned_points !== null) {
                    $earnedScore += (float) $answer->earned_points;
                } else {
                    $isCorrect = $answer->optionAnswer?->is_correct;
                    $earnedScore += $isCorrect === true ? (float) ($question->score ?? 0) : 0.0;
                }
            }
        }

        $cheatCount = $submission->cheats()->count();
        $hasViolations = $cheatCount > 0;

        return [
            'id' => $submission->id,
            'participant_id' => $participant->id,
            'full_name' => $participant->full_name,
            'email' => $participant->email,
            'score' => [
                'earned' => $earnedScore,
                'total' => $totalScore,
                'percentage' => $totalScore > 0 ? round(($earnedScore / $totalScore) * 100, 2) : 0.0,
            ],
            'time_spent_seconds' => $submission->time_spent_seconds,
            'is_auto_submitted' => $submission->is_auto_submitted,
            'submitted_at' => $submission->submitted_at,
            'cheat_count' => $cheatCount,
            'has_violations' => $hasViolations,
            'created_at' => $submission->created_at,
        ];
    }
}
