<?php

namespace App\Console\Commands;

use App\Models\LeaderboardSeason;
use App\Models\User;
use App\Notifications\SeasonEndingSoon;
use App\Services\LeaderboardService;
use Illuminate\Console\Command;

/**
 * Notifie les joueurs participants 3 jours avant la fin d'une saison.
 *
 * Scheduled quotidiennement (cf. routes/console.php). N'envoie qu'une fois
 * par saison (flag transient sur la saison ou check participation).
 */
class NotifySeasonsEnding extends Command
{
    protected $signature = 'seasons:notify-ending {--days=3 : jours avant la fin}';
    protected $description = "Envoie une notification aux participants des saisons qui se terminent prochainement.";

    public function handle(LeaderboardService $leaderboard): int
    {
        $days = (int) $this->option('days');
        $windowStart = now()->addDays($days)->startOfDay();
        $windowEnd   = now()->addDays($days)->endOfDay();

        $seasons = LeaderboardSeason::query()
            ->where('is_active', true)
            ->whereBetween('ends_at', [$windowStart, $windowEnd])
            ->whereIn('type', ['weekly', 'monthly', 'seasonal'])
            ->get();

        $totalSent = 0;
        foreach ($seasons as $season) {
            $top = $leaderboard->topN($season, 500); // élargir : tous les actifs notables
            foreach ($top as $entry) {
                $user = User::find($entry['user_id']);
                if (! $user || $user->is_banned) continue;
                $user->notify(new SeasonEndingSoon(
                    seasonName:   $season->name,
                    endsAt:       optional($season->ends_at)->toDateTimeString() ?? 'bientôt',
                    currentRank:  $entry['rank'],
                    currentScore: $entry['score'],
                ));
                $totalSent++;
            }
            $this->info("Saison {$season->name} : ".count($top)." notifiés.");
        }

        $this->info("Total : {$totalSent} notifications envoyées.");
        return self::SUCCESS;
    }
}
