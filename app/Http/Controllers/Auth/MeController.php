<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class MeController extends Controller
{
    function me(Request $request) : JsonResponse
    {
        return response()->json([
            "message" => "User details retrieved successfully.",
            "data" => $request->user()
        ]);
    }
}
