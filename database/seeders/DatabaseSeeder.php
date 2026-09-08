<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'username' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'Admin123$',
            'is_admin' => true,
        ]);
        User::factory()->create([
            'username' => 'User',
            'email' => 'user@user.com',
            'password' => 'User123$',
            'is_admin' => false,
        ]);
    }
}
