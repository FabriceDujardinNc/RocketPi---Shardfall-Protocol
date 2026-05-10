<?php

namespace Database\Seeders;

use App\Models\Mission;
use Illuminate\Database\Seeder;

class MissionSeeder extends Seeder
{
    public function run(): void
    {
        $missions = [
            // Missions journalières
            [
                'title'            => 'Connexion quotidienne',
                'description'      => 'Connecte-toi au jeu.',
                'type'             => 'daily',
                'objective_type'   => 'login',
                'objective_target' => 1,
                'rewards'          => [['type' => 'credits', 'amount' => 100]],
                'xp_reward'        => 50,
                'is_active'        => true,
            ],
            [
                'title'            => 'Premier tirage du jour',
                'description'      => 'Effectue un tirage sur n\'importe quelle bannière.',
                'type'             => 'daily',
                'objective_type'   => 'pull',
                'objective_target' => 1,
                'rewards'          => [['type' => 'credits', 'amount' => 200]],
                'xp_reward'        => 100,
                'is_active'        => true,
            ],
            [
                'title'            => 'Collectionneur du jour',
                'description'      => 'Obtiens 3 opérateurs via des tirages.',
                'type'             => 'daily',
                'objective_type'   => 'pull',
                'objective_target' => 3,
                'rewards'          => [['type' => 'shards', 'amount' => 30], ['type' => 'credits', 'amount' => 150]],
                'xp_reward'        => 200,
                'is_active'        => true,
            ],
            // Missions hebdomadaires
            [
                'title'            => 'Signal Shard Hebdomadaire',
                'description'      => 'Effectue 10 tirages cette semaine.',
                'type'             => 'weekly',
                'objective_type'   => 'pull',
                'objective_target' => 10,
                'rewards'          => [['type' => 'tickets_premium', 'amount' => 1], ['type' => 'shards', 'amount' => 100]],
                'xp_reward'        => 500,
                'is_active'        => true,
            ],
            [
                'title'            => 'Commandant actif',
                'description'      => 'Connecte-toi 5 jours cette semaine.',
                'type'             => 'weekly',
                'objective_type'   => 'login',
                'objective_target' => 5,
                'rewards'          => [['type' => 'shards', 'amount' => 200], ['type' => 'credits', 'amount' => 500]],
                'xp_reward'        => 400,
                'is_active'        => true,
            ],
        ];

        foreach ($missions as $data) {
            Mission::firstOrCreate(
                ['title' => $data['title'], 'type' => $data['type']],
                $data
            );
        }
    }
}
