<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\MissionProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tracking des missions : progression et claim.
 *
 * Le flow :
 *  - À chaque action (pull, login, kill PvP…) on appelle progressFor()
 *  - progressFor() incrémente la progress des MissionProgress liées
 *  - Quand progress ≥ target → mission complétée (mais pas auto-claim)
 *  - Le joueur clique "Réclamer" → claim() applique les rewards + XP
 *
 * Les MissionProgress sont créées on-demand (premier event qui s'applique).
 */
class MissionService
{
    public function __construct(
        private readonly RewardService $rewards,
        private readonly XpService $xp,
        private readonly LeaderboardService $leaderboard,
    ) {}

    /**
     * Incrémente la progression de toutes les missions actives qui matchent
     * `objective_type`, et marque les complétées.
     *
     * @return int Nombre de missions complétées par cette action.
     */
    public function progressFor(User $user, string $objectiveType, int $increment = 1): int
    {
        if ($increment <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($user, $objectiveType, $increment) {
            $missions = Mission::where('is_active', true)
                ->where('objective_type', $objectiveType)
                ->get();

            $newlyCompleted = 0;

            foreach ($missions as $mission) {
                $progress = MissionProgress::firstOrCreate(
                    ['user_id' => $user->id, 'mission_id' => $mission->id],
                    ['progress' => 0, 'completed' => false, 'reward_claimed' => false]
                );

                if ($progress->completed) {
                    continue;
                }

                $progress->progress = min($mission->objective_target, $progress->progress + $increment);

                if ($progress->progress >= $mission->objective_target) {
                    $progress->completed = true;
                    $progress->completed_at = now();
                    $newlyCompleted++;
                }

                $progress->save();
            }

            return $newlyCompleted;
        });
    }

    public function claim(User $user, Mission $mission, ?string $ipAddress = null): array
    {
        $progress = MissionProgress::where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->first();

        if (! $progress || ! $progress->completed) {
            throw new RuntimeException('Mission non complétée.');
        }
        if ($progress->reward_claimed) {
            throw new RuntimeException('Récompense déjà réclamée.');
        }

        return DB::transaction(function () use ($user, $mission, $progress, $ipAddress) {
            $rewards = is_array($mission->rewards) ? $mission->rewards : [];

            $this->rewards->apply($user, $rewards, 'mission_reward', $mission, $ipAddress);

            $xpResult = $this->xp->award($user, (int) $mission->xp_reward);

            $progress->update([
                'reward_claimed' => true,
                'claimed_at'     => now(),
            ]);

            // Leaderboard : points selon type de mission (weekly = 5pts/×défi, daily = 1pt)
            $points = $mission->type === 'weekly' ? 5 : 1;
            try {
                foreach ($this->leaderboard->activeSeasons() as $season) {
                    if (in_array($season->type, ['weekly', 'monthly', 'seasonal'], true)) {
                        $this->leaderboard->addPoints($user, $season, $points);
                    }
                }
            } catch (\Throwable $e) {
                // Redis indisponible : on ne casse pas le claim, on log seulement
                \Log::warning('Leaderboard points add failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            }

            return [
                'mission_id' => $mission->id,
                'rewards'    => $rewards,
                'xp'         => $xpResult,
            ];
        });
    }

    /**
     * Récupère les missions journalières/hebdo avec leur progression actuelle.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(User $user, string $type = 'daily'): array
    {
        $missions = Mission::where('is_active', true)
            ->where('type', $type)
            ->orderBy('id')
            ->get();

        $progress = MissionProgress::where('user_id', $user->id)
            ->whereIn('mission_id', $missions->pluck('id'))
            ->get()
            ->keyBy('mission_id');

        return $missions->map(function (Mission $m) use ($progress) {
            $p = $progress->get($m->id);
            return [
                'id'               => $m->id,
                'title'            => $m->title,
                'description'      => $m->description,
                'type'             => $m->type,
                'objective_type'   => $m->objective_type,
                'objective_target' => $m->objective_target,
                'rewards'          => $m->rewards,
                'xp_reward'        => $m->xp_reward,
                'progress'         => $p?->progress ?? 0,
                'completed'        => $p?->completed ?? false,
                'reward_claimed'   => $p?->reward_claimed ?? false,
            ];
        })->toArray();
    }
}
