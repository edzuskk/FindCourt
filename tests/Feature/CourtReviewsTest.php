<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\CourtReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourtReviewsTest extends TestCase
{
    use RefreshDatabase;

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
            'photo' => UploadedFile::fake()->image('court.jpg'),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('court_reviews', [
            'court_id' => $court->id,
            'user_id' => $user->id,
            'username' => 'Alice',
            'comment' => 'Amazing court. Great hoops.',
            'rating' => 5,
        ]);
    }

    public function test_user_can_edit_only_their_own_review(): void
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
            ->putJson("/courts/{$court->id}/reviews/{$review->id}", [
                'rating' => 1,
                'comment' => 'Should not be allowed',
            ])
            ->assertForbidden();

        $this->actingAs($author)
            ->putJson("/courts/{$court->id}/reviews/{$review->id}", [
                'rating' => 4,
                'comment' => 'Updated comment',
            ])
            ->assertOk();

        $this->assertDatabaseHas('court_reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => 'Updated comment',
        ]);
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
        $ownedReview = CourtReview::create([
            'court_id' => $ownedCourt->id,
            'user_id' => User::factory()->create()->id,
            'username' => 'Reviewer',
            'rating' => 5,
            'comment' => 'Review on owned court',
            'photo' => 'reviews/owned-review.jpg',
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
        $this->assertDatabaseMissing('courts', ['id' => $ownedCourt->id]);
        $this->assertDatabaseMissing('court_reviews', ['id' => $ownedReview->id]);
        $this->assertDatabaseHas('court_reviews', [
            'id' => $retainedReview->id,
            'user_id' => null,
        ]);
        Storage::disk('public')->assertMissing('courts/owned-court.jpg');
        Storage::disk('public')->assertMissing('reviews/owned-review.jpg');
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
        $reviewerPayload = data_get($response->json(), '0.reviews.0.user');

        $this->assertSame($user->username, $reviewerPayload['username']);
        $this->assertArrayNotHasKey('email', $reviewerPayload);
        $this->assertArrayNotHasKey('is_admin', $reviewerPayload);
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
