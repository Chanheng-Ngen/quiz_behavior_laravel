<?php

namespace App\Http\Resources;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\SubmissionAnswers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScoreDistributionResource extends JsonResource
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

        $distribution = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0,
        ];

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

            $percentage = $totalScore > 0 ? ($earnedScore / $totalScore) * 100 : 0;

            if ($percentage <= 20) {
                $distribution['0-20']++;
            } elseif ($percentage <= 40) {
                $distribution['21-40']++;
            } elseif ($percentage <= 60) {
                $distribution['41-60']++;
            } elseif ($percentage <= 80) {
                $distribution['61-80']++;
            } else {
                $distribution['81-100']++;
            }
        }

        return [
            'quiz_id' => $quiz->id,
            'distribution' => array_map(function ($count, $range) {
                return [
                    'range' => $range,
                    'count' => $count,
                ];
            }, $distribution, array_keys($distribution)),
        ];
    }
}
