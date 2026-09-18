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

        $response = $this->actingAs($admin)->deleteJson("/admin/users/{$user->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
