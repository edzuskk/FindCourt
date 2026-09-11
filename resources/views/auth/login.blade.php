<x-layout>
    <div class="auth-shell">
        <div class="auth-wrapper auth-wrapper-login">
            <div class="auth-visual">
                <div class="auth-kicker">Welcome back</div>
                <h1 class="auth-title">Find your next favorite court.</h1>
                <p class="auth-copy">
                    Log in to manage your profile, track the courts you added, and keep up with your reviews.
                </p>
            </div>

            <div class="auth-panel">
                <div class="auth-content">
                    <div class="auth-header">
                        <h2>Login</h2>
                        <p>Access your account</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="auth-form">
                        @csrf

                        @if ($errors->any())
                            <div class="auth-errors">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="auth-field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="auth-field">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <button type="submit" class="auth-button">Login</button>
                    </form>

                    <p class="auth-footer">
                        Don’t have an account?
                        <a href="{{ route('register') }}">Register</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layout>