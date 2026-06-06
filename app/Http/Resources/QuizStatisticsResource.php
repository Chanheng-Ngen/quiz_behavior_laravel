<?php

namespace App\Http\Resources;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\SubmissionAnswers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizStatisticsResource extends JsonResource
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

        $submissions = Submission::where('quiz_id', $quiz->id)
            ->with(['participant', 'cheats'])
            ->get();

        $totalSubmissions = $submissions->count();
        $questions = $quiz->questions;
        $totalScore = (float) $questions->sum('score');

        $totalEarnedScore = 0.0;
        $totalTimeSpent = 0;
        $participantsWithViolations = 0;
        $passCount = 0;
        $passRate = 70; // Default pass rate 70%

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

            $totalEarnedScore += $earnedScore;
            $totalTimeSpent += $submission->time_spent_seconds;

            if ($submission->cheats()->count() > 0) {
                $participantsWithViolations++;
            }

            if ($totalScore > 0 && ($earnedScore / $totalScore) * 100 >= $passRate) {
                $passCount++;
            }
        }

        $averageScore = $totalSubmissions > 0 ? round(($totalEarnedScore / $totalSubmissions), 2) : 0;
        $averageScorePercentage = $totalScore > 0 && $totalSubmissions > 0 ? round(($averageScore / $totalScore) * 100, 2) : 0;
        $passPercentage = $totalSubmissions > 0 ? round(($passCount / $totalSubmissions) * 100, 2) : 0;
        $averageTimeSeconds = $totalSubmissions > 0 ? round($totalTimeSpent / $totalSubmissions) : 0;

        return [
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'quiz_password' => $quiz->password,
            'total_submissions' => $totalSubmissions,
            'average_score' => [
                'value' => $averageScore,
                'percentage' => $averageScorePercentage,
            ],
            'pass_rate' => [
                'count' => $passCount,
                'percentage' => $passPercentage,
            ],
            'participants_with_violations' => $participantsWithViolations,
            'total_questions' => $questions->count(),
            'time_limit' => $quiz->set_time_limit,
            'average_time_seconds' => $averageTimeSeconds,
            'anti_cheat' => $quiz->enable_anti_cheat,
            'max_violations' => $quiz->max_violations,
        ];
    }
}
