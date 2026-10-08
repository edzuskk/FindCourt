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
        $email = config('services.findcourt_admin.email');
        $password = config('services.findcourt_admin.password');

        if (! filled($email) || ! filled($password)) {
            $this->command?->warn('Admin seeding skipped: configure FINDCOURT_ADMIN_EMAIL and FINDCOURT_ADMIN_PASSWORD.');

            return;
        }

        $admin = User::query()->firstOrNew(['email' => $email]);

        if ($admin->exists) {
            if (! $admin->is_admin) {
                throw new \RuntimeException('FINDCOURT_ADMIN_EMAIL belongs to a non-admin account.');
            }

            return;
        }

        $admin->forceFill([
            'username' => config('services.findcourt_admin.username'),
            'password' => $password,
            'is_admin' => true,
        ])->save();
    }
}
