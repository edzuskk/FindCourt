<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\CourtReaction;
use App\Models\CourtReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourtReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_court_with_a_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/courts', [
            'name' => 'Neighborhood Court',
            'address' => '1 Main St',
            'city' => 'Riga',
            'state' => 'Latvia',
            'rating' => 5,
            'description' => 'Outdoor basketball court.',
            'latitude' => 56.9496,
            'longitude' => 24.1052,
            'photo' => UploadedFile::fake()->createWithContent(
                'court.png',
                file_get_contents(public_path('images/basketball-marker.png'))
            ),
        ]);

        $response->assertOk();
        $courtPhoto = $response->json('court.photo');
        $this->assertNotEmpty($courtPhoto);
        $this->assertDatabaseHas('courts', [
            'id' => $response->json('court.id'),
            'user_id' => $user->id,
            'photo' => $courtPhoto,
        ]);
        Storage::disk('public')->assertExists($courtPhoto);
        $this->assertSame($courtPhoto, $this->getJson('/courts')->assertOk()->json('0.photo'));
    }

    public function test_court_creation_requires_name_address_city_and_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/courts', [
                'rating' => 5,
                'latitude' => 56.9496,
                'longitude' => 24.1052,
            ])
            ->assertUnprocessable()
            ->assertInvalid(['name', 'address', 'city', 'state']);

        $this->assertDatabaseCount('courts', 0);
    }

    public function test_court_creation_rejects_duplicate_coordinates(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $courtData = [
            'name' => 'Neighborhood Court',
            'address' => '1 Main St',
            'city' => 'Riga',
            'state' => 'Latvia',
            'rating' => 5,
            'description' => 'Outdoor basketball court.',
            'latitude' => 56.9496,
            'longitude' => 24.1052,
        ];

        $this->actingAs($firstUser)->postJson('/courts', $courtData)->assertOk();

        $this->actingAs($secondUser)
            ->postJson('/courts', $courtData)
            ->assertUnprocessable()
            ->assertInvalid('latitude');

        $this->assertDatabaseCount('courts', 1);
    }

    public function test_court_creation_rejects_coordinates_outside_the_latvia_envelope(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/courts', [
                'name' => 'Out of region court',
                'address' => '1 Main St',
                'city' => 'Springfield',
                'state' => 'IL',
                'description' => 'Outdoor basketball court.',
                'rating' => 5,
                'latitude' => 39.7817,
                'longitude' => -89.6501,
            ])
            ->assertUnprocessable()
            ->assertInvalid(['latitude', 'longitude']);

        $this->assertDatabaseCount('courts', 0);
    }

    public function test_court_rating_uses_creator_review_and_recalculates_after_review_changes(): void
    {
        $creator = User::factory()->create();
        $reviewer = User::factory()->create();

        $response = $this->actingAs($creator)->postJson('/courts', [
            'name' => 'Neighborhood Court',
            'address' => '1 Main St',
            'city' => 'Riga',
            'state' => 'Latvia',
            'rating' => 5,
            'description' => 'Outdoor basketball court.',
            'latitude' => 56.9496,
            'longitude' => 24.1052,
        ]);

        $response->assertOk();
        $courtId = $response->json('court.id');
        $court = Court::findOrFail($courtId);
        $creatorReview = CourtReview::where('court_id', $courtId)
            ->where('user_id', $creator->id)
            ->firstOrFail();

        $this->assertSame(5, $creatorReview->rating);
        $this->assertNull($creatorReview->comment);
        $this->assertSame(5.0, (float) $court->rating);

        $this->actingAs($reviewer)
            ->postJson("/courts/{$courtId}/reviews", [
                'rating' => 2,
                'comment' => 'Needs work',
            ])
            ->assertOk();

        $this->assertSame(3.5, (float) $court->fresh()->rating);

        $reviewerReview = CourtReview::where('court_id', $courtId)
            ->where('user_id', $reviewer->id)
            ->firstOrFail();

        $this->actingAs($reviewer)
            ->deleteJson("/courts/{$courtId}/reviews/{$reviewerReview->id}")
            ->assertOk();

        $this->assertSame(5.0, (float) $court->fresh()->rating);

        $this->actingAs($creator)
            ->deleteJson("/courts/{$courtId}/reviews/{$creatorReview->id}")
            ->assertOk();

        $this->assertSame(0.0, (float) $court->fresh()->rating);
    }

    public function test_user_can_create_a_review_for_a_court(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'username' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'Password123!',
            'is_admin' => false,
        ]);

        $court = Court::create([
            'name' => 'Central Court',
            'address' => '1 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
            'likes' => 0,
            'dislikes' => 0,
            'rating' => 0,
        ]);

        $response = $this->actingAs($user)->postJson("/courts/{$court->id}/reviews", [
            'rating' => 5,
            'comment' => 'Amazing court. Great hoops.',
            'photo' => UploadedFile::fake()->createWithContent(
                'court.png',
                file_get_contents(public_path('images/basketball-marker.png'))
            ),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('court_reviews', [
            'court_id' => $court->id,
            'user_id' => $user->id,
            'username' => 'Alice',
            'comment' => 'Amazing court. Great hoops.',
            'rating' => 5,
        ]);
        Storage::disk('public')->assertExists($response->json('review.photo'));
    }

    public function test_review_creation_rejects_a_missing_rating(): void
    {
        $user = User::factory()->create();
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 56.9496,
            'longitude' => 24.1052,
        ]);

        $this->actingAs($user)
            ->postJson("/courts/{$court->id}/reviews", ['comment' => 'No rating'])
            ->assertUnprocessable()
            ->assertInvalid('rating');

        $this->assertDatabaseCount('court_reviews', 0);
    }

    public function test_duplicate_review_rejection_does_not_store_an_uploaded_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
        ]);
        CourtReview::create([
            'court_id' => $court->id,
            'user_id' => $user->id,
            'username' => $user->username,
            'rating' => 4,
        ]);

        $this->actingAs($user)
            ->postJson("/courts/{$court->id}/reviews", [
                'rating' => 2,
                'photo' => UploadedFile::fake()->createWithContent(
                    'duplicate.png',
                    file_get_contents(public_path('images/basketball-marker.png'))
                ),
            ])
            ->assertUnprocessable()
            ->assertJson(['message' => 'You already reviewed this court.']);

        $this->assertDatabaseCount('court_reviews', 1);
        Storage::disk('public')->assertDirectoryEmpty('reviews');
    }

    public function test_reaction_counters_include_all_users_and_track_reaction_changes(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
        ]);

        $this->actingAs($firstUser)
            ->postJson("/courts/{$court->id}/react", ['reaction' => 'like'])
            ->assertOk()
            ->assertJson(['likes' => 1, 'dislikes' => 0]);

        $this->actingAs($secondUser)
            ->postJson("/courts/{$court->id}/react", ['reaction' => 'like'])
            ->assertOk()
            ->assertJson(['likes' => 2, 'dislikes' => 0]);

        $this->actingAs($firstUser)
            ->postJson("/courts/{$court->id}/react", ['reaction' => 'dislike'])
            ->assertOk()
            ->assertJson(['likes' => 1, 'dislikes' => 1]);

        $this->assertDatabaseHas('courts', [
            'id' => $court->id,
            'likes' => 1,
            'dislikes' => 1,
        ]);
    }

    public function test_user_can_edit_only_their_own_review(): void
    {
        Storage::fake('public');

        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $court = Court::create([
            'name' => 'Central Court',
            'address' => '1 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
            'likes' => 0,
            'dislikes' => 0,
            'rating' => 5,
        ]);
        $review = CourtReview::create([
            'court_id' => $court->id,
            'user_id' => $author->id,
            'username' => $author->username,
            'rating' => 5,
            'comment' => 'Original comment',
        ]);

        $this->actingAs($otherUser)
            ->putJson("/courts/{$court->id}/reviews/{$review->id}", [
                'rating' => 1,
                'comment' => 'Should not be allowed',
            ])
            ->assertForbidden();

        $this->actingAs($author)
            ->post("/courts/{$court->id}/reviews/{$review->id}", [
                '_method' => 'PUT',
                'rating' => 4,
                'comment' => 'Updated comment',
                'photo' => UploadedFile::fake()->createWithContent(
                    'review.png',
                    file_get_contents(public_path('images/basketball-marker.png'))
                ),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('court_reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => 'Updated comment',
        ]);
        Storage::disk('public')->assertExists($review->fresh()->photo);
    }

    public function test_user_can_delete_only_their_own_review(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $court = Court::create([
            'name' => 'Central Court',
            'address' => '1 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
            'likes' => 0,
            'dislikes' => 0,
            'rating' => 5,
        ]);
        $review = CourtReview::create([
            'court_id' => $court->id,
            'user_id' => $author->id,
            'username' => $author->username,
            'rating' => 5,
            'comment' => 'Original comment',
        ]);

        $this->actingAs($otherUser)
            ->deleteJson("/courts/{$court->id}/reviews/{$review->id}")
            ->assertForbidden();

        $this->actingAs($author)
            ->deleteJson("/courts/{$court->id}/reviews/{$review->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('court_reviews', ['id' => $review->id]);
        $this->assertDatabaseHas('courts', ['id' => $court->id, 'rating' => 0]);
    }

    public function test_admin_can_delete_a_court_and_its_uploaded_photos(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $court = Court::create([
            'name' => 'Community court',
            'latitude' => 40.0,
            'longitude' => -90.0,
            'photo' => 'courts/community-court.jpg',
        ]);
        $review = CourtReview::create([
            'court_id' => $court->id,
            'user_id' => User::factory()->create()->id,
            'username' => 'Reviewer',
            'rating' => 5,
            'comment' => 'Great court',
            'photo' => 'reviews/community-court-review.jpg',
        ]);

        Storage::disk('public')->put('courts/community-court.jpg', 'court photo');
        Storage::disk('public')->put('reviews/community-court-review.jpg', 'review photo');

        $this->actingAs($admin)
            ->deleteJson("/admin/courts/{$court->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('courts', ['id' => $court->id]);
        $this->assertDatabaseMissing('court_reviews', ['id' => $review->id]);
        Storage::disk('public')->assertMissing('courts/community-court.jpg');
        Storage::disk('public')->assertMissing('reviews/community-court-review.jpg');
    }

    public function test_admin_can_delete_a_user(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'username' => 'AdminUser',
            'email' => 'admin@example.com',
            'password' => 'Password123!',
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'username' => 'TargetUser',
            'email' => 'target@example.com',
            'password' => 'Password123!',
            'is_admin' => false,
        ]);
        $regularUser = User::factory()->create();

        $ownedCourt = Court::create([
            'user_id' => $user->id,
            'name' => 'Owned court',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
            'photo' => 'courts/owned-court.jpg',
        ]);
        $contributingUser = User::factory()->create();
        $ownedReview = CourtReview::create([
            'court_id' => $ownedCourt->id,
            'user_id' => $contributingUser->id,
            'username' => 'Reviewer',
            'rating' => 5,
            'comment' => 'Review on owned court',
            'photo' => 'reviews/owned-review.jpg',
        ]);
        CourtReaction::create([
            'court_id' => $ownedCourt->id,
            'user_id' => $contributingUser->id,
            'reaction' => 'like',
        ]);
        $unownedCourt = Court::create([
            'name' => 'Community court',
            'latitude' => 40.0,
            'longitude' => -90.0,
        ]);
        $retainedReview = CourtReview::create([
            'court_id' => $unownedCourt->id,
            'user_id' => $user->id,
            'username' => $user->username,
            'rating' => 4,
            'comment' => 'Review on community court',
            'photo' => 'reviews/retained-review.jpg',
        ]);

        Storage::disk('public')->put('courts/owned-court.jpg', 'court photo');
        Storage::disk('public')->put('reviews/owned-review.jpg', 'owned court review photo');
        Storage::disk('public')->put('reviews/retained-review.jpg', 'retained review photo');

        $this->actingAs($regularUser)
            ->deleteJson("/admin/users/{$user->id}")
            ->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->actingAs($admin)
            ->deleteJson("/admin/users/{$admin->id}")
            ->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $response = $this->actingAs($admin)->deleteJson("/admin/users/{$user->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
        $this->assertDatabaseHas('courts', [
            'id' => $ownedCourt->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('court_reviews', [
            'id' => $ownedReview->id,
            'court_id' => $ownedCourt->id,
            'user_id' => $contributingUser->id,
            'comment' => 'Review on owned court',
        ]);
        $this->assertDatabaseHas('court_reactions', [
            'court_id' => $ownedCourt->id,
            'user_id' => $contributingUser->id,
            'reaction' => 'like',
        ]);
        $this->assertDatabaseHas('court_reviews', [
            'id' => $retainedReview->id,
            'user_id' => null,
        ]);
        Storage::disk('public')->assertExists('courts/owned-court.jpg');
        Storage::disk('public')->assertExists('reviews/owned-review.jpg');
        Storage::disk('public')->assertExists('reviews/retained-review.jpg');
    }

    public function test_user_can_save_and_unsave_a_court(): void
    {
        $user = User::factory()->create();
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
        ]);

        $this->actingAs($user)
            ->postJson("/courts/{$court->id}/save")
            ->assertOk()
            ->assertJson(['success' => true, 'is_saved' => true]);

        $courts = collect($this->actingAs($user)->getJson('/courts')->assertOk()->json());
        $this->assertTrue($courts->firstWhere('id', $court->id)['is_saved']);

        $this->assertDatabaseHas('saved_courts', [
            'user_id' => $user->id,
            'court_id' => $court->id,
        ]);

        $this->actingAs($user)
            ->postJson("/courts/{$court->id}/save")
            ->assertOk()
            ->assertJson(['success' => true, 'is_saved' => false]);

        $courts = collect($this->actingAs($user)->getJson('/courts')->assertOk()->json());
        $this->assertFalse($courts->firstWhere('id', $court->id)['is_saved']);

        $this->assertDatabaseMissing('saved_courts', [
            'user_id' => $user->id,
            'court_id' => $court->id,
        ]);
    }

    public function test_map_page_loads_the_map_script_with_permission_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/map')
            ->assertOk()
            ->assertSee('src="'.asset('map.js').'"', false)
            ->assertSee('data-can-add-court="true"', false)
            ->assertSee('data-current-user-id="'.$admin->id.'"', false)
            ->assertSee('data-is-admin="true"', false);
    }

    public function test_guests_cannot_save_a_court(): void
    {
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
        ]);

        $this->postJson("/courts/{$court->id}/save")
            ->assertUnauthorized();
    }

    public function test_public_courts_response_does_not_expose_reviewer_private_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'reviewer@example.com',
            'is_admin' => true,
        ]);
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
        ]);
        CourtReview::create([
            'court_id' => $court->id,
            'user_id' => $user->id,
            'username' => $user->username,
            'rating' => 5,
        ]);

        $response = $this->getJson('/courts')->assertOk();
        $courtPayload = $response->json('0');
        $this->assertArrayNotHasKey('reviews', $courtPayload);
        $this->assertSame(1, $courtPayload['reviews_count']);
        $this->assertSame(5.0, (float) $courtPayload['avg_rating']);

        $reviewerPayload = $this->getJson("/courts/{$court->id}/reviews")
            ->assertOk()
            ->json('reviews.0');

        $this->assertSame($user->username, $reviewerPayload['username']);
        $this->assertArrayNotHasKey('email', $reviewerPayload);
        $this->assertArrayNotHasKey('is_admin', $reviewerPayload);
    }

    public function test_admin_can_update_a_court_with_a_multipart_method_override(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
        ]);

        $this->actingAs($admin)
            ->post("/admin/courts/{$court->id}", [
                '_method' => 'PUT',
                'name' => 'Updated Court',
                'address' => '2 Main St',
                'photo' => UploadedFile::fake()->createWithContent(
                    'court.png',
                    file_get_contents(public_path('images/basketball-marker.png'))
                ),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('courts', [
            'id' => $court->id,
            'name' => 'Updated Court',
            'address' => '2 Main St',
        ]);
        Storage::disk('public')->assertExists($court->fresh()->photo);
    }

    public function test_admin_can_update_a_review_through_the_admin_route(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
        ]);
        $review = CourtReview::create([
            'court_id' => $court->id,
            'user_id' => User::factory()->create()->id,
            'username' => 'Reviewer',
            'rating' => 5,
            'comment' => 'Original comment',
        ]);

        $this->actingAs($admin)
            ->putJson("/admin/courts/{$court->id}/reviews/{$review->id}", [
                'rating' => 3,
                'comment' => 'Updated by admin',
            ])
            ->assertOk();

        $this->assertDatabaseHas('court_reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => 'Updated by admin',
        ]);
    }

    public function test_admin_can_delete_a_review_through_the_admin_route(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 39.7817,
            'longitude' => -89.6501,
        ]);
        $review = CourtReview::create([
            'court_id' => $court->id,
            'user_id' => User::factory()->create()->id,
            'username' => 'Reviewer',
            'rating' => 5,
            'comment' => 'Original comment',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/admin/courts/{$court->id}/reviews/{$review->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('court_reviews', ['id' => $review->id]);
    }

    public function test_registration_cannot_assign_admin_privileges(): void
    {
        $this->post('/register', [
            'username' => 'NewUser',
            'email' => 'new-user@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'is_admin' => true,
        ])->assertRedirect('/');

        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'is_admin' => false,
        ]);
    }

    public function test_registration_rejects_passwords_shorter_than_twelve_characters(): void
    {
        $this->from('/register')->post('/register', [
            'username' => 'ShortPasswordUser',
            'email' => 'short-password@example.com',
            'password' => 'Short1!',
            'password_confirmation' => 'Short1!',
        ])->assertSessionHasErrors('password');
    }
}
