<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Management</title>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('images/M1-L.png') }}">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
    <!-- FullCalendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
</head>
<body>
    <aside class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-paper-plane" style="margin-right: 1rem;"></i> SMS Management
        </div>
        <ul class="nav-links">
            <li>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>
            </li>
            @if(auth()->user()->role === 'admin')
            <li>
                <a href="{{ route('admin.activities') }}" class="nav-link {{ request()->routeIs('admin.activities') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check"></i> User Activity Log
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('view.contacts.index') }}" class="nav-link {{ request()->routeIs('view.contacts.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-address-book"></i> Contacts & Groups
                </a>
            </li>
            <li>
                <a href="{{ route('accounts.index') }}" class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-viewfinder"></i> Accounts Master List
                </a>
            </li>
            <li class="nav-item has-dropdown">
                @php
                    $isMessagesActive = request()->routeIs('view.messages.*') || request()->routeIs('view.broadcasts.*');
                @endphp
                <a href="#" class="nav-link {{ $isMessagesActive ? 'active' : '' }}" onclick="toggleSubmenu(event, 'messagesSubmenu')">
                    <i class="fa-solid fa-envelope"></i> Messages
                    <i class="fa-solid fa-chevron-down submenu-icon" style="margin-left: auto; font-size: 0.8em; transition: transform 0.3s; transform: {{ $isMessagesActive ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
                </a>
                <ul class="submenu" id="messagesSubmenu" style="display: {{ $isMessagesActive ? 'flex' : 'none' }}; list-style: none; padding-left: 2rem; margin-top: 0.5rem; gap: 0.5rem; flex-direction: column;">
                    <li>
                        <a href="{{ route('view.messages.index', ['type' => 'individual']) }}" class="nav-link {{ request()->routeIs('view.messages.*') && request('type') === 'individual' && !request('scheduled') ? 'active' : '' }}" style="padding: 0.5rem 1rem; font-size: 0.9em;">
                            <i class="fa-solid fa-user"></i> Individual Notification
                        </a>
                        @if(request('type') === 'individual')
                        <ul style="list-style: none; padding-left: 2.5rem; margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <li>
                                <a href="{{ route('view.messages.index', ['type' => 'individual', 'scheduled' => 1]) }}" class="nav-link {{ request('scheduled') ? 'active' : '' }}" style="padding: 0.35rem 1rem; font-size: 0.8em; color: var(--text-light); border-left: 2px solid {{ request('scheduled') ? 'var(--primary-color)' : 'transparent' }};">
                                    <i class="fa-solid fa-clock"></i> Scheduled Messages
                                </a>
                            </li>
                        </ul>
                        @endif
                    </li>
                    <li>
                        <a href="{{ route('view.messages.index', ['type' => 'broadcast']) }}" class="nav-link {{ request()->routeIs('view.messages.*') && request('type') === 'broadcast' && !request('scheduled') ? 'active' : '' }}" style="padding: 0.5rem 1rem; font-size: 0.9em; margin-top: 0.5rem;">
                            <i class="fa-solid fa-bullhorn"></i> Broadcast Messages
                        </a>
                        @if(request('type') === 'broadcast')
                        <ul style="list-style: none; padding-left: 2.5rem; margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <li>
                                <a href="{{ route('view.messages.index', ['type' => 'broadcast', 'scheduled' => 1]) }}" class="nav-link {{ request('scheduled') ? 'active' : '' }}" style="padding: 0.35rem 1rem; font-size: 0.8em; color: var(--text-light); border-left: 2px solid {{ request('scheduled') ? 'var(--primary-color)' : 'transparent' }};">
                                    <i class="fa-solid fa-clock"></i> Scheduled Messages
                                </a>
                            </li>
                        </ul>
                        @endif
                    </li>
                </ul>
            </li>
            <li class="nav-item has-dropdown">
                @php
                    $isKeywordsActive = request()->routeIs('view.keywords.*') || (request()->routeIs('view.messages.*') && request('type') === 'auto_reply');
                @endphp
                <a href="#" class="nav-link {{ $isKeywordsActive ? 'active' : '' }}" onclick="toggleSubmenu(event, 'keywordsSubmenu')">
                    <i class="fa-solid fa-keyboard"></i> Keywords
                    <i class="fa-solid fa-chevron-down submenu-icon" style="margin-left: auto; font-size: 0.8em; transition: transform 0.3s; transform: {{ $isKeywordsActive ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
                </a>
                <ul class="submenu" id="keywordsSubmenu" style="display: {{ $isKeywordsActive ? 'flex' : 'none' }}; list-style: none; padding-left: 2rem; margin-top: 0.5rem; gap: 0.5rem; flex-direction: column;">
                    <li>
                        <a href="{{ route('view.keywords.index') }}" class="nav-link {{ request()->routeIs('view.keywords.index') ? 'active' : '' }}" style="padding: 0.5rem 1rem; font-size: 0.9em;">
                            <i class="fa-solid fa-list"></i> Manage Keywords
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('view.messages.index', ['type' => 'auto_reply']) }}" class="nav-link {{ request()->routeIs('view.messages.index') && request('type') === 'auto_reply' ? 'active' : '' }}" style="padding: 0.5rem 1rem; font-size: 0.9em;">
                            <i class="fa-solid fa-clock-rotate-left"></i> Keyword History
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="{{ route('view.simulator.index') }}" class="nav-link {{ request()->routeIs('view.simulator.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-comments"></i> Message Threads
                </a>
            </li>
            <li>
                <a href="{{ url('/test-billing') }}" class="nav-link {{ request()->is('test-billing') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Billing Tester
                </a>
            </li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <h1 class="page-title" style="margin-bottom: 0;">@yield('title')</h1>
                @stack('header_actions')
            </div>
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
                        <div class="profile-email" title="{{ auth()->user()->email }}" style="font-size: 0.75rem; color: var(--text-light);">{{ auth()->user()->email }}</div>
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const response = await fetch(`${API_BASE_URL}${endpoint}`, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
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

        function toggleSubmenu(event, id) {
            event.preventDefault();
            const submenu = document.getElementById(id);
            const icon = event.currentTarget.querySelector('.submenu-icon');
            if (submenu.style.display === 'none') {
                submenu.style.display = 'flex';
                if(icon) icon.style.transform = 'rotate(180deg)';
            } else {
                submenu.style.display = 'none';
                if(icon) icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
    
    <!-- SweetAlert2 for beautiful alerts and modals -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @stack('scripts')
</body>
</html>
