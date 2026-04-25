<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Mail\ResetPasswordMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Mail;;

class ForgotPasswordController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $token,
                'created_at' => now(),
            ]
        );

        $user = User::where('email', $email)->first();
        if ($user) {
            Mail::to($user)->send(new ResetPasswordMail($user, $token));
        }

        return response()->json([
            'message' => 'Password reset link has been sent to your email.',
            'token' => $token,
        ]);
    }
}
