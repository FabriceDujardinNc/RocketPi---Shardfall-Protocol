<?php

namespace App\Services;

use App\Models\Operator;
use App\Models\OperatorAffinity;
use App\Models\User;

/**
 * Affinité par opérateur (0-10).
 *
 * Spec : XP par opérateur via utilisation, débloque progressivement
 * lore/skins/voicelines.
 *
 * En l'absence de gameplay (Phase 4), l'XP affinité est gagnée :
 *   - Tirage de l'opérateur (nouveau ou doublon) — 25 XP
 *   - Doublon (déjà obtenu)                       — 10 XP supplémentaires
 *
 * Formule level-up : niveau N → N+1 = 100 × N XP
 *   Niv 0 → 1 : 100 XP
 *   Niv 5 → 6 : 500 XP
 *   Niv 9 → 10 : 900 XP
 */
class AffinityService
{
    public const MAX_LEVEL = 10;
    public const XP_PER_PULL = 25;
    public const XP_PER_DUPLICATE = 10;

    public function __construct(private readonly CosmeticService $cosmetics) {}

    public function award(User $user, Operator $operator, int $xp): array
    {
        if ($xp <= 0) {
            return ['xp_gained' => 0, 'leveled_up' => false, 'new_level' => 0, 'cosmetics_unlocked' => []];
        }

        $affinity = OperatorAffinity::firstOrCreate(
            ['user_id' => $user->id, 'operator_id' => $operator->id],
            ['level' => 0, 'xp_current' => 0, 'unlocked_rewards' => []]
        );

        $startLevel = $affinity->level;
        $affinity->xp_current += $xp;

        while ($affinity->level < self::MAX_LEVEL && $affinity->xp_current >= $this->thresholdFor($affinity->level)) {
            $affinity->xp_current -= $this->thresholdFor($affinity->level);
            $affinity->level++;
        }

        if ($affinity->level >= self::MAX_LEVEL) {
            $affinity->xp_current = 0;
        }

        $affinity->save();

        // Hook cosmétiques : tout palier franchi peut débloquer skin/voiceline associés.
        $unlocked = [];
        if ($affinity->level > $startLevel) {
            $unlocked = $this->cosmetics->unlockForAffinity($user, $operator->id, $affinity->level);
        }

        return [
            'xp_gained'          => $xp,
            'leveled_up'         => $affinity->level > $startLevel,
            'new_level'          => $affinity->level,
            'cosmetics_unlocked' => $unlocked,
        ];
    }

    public function thresholdFor(int $level): int
    {
        return 100 * max(1, $level + 1);
    }
}
