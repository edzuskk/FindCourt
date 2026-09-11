<x-layout>
    <div style="margin-top: 50px">
    <div style="max-width: 1100px; margin: 0 auto; padding: 32px 16px 48px;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
            <h1 style="margin: 0;">Profile</h1>
            <a href="{{ route('profile.edit') }}" class="edit-profile">Edit profile</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 32px;">
            <div style="background: #f5f7f4; border: 1px solid #d7ddd8; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 16px;">
                @if ($user->photo == null)
                    <img src="{{ asset('images/Default_pfp.jpg') }}" alt="Profile Picture" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid #c9d2cc;">
                @else
                    <img src="{{ asset('storage/' . $user->photo) }}" alt="Profile Picture" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid #c9d2cc;">
                @endif

                <div>
                    @if (auth()->user()->is_admin == 1)
                        <div style="font-size: 1.4rem; font-weight: 700;">
                            {{ $user->username }} 👑
                        </div>
                    @else
                        <div style="font-size: 1.4rem; font-weight: 700;">
                            {{ $user->username }}
                        </div>
                    @endif

                    <div style="color: #4a4f4b;">
                        {{ $user->email }}
                    </div>
                </div>
            </div>

            <div style="background: #f5f7f4; border: 1px solid #d7ddd8; border-radius: 16px; padding: 20px;">
                <div style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b736e; margin-bottom: 8px;">Profile stats</div>
                <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                    <div>
                        <div style="font-size: 1.6rem; font-weight: 700;">{{ $user->courts->count() }}</div>
                        <div style="color: #4a4f4b;">Courts</div>
                    </div>
                    <div>
                        <div style="font-size: 1.6rem; font-weight: 700;">{{ $user->reviews->count() }}</div>
                        <div style="color: #4a4f4b;">Reviews</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            <div style="background: #ffffff; border: 1px solid #d7ddd8; border-radius: 16px; padding: 20px;">
                <h2 style="margin-top: 0; margin-bottom: 16px;">My added courts</h2>

                @if ($user->courts->isEmpty())
                    <p style="margin: 0; color: #4a4f4b;">You haven’t added any courts yet.</p>
                @else
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        @foreach ($user->courts as $court)
                            <div style="border: 1px solid #dde4df; border-radius: 12px; padding: 14px; background: #f8faf8;">
                                @if ($court->photo)
                                    <img src="{{ asset('storage/' . $court->photo) }}" alt="Court photo" style="width: 100%; max-height: 160px; object-fit: cover; border-radius: 10px; margin-bottom: 10px;">
                                @endif

                                <div style="font-weight: 700; font-size: 1.05rem;">{{ $court->name ?: 'Untitled court' }}</div>
                                <div style="color: #4a4f4b; margin-top: 4px;">{{ $court->city }}, {{ $court->state }}</div>
                                <div style="color: #6b736e; font-size: 0.85rem; margin-top: 8px;">🗓️Added {{ $court->created_at?->format('M d, Y') ?? 'recently' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div style="background: #ffffff; border: 1px solid #d7ddd8; border-radius: 16px; padding: 20px;">
                <h2 style="margin-top: 0; margin-bottom: 16px;">My reviews</h2>

                @if ($user->reviews->isEmpty())
                    <p style="margin: 0; color: #4a4f4b;">You haven’t written any reviews yet.</p>
                @else
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        @foreach ($user->reviews as $review)
                            <div style="border: 1px solid #dde4df; border-radius: 12px; padding: 14px; background: #f8faf8;">
                                <div style="font-weight: 700; margin-bottom: 4px;">{{ $review->court?->name ?? 'Court' }}</div>
                                <div style="color: #6b736e; font-size: 0.85rem; margin-bottom: 8px;">{{ $review->created_at?->format('M d, Y') ?? 'recently' }}</div>

                                @if ($review->rating)
                                    <div style="margin-bottom: 8px;">Rating: {{ $review->rating }}/5</div>
                                @endif

                                @if ($review->comment)
                                    <div style="color: #2f352f; line-height: 1.5;">{{ $review->comment }}</div>
                                @endif

                                @if ($review->photo)
                                    <img src="{{ asset('storage/' . $review->photo) }}" alt="Review photo" style="width: 100%; max-height: 180px; object-fit: cover; border-radius: 10px; margin-top: 10px;">
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    </div>
</x-layout>
