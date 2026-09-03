<nav>
    <div class="navbar">
        <div class="nav-navbar">
            <a class="home" href="/">Karte</a>
            <p>Admin parole: Admin$123</p>
        </div>
        <div class="auth">
            @auth
                <p>Sveicināts, {{ auth()->user()->username }}!</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Iziet</button>
                </form>
            @else
                <a href="{{ route('login') }}">Ienākt</a>
                <a href="{{ route('register') }}">Reģistrēties</a>
            @endauth

        </div>
        <div class="add-marker">
            <a href="{{ url('/create') }}">Pievienot jaunu vietu</a>
        </div>
    </div>
</nav>
