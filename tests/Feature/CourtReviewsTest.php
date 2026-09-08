<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_review_for_a_court(): void
    {
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
            'photo' => 'https://example.com/court.jpg',
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
}
