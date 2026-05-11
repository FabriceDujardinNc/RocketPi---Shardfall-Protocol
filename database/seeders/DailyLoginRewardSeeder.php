<?php

namespace Database\Seeders;

use App\Models\DailyLoginReward;
use Illuminate\Database\Seeder;

/**
 * Reproduit DailyLoginService::REWARDS_BY_DAY pour que l'app reste iso-comportement
 * une fois que le service lit la table au lieu de la constante.
 */
class DailyLoginRewardSeeder extends Seeder
{
    private const MILESTONES = [
        1  => ['label' => 'Connexion initiale', 'rewards' => [['type' => 'credits', 'amount' => 200],  ['type' => 'shards', 'amount' => 30]]],
        7  => ['label' => 'Première semaine',  'rewards' => [['type' => 'credits', 'amount' => 500],  ['type' => 'shards', 'amount' => 50]]],
        15 => ['label' => 'Mi-mois',           'rewards' => [['type' => 'credits', 'amount' => 1000], ['type' => 'shards', 'amount' => 100]]],
        30 => ['label' => 'Cycle complet',     'rewards' => [['type' => 'credits', 'amount' => 2000], ['type' => 'shards', 'amount' => 300], ['type' => 'tickets_premium', 'amount' => 1]]],
    ];

    public function run(): void
    {
        foreach (self::MILESTONES as $day => $data) {
            DailyLoginReward::firstOrCreate(
                ['day_number' => $day],
                [
                    'rewards'      => $data['rewards'],
                    'is_milestone' => true,
                    'label'        => $data['label'],
                ]
            );
        }
    }
}
