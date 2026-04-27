<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\OptionAnswer;
use App\Models\Question;
use App\Models\QuestionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $questions = Question::with('questionType')->paginate(10);

        return response()->json([
            'data' => QuestionResource::collection($questions->items()),
            'meta' => [
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
                'per_page' => $questions->perPage(),
                'total' => $questions->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $payloads = $request->questionPayloads();

        $typeMap = QuestionType::query()
            ->whereIn('name', collect($payloads)->pluck('question_type')->unique()->all())
            ->pluck('id', 'name');

        $questions = DB::transaction(function () use ($payloads, $typeMap) {
            $createdQuestions = [];

            foreach ($payloads as $payload) {
                $question = Question::create([
                    'question_type_id' => $typeMap[$payload['question_type']],
                    'quiz_id' => $payload['quiz_id'],
                    'content' => $payload['content'],
                    'score' => $payload['score'] ?? 0,
                ]);

                foreach ($payload['option_answers'] ?? [] as $optionAnswer) {
                    OptionAnswer::create([
                        'question_id' => $question->id,
                        'content' => $optionAnswer['content'],
                        'is_correct' => $optionAnswer['is_correct'],
                    ]);
                }

                $createdQuestions[] = $question->load(['optionAnswers', 'questionType']);
            }

            return collect($createdQuestions);
        });

        if ($questions->count() === 1) {
            return (new QuestionResource($questions->first()))->response()->setStatusCode(201);
        }

        return response()->json([
            'message' => 'Questions created successfully.',
            'data' => QuestionResource::collection($questions),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question): QuestionResource
    {
        return new QuestionResource($question->load('questionType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question): QuestionResource
    {
        $question->update($request->validated());

        return new QuestionResource($question->load('questionType'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question): JsonResponse
    {
        $question->delete();

        return response()->json([
            'message' => 'Question deleted successfully.',
        ]);
    }
}
