<nav>
    <div class="navbar">
        <a class="brand" href="/">
            <span class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 2c1.96 0 3.74.82 5 2.13-1.1 1.32-2.62 2.22-4.33 2.56-.46-.86-1.02-1.66-1.67-2.37A7.98 7.98 0 0 0 12 4zm-2.1.28c.64.7 1.2 1.49 1.66 2.34-1.7.35-3.4.15-4.96-.63A8.02 8.02 0 0 1 9.9 4.28zM4.26 7.61c1.83.86 3.82 1.1 5.74.74.42.86.73 1.78.91 2.72-.98.5-2.06.77-3.2.77-.77 0-1.5-.13-2.18-.36a8.1 8.1 0 0 1-1.27-3.87zM6 14.31c-.85-.48-1.63-1.1-2.29-1.83.25 1.66.94 3.18 1.94 4.42A7.9 7.9 0 0 1 6 14.31zm1.92.47c1.26-.34 2.58-.44 3.87-.3.2.94.52 1.84.93 2.67-1.53.08-3.03-.4-4.3-1.32a8.6 8.6 0 0 1-.5-1.05zm4.08 1.63c-.4-.8-.72-1.66-.92-2.54a7.66 7.66 0 0 1 2.92.7c-.5.66-1.17 1.28-2 1.84zm2.44-3.6a9.3 9.3 0 0 0-3.5-.85c.13-.72.34-1.42.6-2.08 1.54-.32 3.12-.08 4.6.66a8.1 8.1 0 0 1 .86 2.06c-.82.14-1.65.2-2.56.21zm.5 3.3c1.18-.62 2.16-1.5 2.86-2.56a7.86 7.86 0 0 1 .96 3.9c-1.23.1-2.5-.13-3.6-.68a8 8 0 0 1-.22-.66zm4.38 1.03a9.94 9.94 0 0 0-1.16-4.58c.5-.05 1-.13 1.47-.27a8.1 8.1 0 0 1-.31 4.85zM12 20c-1.5 0-2.9-.42-4.1-1.14.63-1.2 1.6-2.12 2.73-2.7.4.2.83.37 1.37.5.5.12 1.02.19 1.53.19.2 0 .42-.01.62-.03a8.04 8.04 0 0 1-2.15 3.18z" fill="#0f2e1d"/>
                </svg>
            </span>
            Find<span>Court</span>
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
            @else
                <a href="{{ route('login') }}">Login</a>
                <a href="{{ route('register') }}">Register</a>
            @endauth
        </div>
    </div>
</nav>
