<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitQuizRequest;
use App\Http\Resources\QuizSubmissionResource;
use App\Models\Participant;
use App\Models\Quiz;
use App\Models\SubmissionAnswers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ParticipantQuizController extends Controller
{
    public function submit(SubmitQuizRequest $request, Quiz $quiz): JsonResponse
    {
        $participantPayload = $request->participantPayload();
        $answerPayloads     = $request->answerPayloads();
        $validQuestionIds   = $quiz->questions()->pluck('id');

        DB::transaction(function () use ($participantPayload, $answerPayloads, $validQuestionIds): void {
            $participant = Participant::query()->updateOrCreate(
                ['email' => $participantPayload['email']],
                ['full_name' => $participantPayload['full_name']]
            );

            SubmissionAnswers::query()
                ->where('participant_id', $participant->id)
                ->whereIn('question_id', $validQuestionIds)
                ->delete();

            $records = collect($answerPayloads)->map(fn($answerPayload) => [
                'participant_id'   => $participant->id,
                'question_id'      => $answerPayload['question_id'],
                'option_answer_id' => $answerPayload['option_answer_id'] ?? null,
                'text_answer'      => $answerPayload['text_answer'] ?? null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ])->toArray();

            SubmissionAnswers::query()->insert($records);
        });

        return response()->json([
            'result'  => true,
            'message' => 'Quiz submitted successfully.',
        ], 201);
    }
    public function showSubmission(Quiz $quiz, int $participantId): JsonResponse
    {
        $participant = Participant::query()->find($participantId);

        if ($participant === null) {
            return response()->json([
                'result'  => false,
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
                'result'  => false,
                'message' => 'No submission found for this quiz.',
            ]);
        }

        return response()->json([
            'result'  => true,
            'message' => 'Quiz submission retrieved successfully.',
            'data'    => new QuizSubmissionResource([
                'participant' => $participant,
                'quiz'        => $quiz,
                'answers'     => $answers,
            ]),
        ]);
    }
}
