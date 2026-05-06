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

class QuizController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $allowedStatuses = ['active', 'draft', 'closed'];
        $status = $request->string('status')->lower();
        $search = $request->string('search')->trim();

        $quizzes = Quiz::query()
            ->withCount('questions')
            ->when($status->isNotEmpty() && in_array((string) $status, $allowedStatuses, true), function ($query) use ($status) {
                $query->where('status', (string) $status);
            })
            ->when($search->isNotEmpty(), function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->paginate(10);

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
            'password' => Str::upper(Str::random(6)),
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
            'data' => new QuizResource($quiz->loadCount('questions')),
        ]);
    }

    // Find quiz by password for participants to join
    public function findQuizByPassword(Request $request): JsonResponse
    {
        $password = $request->string('password')->upper();

        $quiz = Quiz::query()
            ->where('password', $password)
            ->withCount('questions')
            ->first();

        if ($quiz === null) {
            return response()->json([
                'result' => false,
                'message' => 'No quiz found with the provided password.',
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
