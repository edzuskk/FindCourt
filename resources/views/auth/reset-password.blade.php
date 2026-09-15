<x-layout>
    <div class="auth-shell auth-shell-register">
        <div class="auth-wrapper auth-wrapper-register">
            <div class="auth-panel">
                <div class="auth-content">

                    <div class="auth-header auth-header-centered">
                        <h2>Reset your account password</h2>
                        <p>
                            Enter your new password below.
                        </p>
                    </div>

                    <form method="POST"
                          action="{{ route('password.update') }}"
                          class="auth-form">

                        @csrf

                        {{-- Token from the email link --}}
                        <input type="hidden" name="token" value="{{ $token }}">

                        {{-- Email from the email link --}}
                        <div class="auth-field">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ $email }}"
                                required
                            >
                        </div>

                        {{-- New password --}}
                        <div class="auth-field">
                            <label for="password">New password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                            >
                        </div>

                        {{-- Confirm password --}}
                        <div class="auth-field">
                            <label for="password_confirmation">
                                Confirm new password
                            </label>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                            >
                        </div>

                        @if ($errors->any())
                            <div class="auth-errors">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <button type="submit" class="auth-button">
                            Reset your password
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
                    your account.
                </p>
            </div>
        </div>
    </div>
</x-layout>