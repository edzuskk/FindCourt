<x-layout>
    <main class="profile-page">
        <div class="profile-header">
            <h1>Your profile</h1>
            <a href="{{ route('profile.edit') }}" class="btn-edit">✏️ Edit profile</a>
        </div>

        <div class="profile-hero">
            <div class="card card-soft profile-identity">
                <img
                    src="{{ $user->photo ? asset('storage/' . $user->photo) : asset('images/Default_pfp.jpg') }}"
                    alt="Profile picture" class="avatar">

                <div>
                    <div class="profile-name">
                        {{ $user->username }}@if (auth()->user()->is_admin == 1) 👑 @endif
                    </div>
                    <div class="profile-email">{{ $user->email }}</div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="stats-label">Profile stats</div>
                <div class="stats-row">
                    <div class="stat">
                        <div class="stat-value">{{ $user->courts->count() }}</div>
                        <div class="stat-label">Courts</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value">{{ $user->reviews->count() }}</div>
                        <div class="stat-label">Reviews</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value">{{ $user->savedCourts->count() }}</div>
                        <div class="stat-label">Saved</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-grid">
            <div class="card">
                <h2>🏀 My added courts</h2>

                @if ($user->courts->isEmpty())
                    <p class="empty-note">You haven’t added any courts yet.</p>
                @else
                    @foreach ($user->courts as $court)
                        <div class="item" @if ($loop->index >= 3) hidden data-extra-item="added-courts" @endif>
                            @if ($court->photo)
                                <img src="{{ asset('storage/' . $court->photo) }}" alt="Court photo" class="item-photo">
                            @endif

                            <div class="item-title">{{ $court->name ?: 'Untitled court' }}</div>
                            <div class="item-sub">{{ $court->city }}{{ $court->state ? ', ' . $court->state : '' }}</div>

                            <a href="{{ route('map') }}?court={{ $court->id }}" class="item-link">🗺️ See on map</a>

                            <div class="item-meta">📅 Added {{ $court->created_at?->format('M d, Y') ?? 'recently' }}</div>
                        </div>
                    @endforeach

                    @if ($user->courts->count() > 3)
                        <button type="button" class="btn-show-more" onclick="showMoreItems('added-courts', this)" data-expanded="false">Show more</button>
                    @endif
                @endif
            </div>

            <div class="card">
                <h2>💬 My reviews</h2>

                @if ($user->reviews->isEmpty())
                    <p class="empty-note">You haven’t written any reviews yet.</p>
                @else
                    @foreach ($user->reviews as $review)
                        <div class="item" @if ($loop->index >= 3) hidden data-extra-item="reviews" @endif>
                            <div class="review-head">
                                <span class="item-title">{{ $review->court?->name ?? 'Court' }}</span>
                                @if ($review->rating)
                                    <span class="rating">⭐ {{ $review->rating }}/5</span>
                                @endif
                            </div>

                            <div class="item-sub">{{ $review->created_at?->format('M d, Y') ?? 'Recently' }}</div>

                            @if ($review->comment)
                                <p class="review-comment">{{ $review->comment }}</p>
                            @endif

                            @if ($review->photo)
                                <img src="{{ asset('storage/' . $review->photo) }}" alt="Review photo" class="item-photo" style="margin-top: 10px;">
                            @endif
                        </div>
                    @endforeach

                    @if ($user->reviews->count() > 3)
                        <button type="button" class="btn-show-more" onclick="showMoreItems('reviews', this)" data-expanded="false">Show more</button>
                    @endif
                @endif
            </div>
        </div>

        <div class="section-full">
            <div class="card">
                <h2>⭐ Your saved courts</h2>

                @if ($user->savedCourts->isEmpty())
                    <p class="empty-note">You haven’t saved any courts yet.</p>
                @else
                    <div class="item-grid">
                        @foreach ($user->savedCourts as $court)
                            <div class="item" @if ($loop->index >= 3) hidden data-extra-item="saved-courts" @endif>
                                @if ($court->photo)
                                    <img src="{{ asset('storage/' . $court->photo) }}" alt="Court photo" class="item-photo">
                                @endif

                                <div class="item-title">{{ $court->name ?: 'Untitled court' }}</div>
                                <div class="item-sub">{{ $court->city ?: 'Unknown city' }}{{ $court->state ? ', ' . $court->state : '' }}</div>

                                <div style="margin-top: 10px;">
                                    <span class="rating">⭐ {{ $court->rating ? number_format($court->rating, 1) : 'No rating yet' }}</span>
                                </div>

                                <a href="{{ route('map') }}?court={{ $court->id }}" class="item-link">🗺️ See on map</a>

                                <div class="item-meta">Saved {{ $court->pivot?->created_at?->format('M d, Y') ?? 'recently' }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if ($user->savedCourts->count() > 3)
                        <button type="button" class="btn-show-more" onclick="showMoreItems('saved-courts', this)" data-expanded="false">Show more</button>
                    @endif
                @endif
            </div>
        </div>

        <div class="section-full">
            <div class="card">
                <h2>⚑ Your reported courts</h2>

                @if ($user->reportedCourts->isEmpty())
                    <p class="empty-note">You haven’t reported any courts.</p>
                @else
                    <div class="item-grid">
                        @foreach ($user->reportedCourts as $court)
                            @php
                                $resolved = ($court->pivot->is_resolved ?? null) == 1;
                            @endphp

                            <div class="item" @if ($loop->index >= 3) hidden data-extra-item="reported-courts" @endif>
                                @if ($court->photo)
                                    <img src="{{ asset('storage/' . $court->photo) }}" alt="Court photo" class="item-photo">
                                @endif

                                <span class="badge {{ $resolved ? 'badge-resolved' : 'badge-pending' }}">
                                    {{ $resolved ? '✔ Handled' : '⌛ Pending' }}
                                </span>

                                <div class="item-title">{{ $court->name ?: 'Untitled court' }}</div>
                                <div class="item-sub">{{ $court->city ?: 'Unknown city' }}{{ $court->state ? ', ' . $court->state : '' }}</div>

                                <div style="margin-top: 10px;">
                                    <span class="rating">⭐ {{ $court->rating ? number_format($court->rating, 1) : 'No rating yet' }}</span>
                                </div>

                                <a href="{{ route('map') }}?court={{ $court->id }}" class="item-link">🗺️ See on map</a>

                                <div class="item-meta">Reported {{ $court->pivot?->created_at?->format('M d, Y') ?? 'recently' }}</div>

                                @if (! $resolved)
                                    <form action="{{ route('reports.cancel', ['report' => $court->pivot->id]) }}"
                                          method="POST" onsubmit="return confirm('Cancel this report?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-cancel-report">✖ Cancel report</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </main>

    <script>
        function showMoreItems(group, button) {
            const extraItems = document.querySelectorAll(`[data-extra-item="${group}"]`);
            const expanded = button.dataset.expanded === 'true';

            extraItems.forEach(item => {
                item.hidden = expanded;
            });

            button.dataset.expanded = expanded ? 'false' : 'true';
            button.textContent = expanded ? 'Show more' : 'Show less';
        }
    </script>
</x-layout>
