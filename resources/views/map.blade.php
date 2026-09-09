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
        const canAddCourt = @json(auth()->check());
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const mapBounds = L.latLngBounds(
            [55.65, 20.65],
            [58.10, 28.25]
        );

        const map = L.map('map', {
            zoomControl: false,
            maxBounds: mapBounds,
            maxBoundsViscosity: 1.0,
            minZoom: 7,
            maxZoom: 19
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

        function resolvePhotoUrl(photo) {
            if (!photo) return '';
            if (photo.startsWith('http://') || photo.startsWith('https://') || photo.startsWith('data:') || photo.startsWith('/')) {
                return photo;
            }

            return `/storage/${photo}`;
        }

        function bindImagePreview(form, inputSelector, previewSelector) {
            const input = form.querySelector(inputSelector);
            const preview = form.querySelector(previewSelector);

            if (!input || !preview) {
                return;
            }

            input.addEventListener('change', function () {
                const file = this.files && this.files[0];

                if (!file) {
                    preview.style.display = 'none';
                    preview.removeAttribute('src');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });
        }

        function buildCourtForm(latitude, longitude) {
        const form = document.createElement('form');

        form.innerHTML = `
                @csrf
                <div class="form-group">
                    <label for="courtName">Court name</label>
                    <input type="text" id="courtName" name="name" required>
                </div>

                <div class="form-group">
                    <label for="courtAddress">Address</label>
                    <input type="text" id="courtAddress" name="address">
                </div>

                <div class="form-group">
                    <label for="courtCity">City</label>
                    <input type="text" id="courtCity" name="city">
                </div>

                <div class="form-group">
                    <label for="courtState">State</label>
                    <input type="text" id="courtState" name="state">
                </div>

                <div class="form-group">
                    <label for="courtPhoto">Photo</label>
                    <input type="file" id="courtPhoto" name="photo" accept="image/jpeg, image/png, image/jpg">
                </div>

                <div class="form-group">
                    <label for="courtPhotoPreview">Preview</label>
                    <img id="courtPhotoPreview" alt="Court photo preview" style="display:none; max-width:100%; border-radius:12px; border:1px solid #d7ddd8; margin-top:4px;">
                </div>

                <div class="form-group">
                    <label for="courtDescription">Description</label>
                    <textarea id="courtDescription" name="description"></textarea>
                </div>

                <p>
                    <strong>Latitude:</strong> ${latitude}
                </p>

                <p>
                    <strong>Longitude:</strong> ${longitude}
                </p>

                <button type="submit">Save court</button>
            `;

            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const name = form.querySelector('#courtName').value;
                const address = form.querySelector('#courtAddress').value;
                const city = form.querySelector('#courtCity').value;
                const state = form.querySelector('#courtState').value;
                const description = form.querySelector('#courtDescription').value;
                const photoInput = form.querySelector('#courtPhoto');

                const formData = new FormData();
                formData.append('name', name || '');
                formData.append('address', address || '');
                formData.append('city', city || '');
                formData.append('state', state || '');
                formData.append('description', description || '');
                formData.append('latitude', String(latitude));
                formData.append('longitude', String(longitude));

                if (photoInput && photoInput.files && photoInput.files.length > 0) {
                    formData.append('photo', photoInput.files[0]);
                }

                fetch('/courts', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Server error: ' + response.status);
                    }

                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        closeSidebar();
                        loadCourts();
                    }
                })
                .catch(error => {
                    console.error('Error saving court:', error);
                });
            });

            bindImagePreview(form, '#courtPhoto', '#courtPhotoPreview');

            return form;
        }

        function buildReviewForm(courtId) {
            const form = document.createElement('form');
            form.innerHTML = `
                @csrf
                <div class="form-group">
                    <label for="reviewRating">Rating (1-5)</label>
                    <select id="reviewRating" name="rating">
                        <option value="">No rating</option>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Good</option>
                        <option value="3">3 - Average</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Very poor</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reviewPhoto">Photo</label>
                    <input type="file" id="reviewPhoto" name="photo" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="reviewPhotoPreview">Preview</label>
                    <img id="reviewPhotoPreview" alt="Review photo preview" style="display:none; max-width:100%; border-radius:12px; border:1px solid #d7ddd8; margin-top:4px;">
                </div>

                <div class="form-group">
                    <label for="reviewComment">Comment</label>
                    <textarea id="reviewComment" name="comment" placeholder="Share your experience..."></textarea>
                </div>

                <button type="submit">Post review</button>
            `;

            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const rating = form.querySelector('#reviewRating').value;
                const photoInput = form.querySelector('#reviewPhoto');
                const comment = form.querySelector('#reviewComment').value;

                const formData = new FormData();

                formData.append('rating', rating || '');
                formData.append('comment', comment || '');

                if (photoInput && photoInput.files && photoInput.files.length > 0) {
                    formData.append('photo', photoInput.files[0]);
                }

                fetch(`/courts/${courtId}/reviews`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Server error: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        loadCourts();
                        showSidebar('Court details', buildCourtCard(data.court));
                    }
                })
                .catch(error => {
                    console.error('Error posting review:', error);
                });
            });

            bindImagePreview(form, '#reviewPhoto', '#reviewPhotoPreview');

            return form;
        }

        function buildCourtCard(court) {
            const card = document.createElement('div');
            card.className = 'court-card';

            const reviews = Array.isArray(court.reviews) ? court.reviews : [];
            const totalReviews = reviews.length;
            const averageRating = court.avg_rating ?? (reviews.length ? (reviews.reduce((sum, review) => sum + Number(review.rating || 0), 0) / reviews.length) : 0);

            let reviewMarkup = '';
            if (reviews.length === 0) {
                reviewMarkup = '<p>No comments yet.</p>';
            } else {
                reviewMarkup = reviews.map(review => `
                    <div class="review-item">
                        <strong>${review.username || 'Anonymous'}</strong>
                        <div>⭐ ${review.rating || 'No rating'}</div>
                        ${review.photo ? `<img src="${resolvePhotoUrl(review.photo)}" alt="Court review photo" style="max-width:100%; margin-top:8px; border-radius:8px;">` : ''}
                        <p>${review.comment || 'No comment provided.'}</p>
                    </div>
                `).join('');
            }

            card.innerHTML = `
                <h3>${court.name || 'Untitled Court'}</h3>
                ${court.photo ? `<img src="${resolvePhotoUrl(court.photo)}" alt="Court photo" style="max-width:100%; border-radius:8px; margin-bottom:12px;">` : ''}
                <p class="court-address"><strong>📍Address:</strong> ${court.address || 'Not added'}</p>
                <p class="court-city"><strong>🏙️City:</strong> ${court.city || 'Not added'}</p>
                <p class="court-coordinates"><strong>Coordinates:</strong> ${court.latitude}, ${court.longitude}</p>
                <p class="court-rating"><strong>⭐Average rating:</strong> ${averageRating ? averageRating.toFixed(1) : 'No rating'}${averageRating ? ` (${totalReviews} review${totalReviews === 1 ? '' : 's'})` : ''}</p>
                <div class="court-actions">
                    <button type="button" class="reaction-btn" data-court-id="${court.id}" data-reaction="like">👍 Like (${court.likes || 0})</button>
                    <button type="button" class="reaction-btn" data-court-id="${court.id}" data-reaction="dislike">👎 Dislike (${court.dislikes || 0})</button>
                </div>
                <p class="court-description"><strong>📝Description:</strong> ${court.description || 'No description yet.'}</p>
                <div class="court-comments-header"><strong>💬Comments:</strong></div>
                <div class="court-comments">${reviewMarkup}</div>
                <div class="court-review-form-wrap"></div>
            `;

            const reviewFormWrap = card.querySelector('.court-review-form-wrap');
            const reactionButtons = card.querySelectorAll('.reaction-btn');
            reactionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (!canAddCourt) {
                        alert('Please log in or register to react to a court.');
                        return;
                    }
                    const reaction = button.dataset.reaction;
                    fetch(`/courts/${court.id}/react`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ reaction })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Server error: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(() => {
                        loadCourts();
                        showSidebar('Court details', buildCourtCard(court));
                    })
                    .catch(error => {
                        console.error('Error reacting:', error);
                    });
                });
            });

            if (canAddCourt) {
                reviewFormWrap.appendChild(buildReviewForm(court.id));
            }

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
            if (!canAddCourt) {
                return;
            }
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

                marker.closePopup();

                showSidebar(
                    'Add basketball court',
                    buildCourtForm(latitude, longitude)
                );

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