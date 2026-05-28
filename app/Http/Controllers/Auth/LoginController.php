<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function googleRedirect(): JsonResponse
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'url' => $url,
        ]);
    }

    public function googleCallback(): JsonResponse
    {
        $googleUser = Socialite::driver('google')->stateless()->user();
        $email = $googleUser->getEmail();

        if (! $email) {
            return response()->json([
                'message' => 'Google account does not have an email address.',
            ], 422);
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        $googleName = $googleUser->getName() ?? $googleUser->getNickname() ?? $email;

        if (! $user) {
            $user = User::create([
                'name' => $googleName,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'google_id' => $googleUser->getId(),
                'provider' => 'google',
                'avatar' => $googleUser->getAvatar(),
            ]);

            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        } else {
            $updates = [
                'google_id' => $googleUser->getId(),
                'provider' => 'google',
                'avatar' => $googleUser->getAvatar(),
            ];

            if (! $user->name) {
                $updates['name'] = $googleName;
            }

            if (! $user->hasVerifiedEmail()) {
                $updates['email_verified_at'] = now();
            }

            $user->forceFill($updates)->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'result' => true,
            'message' => 'Logged in successfully.',
            'token' => $token,
            'data' => $user,
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'result' => true,
            'message' => 'Logged in successfully.',
            'token' => $token,
            'data' => $user,
        ]);
    }
}
