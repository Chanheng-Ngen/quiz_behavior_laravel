<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $quizzes = Quiz::query()->withCount('questions')->paginate(10);

        return response()->json([
            'message' => 'Quizzes retrieved successfully.',
            'data' => QuizResource::collection($quizzes),
            'meta' => [
                'current_page' => $quizzes->currentPage(),
                'last_page' => $quizzes->lastPage(),
                'per_page' => $quizzes->perPage(),
                'total' => $quizzes->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuizRequest $request): JsonResponse
    {
        $quiz = Quiz::create([
            ...$request->validated(),
            'creator_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Quiz created successfully.',
            'data' => new QuizResource($quiz),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Quiz $quiz): JsonResponse
    {
        return response()->json([
            'message' => 'Quiz retrieved successfully.',
            'data' => new QuizResource($quiz->loadMissing(['questions.questionType', 'questions.optionAnswers'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuizRequest $request, Quiz $quiz): JsonResponse
    {
        $this->authorize('update', $quiz);

        $quiz->update($request->validated());

        return response()->json([
            'message' => 'Quiz updated successfully.',
            'data' => new QuizResource($quiz),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quiz $quiz): JsonResponse
    {
        $this->authorize('delete', $quiz);

        $quiz->delete();

        return response()->json([
            'result' => true,
            'message' => 'Quiz deleted successfully.',
        ]);
    }
}
