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

        {{-- Rating filter: only show courts rated at least this high --}}
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

        {{-- Sort dropdown: re-orders the filtered courts --}}
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
        const currentUserId = @json(auth()->id());
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const mapBounds = L.latLngBounds(
            [55.60, 20.50],
            [58.10, 28.30]
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

        const courtSearch = document.getElementById('courtSearch');
        const ratingFilter = document.getElementById('ratingFilter');
        const sortFilter = document.getElementById('sortFilter');
        const requestedCourtId = new URLSearchParams(window.location.search).get('court');
        let allCourts = [];
        let currentSearchTerm = '';
        let currentRatingFilter = 'all';
        let currentSort = 'default'; // active sort option

        function getCourtRating(court) {
            const rawRating = Number(court.avg_rating ?? court.rating ?? 0);
            return Number.isFinite(rawRating) ? rawRating : 0;
        }

        function applyCourtFilters() {
            const term = currentSearchTerm.trim().toLowerCase();

            const filteredCourts = allCourts.filter(court => {
                const searchable = [
                    court.name,
                    court.address,
                    court.city,
                    court.state,
                    court.description
                ].filter(Boolean).join(' ').toLowerCase();

                const matchesSearch = term === '' || searchable.includes(term);
                const matchesRating = currentRatingFilter === 'all' || getCourtRating(court) >= Number(currentRatingFilter);

                return matchesSearch && matchesRating;
            });

            sortCourts(filteredCourts);
            renderCourts(filteredCourts);
        }

        // Orders the court list based on the selected sort option
        function sortCourts(courts) {
            if (currentSort === 'rating') {
                courts.sort((a, b) => getCourtRating(b) - getCourtRating(a));
            } else if (currentSort === 'likes') {
                courts.sort((a, b) => Number(b.likes || 0) - Number(a.likes || 0));
            } else if (currentSort === 'newest') {
                // 'created_at' comes from Laravel as an ISO date string -
                // string comparison works for ISO dates
                courts.sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''));
            }
            // 'default' = leave the order as the server sent it
        }

        ratingFilter.addEventListener('change', function () {
            currentRatingFilter = this.value;
            applyCourtFilters();
        });

        sortFilter.addEventListener('change', function () {
            currentSort = this.value;
            applyCourtFilters();
        });

        courtSearch.addEventListener('input', function () {
            currentSearchTerm = this.value;
            applyCourtFilters();
        });

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
            if (photo.startsWith('http://') || photo.startsWith('https://') || photo.startsWith('/')) {
                return photo;
            }

            return `/storage/${photo}`;
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
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
                    <input type="text" id="courtAddress" name="address" required>
                </div>

                <div class="form-group">
                    <label for="courtCity">City</label>
                    <input type="text" id="courtCity" name="city" required>
                </div>

                <div class="form-group">
                    <label for="courtState">State</label>
                    <input type="text" id="courtState" name="state" required>
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
                <label>Rating</label>

                <div class="rating-stars">
                    <span data-rating="1">★</span>
                    <span data-rating="2">★</span>
                    <span data-rating="3">★</span>
                    <span data-rating="4">★</span>
                    <span data-rating="5">★</span>
                </div>

                <input type="hidden" id="courtRatingInput" name="rating" value="">
                </div>  

                <div class="form-group">
                    <label for="courtDescription">Description</label>
                    <textarea id="courtDescription" name="description" required ></textarea>
                </div>

                <p>
                    <strong>Latitude:</strong> ${latitude}
                </p>

                <p>
                    <strong>Longitude:</strong> ${longitude}
                </p>

                <button type="submit">Save court</button>
            `;

            const stars = form.querySelectorAll('.rating-stars span');
            const ratingInput = form.querySelector('#courtRatingInput');

            stars.forEach(star => {
                star.addEventListener('click', function () {
                    const rating = this.dataset.rating;

                    ratingInput.value = rating;

                    stars.forEach(s => {
                        s.classList.toggle(
                            'selected',
                            Number(s.dataset.rating) <= Number(rating)
                        );
                    });
                });
            });

            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const name = form.querySelector('#courtName').value;
                const address = form.querySelector('#courtAddress').value;
                const city = form.querySelector('#courtCity').value;
                const state = form.querySelector('#courtState').value;
                const description = form.querySelector('#courtDescription').value;
                const photoInput = form.querySelector('#courtPhoto');
                const rating = ratingInput.value;

                if (!rating) {
                    alert('Please choose a rating before saving the court.');
                    return;
                }

                const formData = new FormData();
                formData.append('name', name || '');
                formData.append('address', address || '');
                formData.append('city', city || '');
                formData.append('state', state || '');
                formData.append('description', description || '');
                formData.append('rating', rating || '');
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

        function editCourt(court){
            const form = document.createElement('form');
            form.innerHTML = `
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="editCourtName">Court name</label>
                    <input type="text" id="editCourtName" name="name" required>
                </div>

                <div class="form-group">
                    <label for="editCourtAddress">Address</label>
                    <input type="text" id="editCourtAddress" name="address" required>
                </div>

                <div class="form-group">
                    <label for="editCourtCity">City</label>
                    <input type="text" id="editCourtCity" name="city" required>
                    
                </div>

                <div class="form-group">
                    <label for="editCourtState">State</label>
                    <input type="text" id="editCourtState" name="state" required>
                </div>

                <div class="form-group">
                    <label for="courtPhoto">Photo</label>
                    <input type="file" id="courtPhoto" name="photo" accept="image/jpeg, image/png, image/jpg">
                </div>

                <div class="form-group">
                    <label for="editCourtDescription">Description</label>
                    <textarea id="editCourtDescription" name="description" required></textarea>
                </div>

                <button type="submit">Update court</button>
            `;

            form.querySelector('#editCourtName').value = court.name || '';
            form.querySelector('#editCourtAddress').value = court.address || '';
            form.querySelector('#editCourtCity').value = court.city || '';
            form.querySelector('#editCourtState').value = court.state || '';
            form.querySelector('#editCourtDescription').value = court.description || '';

            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const name = form.querySelector('#editCourtName').value;
                const address = form.querySelector('#editCourtAddress').value;
                const city = form.querySelector('#editCourtCity').value;
                const state = form.querySelector('#editCourtState').value;
                const description = form.querySelector('#editCourtDescription').value;
                const photoInput = form.querySelector('#courtPhoto');


                const formData = new FormData();
                formData.append('name', name || '');
                formData.append('address', address || '');
                formData.append('city', city || '');
                formData.append('state', state || '');
                formData.append('description', description || '');

                if (photoInput && photoInput.files && photoInput.files.length > 0) {
                    formData.append('photo', photoInput.files[0]);
                }

                fetch(`/admin/courts/${court.id}`, {
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
                    console.error('Error updating court:', error);
                });
            });

            return form;
        }

        function buildReviewForm(courtId, existingReview = null) {
            const form = document.createElement('form');
            const isEditing = Boolean(existingReview);
            form.innerHTML = `
                @csrf
                <div class="form-group">
                <label>Rating</label>

                <div class="rating-stars">
                    <span data-rating="1">★</span>
                    <span data-rating="2">★</span>
                    <span data-rating="3">★</span>
                    <span data-rating="4">★</span>
                    <span data-rating="5">★</span>
                </div>

                <input type="hidden" id="reviewRatingInput" name="rating" value="">
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

                <button type="submit">${isEditing ? 'Save review' : 'Post review'}</button>
            `;

            if (isEditing) {
                form.querySelector('#reviewRatingInput').value = existingReview.rating || '';
                form.querySelector('#reviewComment').value = existingReview.comment || '';
                form.querySelectorAll('.rating-stars span').forEach(star => {
                    star.classList.toggle('selected', Number(star.dataset.rating) <= Number(existingReview.rating || 0));
                });
            }

            const stars = form.querySelectorAll('.rating-stars span');
            const ratingInput = form.querySelector('#reviewRatingInput');

            stars.forEach(star => {
                star.addEventListener('click', function () {
                    const rating = this.dataset.rating;

                    ratingInput.value = rating;

                    stars.forEach(s => {
                        s.classList.toggle(
                            'selected',
                            Number(s.dataset.rating) <= Number(rating)
                        );
                    });
                });
            });

            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const photoInput = form.querySelector('#reviewPhoto');
                const comment = form.querySelector('#reviewComment').value;
                const rating = ratingInput.value;

                if (!rating) {
                    alert('Please choose a rating before posting your review.');
                    return;
                }

                const formData = new FormData();

                formData.append('rating', rating || '');
                formData.append('comment', comment || '');

                if (photoInput && photoInput.files && photoInput.files.length > 0) {
                    formData.append('photo', photoInput.files[0]);
                }

                fetch(isEditing ? `/courts/${courtId}/reviews/${existingReview.id}` : `/courts/${courtId}/reviews`, {
                    method: isEditing ? 'PUT' : 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    return response.json().catch(() => ({})).then(data => {
                        if (!response.ok) {
                            throw new Error(data.message || 'Could not save the review.');
                        }

                        return data;
                    });
                })
                .then(data => {
                    if (data.success) {
                        loadCourts();

                        fetch(`/courts/${courtId}/reviews`)
                            .then(reviewResponse => {
                                if (!reviewResponse.ok) {
                                    throw new Error('Server error: ' + reviewResponse.status);
                                }

                                return reviewResponse.json();
                            })
                            .then(reviewData => {
                                const refreshedCourt = {
                                    ...reviewData.court,
                                    reviews: reviewData.reviews || [],
                                    avg_rating: reviewData.court?.rating ?? 0
                                };

                                showSidebar('Court details', buildCourtCard(refreshedCourt));
                            })
                            .catch(error => {
                                console.error('Error refreshing court details:', error);
                            });
                    }
                })
                .catch(error => {
                    alert(error.message);
                    console.error('Error posting review:', error);
                });
            });

            bindImagePreview(form, '#reviewPhoto', '#reviewPhotoPreview');

            return form;
        }

        function buildReportCard(court, report = {}) {
            const form = document.createElement('form');

            form.innerHTML = `
                <div class="report-card-info"> 
                    <div class="report-card-header">
                        <p>What do you want to report about ${escapeHtml(court.name || 'this court')}?</p>
                    </div>
                    <div>
                        <label for="reportReason">Reason</label>
                        <select id="reportReason" name="reportReason">
                            <option value="closed">Closed</option>
                            <option value="duplicate">Duplicate</option>
                            <option value="unsafe">Unsafe</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="reportComment">Comment</label>
                        <textarea id="reportComment" name="reportComment" placeholder="Tell us more (optional)"></textarea>
                    </div>
                    <button type="submit">Report</button>
                </div>
            `;

            form.querySelector('#reportReason').value = report.reason || '';
            form.querySelector('#reportComment').value = report.comment || '';

            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const reason = form.querySelector('#reportReason').value;
                const comment = form.querySelector('#reportComment').value;

                if (!reason) {
                    alert('Please choose a reason before reporting the court.');
                    return;
                }

                const formData = new FormData();
                formData.append('reportReason', reason);
                formData.append('reportComment', comment);

                fetch(`/courts/${court.id}/report`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    return response.json().catch(() => ({})).then(data => {
                        if (!response.ok) {
                            throw new Error(data.message || 'Could not report the court.');
                        }
                        return data;
                    });
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Thank you! The report was sent to the admins.');
                        closeSidebar();
                    }
                })
                .catch(error => {
                    alert(error.message);
                    console.error('Error reporting court:', error);
                });
            });

            return form;
        }

        function buildCourtCard(court) {
            const card = document.createElement('div');
            card.className = 'court-card';

            const reviews = Array.isArray(court.reviews) ? court.reviews : [];
            const ownReview = reviews.find(review =>
                review.is_owner || (currentUserId && Number(review.user_id) === Number(currentUserId))
            );
            const totalReviews = reviews.length;
            const calculatedAverage = reviews.length ? (reviews.reduce((sum, review) => sum + Number(review.rating || 0), 0) / reviews.length) : 0;
            const averageRating = Number(court.avg_rating ?? calculatedAverage ?? 0);

            let reviewMarkup = '';
            if (reviews.length === 0) {
                reviewMarkup = '<p>No comments yet.</p>';
            } else {
                reviewMarkup = reviews.map(review => `
                    <div class="review-item">
                        <strong>${escapeHtml(review.username)}</strong>
                        <div>⭐ ${escapeHtml(review.rating || 'No rating')}</div>
                        ${review.photo ? `<img src="${escapeHtml(resolvePhotoUrl(review.photo))}" alt="Court review photo" style="max-width:100%; margin-top:8px; border-radius:8px;">` : ''}
                        <p>${escapeHtml(review.comment || 'No comment provided.')} </p>
                        ${(review.is_owner || (currentUserId && Number(review.user_id) === Number(currentUserId))) ? `<button type="button" class="edit-review-btn" data-review-id="${review.id}">Edit review</button><button type="button" class="delete-review-btn" data-review-id="${review.id}">Delete review</button>` : ''}
                    </div>
                `).join('');
            }

            card.innerHTML = `
                <button type="button" class="report-court-btn" data-court-id="${court.id}">🚩Report court</button>
                <h3>${escapeHtml(court.name || 'Untitled Court')}</h3>
                ${court.photo ? `<img src="${escapeHtml(resolvePhotoUrl(court.photo))}" alt="Court photo" class="court-photo">` : ''}
                <p class="court-address"><strong>📍Address:</strong> ${escapeHtml(court.address || 'Not added')}</p>
                <p class="court-city"><strong>🏙️City:</strong> ${escapeHtml(court.city || 'Not added')}</p>
                <p class="court-coordinates"><strong>🧭Coordinates:</strong> ${escapeHtml(`${court.latitude}, ${court.longitude}`)}</p>
                <p class="court-rating"><strong>⭐Average rating:</strong> ${averageRating > 0 ? averageRating.toFixed(1) : 'No rating'}</p>
                <p class="when-added"><strong>Added:</strong> ${court.created_at ? new Date(court.created_at).toLocaleString() : 'Unknown'}</p>
                <a href="/courts/view/${court.id}" class="view-court-btn">Skatīt detalizētāk</a>
                <div class="court-actions">
                    <button type="button" class="reaction-btn" data-court-id="${court.id}" data-reaction="like">👍 Like (${court.likes || 0})</button>
                    <button type="button" class="reaction-btn" data-court-id="${court.id}" data-reaction="dislike">👎 Dislike (${court.dislikes || 0})</button>
                    <button type="button" class="court-save-btn" data-court-id="${court.id}" data-saved="${court.is_saved ? 'true' : 'false'}">💾 ${court.is_saved ? 'Saved' : 'Save court'}</button>
                </div>
                <p class="court-description"><strong>📝Description:</strong> ${escapeHtml(court.description || 'No description yet.')}</p>
                <div class="court-comments-header"><strong>💬Comments:</strong><p>${averageRating > 0 ? ` (${totalReviews} review${totalReviews === 1 ? '' : 's'})` : ''}</p></div>
                <div class="court-comments">${reviewMarkup}</div>
                <div class="court-review-form-wrap"></div>
                @if(!auth()->check())
                    <p class="sidebar-login-prompt">Please log in or register to post a review and to react.</p>
                @endif
                @if(auth()->check() && auth()->user()->is_admin == 1)
                    <button type="button" class="edit-court-btn">Edit court</button>
                    <button type="button" class="delete-court-btn" data-court-id="${court.id}">Delete court</button>
                @endif
            `;
            
            const reportButton = card.querySelector('.report-court-btn');
            const reviewFormWrap = card.querySelector('.court-review-form-wrap');
            const reactionButtons = card.querySelectorAll('.reaction-btn');
            const saveButtons = card.querySelectorAll('.court-save-btn');
            const editReviewButtons = card.querySelectorAll('.edit-review-btn');
            const deleteReviewButtons = card.querySelectorAll('.delete-review-btn');
            const editButton = card.querySelector('.edit-court-btn');
            const deleteButton = card.querySelector('.delete-court-btn');

            if(reportButton) {
                reportButton.addEventListener('click', function() {
                    if (!canAddCourt) {
                        alert('Please log in or register to report a court.');
                        return;
                    }

                    showSidebar('Report court', buildReportCard(court));
                });
            }

            editReviewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const review = reviews.find(item => String(item.id) === button.dataset.reviewId);

                    if (review) {
                        showSidebar('Edit review', buildReviewForm(court.id, review));
                    }
                });
            });

            deleteReviewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const review = reviews.find(item => String(item.id) === button.dataset.reviewId);

                    if (!review) {
                        return;
                    }

                    if (!confirm('Delete this review?')) {
                        return;
                    }

                    fetch(`/courts/${court.id}/reviews/${review.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
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
                                fetch(`/courts/${court.id}/reviews`)
                                    .then(reviewResponse => reviewResponse.json())
                                    .then(reviewData => showSidebar('Court details', buildCourtCard({
                                        ...reviewData.court,
                                        reviews: reviewData.reviews || [],
                                        avg_rating: reviewData.court?.rating ?? 0
                                    })));
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting review:', error);
                        });
                });
            });

            if (editButton) {
                editButton.addEventListener('click', function() {
                    showSidebar('Edit court', editCourt(court));
                });
            }

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

            saveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (!canAddCourt) {
                        alert('Please log in or register to save a court.');
                        return;
                    }

                    button.disabled = true;

                    fetch(`/courts/${court.id}/save`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Server error: ' + response.status);
                            }

                            return response.json();
                        })
                        .then(data => {
                            const isSaved = Boolean(data.is_saved);
                            button.dataset.saved = isSaved ? 'true' : 'false';
                            button.textContent = isSaved ? '💾 Saved' : '💾 Save court';
                        })
                        .catch(error => {
                            console.error('Error saving court:', error);
                        })
                        .finally(() => {
                            button.disabled = false;
                        });
                });
            });

            if (deleteButton) {
            deleteButton.addEventListener('click', function () {
                if (!confirm('Delete this court?')) {
                    return;
                }

            fetch(`/admin/courts/${court.id}`, {
                method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
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
                        console.error('Error deleting court:', error);
                    });
                });
            }

            if (canAddCourt && !ownReview) {
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
                    allCourts = courts;
                    applyCourtFilters();
                })
                .catch(error => {
                    console.error('Error loading courts:', error);
                });
        }

        function renderCourts(courts) {
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

                if (requestedCourtId && String(court.id) === requestedCourtId) {
                    map.setView([court.latitude, court.longitude], Math.max(map.getZoom(), 15));
                    showSidebar('Court details', buildCourtCard(court));
                }
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