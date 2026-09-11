<x-layout>
    <div class="auth-shell auth-shell-register">
        <div class="auth-wrapper auth-wrapper-register">
            <div class="auth-panel">
                <div class="auth-content">
                    <div class="auth-header auth-header-centered">
                        <h2>Create account</h2>
                        <p>Join and start sharing your courts</p>
                    </div>

                    <form method="POST" action="{{ route('register') }}" class="auth-form">
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
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required>
                        </div>

                        <div class="auth-field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="auth-field">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <div class="auth-field">
                            <label for="password_confirmation">Confirm password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required>
                        </div>

                        <button type="submit" class="auth-button">Register</button>
                    </form>

                    <p class="auth-footer">
                        Already have an account?
                        <a href="{{ route('login') }}">Login</a>
                    </p>
                </div>
            </div>

            <div class="auth-visual">
                <div class="auth-kicker">Start now</div>
                <h1 class="auth-title">Build your own court community.</h1>
                <p class="auth-copy">
                    Create an account to add courts, leave reviews, and connect with other basketball and streetball lovers.
                </p>
            </div>
        </div>
    </div>
</x-layout>