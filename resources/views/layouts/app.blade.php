<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS System</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <aside class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-paper-plane"></i> SMS System
        </div>
        <ul class="nav-links">
            <li>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('view.contacts.index') }}" class="nav-link {{ request()->routeIs('view.contacts.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-address-book"></i> Contacts & Groups
                </a>
            </li>
            <li>
                <a href="{{ route('view.messages.index') }}" class="nav-link {{ request()->routeIs('view.messages.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-envelope"></i> Messages
                </a>
            </li>
            <li>
                <a href="{{ route('view.keywords.index') }}" class="nav-link {{ request()->routeIs('view.keywords.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-keyboard"></i> Keywords
                </a>
            </li>
            <li>
                <a href="{{ route('view.simulator.index') }}" class="nav-link {{ request()->routeIs('view.simulator.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-vial"></i> Simulator
                </a>
            </li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1 class="page-title">@yield('title')</h1>
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-trigger" onclick="toggleProfileMenu(event)" title="{{ auth()->user()->name }}">
                    <div class="avatar">
                        @if (auth()->user()->avatar)
                            <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" />
                        @else
                            {{ collect(explode(' ', auth()->user()->name))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('') }}
                        @endif
                    </div>
                </div>
                <div class="profile-menu" id="profileMenu">
                    <div style="padding: 0.75rem 1rem;">
                        <div style="font-weight: 600; font-size: 0.875rem;">{{ auth()->user()->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-light);">{{ auth()->user()->email }}</div>
                    </div>
                    <div class="profile-menu-divider"></div>
                    <a href="{{ route('profile.edit') }}" class="profile-menu-item">
                        <i class="fa-solid fa-user"></i> My Profile
                    </a>
                    <a href="{{ route('settings.index') }}" class="profile-menu-item">
                        <i class="fa-solid fa-gear"></i> Settings
                    </a>
                    <div class="profile-menu-divider"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="profile-menu-item" style="color: var(--danger-color);">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>

        <footer class="footer">
            <p>&copy; {{ date('Y') }} Moresco-1 SMS Management System. All Rights Reserved.</p>
        </footer>
    </main>

    <!-- Base Scripts -->
    <script>
        // Apply theme immediately
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }

        const API_BASE_URL = "{{ url('/api') }}";
        
        async function fetchAPI(endpoint, options = {}) {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...options.headers
                }
            });
            if (!response.ok) {
                throw new Error(`API Error: ${response.statusText}`);
            }
            const text = await response.text();
            return text ? JSON.parse(text) : {};
        }

        function toggleProfileMenu(event) {
            event.stopPropagation();
            document.getElementById('profileMenu').classList.toggle('show');
        }

        window.onclick = function(event) {
            if (!event.target.closest('#profileDropdown')) {
                const menu = document.getElementById('profileMenu');
                if (menu.classList.contains('show')) {
                    menu.classList.remove('show');
                }
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
