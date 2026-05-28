<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'result' => true,
            'message' => 'User details retrieved successfully.',
            'data' => $request->user(),
        ]);
    }
}
