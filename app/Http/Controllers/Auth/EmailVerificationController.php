<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendEmailVerificationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(
            sha1($user->getEmailForVerification()),
            $request->route('hash')
        )) {
            return redirect(config('app.frontend_url') . '/email-verification?status=invalid-hash');
        }

        if (! $request->hasValidSignature()) {
            return redirect( config('app.frontend_url') . '/email-verification?status=expired');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect( config('app.frontend_url') . '/auth');
    }

    public function sendVerificationEmail(SendEmailVerificationRequest $request): JsonResponse
    {
        $email = $request->validated('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'No account found with this email.',
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'This email is already verified.',
            ], 409);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent successfully.',
        ]);
    }
}
