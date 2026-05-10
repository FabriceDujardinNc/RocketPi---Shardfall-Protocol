<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            // Collection
            ['key' => 'first_pull',       'title' => 'Premier Signal',          'description' => 'Effectue ton premier tirage gacha.',       'category' => 'collection', 'rewards' => [['type' => 'credits', 'amount' => 100]]],
            ['key' => 'pulled_10',        'title' => 'Apprenti Recruteur',      'description' => 'Effectue 10 tirages.',                       'category' => 'collection', 'rewards' => [['type' => 'shards', 'amount' => 50]]],
            ['key' => 'pulled_100',       'title' => 'Recruteur expérimenté',   'description' => 'Effectue 100 tirages cumulés.',              'category' => 'collection', 'rewards' => [['type' => 'shards', 'amount' => 300], ['type' => 'tickets_premium', 'amount' => 1]]],
            ['key' => 'first_epic',       'title' => 'Premier Épique',          'description' => 'Obtiens ton premier Opérateur épique.',     'category' => 'collection', 'rewards' => [['type' => 'shards', 'amount' => 100]]],
            ['key' => 'first_legendary',  'title' => 'Première Légende',        'description' => 'Obtiens ton premier Opérateur légendaire.', 'category' => 'collection', 'rewards' => [['type' => 'shards', 'amount' => 500]]],

            // Progression
            ['key' => 'reach_level_10',   'title' => 'Recrue confirmée',         'description' => 'Atteins le niveau 10.',  'category' => 'progression', 'rewards' => [['type' => 'shards', 'amount' => 200]]],
            ['key' => 'reach_level_30',   'title' => 'Commandant aguerri',       'description' => 'Atteins le niveau 30.',  'category' => 'progression', 'rewards' => [['type' => 'shards', 'amount' => 500]]],
            ['key' => 'reach_level_60',   'title' => 'Légende du Protocole',     'description' => 'Atteins le niveau 60.',  'category' => 'progression', 'rewards' => [['type' => 'shards', 'amount' => 1000], ['type' => 'tokens_legendary_choice', 'amount' => 1]]],

            // Special
            ['key' => 'daily_login_7',    'title' => 'Une semaine, un signal',   'description' => 'Connecte-toi 7 jours d\'affilée.', 'category' => 'special', 'rewards' => [['type' => 'shards', 'amount' => 150]]],
            ['key' => 'first_referral',   'title' => 'Recruteur de talents',     'description' => 'Parraine un premier joueur.',     'category' => 'social',  'rewards' => [['type' => 'shards', 'amount' => 100]]],
        ];

        foreach ($achievements as $data) {
            Achievement::firstOrCreate(['key' => $data['key']], $data);
        }
    }
}
