```blade
<x-layout>
    <div class="auth-shell auth-shell-register">
        <div class="auth-wrapper auth-wrapper-register">
            <div class="auth-panel">
                <div class="auth-content">
                    <div class="auth-header auth-header-centered">
                        <h2>Reset your account password</h2>
                        <p>
                            Enter your email and you will get a reset link sent
                            to the email connected to your account.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
                        @csrf

                        @if (session('status'))
                            <p>{{ session('status') }}</p>
                        @endif

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
                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                            >
                        </div>

                        <button type="submit" class="auth-button">
                            Send reset password link
                        </button>
                    </form>
                </div>
            </div>

            <div class="auth-visual">
                <div class="auth-kicker">Reset your password</div>

                <h1 class="auth-title">
                    Reset your password to access your account.
                </h1>

                <p class="auth-copy">
                    By resetting your password, you will be able to access
                    your account, if you have one.
                </p>
            </div>
        </div>
    </div>
</x-layout>
```
