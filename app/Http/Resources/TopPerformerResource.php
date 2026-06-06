<?php

namespace App\Http\Resources;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\SubmissionAnswers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopPerformerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Quiz $quiz */
        $quiz = $this->resource;

        $submissions = Submission::where('quiz_id', $quiz->id)->with('participant')->get();
        $questions = $quiz->questions;
        $totalScore = (float) $questions->sum('score');

        $performers = [];

        foreach ($submissions as $submission) {
            $submissionAnswers = SubmissionAnswers::where('participant_id', $submission->participant_id)
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

            $percentage = $totalScore > 0 ? round(($earnedScore / $totalScore) * 100, 2) : 0;

            $performers[] = [
                'participant_id' => $submission->participant_id,
                'full_name' => $submission->participant->full_name,
                'email' => $submission->participant->email,
                'score' => [
                    'earned' => $earnedScore,
                    'total' => $totalScore,
                    'percentage' => $percentage,
                ],
                'time_spent_seconds' => $submission->time_spent_seconds,
            ];
        }

        usort($performers, function ($a, $b) {
            return $b['score']['percentage'] <=> $a['score']['percentage'];
        });

        return [
            'quiz_id' => $quiz->id,
            'top_performers' => array_slice($performers, 0, 5),
        ];
    }
}
