<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Moresco-1 SMS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="logo-area">
                <img src="{{ asset('images/M1.jpg') }}" alt="Moresco-1 Logo" style="width: 80px; height: auto; border-radius: 50%; object-fit: cover;">
                <div>MORESCO-1</div>
                <div style="font-size: 0.875rem; color: var(--text-light); font-weight: 400;">SMS Management System</div>
            </div>

            <h2 style="margin-bottom: 0.5rem; color: var(--text-dark); font-size: 1.25rem;">Reset Password</h2>
            <p style="margin-bottom: 1.5rem; color: var(--text-light); font-size: 0.875rem;">Please enter your email and your new password below.</p>

            @if ($errors->any())
                <div style="background: #fef2f2; color: #b91c1c; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem;">
                    <ul style="list-style: none;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required value="{{ old('email', $email) }}" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">
                    Reset Password
                </button>
            </form>
        </div>
    </div>

    <div class="auth-footer">
        &copy; {{ date('Y') }} Moresco-1 SMS Management System. All Rights Reserved.
    </div>
</body>
</html>
