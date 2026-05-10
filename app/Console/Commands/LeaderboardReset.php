<?php

namespace App\Console\Commands;

use App\Models\LeaderboardSeason;
use App\Services\LeaderboardService;
use App\Services\RewardService;
use Illuminate\Console\Command;

/**
 * Snapshot Redis → MySQL puis distribue les rewards.
 * À programmer en cron au reset (lundi 00h UTC pour weekly, 1er du mois pour monthly).
 *
 * Usage :
 *   php artisan leaderboard:reset --season=42
 *   php artisan leaderboard:reset --type=weekly --expired-only
 */
class LeaderboardReset extends Command
{
    protected $signature = 'leaderboard:reset
                            {--season= : ID d\'une saison spécifique}
                            {--type= : Type (weekly|monthly|seasonal|annual|collection|faction)}
                            {--expired-only : Ne reset que les saisons dont ends_at est dépassé}';

    protected $description = 'Snapshot Redis → MySQL et distribue les rewards d\'une saison';

    public function handle(LeaderboardService $service, RewardService $rewards): int
    {
        $query = LeaderboardSeason::query()->where('is_active', true);

        if ($id = $this->option('season')) {
            $query->where('id', $id);
        }
        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }
        if ($this->option('expired-only')) {
            $query->where('ends_at', '<=', now());
        }

        $seasons = $query->get();

        if ($seasons->isEmpty()) {
            $this->info('Aucune saison à reset.');
            return self::SUCCESS;
        }

        foreach ($seasons as $season) {
            $this->info("→ Reset {$season->name} (#{$season->id})");

            $count = $service->snapshotToMysql($season);
            $this->line("  ✓ {$count} entries archivées en MySQL");

            $distributed = $service->distributeRewards($season, $rewards);
            $this->line("  ✓ {$distributed} récompenses distribuées");
        }

        return self::SUCCESS;
    }
}
