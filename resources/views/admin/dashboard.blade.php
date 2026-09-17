<x-layout>
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <div class="admin-header-kicker">Management</div>
                <h1 class="admin-title">Admin Panel</h1>
            </div>
            <a href="/map" class="admin-link-button">
                View courts map
            </a>
        </div>

        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <div class="admin-stat-label">Users</div>
                <div class="admin-stat-value">{{ $users->count() }}</div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-label">Courts</div>
                <div class="admin-stat-value">{{ $courts->count() }}</div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-label">Court Reviews</div>
                <div class="admin-stat-value">{{ $courtReviews->count() }}</div>
            </div>
        </div>

        <div class="admin-sections">
            <section class="admin-panel">
                <div class="admin-panel-header">Users</div>
                <div class="court-search-wrapper">
                    <span class="filter-icon" aria-hidden="true">🔎︎</span>
                    <input
                        class="admin-search-input"
                        data-target=".admin-table-users"
                        type="text"
                        placeholder="Search users..."
                        aria-label="Search users"
                    >
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table admin-table-users">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Courts</th>
                                <th>Reviews</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->is_admin ? 'Admin' : 'User' }}</td>
                                    <td>{{ $user->courts_count }}</td>
                                    <td>{{ $user->reviews_count }}</td>
                                    <td>{{ $user->created_at?->format('M d, Y') ?? '—' }}</td>
                                    <td>
                                        <button
                                            type="button"
                                            class="delete-user-btn"
                                            data-user-id="{{ $user->id }}"
                                        >
                                            Delete User
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="admin-empty-state">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">Courts</div>
                <div class="court-search-wrapper">
                    <span class="filter-icon" aria-hidden="true">🔎︎</span>
                    <input
                        class="admin-search-input"
                        data-target=".admin-table-courts"
                        type="text"
                        placeholder="Search courts..."
                        aria-label="Search courts"
                    >
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table admin-table-courts">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>City</th>
                                <th>State</th>
                                <th>Added by</th>
                                <th>Rating</th>
                                <th>Reviews</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($courts as $court)
                                <tr>
                                    <td>{{ $court->id }}</td>
                                    <td>{{ $court->name ?: 'Untitled court' }}</td>
                                    <td>{{ $court->city ?: '—' }}</td>
                                    <td>{{ $court->state ?: '—' }}</td>
                                    <td>{{ $court->user?->username ?? 'Unknown user' }}</td>
                                    <td>{{ $court->rating ?: 'No rating' }}</td>
                                    <td>{{ $court->reviews_count }}</td>
                                    <td>{{ $court->created_at?->format('M d, Y') ?? '—' }}</td>
                                    <td>
                                        <button
                                            type="button"
                                            class="edit-court-btn"
                                            data-court-id="{{ $court->id }}"
                                            data-name="{{ $court->name ?? '' }}"
                                            data-address="{{ $court->address ?? '' }}"
                                            data-city="{{ $court->city ?? '' }}"
                                            data-state="{{ $court->state ?? '' }}"
                                            data-description="{{ $court->description ?? '' }}"
                                        >
                                            Edit
                                        </button>
                                        <button type="button" class="delete-court-btn" data-court-id="{{ $court->id }}">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="admin-empty-state">No courts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">Court Reviews</div>
                <div class="court-search-wrapper">
                    <span class="filter-icon" aria-hidden="true">🔎︎</span>
                    <input
                        class="admin-search-input"
                        data-target=".admin-table-reviews"
                        type="text"
                        placeholder="Search court reviews..."
                        aria-label="Search court reviews"
                    >
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table admin-table-reviews">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Court</th>
                                <th>Reviewer</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($courtReviews as $review)
                                <tr>
                                    <td>{{ $review->id }}</td>
                                    <td>{{ $review->court?->name ?? 'Unknown court' }}</td>
                                    <td>{{ $review->user?->username ?? $review->username ?? 'Unknown user' }}</td>
                                    <td>{{ $review->rating ? $review->rating . '/5' : 'No rating' }}</td>
                                    <td class="admin-review-comment">{{ $review->comment ?: '—' }}</td>
                                    <td>{{ $review->created_at?->format('M d, Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="admin-empty-state">No court reviews found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <div id="adminCourtEditModal" class="admin-modal hidden" aria-hidden="true">
        <div class="admin-modal-backdrop" data-close="true"></div>
        <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="adminCourtEditTitle">
            <div class="admin-modal-header">
                <h2 id="adminCourtEditTitle">Edit court</h2>
                <button type="button" class="admin-modal-close" data-close="true" aria-label="Close">×</button>
            </div>

            <form id="adminCourtEditForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" id="adminCourtEditId" name="court_id">

                <div class="admin-form-grid">
                    <div class="admin-form-field">
                        <label for="adminCourtEditName">Name</label>
                        <input type="text" id="adminCourtEditName" name="name">
                    </div>

                    <div class="admin-form-field">
                        <label for="adminCourtEditAddress">Address</label>
                        <input type="text" id="adminCourtEditAddress" name="address">
                    </div>

                    <div class="admin-form-field">
                        <label for="adminCourtEditCity">City</label>
                        <input type="text" id="adminCourtEditCity" name="city">
                    </div>

                    <div class="admin-form-field">
                        <label for="adminCourtEditState">State</label>
                        <input type="text" id="adminCourtEditState" name="state">
                    </div>

                    <div class="admin-form-field admin-form-field-full">
                        <label for="adminCourtEditDescription">Description</label>
                        <textarea id="adminCourtEditDescription" name="description" rows="4"></textarea>
                    </div>

                    <div class="admin-form-field admin-form-field-full">
                        <label for="adminCourtEditPhoto">Photo</label>
                        <input type="file" id="adminCourtEditPhoto" name="photo" accept="image/*">
                    </div>
                </div>

                <div class="admin-modal-actions">
                    <button type="button" class="admin-secondary-btn" data-close="true">Cancel</button>
                    <button type="submit" class="admin-primary-btn">Save changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const modal = document.getElementById('adminCourtEditModal');
            const form = document.getElementById('adminCourtEditForm');
            const courtIdInput = document.getElementById('adminCourtEditId');
            const nameInput = document.getElementById('adminCourtEditName');
            const addressInput = document.getElementById('adminCourtEditAddress');
            const cityInput = document.getElementById('adminCourtEditCity');
            const stateInput = document.getElementById('adminCourtEditState');
            const descriptionInput = document.getElementById('adminCourtEditDescription');
            const photoInput = document.getElementById('adminCourtEditPhoto');

            function closeModal() {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                form.reset();
                photoInput.value = '';
            }

            function openModal(court) {
                courtIdInput.value = court.id;
                nameInput.value = court.name || '';
                addressInput.value = court.address || '';
                cityInput.value = court.city || '';
                stateInput.value = court.state || '';
                descriptionInput.value = court.description || '';
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
            }

            document.querySelectorAll('.admin-search-input').forEach((input) => {
                input.addEventListener('input', function () {
                    const table = document.querySelector(this.dataset.target);
                    if (!table) {
                        return;
                    }

                    const term = this.value.trim().toLowerCase();
                    const rows = table.querySelectorAll('tbody tr');

                    rows.forEach((row) => {
                        const rowText = row.textContent.toLowerCase();
                        row.style.display = term === '' || rowText.includes(term) ? '' : 'none';
                    });
                });
            });

            document.querySelectorAll('.edit-court-btn').forEach((button) => {
                button.addEventListener('click', function () {
                    openModal({
                        id: this.dataset.courtId,
                        name: this.dataset.name,
                        address: this.dataset.address,
                        city: this.dataset.city,
                        state: this.dataset.state,
                        description: this.dataset.description
                    });
                });
            });

            document.querySelectorAll('.delete-user-btn').forEach((button) => {
                button.addEventListener('click', function () {
                    const userId = this.dataset.userId;

                    if (!confirm('Delete this user?')) {
                        return;
                    }

                    fetch(`/admin/users/${userId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(data.message || 'Server error: ' + response.status);
                        }

                        return data;
                    })
                    .then((data) => {
                        if (data.success) {
                            window.location.reload();
                        }
                    })
                    .catch((error) => {
                        console.error('Error deleting user:', error);
                        alert(error.message || 'Failed to delete user.');
                    });
                });
            });

            document.querySelectorAll('.delete-court-btn').forEach((button) => {
                button.addEventListener('click', function () {
                    const courtId = this.dataset.courtId;

                    if (!confirm('Delete this court?')) {
                        return;
                    }

                    fetch(`/admin/courts/${courtId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Server error: ' + response.status);
                        }

                        return response.json();
                    })
                    .then((data) => {
                        if (data.success) {
                            window.location.reload();
                        }
                    })
                    .catch((error) => {
                        console.error('Error deleting court:', error);
                        alert('Failed to delete court.');
                    });
                });
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const courtId = courtIdInput.value;
                const formData = new FormData(form);

                fetch(`/admin/courts/${courtId}`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Server error: ' + response.status);
                    }

                    return response.json();
                })
                .then((data) => {
                    if (data.success) {
                        closeModal();
                        window.location.reload();
                    }
                })
                .catch((error) => {
                    console.error('Error updating court:', error);
                    alert('Failed to update court.');
                });
            });

            document.querySelectorAll('[data-close="true"]').forEach((element) => {
                element.addEventListener('click', closeModal);
            });
        })();
    </script>
</x-layout>