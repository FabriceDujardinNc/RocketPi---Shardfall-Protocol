<?php

namespace App\Services;

use App\Models\LeaderboardEntry;
use App\Models\LeaderboardReward;
use App\Models\LeaderboardSeason;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Redis;

/**
 * Classements basés sur Redis Sorted Sets (perfs lecture O(log N)).
 *
 * Patterns Redis :
 *   ZSET key  : leaderboard:season:{id}    (member=user_id, score=points)
 *   HSET key  : leaderboard:meta:{id}      (cache name, type, etc.)
 *
 * Snapshot MySQL au reset via `snapshotToMysql(season)` — la table
 * `leaderboard_entries` archive les rangs finaux pour l'historique.
 *
 * Spec :
 *  - PvP win = +3 points, mission = +1, défi hebdo = +5, bonus MVP variable
 *  - Plafond quotidien (à implémenter par jeu — pour l'instant pas de cap)
 *  - Reset hebdo lundi 00h UTC, mensuel 1er du mois 00h UTC
 */
class LeaderboardService
{
    public function key(int $seasonId): string
    {
        return "leaderboard:season:{$seasonId}";
    }

    /**
     * Clé Redis tracking le total de points classement gagnés par un joueur
     * dans une saison, sur un jour UTC donné. Utilisée pour le plafond anti-farm.
     */
    public function dailyKey(int $seasonId, int $userId, ?string $date = null): string
    {
        $date ??= CarbonImmutable::now('UTC')->toDateString();
        return "leaderboard:daily:{$seasonId}:{$userId}:{$date}";
    }

    /**
     * Ajoute des points à un joueur dans une saison active.
     *
     * Applique le plafond quotidien `leaderboard.daily_cap` (setting BDD,
     * défaut 5000). Si le total cumulé sur la journée dépasse le cap,
     * on ne crédite que ce qui rentre encore — au-delà, on no-op.
     *
     * @return int score total après ajout (0 si rejeté, ou même score qu'avant si déjà au cap)
     */
    public function addPoints(User $user, LeaderboardSeason $season, int $points): int
    {
        if ($points <= 0 || ! $season->is_active) {
            return $this->scoreOf($user, $season);
        }

        $cap = (int) Setting::value('leaderboard.daily_cap', 5000);

        if ($cap > 0) {
            $dailyKey = $this->dailyKey($season->id, $user->id);
            $earnedToday = (int) (Redis::get($dailyKey) ?? 0);

            if ($earnedToday >= $cap) {
                return $this->scoreOf($user, $season);
            }

            $points = min($points, $cap - $earnedToday);

            Redis::incrby($dailyKey, $points);
            // 36h d'expiration : couvre les fuseaux et le rollover minuit UTC
            Redis::expire($dailyKey, 36 * 3600);
        }

        $newScore = (int) Redis::zincrby($this->key($season->id), $points, (string) $user->id);

        return $newScore;
    }

    /**
     * Combien de points ce joueur a-t-il déjà gagné aujourd'hui (UTC) ?
     * Utile pour afficher le cap en UI.
     */
    public function dailyEarned(User $user, LeaderboardSeason $season): int
    {
        return (int) (Redis::get($this->dailyKey($season->id, $user->id)) ?? 0);
    }

    /**
     * Retourne le top N pour une saison, avec hydratation User.
     *
     * @return array<int, array{rank: int, user_id: int, score: int, name: string, display_name: string|null, account_level: int}>
     */
    public function topN(LeaderboardSeason $season, int $limit = 100): array
    {
        $raw = Redis::zrevrange($this->key($season->id), 0, $limit - 1, ['withscores' => true]);
        if (empty($raw)) {
            return [];
        }

        $userIds = array_keys($raw);
        $users = User::whereIn('id', $userIds)
            ->get(['id', 'name', 'display_name', 'account_level'])
            ->keyBy('id');

        $rank = 0;
        $out = [];
        foreach ($raw as $userId => $score) {
            $rank++;
            $u = $users->get((int) $userId);
            $out[] = [
                'rank'          => $rank,
                'user_id'       => (int) $userId,
                'score'         => (int) $score,
                'name'          => $u?->name ?? 'Unknown',
                'display_name'  => $u?->display_name,
                'account_level' => (int) ($u?->account_level ?? 1),
            ];
        }

        return $out;
    }

    /**
     * Retourne le rang du joueur (1-indexed) ou null si pas dans le ZSET.
     */
    public function rankOf(User $user, LeaderboardSeason $season): ?int
    {
        $rank = Redis::zrevrank($this->key($season->id), (string) $user->id);
        return $rank === null || $rank === false ? null : ((int) $rank) + 1;
    }

    /**
     * Score actuel du joueur pour cette saison.
     */
    public function scoreOf(User $user, LeaderboardSeason $season): int
    {
        $score = Redis::zscore($this->key($season->id), (string) $user->id);
        return $score === null || $score === false ? 0 : (int) $score;
    }

    /**
     * Joueurs autour du rang du user (3 avant + user + 3 après).
     *
     * @return array<int, array<string, mixed>>
     */
    public function neighborsOf(User $user, LeaderboardSeason $season, int $span = 3): array
    {
        $rank = Redis::zrevrank($this->key($season->id), (string) $user->id);
        if ($rank === null || $rank === false) {
            return [];
        }

        $start = max(0, $rank - $span);
        $end   = $rank + $span;

        $raw = Redis::zrevrange($this->key($season->id), $start, $end, ['withscores' => true]);
        $userIds = array_keys($raw);
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'display_name', 'account_level'])->keyBy('id');

        $out = [];
        $idx = $start;
        foreach ($raw as $userId => $score) {
            $idx++;
            $u = $users->get((int) $userId);
            $out[] = [
                'rank'          => $idx,
                'user_id'       => (int) $userId,
                'score'         => (int) $score,
                'name'          => $u?->name ?? 'Unknown',
                'display_name'  => $u?->display_name,
                'account_level' => (int) ($u?->account_level ?? 1),
                'is_current_user' => (int) $userId === $user->id,
            ];
        }
        return $out;
    }

    /**
     * Total des participants dans la saison (cardinal du ZSET).
     */
    public function participantCount(LeaderboardSeason $season): int
    {
        return (int) Redis::zcard($this->key($season->id));
    }

    /**
     * Snapshot Redis → MySQL leaderboard_entries. À appeler au reset.
     * Vide le ZSET après archivage.
     */
    public function snapshotToMysql(LeaderboardSeason $season): int
    {
        $key = $this->key($season->id);
        $all = Redis::zrevrange($key, 0, -1, ['withscores' => true]);

        $rank = 0;
        $count = 0;
        foreach ($all as $userId => $score) {
            $rank++;
            LeaderboardEntry::updateOrCreate(
                ['season_id' => $season->id, 'user_id' => (int) $userId],
                ['score' => (int) $score, 'rank' => $rank]
            );
            $count++;
        }

        Redis::del($key);

        return $count;
    }

    /**
     * Distribue les rewards aux joueurs selon les paliers configurés.
     * Doit être appelé après snapshotToMysql().
     */
    public function distributeRewards(LeaderboardSeason $season, RewardService $rewards): int
    {
        if ($season->rewards_distributed) {
            return 0;
        }

        $entries = LeaderboardEntry::where('season_id', $season->id)
            ->whereNotNull('rank')
            ->orderBy('rank')
            ->get();

        if ($entries->isEmpty()) {
            return 0;
        }

        $total = $entries->count();
        $rewardConfigs = LeaderboardReward::where('season_id', $season->id)->get();

        $distributedCount = 0;

        foreach ($entries as $entry) {
            foreach ($rewardConfigs as $cfg) {
                if ($this->rankMatchesTier($entry->rank, $total, $cfg->tier)) {
                    $user = $entry->user;
                    if ($user) {
                        $rewards->apply($user, $cfg->rewards, "leaderboard_reward:{$cfg->tier}", $cfg);
                        $distributedCount++;
                    }
                }
            }
        }

        $season->update(['rewards_distributed' => true, 'is_active' => false]);

        return $distributedCount;
    }

    private function rankMatchesTier(int $rank, int $totalParticipants, string $tier): bool
    {
        return match ($tier) {
            'top_1'      => $rank === 1,
            'top_10'     => $rank >= 2 && $rank <= 10,
            'top_100'    => $rank >= 11 && $rank <= 100,
            'top_1pct'   => $rank > 100 && $rank <= max(1, (int) ceil($totalParticipants * 0.01)),
            'top_10pct'  => $rank > max(100, (int) ceil($totalParticipants * 0.01)) && $rank <= (int) ceil($totalParticipants * 0.10),
            'top_50pct'  => $rank > (int) ceil($totalParticipants * 0.10) && $rank <= (int) ceil($totalParticipants * 0.50),
            default      => false,
        };
    }

    /**
     * Saisons actives groupées par type pour la liste joueur.
     *
     * @return array<int, LeaderboardSeason>
     */
    public function activeSeasons()
    {
        return LeaderboardSeason::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->orderByDesc('type')
            ->get();
    }
}
