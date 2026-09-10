<x-layout>
    <h1>Profile</h1>

    <div class="profile-info">
        @if (auth()->user()->profile_picture == null)
            <img src="images/Default_pfp.jpg" alt="Profile Picture" class="profile-picture">
        @else
            <img src="{{ auth()->user()->profile_picture }}" alt="Profile Picture" class="profile-picture">
        @endif
        <p>{{ auth()->user()->username }}</p>
    </div>

    <a href="{{ route('profile.edit') }}" class="edit-profile">Edit profile</a>
</x-layout>