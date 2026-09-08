<nav>
    <div class="navbar">
        <a class="brand" href="/">
            Find<span>🏀</span><span>Court</span>
        </a>

        <div class="nav-links">
            @auth
                <span class="nav-greeting">👋Sveicināts, {{ auth()->user()->username }}!</span>
                <a class="profile" href="{{ route('profile.view') }}">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Profils
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </button>
                </form>
                @if (auth()->user()->is_admin == 1)
                    <a href="{{ route('admin.dashboard') }}" class="admin-link">Admin Panel</a>
                @endif
            @else
                <p style="color: white">Please register to add courts.</p>
                <a href="{{ route('login') }}">Login</a>
                <a href="{{ route('register') }}">Register</a>
            @endauth
        </div>
    </div>
</nav>
