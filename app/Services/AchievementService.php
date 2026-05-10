<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Système d'achievements.
 *
 * Pattern simple : chaque achievement a une `key` unique. Les services
 * métier appellent `track(user, key, increment=1)` aux moments clés
 * (premier légendaire, niveau atteint, etc.). Le service incrémente
 * la progression et marque complété si l'objectif est atteint.
 *
 * Le déblocage de la progression vers l'objectif est géré par les
 * appelants (ils savent quand un event se produit).
 */
class AchievementService
{
    public function __construct(private readonly RewardService $rewards) {}

    /**
     * Récupère ou crée la progression pour cet achievement.
     * Si l'achievement n'existe pas, retourne null.
     */
    public function track(User $user, string $key, int $increment = 1, ?int $target = null): ?UserAchievement
    {
        $achievement = Achievement::where('key', $key)->first();
        if (! $achievement) {
            return null;
        }

        return DB::transaction(function () use ($user, $achievement, $increment, $target) {
            $progress = UserAchievement::firstOrCreate(
                ['user_id' => $user->id, 'achievement_id' => $achievement->id],
                ['progress' => 0, 'completed' => false]
            );

            if ($progress->completed) {
                return $progress;
            }

            $progress->progress += $increment;

            // Le target peut être passé inline (pour achievements progressifs)
            // ou implicite (1 = boolean unlock)
            $effectiveTarget = $target ?? 1;
            if ($progress->progress >= $effectiveTarget) {
                $progress->completed     = true;
                $progress->completed_at  = now();
            }

            $progress->save();
            return $progress;
        });
    }

    public function claim(User $user, UserAchievement $progress, ?string $ipAddress = null): array
    {
        if ($progress->user_id !== $user->id) {
            throw new RuntimeException('Cet achievement ne te concerne pas.');
        }
        if (! $progress->completed) {
            throw new RuntimeException('Achievement non complété.');
        }
        if ($progress->reward_claimed) {
            throw new RuntimeException('Récompense déjà réclamée.');
        }

        $achievement = $progress->achievement;
        $rewards = is_array($achievement->rewards) ? $achievement->rewards : [];

        return DB::transaction(function () use ($user, $progress, $rewards, $achievement, $ipAddress) {
            if (! empty($rewards)) {
                $this->rewards->apply($user, $rewards, "achievement:{$achievement->key}", $achievement, $ipAddress);
            }

            $progress->update(['reward_claimed' => true]);

            return [
                'achievement_key' => $achievement->key,
                'rewards'         => $rewards,
            ];
        });
    }

    /**
     * Liste les achievements visibles pour le joueur.
     * Cache les hidden non débloqués.
     */
    public function listForUser(User $user): array
    {
        $progress = UserAchievement::where('user_id', $user->id)->get()->keyBy('achievement_id');

        return Achievement::orderBy('category')->orderBy('id')->get()
            ->filter(fn ($a) => ! $a->is_hidden || $progress->has($a->id))
            ->map(function ($a) use ($progress) {
                $p = $progress->get($a->id);
                return [
                    'id'             => $a->id,
                    'key'            => $a->key,
                    'title'          => $a->title,
                    'description'    => $a->description,
                    'category'       => $a->category,
                    'icon_url'       => $a->icon_url,
                    'rewards'        => $a->rewards,
                    'progress'       => $p?->progress ?? 0,
                    'completed'      => $p?->completed ?? false,
                    'reward_claimed' => $p?->reward_claimed ?? false,
                    'completed_at'   => $p?->completed_at,
                ];
            })
            ->values()
            ->toArray();
    }
}
