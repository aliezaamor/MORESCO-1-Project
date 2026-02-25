<x-mail::message>
# Password Reset Code

You are receiving this email because we received a password reset request for your account.

Your password reset code is:
<h1 style="text-align: center; color: #004d99; font-size: 2rem; letter-spacing: 0.5rem; margin: 1.5rem 0;">{{ $code }}</h1>

This code will expire in 60 minutes.

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
