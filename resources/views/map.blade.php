<x-layout>
    <div class="court-filter-bar" aria-label="Court filters">
        <div class="court-search-wrapper">
            <span class="filter-icon" aria-hidden="true">🔎︎</span>
            <input
                id="courtSearch"
                type="text"
                placeholder="Search courts, cities, addresses..."
                aria-label="Search courts"
            >
        </div>

        <button type="button" id="filterToggle" class="filter-toggle-btn" aria-expanded="false" aria-controls="filterPanel" aria-label="Toggle filters">
            <span aria-hidden="true">⚙</span> Filters
        </button>

        <div id="filterPanel" class="court-filter-panel" hidden>
            <div class="rating-filter-wrapper">
                <label for="ratingFilter">Rating</label>
                <select id="ratingFilter" aria-label="Filter courts by rating">
                    <option value="all">All ratings</option>
                    <option value="5">5 stars</option>
                    <option value="4">4+ stars</option>
                    <option value="3">3+ stars</option>
                    <option value="2">2+ stars</option>
                    <option value="1">1+ stars</option>
                </select>
            </div>

            <div class="rating-filter-wrapper">
                <label for="cityFilter">City</label>
                <select id="cityFilter" aria-label="Filter courts by city">
                    <option value="all">All cities</option>
                    @foreach($courts->pluck('city')->filter()->sort()->unique() as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rating-filter-wrapper">
                <label for="stateFilter">State</label>
                <select id="stateFilter" aria-label="Filter courts by state">
                    <option value="all">All states</option>
                    @foreach($courts->pluck('state')->filter()->sort()->unique() as $state)
                        <option value="{{ $state }}">{{ $state }}</option>
                    @endforeach
                </select>
            </div>

            @auth
            <div class="rating-filter-wrapper" style="display: flex; align-items: center; gap: 6px;">
                <input type="checkbox" id="savedOnlyFilter" aria-label="Only saved courts">
                <label for="savedOnlyFilter" style="margin: 0;">⭐ Only saved</label>
            </div>
            @endauth

            <div class="rating-filter-wrapper">
                <label for="sortFilter">Sort</label>
                <select id="sortFilter" aria-label="Sort courts">
                    <option value="default">Default</option>
                    <option value="rating">Highest rated</option>
                    <option value="likes">Most liked</option>
                    <option value="newest">Newest</option>
                </select>
            </div>
        </div>
    </div>

    <div id="map" data-can-add-court="{{ auth()->check() ? 'true' : 'false' }}" data-current-user-id="{{ auth()->id() ?? '' }}" data-is-admin="{{ auth()->check() && auth()->user()->is_admin ? 'true' : 'false' }}"></div>

    <aside id="sidebar" class="sidebar" aria-label="Court sidebar">
        <div class="sidebar-header">
            <h2 id="sidebarTitle">Court information</h2>
            <button type="button" class="close-btn" onclick="closeSidebar()" aria-label="Close sidebar">×</button>
        </div>
        <div id="sidebarContent" class="sidebar-body"></div>
    </aside>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('map.js') }}"></script>
</x-layout>