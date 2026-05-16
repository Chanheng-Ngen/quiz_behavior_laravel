<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333;">
    <h2 style="color: #2563eb; margin-bottom: 20px;">Email Verification</h2>

    <p style="line-height: 1.6; margin-bottom: 20px;">
        Hello <strong>{{ $user->name }}</strong>,
    </p>

    <p style="line-height: 1.6; margin-bottom: 20px;">
        Thank you for creating an account with <strong>{{ config('app.name') }}</strong>. 
        To complete your registration and activate your account, please verify your email address by clicking the button below.
    </p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $url }}" 
           style="display: inline-block; padding: 12px 28px; background-color: #2563eb; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
            Verify Email Address
        </a>
    </div>

    <p style="line-height: 1.6; margin-bottom: 15px; color: #666; font-size: 14px;">
        If the button above doesn't work, copy and paste this link in your browser:
    </p>
    <p style="word-break: break-all; background-color: #f3f4f6; padding: 10px; border-radius: 4px; font-size: 13px; color: #555;">
        {{ $url }}
    </p>

    <p style="line-height: 1.6; margin-top: 20px; color: #666; font-size: 13px;">
        <strong>Security notice:</strong> This verification link will expire in {{ config('auth.verification.expire', 60) }} minutes.
    </p>

    <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">

    <p style="line-height: 1.6; margin-bottom: 10px; color: #666; font-size: 13px;">
        If you did not create an account with us, please ignore this message. No further action is required.
    </p>

    <p style="line-height: 1.6; margin-top: 20px; color: #666; font-size: 13px;">
        Regards,<br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #999; font-size: 12px;">
        © {{ date('Y') }} {{ config('app.name') }} — All rights reserved.
    </div>
</div>