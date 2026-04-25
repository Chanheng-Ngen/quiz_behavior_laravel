Hi {{ $user->name }},

Click the link below to reset your password:
{{ url('/api/auth/reset-password?token=' . $token . '&email=' . $user->email) }}

If you didn't request this, ignore this email.