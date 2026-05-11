<?php

namespace App\Services;

use App\Models\Cosmetic;
use App\Models\PlayerCosmetic;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Catalogue + inventaire cosmétiques côté joueur.
 *
 * Responsabilités :
 *  - unlock : crée la ligne player_cosmetics (idempotent)
 *  - unlockForAffinity : auto-grant des skins/voicelines liés à un opérateur
 *    quand le joueur atteint le niveau d'affinité requis
 *  - equip / unequip : règle "1 actif par type", sauf skins (1 actif par opérateur)
 */
class CosmeticService
{
    public function unlock(User $user, Cosmetic $cosmetic, string $source): bool
    {
        $existing = PlayerCosmetic::where('user_id', $user->id)
            ->where('cosmetic_id', $cosmetic->id)
            ->first();
        if ($existing) {
            return false;
        }

        PlayerCosmetic::create([
            'user_id'     => $user->id,
            'cosmetic_id' => $cosmetic->id,
            'unlocked_at' => now(),
            'source'      => $source,
            'is_equipped' => false,
        ]);
        return true;
    }

    /**
     * Pour un opérateur donné, débloque tous les cosmétiques dont
     * `unlock_at_affinity` est <= au niveau passé.
     *
     * @return array<int> liste des cosmetic.id nouvellement débloqués
     */
    public function unlockForAffinity(User $user, int $operatorId, int $newLevel): array
    {
        $candidates = Cosmetic::query()
            ->where('operator_id', $operatorId)
            ->whereNotNull('unlock_at_affinity')
            ->where('unlock_at_affinity', '<=', $newLevel)
            ->where('is_active', true)
            ->get();

        $newlyUnlocked = [];
        foreach ($candidates as $cosmetic) {
            if ($this->unlock($user, $cosmetic, 'affinity')) {
                $newlyUnlocked[] = $cosmetic->id;
            }
        }
        return $newlyUnlocked;
    }

    /**
     * Équipe un cosmétique :
     *  - title / voiceline / banner / border : un seul actif global
     *  - skin : un seul actif par opérateur
     */
    public function equip(User $user, Cosmetic $cosmetic): void
    {
        $owned = PlayerCosmetic::where('user_id', $user->id)
            ->where('cosmetic_id', $cosmetic->id)
            ->first();
        if (! $owned) {
            throw new RuntimeException('Tu ne possèdes pas ce cosmétique.');
        }

        DB::transaction(function () use ($user, $cosmetic, $owned) {
            if ($cosmetic->type === 'skin' && $cosmetic->operator_id) {
                PlayerCosmetic::query()
                    ->where('user_id', $user->id)
                    ->where('is_equipped', true)
                    ->whereIn('cosmetic_id', Cosmetic::query()
                        ->where('type', 'skin')
                        ->where('operator_id', $cosmetic->operator_id)
                        ->pluck('id'))
                    ->update(['is_equipped' => false]);
            } else {
                PlayerCosmetic::query()
                    ->where('user_id', $user->id)
                    ->where('is_equipped', true)
                    ->whereIn('cosmetic_id', Cosmetic::query()
                        ->where('type', $cosmetic->type)
                        ->pluck('id'))
                    ->update(['is_equipped' => false]);
            }

            $owned->update(['is_equipped' => true]);
        });
    }

    public function unequip(User $user, Cosmetic $cosmetic): void
    {
        PlayerCosmetic::where('user_id', $user->id)
            ->where('cosmetic_id', $cosmetic->id)
            ->update(['is_equipped' => false]);
    }

    /**
     * Renvoie l'inventaire complet du joueur, joint avec le catalogue.
     */
    public function inventory(User $user): Collection
    {
        return PlayerCosmetic::with(['cosmetic', 'cosmetic.operator:id,slug,name,codename'])
            ->where('user_id', $user->id)
            ->get()
            ->filter(fn ($pc) => $pc->cosmetic && $pc->cosmetic->is_active)
            ->map(fn ($pc) => [
                'id'           => $pc->cosmetic->id,
                'slug'         => $pc->cosmetic->slug,
                'name'         => $pc->cosmetic->name,
                'description'  => $pc->cosmetic->description,
                'type'         => $pc->cosmetic->type,
                'rarity'       => $pc->cosmetic->rarity,
                'preview_url'  => $pc->cosmetic->preview_url,
                'is_equipped'  => (bool) $pc->is_equipped,
                'unlocked_at'  => $pc->unlocked_at,
                'source'       => $pc->source,
                'operator'     => $pc->cosmetic->operator
                    ? ['name' => $pc->cosmetic->operator->name, 'codename' => $pc->cosmetic->operator->codename]
                    : null,
            ])
            ->values();
    }
}
