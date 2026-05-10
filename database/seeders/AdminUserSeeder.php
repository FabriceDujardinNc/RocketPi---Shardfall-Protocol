<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Super admin (configurable via .env) ────────────────────
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@rocketpi.pro')],
            [
                'name'              => 'Super Admin',
                'display_name'      => 'COMMANDANT',
                'password'          => Hash::make(env('ADMIN_PASSWORD', 'change-me-in-production!')),
                'role'              => User::ROLE_SUPER_ADMIN,
                'referral_code'     => 'ADM-0000-0000',
                'email_verified_at' => now(),
                'account_level'     => 99,
            ]
        );

        // ── Compte développeur principal (Fabrice) ─────────────────
        // Mot de passe par défaut : `dev1234` (à changer en production)
        User::firstOrCreate(
            ['email' => 'fabricedujardin873@gmail.com'],
            [
                'name'              => 'Fabrice',
                'display_name'      => 'FABRICE',
                'password'          => Hash::make('dev1234'),
                'role'              => User::ROLE_SUPER_ADMIN,
                'referral_code'     => 'FAB-0000-0001',
                'email_verified_at' => now(),
                'account_level'     => 99,
            ]
        );

        // ── Comptes test (uniquement en local) ─────────────────────
        if (app()->environment('local')) {
            $this->seedDevAccounts();

            // Crédite chaque user en shards/credits pour tester le gacha
            foreach (User::all() as $u) {
                Currency::firstOrCreate(
                    ['user_id' => $u->id, 'type' => Currency::TYPE_SHARDS],
                    ['balance' => 5000]
                );
                Currency::firstOrCreate(
                    ['user_id' => $u->id, 'type' => Currency::TYPE_CREDITS],
                    ['balance' => 50000]
                );
            }
        }
    }

    private function seedDevAccounts(): void
    {
        $accounts = [
            [
                'email'         => 'admin@rocketpi.local',
                'name'          => 'Admin Test',
                'display_name'  => 'ADMIN',
                'role'          => User::ROLE_ADMIN,
                'referral_code' => 'TST-ADM0-0000',
                'account_level' => 50,
            ],
            [
                'email'         => 'player@rocketpi.local',
                'name'          => 'Player Test',
                'display_name'  => 'JOUEUR',
                'role'          => User::ROLE_USER,
                'referral_code' => 'TST-PLY0-0000',
                'account_level' => 12,
            ],
            [
                'email'         => 'banned@rocketpi.local',
                'name'          => 'Banned Test',
                'display_name'  => 'BANNI',
                'role'          => User::ROLE_USER,
                'referral_code' => 'TST-BAN0-0000',
                'account_level' => 5,
                'is_banned'     => true,
                'ban_reason'    => 'Compte test pour vérifier le flow ban',
                'banned_at'     => now(),
            ],
        ];

        foreach ($accounts as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password'          => Hash::make('dev1234'),
                    'email_verified_at' => now(),
                ])
            );
        }
    }
}
