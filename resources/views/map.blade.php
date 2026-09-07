<x-layout>
    <div id="map"></div>

    <aside id="sidebar" class="sidebar" aria-label="Court sidebar">
        <div class="sidebar-header">
            <h2 id="sidebarTitle">Court information</h2>
            <button type="button" class="close-btn" onclick="closeSidebar()" aria-label="Close sidebar">×</button>
        </div>
        <div id="sidebarContent" class="sidebar-body"></div>
    </aside>

    

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const map = L.map('map', {
            zoomControl: false
        }).setView([56.9630576312498, 24.810031163689104], 8.1);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        const sidebar = document.getElementById('sidebar');
        const sidebarTitle = document.getElementById('sidebarTitle');
        const sidebarContent = document.getElementById('sidebarContent');

        function showSidebar(title, html) {
            sidebarTitle.textContent = title;
            sidebarContent.innerHTML = '';
            if (typeof html === 'string') {
                sidebarContent.innerHTML = html;
            } else {
                sidebarContent.appendChild(html);
            }
            sidebar.classList.add('open');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarContent.innerHTML = '';
        }

        function buildCourtCard(court) {
            const card = document.createElement('div');
            card.className = 'court-card';
            card.innerHTML = `
                <h3>${court.name || 'Untitled Court'}</h3>
                <p class="court-address"><strong>📍Address:</strong> ${court.address || 'Not added'}</p>
                <p class="court-city"><strong>🏙️City:</strong> ${court.city || 'Not added'}</p>
                <p class="court-coordinates"><strong>Coordinates:</strong> ${court.latitude}, ${court.longitude}</p>
                <p class="court-rating"><strong>⭐Rating:</strong> ${court.rating || 'No rating'}</p>
                <p class="court-likes"><strong>👍Likes:</strong> ${court.likes || 0}</p>
                <p class="court-dislikes"><strong>👎Dislikes:</strong> ${court.dislikes || 0}</p>
                <p class="court-comments-header"><strong>💬Comments:</strong></p>
                <p class="court-description">:<strong></strong> ${court.description || 'No description yet.'}</p>
            `;
            return card;    
        }

        const courtIcon = L.icon({
            iconUrl: '/images/basketball-marker.png',
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });

        function loadCourts() {
            fetch('/courts')
                .then(response => {
                    if (!response.ok) throw new Error('Server error: ' + response.status);
                    return response.json();
                })
                .then(courts => {
                    map.eachLayer(layer => {
                        if (layer instanceof L.Marker) {
                            map.removeLayer(layer);
                        }
                    });

                    courts.forEach(court => {
                        const marker = L.marker(
                            [court.latitude, court.longitude],
                            { icon: courtIcon }
                        ).addTo(map);

                        marker.on('click', (event) => {
                            L.DomEvent.stopPropagation(event);
                            showSidebar('Court details', buildCourtCard(court));
                        });
                    });
                })
                .catch(error => {
                    console.error('Error loading courts:', error);
                    showSidebar('Error', '<p>Could not load courts.</p>');
                });
        }

        map.on('click', function (event) {
            if (event.originalEvent && event.originalEvent.target && event.originalEvent.target.closest && event.originalEvent.target.closest('.leaflet-marker-icon')) {
                return;
            }

            const latitude = event.latlng.lat;
            const longitude = event.latlng.lng;
            const marker = L.marker(
                [latitude, longitude],
                { icon: courtIcon }
            ).addTo(map);

            const popupContent = document.createElement('div');
            popupContent.innerHTML = 'Save this court?<br>';

            const yesLink = document.createElement('a');
            yesLink.href = '#';
            yesLink.textContent = 'Yes';
            yesLink.onclick = function (e) {
                e.preventDefault();
                fetch('/courts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ latitude, longitude })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        marker.closePopup();
                        closeSidebar();
                        loadCourts();
                    }
                })
                .catch(error => console.error('Error saving court:', error));
                return false;
            };

            const noLink = document.createElement('a');
            noLink.href = '#';
            noLink.textContent = 'No';
            noLink.onclick = function (e) {
                e.preventDefault();
                map.removeLayer(marker);
                return false;
            };

            popupContent.appendChild(yesLink);
            popupContent.appendChild(document.createElement('br'));
            popupContent.appendChild(noLink);

            marker.bindPopup(popupContent).openPopup();
        });

        loadCourts();
    </script>
</x-layout>

