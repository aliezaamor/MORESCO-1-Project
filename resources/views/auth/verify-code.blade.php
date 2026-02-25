<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - Moresco-1 SMS System</title>
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

            <h2 style="margin-bottom: 0.5rem; color: var(--text-dark); font-size: 1.25rem;">Verify Reset Code</h2>
            <p style="margin-bottom: 1.5rem; color: var(--text-light); font-size: 0.875rem;">Please enter the 6-digit code sent to {{ $email }}.</p>

            @if ($errors->any())
                <div style="background: #fef2f2; color: #b91c1c; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem;">
                    <ul style="list-style: none;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div style="background: #ecfdf5; color: #047857; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem; border: 1px solid #a7f3d0;">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('password.confirm') }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                
                <div class="form-group">
                    <label class="form-label">6-Digit Code</label>
                    <input type="text" name="code" class="form-control" maxlength="6" style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem;" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">
                    Verify Code
                </button>
            </form>
            
            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.875rem;">
                Didn't receive code? <a href="{{ route('password.request') }}" style="color: var(--moresco-blue); text-decoration: none; font-weight: 500;">Resend</a>
            </div>
        </div>
    </div>

    <div class="auth-footer">
        &copy; {{ date('Y') }} Moresco-1 SMS Management System. All Rights Reserved.
    </div>
</body>
</html>
