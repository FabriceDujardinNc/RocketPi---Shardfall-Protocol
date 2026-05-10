<?php

namespace Database\Seeders;

use App\Models\LeaderboardSeason;
use Illuminate\Database\Seeder;

class LeaderboardSeasonSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Saison hebdomadaire active
        LeaderboardSeason::firstOrCreate(
            ['type' => 'weekly', 'season_number' => 1],
            [
                'name'      => 'Semaine 1 — Éveil des Shards',
                'type'      => 'weekly',
                'season_number' => 1,
                'starts_at' => $now->startOfWeek(),
                'ends_at'   => $now->copy()->endOfWeek(),
                'is_active' => true,
            ]
        );

        // Saison mensuelle active
        LeaderboardSeason::firstOrCreate(
            ['type' => 'monthly', 'season_number' => 1],
            [
                'name'      => 'Mai 2026 — Shardfall Protocol',
                'type'      => 'monthly',
                'season_number' => 1,
                'starts_at' => $now->copy()->startOfMonth(),
                'ends_at'   => $now->copy()->endOfMonth(),
                'is_active' => true,
            ]
        );

        // Saison compétitive (trimestrielle)
        LeaderboardSeason::firstOrCreate(
            ['type' => 'seasonal', 'season_number' => 1],
            [
                'name'      => 'Saison 1 — Éclat Primordial',
                'type'      => 'seasonal',
                'season_number' => 1,
                'starts_at' => now(),
                'ends_at'   => now()->addMonths(3),
                'is_active' => true,
            ]
        );

        // Classement Collection global (permanent)
        LeaderboardSeason::firstOrCreate(
            ['type' => 'collection', 'season_number' => 1],
            [
                'name'      => 'Collection — Hall des Recruteurs',
                'type'      => 'collection',
                'season_number' => 1,
                'starts_at' => now(),
                'ends_at'   => now()->addYears(10),
                'is_active' => true,
            ]
        );

        // Classements par faction (un par faction)
        foreach (['ORBIT', 'FERRO', 'VEIL'] as $i => $faction) {
            LeaderboardSeason::firstOrCreate(
                ['type' => 'faction', 'faction' => $faction, 'season_number' => 1],
                [
                    'name'          => "Faction {$faction} — Classement Collection",
                    'type'          => 'faction',
                    'faction'       => $faction,
                    'season_number' => 1,
                    'starts_at'     => now(),
                    'ends_at'       => now()->addYears(10),  // Permanent
                    'is_active'     => true,
                ]
            );
        }
    }
}
