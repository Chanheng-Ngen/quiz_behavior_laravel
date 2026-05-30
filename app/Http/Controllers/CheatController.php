<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheatRequest;
use App\Http\Resources\CheatResource;
use App\Models\Cheat;
use App\Models\Participant;
use App\Models\Quiz;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CheatController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store a newly created cheat in storage.
     */
    public function store(StoreCheatRequest $request): JsonResponse
    {
        $cheat = Cheat::create($request->validated());

        return response()->json([
            'message'    => 'Cheat created successfully.',
            'cheat_type' => $cheat->name,
        ], 201);
    }

    /**
     * Get summary of cheats for a specific quiz.
     */
    public function summary(Quiz $quiz): JsonResponse
    {
        $participantIds = DB::table('submission_answers')
            ->join('questions', 'submission_answers.question_id', '=', 'questions.id')
            ->where('questions.quiz_id', $quiz->id)
            ->distinct()
            ->pluck('submission_answers.participant_id');

        $cheatsData = Cheat::query()
            ->where('quiz_id', $quiz->id)
            ->whereIn('participant_id', $participantIds)
            ->with('participant')
            ->get()
            ->groupBy('participant_id');

        $summary = $cheatsData->map(function ($cheats, $participantId) {
            $participant = $cheats->first()->participant;

            return [
                'result'           => true,
                'participant_id'   => $participantId,
                'participant_name' => $participant->full_name,
                'email'            => $participant->email,
                'cheat_count'      => $cheats->count(),
            ];
        });

        return response()->json([
            'result'                         => true,
            'message'                        => 'Cheat summary retrieved successfully.',
            'quiz_id'                        => $quiz->id,
            'total_participants_with_cheats' => $summary->count(),
            'data'                           => $summary->values(),
        ]);
    }

    /**
     * Get cheats for a specific participant.
     */
    public function indexByParticipant(Quiz $quiz, Participant $participant): JsonResponse
    {
        $cheats = Cheat::query()
            ->where('quiz_id', $quiz->id)
            ->where('participant_id', $participant->id)
            ->get();

        return response()->json([
            'result'           => true,
            'message'          => 'Participant cheats retrieved successfully.',
            'quiz_id'          => $quiz->id,
            'participant_id'   => $participant->id,
            'participant_name' => $participant->full_name,
            'data'             => CheatResource::collection($cheats)
        ]);
    }
}
