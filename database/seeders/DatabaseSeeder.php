<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            ElyxStudioSeeder::class,
            EduPulseDatabaseSeeder::class,
            ShapesColorsInDesignQuizSeeder::class,
            AnnouncementSeeder::class,
            ClassTodoSeeder::class,
        ]);

        // Support environment-configured administrator credentials if provided
        $adminUsername = env('ADMIN_USERNAME');
        $adminPassword = env('ADMIN_PASSWORD');
        $adminName = env('ADMIN_NAME', 'System Administrator');

        if ($adminUsername && $adminPassword) {
            $adminEmail = filter_var($adminUsername, FILTER_VALIDATE_EMAIL)
                ? $adminUsername
                : "{$adminUsername}@elytrack.local";

            User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => $adminName,
                    'password' => Hash::make($adminPassword),
                    'role' => 'superadmin',
                    'email_verified_at' => now(),
                ]
            );

            // Also ensure the primary superadmin account has the configured password
            $firstSuperadmin = User::where('role', 'superadmin')->orderBy('id')->first();
            if ($firstSuperadmin) {
                $firstSuperadmin->update([
                    'password' => Hash::make($adminPassword),
                ]);
            }
        }
    }
}
