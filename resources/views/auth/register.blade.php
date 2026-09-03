<x-layout>
<h1>Reģistrācija</h1>
<label>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        @endif
        <label for="username">Lietotājs vārds:</label>
        <input type="text" id="username" name="username" required><br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>
        <label for="password_confirmation">Password Confirmation:</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required><br>
        <button type="submit">Reģistrācija</button>
    </form>
</x-layout>