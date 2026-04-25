<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
