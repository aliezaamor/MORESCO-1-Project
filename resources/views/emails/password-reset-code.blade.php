<x-mail::message>
# Password Reset Code

Hello,

You are receiving this email because we received a password reset request for your account.

Your password reset code is:

<div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
    <span style="font-size: 32px; font-weight: bold; color: #004d99; letter-spacing: 5px;">{{ $code }}</span>
</div>

This code will expire in **60 minutes**.

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
