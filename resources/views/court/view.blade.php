<x-layout>
        <main class="court-page">
        <div class="court-card court-detail">
            <a href="{{ url('/') }}" class="court-back-link">&larr; Back to map</a>

            <h3>{{ $court->name }}</h3>

            @if($court->photo)
                <img src="{{ str_starts_with($court->photo, 'http') || str_starts_with($court->photo, '/') ? $court->photo : '/storage/' . $court->photo }}" alt="{{ $court->name }} photo" class="court-photo">
            @endif

            <div class="court-meta">
                <p class="meta-row"><span>📍</span><strong>Address:</strong> {{ $court->address ?? 'Not added' }}</p>
                <p class="meta-row"><span>🏙️</span><strong>City:</strong> {{ $court->city ?? 'Not added' }}</p>
                @if($court->state)
                    <p class="meta-row"><span>🗺️</span><strong>State:</strong> {{ $court->state }}</p>
                @endif
                <p class="meta-row"><span>🧭</span><strong>Coordinates:</strong> {{ $court->latitude }}, {{ $court->longitude }}</p>
                <p class="meta-row"><span>⭐</span><strong>Average rating:</strong> {{ $court->rating > 0 ? $court->rating : 'No rating' }}</p>
                <p class="meta-row"><span>👍</span><strong>Likes:</strong> {{ $court->likes ?? 0 }} &nbsp; <span>👎</span><strong>Dislikes:</strong> {{ $court->dislikes ?? 0 }}</p>
            </div>

            <p class="court-description"><strong>📝 Description:</strong> {{ $court->description ?? 'No description yet.' }}</p>

            <div class="court-comments-header"><strong>💬 Comments ({{ $reviews->count() }})</strong></div>

            <div class="court-comments">
                @forelse($reviews as $review)
                    <div class="review-item">
                        <div class="review-head">
                            <strong>{{ $review->username }}</strong>
                            <span>⭐ {{ $review->rating ?? 'No rating' }}</span>
                        </div>
                        <small class="review-date">{{ $review->created_at }}</small>
                        @if($review->photo)
                            <img src="{{ str_starts_with($review->photo, 'http') || str_starts_with($review->photo, '/') ? $review->photo : '/storage/' . $review->photo }}" alt="Review photo" class="review-photo">
                        @endif
                        <p>{{ $review->comment ?? 'No comment provided.' }}</p>
                    </div>
                @empty
                    <p class="empty-note">No comments yet.</p>
                @endforelse
            </div>
        </div>
    </main>
</x-layout>