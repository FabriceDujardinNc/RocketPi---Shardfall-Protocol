<?php

namespace App\Services;

use App\Models\User;

/**
 * Système d'XP / niveaux compte.
 *
 * Spec : niveaux 1 à 60+, croissance linéaire simple.
 * Formule : pour passer du niveau N au niveau N+1, il faut 100 × N XP.
 *   Niveau 1 → 2 : 100 XP
 *   Niveau 2 → 3 : 200 XP
 *   Niveau N → N+1 : 100×N XP
 *
 * `account_xp` stocke l'XP cumulée DANS le niveau actuel (pas total).
 * `account_level` est plafonné à 99.
 */
class XpService
{
    public const MAX_LEVEL = 99;

    public function award(User $user, int $xp): array
    {
        if ($xp <= 0) {
            return ['xp_gained' => 0, 'leveled_up' => false, 'new_level' => $user->account_level];
        }

        $startLevel = $user->account_level;
        $user->account_xp += $xp;

        while ($user->account_level < self::MAX_LEVEL && $user->account_xp >= $this->thresholdFor($user->account_level)) {
            $user->account_xp -= $this->thresholdFor($user->account_level);
            $user->account_level++;
        }
        if ($user->account_level >= self::MAX_LEVEL) {
            $user->account_xp = 0;
        }
        $user->save();

        return [
            'xp_gained'  => $xp,
            'leveled_up' => $user->account_level > $startLevel,
            'new_level'  => $user->account_level,
        ];
    }

    public function thresholdFor(int $level): int
    {
        return 100 * max(1, $level);
    }
}
