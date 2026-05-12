<?php

namespace App\Services;

use App\Models\User;

/**
 * Système de classement compétitif Bronze → Master (Phase 4).
 *
 * Le tier est **dérivé** de `users.rank_points` (MMR). Stocker uniquement
 * les points évite les désync de promotion/relégation.
 *
 * Échelle (ajustable selon analytics) :
 *   0    – 199  → Bronze    (5 paliers)
 *   200  – 499  → Argent
 *   500  – 999  → Or
 *   1000 – 1499 → Platine
 *   1500 – 2199 → Diamant
 *   2200+       → Maître (top 0.5%)
 */
class RankingService
{
    /** Bornes basses inclusives → label tier. */
    public const TIERS = [
        2200 => 'master',
        1500 => 'diamond',
        1000 => 'platinum',
        500  => 'gold',
        200  => 'silver',
        0    => 'bronze',
    ];

    /** Gain de base par victoire en ranked. */
    public const POINTS_WIN     = 25;
    /** Perte de base par défaite en ranked. */
    public const POINTS_LOSS    = -15;
    /** Bonus MVP additionnel (s'ajoute à win/loss). */
    public const POINTS_MVP     = 10;
    /** Plafond quotidien anti-farm (clipping côté addPoints LeaderboardService déjà en place). */
    public const DAILY_MATCH_LIMIT = 50;

    public function tierFor(int $points): string
    {
        $points = max(0, $points);
        foreach (self::TIERS as $threshold => $label) {
            if ($points >= $threshold) {
                return $label;
            }
        }
        return 'bronze';
    }

    /**
     * Calcule le delta de points pour un résultat de match.
     * Gestion défensive : aucune négative pour les utilisateurs à 0 (floor),
     * pour ne pas désespérer les nouveaux joueurs.
     */
    public function pointsDelta(bool $won, bool $isMvp, int $currentPoints): int
    {
        $delta = $won ? self::POINTS_WIN : self::POINTS_LOSS;
        if ($isMvp) {
            $delta += self::POINTS_MVP;
        }

        // Floor à 0 : un joueur ne descend pas en dessous de 0 sur une seule défaite.
        if ($currentPoints + $delta < 0) {
            $delta = -$currentPoints;
        }

        return $delta;
    }

    /**
     * Applique un delta de points au user (atomique côté caller via lockForUpdate).
     */
    public function applyDelta(User $user, int $delta): int
    {
        $newPoints = max(0, $user->rank_points + $delta);
        $user->forceFill(['rank_points' => $newPoints])->save();
        return $newPoints;
    }

    /**
     * Renvoie la prochaine borne pour afficher la progression vers le tier supérieur.
     * Renvoie null si déjà Maître.
     */
    public function nextTierThreshold(int $points): ?int
    {
        $sorted = array_keys(self::TIERS);
        sort($sorted);
        foreach ($sorted as $threshold) {
            if ($points < $threshold) {
                return $threshold;
            }
        }
        return null; // Master = pas de cap
    }
}
