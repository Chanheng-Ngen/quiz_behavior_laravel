<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $token = $validated['token'];
        $password = $validated['password'];

        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $resetToken || $resetToken->token !== $token) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($password),
        ]);

        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ]);
    }
}
