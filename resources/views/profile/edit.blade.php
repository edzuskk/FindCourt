<x-layout>
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <br>
        <br>
        <br>
        <br>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="{{ auth()->user()->username }}" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ auth()->user()->email }}" required>
        </div>
        
        <div class="form-group">
            <label for="profile_picture">Profile Picture</label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
        </div>

        <button type="submit">Update Profile</button>
    </form>
</x-layout>