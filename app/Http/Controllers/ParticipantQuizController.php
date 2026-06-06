<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitQuizRequest;
use App\Http\Requests\UpdateSubmissionGradesRequest;
use App\Http\Resources\QuizSubmissionListResource;
use App\Http\Resources\QuizSubmissionResource;
use App\Http\Resources\QuizStatisticsResource;
use App\Http\Resources\ScoreDistributionResource;
use App\Http\Resources\TopPerformerResource;
use App\Models\Cheat;
use App\Models\Participant;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\SubmissionAnswers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParticipantQuizController extends Controller
{
    public function checkQuizStatus(Quiz $quiz, string $email): JsonResponse
    {
        $participant = Participant::where('email', $email)->first();
        
        if (!$participant) {
            return response()->json([
                'result' => true,
                'can_take' => true,
                'message' => 'You can take this quiz.',
            ]);
        }

        $existingSubmission = Submission::where('quiz_id', $quiz->id)
            ->where('participant_id', $participant->id)
            ->first();

        if ($existingSubmission && !$quiz->allow_multiple_submissions) {
            return response()->json([
                'result' => true,
                'can_take' => false,
                'already_taken' => true,
                'participant_id' => $participant->id,
                'message' => 'You have already taken this quiz. You can only take it once.',
            ]);
        }

        return response()->json([
            'result' => true,
            'can_take' => true,
            'message' => 'You can take this quiz.',
        ]);
    }

    public function submit(SubmitQuizRequest $request, Quiz $quiz): JsonResponse
    {
        $participantPayload = $request->participantPayload();
        $answerPayloads = $request->answerPayloads();
        $validQuestionIds = $quiz->questions()->pluck('id');
        $timeSpentSeconds = $request->input('time_spent_seconds', 0);
        $isAutoSubmitted = $request->boolean('is_auto_submitted', false);

        $participant = null;
        $submission = null;

        // Check if participant already has a submission for this quiz
        $existingParticipant = Participant::where('email', $participantPayload['email'])->first();
        if ($existingParticipant && !$quiz->allow_multiple_submissions) {
            $existingSubmission = Submission::where('quiz_id', $quiz->id)
                ->where('participant_id', $existingParticipant->id)
                ->first();
            
            if ($existingSubmission) {
                return response()->json([
                    'result' => false,
                    'message' => 'You have already taken this quiz. You can only take it once.',
                    'already_taken' => true,
                    'participant_id' => $existingParticipant->id,
                ], 409);
            }
        }

        DB::transaction(function () use (
            $participantPayload,
            $answerPayloads,
            $validQuestionIds,
            $quiz,
            $timeSpentSeconds,
            $isAutoSubmitted,
            &$participant,
            &$submission
        ): void {
            $participant = Participant::query()->updateOrCreate(
                ['email' => $participantPayload['email']],
                ['full_name' => $participantPayload['full_name']]
            );

            SubmissionAnswers::query()
                ->where('participant_id', $participant->id)
                ->whereIn('question_id', $validQuestionIds)
                ->delete();

            $records = collect($answerPayloads)->map(fn ($answerPayload) => [
                'participant_id' => $participant->id,
                'question_id' => $answerPayload['question_id'],
                'option_answer_id' => $answerPayload['option_answer_id'] ?? null,
                'text_answer' => $answerPayload['text_answer'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            SubmissionAnswers::query()->insert($records);

            $submission = Submission::updateOrCreate(
                ['quiz_id' => $quiz->id, 'participant_id' => $participant->id],
                [
                    'time_spent_seconds' => $timeSpentSeconds,
                    'is_auto_submitted' => $isAutoSubmitted,
                    'submitted_at' => now(),
                ]
            );
        });

        return response()->json([
            'result' => true,
            'participant_id' => $participant->id,
            'submission_id' => $submission?->id,
            'message' => 'Quiz submitted successfully.',
        ], 201);
    }

    public function getSubmissions(Request $request, Quiz $quiz): JsonResponse
    {
        $this->authorizeQuizAccess($quiz);

        $query = Submission::where('quiz_id', $quiz->id)
            ->with(['participant', 'cheats']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('participant', function ($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($request->has('filter')) {
            $filter = $request->input('filter');
            if ($filter === 'pass') {
                $passRate = 70;
                $query->whereHas('participant.submissionAnswers', function ($q) use ($quiz, $passRate) {
                    $q->whereIn('question_id', $quiz->questions()->pluck('id'));
                });
            } elseif ($filter === 'fail') {
                $passRate = 70;
            } elseif ($filter === 'violations') {
                $query->whereHas('cheats');
            }
        }

        $submissions = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'result' => true,
            'message' => 'Submissions retrieved successfully.',
            'data' => QuizSubmissionListResource::collection($submissions),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
            ],
        ]);
    }

    public function getQuizStats(Quiz $quiz): JsonResponse
    {
        $this->authorizeQuizAccess($quiz);

        return response()->json([
            'result' => true,
            'message' => 'Quiz statistics retrieved successfully.',
            'data' => new QuizStatisticsResource($quiz),
        ]);
    }

    public function getScoreDistribution(Quiz $quiz): JsonResponse
    {
        $this->authorizeQuizAccess($quiz);

        return response()->json([
            'result' => true,
            'message' => 'Score distribution retrieved successfully.',
            'data' => new ScoreDistributionResource($quiz),
        ]);
    }

    public function getTopPerformers(Quiz $quiz): JsonResponse
    {
        $this->authorizeQuizAccess($quiz);

        return response()->json([
            'result' => true,
            'message' => 'Top performers retrieved successfully.',
            'data' => new TopPerformerResource($quiz),
        ]);
    }

    public function showSubmission(Quiz $quiz, int $participantId): JsonResponse
    {
        $participant = Participant::query()->find($participantId);

        if ($participant === null) {
            return response()->json([
                'result' => false,
                'message' => 'Participant not found.',
            ], 404);
        }

        $quiz->loadMissing(['questions.questionType', 'questions.optionAnswers', 'questions.images']);

        $answers = SubmissionAnswers::query()
            ->where('participant_id', $participant->id)
            ->whereIn('question_id', $quiz->questions()->select('id'))
            ->with(['optionAnswer:id,content,is_correct,question_id'])
            ->get()
            ->keyBy('question_id');

        if ($answers->isEmpty()) {
            return response()->json([
                'result' => false,
                'message' => 'No submission found for this quiz.',
            ]);
        }

        $submission = Submission::where('quiz_id', $quiz->id)
            ->where('participant_id', $participantId)
            ->first();

        $cheatCount = Cheat::where('quiz_id', $quiz->id)
            ->where('participant_id', $participantId)
            ->count();

        return response()->json([
            'result' => true,
            'message' => 'Quiz submission retrieved successfully.',
            'data' => new QuizSubmissionResource([
                'participant' => $participant,
                'quiz' => $quiz,
                'answers' => $answers,
            ]),
            'submission' => $submission,
            'cheat_count' => $cheatCount,
        ]);
    }

    public function updateGrades(UpdateSubmissionGradesRequest $request, Quiz $quiz, int $participantId): JsonResponse
    {
        $this->authorizeQuizAccess($quiz);

        $answersData = $request->input('answers');

        DB::transaction(function () use ($answersData, $participantId, $quiz): void {
            foreach ($answersData as $answerData) {
                $submissionAnswer = SubmissionAnswers::where('id', $answerData['submission_answer_id'])
                    ->where('participant_id', $participantId)
                    ->whereIn('question_id', $quiz->questions()->pluck('id'))
                    ->first();

                if ($submissionAnswer) {
                    $submissionAnswer->update([
                        'earned_points' => $answerData['earned_points'],
                        'feedback' => $answerData['feedback'] ?? null,
                        'is_graded' => true,
                    ]);
                }
            }
        });

        return response()->json([
            'result' => true,
            'message' => 'Grades updated successfully.',
        ]);
    }

    public function resetGrades(Quiz $quiz, int $participantId): JsonResponse
    {
        $this->authorizeQuizAccess($quiz);

        $validQuestionIds = $quiz->questions()->pluck('id');

        SubmissionAnswers::where('participant_id', $participantId)
            ->whereIn('question_id', $validQuestionIds)
            ->update([
                'earned_points' => null,
                'feedback' => null,
                'is_graded' => false,
            ]);

        return response()->json([
            'result' => true,
            'message' => 'Grades reset successfully.',
        ]);
    }

    private function authorizeQuizAccess(Quiz $quiz): void
    {
        if ($quiz->creator_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this quiz.');
        }
    }
}
