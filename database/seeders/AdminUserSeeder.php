<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@rocketpi.pro')],
            [
                'name'              => 'Super Admin',
                'display_name'      => 'COMMANDANT',
                'password'          => Hash::make(env('ADMIN_PASSWORD', 'change-me-in-production!')),
                'role'              => 'super_admin',
                'referral_code'     => 'ADM-0000-0000',
                'email_verified_at' => now(),
                'account_level'     => 99,
            ]
        );
    }
}
