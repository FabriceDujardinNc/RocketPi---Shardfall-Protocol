<?php

namespace Database\Seeders;

use App\Models\BattlePass;
use App\Models\BattlePassTier;
use Illuminate\Database\Seeder;

class BattlePassSeeder extends Seeder
{
    public function run(): void
    {
        $bp = BattlePass::firstOrCreate(
            ['name' => 'Saison 1 — Éveil des Shards', 'season_number' => 1],
            [
                'total_tiers'          => 50,
                'premium_price_shards' => 1000,
                'starts_at'            => now(),
                'ends_at'              => now()->addWeeks(8),
                'is_active'            => true,
            ]
        );

        // 50 paliers — XP cumulé croissant, paliers spéciaux à 5/10/25/50
        for ($tier = 1; $tier <= 50; $tier++) {
            $isMilestone = in_array($tier, [5, 10, 25, 50], true);
            $xpRequired  = $tier * 250;   // 250 XP par palier (12 500 XP pour les 50)

            $freeReward = match (true) {
                $tier === 50  => [['type' => 'shards', 'amount' => 1000]],
                $tier === 25  => [['type' => 'shards', 'amount' => 500]],
                $tier === 10  => [['type' => 'shards', 'amount' => 200], ['type' => 'tickets_standard', 'amount' => 1]],
                $tier === 5   => [['type' => 'shards', 'amount' => 100]],
                $tier % 5 === 0 => [['type' => 'credits', 'amount' => 500]],
                default       => [['type' => 'credits', 'amount' => 200]],
            };

            $premiumReward = match (true) {
                $tier === 50  => [['type' => 'tokens_legendary_choice', 'amount' => 1], ['type' => 'shards', 'amount' => 500]],
                $tier === 25  => [['type' => 'tokens_epic_choice', 'amount' => 1], ['type' => 'shards', 'amount' => 200]],
                $tier === 10  => [['type' => 'tickets_premium', 'amount' => 2]],
                $tier === 5   => [['type' => 'tickets_premium', 'amount' => 1]],
                $tier % 5 === 0 => [['type' => 'shards', 'amount' => 50], ['type' => 'tickets_standard', 'amount' => 1]],
                default       => [['type' => 'shards', 'amount' => 30]],
            };

            BattlePassTier::firstOrCreate(
                ['battle_pass_id' => $bp->id, 'tier_number' => $tier],
                [
                    'xp_required'    => $xpRequired,
                    'free_reward'    => $freeReward,
                    'premium_reward' => $premiumReward,
                    'is_milestone'   => $isMilestone,
                ]
            );
        }
    }
}
