<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => env('SEED_ADMIN_EMAIL', 'alaa0109155426@gmail.com')],
            [
                'name' => 'Admin',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'Alaa0109155426')),
                'phone' => '0500000000',
                'is_active' => true,
            ]
        );

        $admin->assignRole('admin');

        User::firstOrCreate(
            ['email' => env('SEED_USER_EMAIL', 'user@evona.com')],
            [
                'name' => 'User',
                'password' => Hash::make(env('SEED_USER_PASSWORD', 'password')),
                'phone' => '0511111111',
                'is_active' => true,
            ]
        )->assignRole('customer');
    }
}
