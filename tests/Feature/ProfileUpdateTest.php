<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_photo_is_replaced_after_profile_update_succeeds(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'photo' => 'profiles/old.png',
        ]);
        Storage::disk('public')->put('profiles/old.png', 'old image');

        $response = $this->actingAs($user)->put('/profile', [
            'username' => $user->username,
            'email' => $user->email,
            'profile_picture' => UploadedFile::fake()->createWithContent(
                'new.png',
                file_get_contents(public_path('images/basketball-marker.png'))
            ),
        ]);

        $response->assertRedirect(route('profile.view'));

        $newPhoto = $user->fresh()->photo;
        $this->assertNotSame('profiles/old.png', $newPhoto);
        Storage::disk('public')->assertExists($newPhoto);
        Storage::disk('public')->assertMissing('profiles/old.png');
    }

    public function test_changing_email_requires_the_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'password' => 'CurrentPassword123!',
        ]);

        $this->actingAs($user)
            ->put('/profile', [
                'username' => $user->username,
                'email' => 'new@example.com',
                'current_password' => 'WrongPassword123!',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'old@example.com',
        ]);

        $this->actingAs($user)
            ->put('/profile', [
                'username' => $user->username,
                'email' => 'new@example.com',
                'current_password' => 'CurrentPassword123!',
            ])
            ->assertRedirect(route('profile.view'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new@example.com',
        ]);
    }
}
