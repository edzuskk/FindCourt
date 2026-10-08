<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_rejects_invalid_email_addresses(): void
    {
        $this->post('/register', [
            'username' => 'CourtFan',
            'email' => 'not-an-email',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_authenticates_the_new_user(): void
    {
        $response = $this->post('/register', [
            'username' => 'CourtFan',
            'email' => 'court-fan@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'court-fan@example.com']);
    }

    public function test_logout_invalidates_session_and_logs_out_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['logout_marker' => 'remove-me', '_token' => 'old-token'])
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertFalse(session()->has('logout_marker'));
        $this->assertNotSame('old-token', session()->token());
    }

    public function test_admin_seeder_is_idempotent_and_does_not_reset_existing_credentials(): void
    {
        config()->set('services.findcourt_admin.email', 'seed-admin@example.com');
        config()->set('services.findcourt_admin.password', 'SeedPassword123!');
        config()->set('services.findcourt_admin.username', 'SeedAdmin');

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'seed-admin@example.com')->firstOrFail();
        $admin->forceFill(['password' => 'ChangedPassword123!'])->save();
        $changedPasswordHash = $admin->fresh()->password;

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($changedPasswordHash, $admin->fresh()->password);
        $this->assertTrue(Hash::check('ChangedPassword123!', $admin->fresh()->password));
    }
}
