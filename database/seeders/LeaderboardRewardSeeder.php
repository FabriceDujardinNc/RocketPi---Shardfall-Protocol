<?php

namespace Database\Seeders;

use App\Models\LeaderboardReward;
use App\Models\LeaderboardSeason;
use Illuminate\Database\Seeder;

/**
 * Récompenses par tier pour chaque saison active.
 *
 * Les tiers sont matchés par LeaderboardService::rankMatchesTier() :
 *  top_1 / top_10 / top_100 / top_1pct / top_10pct / top_50pct
 *
 * Les payouts varient par type de saison :
 *  - weekly  : rotation rapide, petits incentives
 *  - monthly : plus généreux, débloque tickets premium
 *  - seasonal (3 mois, compétitif) : titres + skins + gros pack
 *  - collection (permanent) : reconnaissance, peu de currency
 *  - faction (permanent) : badges faction
 *
 * Note : un titre / skin est exprimé comme un currency `cosmetic_<slug>`
 * — l'asset n'est pas encore en BDD, RewardService::apply le crédite
 * comme un compteur, et l'inventaire cosmétique le résoudra plus tard.
 */
class LeaderboardRewardSeeder extends Seeder
{
    private const REWARDS = [
        'weekly' => [
            'top_1'     => [['type' => 'shards', 'amount' => 500],  ['type' => 'tickets_premium', 'amount' => 2]],
            'top_10'    => [['type' => 'shards', 'amount' => 300],  ['type' => 'tickets_premium', 'amount' => 1]],
            'top_100'   => [['type' => 'shards', 'amount' => 150]],
            'top_1pct'  => [['type' => 'shards', 'amount' => 100]],
            'top_10pct' => [['type' => 'credits', 'amount' => 500]],
            'top_50pct' => [['type' => 'credits', 'amount' => 200]],
        ],
        'monthly' => [
            'top_1'     => [['type' => 'shards', 'amount' => 2000], ['type' => 'tickets_premium', 'amount' => 10], ['type' => 'cosmetic_title_apex', 'amount' => 1]],
            'top_10'    => [['type' => 'shards', 'amount' => 1000], ['type' => 'tickets_premium', 'amount' => 5]],
            'top_100'   => [['type' => 'shards', 'amount' => 500],  ['type' => 'tickets_premium', 'amount' => 2]],
            'top_1pct'  => [['type' => 'shards', 'amount' => 250]],
            'top_10pct' => [['type' => 'shards', 'amount' => 100]],
            'top_50pct' => [['type' => 'credits', 'amount' => 500]],
        ],
        'seasonal' => [
            'top_1'     => [['type' => 'shards', 'amount' => 5000], ['type' => 'tokens_legendary_choice', 'amount' => 1], ['type' => 'cosmetic_title_primordial_champion', 'amount' => 1], ['type' => 'cosmetic_border_legend', 'amount' => 1]],
            'top_10'    => [['type' => 'shards', 'amount' => 2500], ['type' => 'tokens_epic_choice', 'amount' => 1],      ['type' => 'cosmetic_border_legend', 'amount' => 1]],
            'top_100'   => [['type' => 'shards', 'amount' => 1000], ['type' => 'tickets_premium', 'amount' => 5]],
            'top_1pct'  => [['type' => 'shards', 'amount' => 500],  ['type' => 'tickets_premium', 'amount' => 2]],
            'top_10pct' => [['type' => 'shards', 'amount' => 200]],
            'top_50pct' => [['type' => 'credits', 'amount' => 1000]],
        ],
        'collection' => [
            'top_1'     => [['type' => 'cosmetic_title_master_collector', 'amount' => 1], ['type' => 'shards', 'amount' => 1000]],
            'top_10'    => [['type' => 'cosmetic_title_collector',        'amount' => 1], ['type' => 'shards', 'amount' => 500]],
            'top_100'   => [['type' => 'shards', 'amount' => 200]],
            'top_1pct'  => [['type' => 'shards', 'amount' => 100]],
            'top_10pct' => [['type' => 'credits', 'amount' => 300]],
            'top_50pct' => [['type' => 'credits', 'amount' => 100]],
        ],
        'faction' => [
            'top_1'     => [['type' => 'cosmetic_faction_banner', 'amount' => 1], ['type' => 'shards', 'amount' => 750]],
            'top_10'    => [['type' => 'shards', 'amount' => 400],  ['type' => 'tickets_premium', 'amount' => 1]],
            'top_100'   => [['type' => 'shards', 'amount' => 200]],
            'top_1pct'  => [['type' => 'shards', 'amount' => 100]],
            'top_10pct' => [['type' => 'credits', 'amount' => 300]],
            'top_50pct' => [['type' => 'credits', 'amount' => 100]],
        ],
    ];

    private const RANK_RANGES = [
        'top_1'     => ['1',  '1'],
        'top_10'    => ['2',  '10'],
        'top_100'   => ['11', '100'],
        'top_1pct'  => ['1%', '1%'],
        'top_10pct' => ['1%', '10%'],
        'top_50pct' => ['10%', '50%'],
    ];

    public function run(): void
    {
        $seasons = LeaderboardSeason::where('is_active', true)->get();

        foreach ($seasons as $season) {
            $payouts = self::REWARDS[$season->type] ?? null;
            if (! $payouts) {
                continue;
            }

            foreach ($payouts as $tier => $rewards) {
                [$rankMin, $rankMax] = self::RANK_RANGES[$tier];

                LeaderboardReward::firstOrCreate(
                    ['season_id' => $season->id, 'tier' => $tier],
                    [
                        'rank_min' => $rankMin,
                        'rank_max' => $rankMax,
                        'rewards'  => $rewards,
                    ]
                );
            }
        }
    }
}
