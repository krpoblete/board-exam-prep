<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Known passwords must never exist in production.
        // Create the first real admin another way (tinker or a command).
        if (app()->isProduction()) {
            $this->command?->warn('UserSeeder skipped in production.');

            return;
        }

        $users = [
            ['Admin User', 'admin@mail.com', Role::Admin],
            ['Instructor User', 'instructor@mail.com', Role::Instructor],
            ['Examinee User', 'examinee@mail.com', Role::Examinee],
        ];

        foreach ($users as [$name, $email, $role]) {
            // `role` is deliberately not mass-assignable, so use forceFill.
            $user = User::firstOrNew([
                'email' => $email,
            ]);
            $user->forceFill([
                'name' => $name,
                // hashed by the model cast
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => $role,
            ])->save();
        }
    }
}
