<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Enums\QuizStatus;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource and filter by status and search by title query parameters.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->lower();
        $search = $request->string('search')->trim();

        $quizzes = Quiz::query()
            ->where('creator_id', Auth::id())
            ->withCount('questions')
            ->when($status->isNotEmpty() && QuizStatus::tryFrom((string) $status), function ($query) use ($status) {
                $query->where('status', (string) $status);
            })
            ->when($search->isNotEmpty(), function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            })
            ->paginate(10);

        return response()->json([
            'result' => true,
            'message' => 'Quizzes retrieved successfully.',
            'data' => QuizResource::collection($quizzes),
            'meta' => [
                'current_page' => $quizzes->currentPage(),
                'last_page'    => $quizzes->lastPage(),
                'per_page'     => $quizzes->perPage(),
                'total'        => $quizzes->total(),
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
            'password' => Str::upper(Str::random(6)),
        ]);

        return response()->json([
            'result' => true,
            'message' => 'Quiz created successfully.',
            'data' => new QuizResource($quiz),
        ]);
    }

    /**
     * Display the specified resource base on param
     */

    public function show(int $quiz_id): JsonResponse
    {
        $quiz = Quiz::where('id', $quiz_id)
            ->where('creator_id', Auth::id())
            ->first();

        if (!$quiz) {
            return response()->json([
                'result' => false,
                'message' => 'Quiz not found or you do not have permission.',
            ], 404);
        }

        return response()->json([
            'result' => true,
            'message' => 'Quiz retrieved successfully.',
            'data' => new QuizResource($quiz->loadCount('questions')),
        ]);
    }


    // Find quiz by password for participants to join by param
    public function findQuizByPassword(string $password_quiz): JsonResponse
    {
        $password = strtoupper($password_quiz);

        $quiz = Quiz::query()
            ->where('password', $password)->where('status', QuizStatus::ACTIVE->value)
            ->withCount('questions')
            ->first();

        if (!$quiz) {
            return response()->json([
                'result' => false,
                'message' => 'Quiz not found or is not active.',
            ], 404);
        }

        return response()->json([
            'result' => true,
            'message' => 'Quiz found successfully.',
            'data' => new QuizResource($quiz),
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
            'result' => true,
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
