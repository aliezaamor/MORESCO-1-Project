<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Moresco-1 SMS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/M1-L.png') }}">
</head>
<body class="auth-body login-page">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="logo-area">
                <img src="{{ asset('images/M1.jpg') }}" alt="Moresco-1 Logo" style="width: 80px; height: auto; border-radius: 50%; object-fit: cover;">
                <div>MORESCO-1</div>
                <div style="font-size: 0.875rem; color: var(--text-light); font-weight: 400;">SMS Management System</div>
            </div>

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

            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" value="{{ old('username') }}" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="#" style="color: var(--moresco-blue); text-decoration: none;">Forgot Password?</a>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">
                    Log In
                </button>
            </form>
            
            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.875rem;">
                Don't have an account? <a href="{{ route('register') }}" style="color: var(--moresco-blue); text-decoration: none; font-weight: 500;">Register</a>
            </div>
        </div>
    </div>

    <div class="auth-footer">
        &copy; {{ date('Y') }} Moresco-1 SMS Management System. All Rights Reserved.
    </div>
</body>
</html>
