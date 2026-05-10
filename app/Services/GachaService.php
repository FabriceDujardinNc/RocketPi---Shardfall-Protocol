<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Currency;
use App\Models\GachaPull;
use App\Models\Operator;
use App\Models\PityCounter;
use App\Models\PlayerOperator;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Logique gacha 100% serveur. Tout calcul (drops, pity, rate-up) ici.
 * Tirages atomiques avec lockForUpdate sur PityCounter et Currency.
 *
 * Spec :
 *  - Taux Common 60% / Rare 30% / Epic 8% / Legendary 2%
 *  - Pity Légendaire : garanti à 80 (soft pity dès 60 — taux Légendaire boostés)
 *  - Pity Épique : garanti à 10
 *  - Bannières peuvent overrider taux et seuils via colonnes Banner
 *  - Rate-up : opérateurs listés dans `rate_up_operators` ont 50% chance
 *    d'être tirés au sein de leur rareté
 */
class GachaService
{
    public const COST_PER_PULL = 10;       // Shards par tirage simple
    public const COST_PER_TEN  = 100;      // Shards pour x10 (pas de rabais)

    /**
     * Effectue un tirage de N exemplaires. Atomique.
     *
     * @return array<int, array{pull: GachaPull, operator: Operator, is_new: bool}>
     */
    public function pull(User $user, Banner $banner, int $count, ?string $sessionId = null, ?string $ipAddress = null): array
    {
        if (! in_array($count, [1, 10], true)) {
            throw new RuntimeException('Seuls les tirages 1× et 10× sont supportés.');
        }
        if (! $banner->is_active) {
            throw new RuntimeException("Bannière #{$banner->id} non active.");
        }

        $cost = $count === 10 ? self::COST_PER_TEN : self::COST_PER_PULL * $count;

        return DB::transaction(function () use ($user, $banner, $count, $cost, $sessionId, $ipAddress) {
            // 1. Lock & vérif solde
            $wallet = Currency::where('user_id', $user->id)
                ->where('type', Currency::TYPE_SHARDS)
                ->lockForUpdate()
                ->first();

            if (! $wallet || $wallet->balance < $cost) {
                throw new RuntimeException("Solde insuffisant. Requis: {$cost} shards.");
            }

            // 2. Lock pity counter
            $pity = PityCounter::firstOrCreate(
                ['user_id' => $user->id, 'banner_id' => $banner->id],
                ['legendary_counter' => 0, 'epic_counter' => 0, 'total_pulls' => 0]
            );
            $pity = PityCounter::where('id', $pity->id)->lockForUpdate()->first();

            // 3. Effectue les N tirages
            $results = [];
            for ($i = 0; $i < $count; $i++) {
                $results[] = $this->singleDraw($user, $banner, $pity, $sessionId, $ipAddress);
            }

            // 4. Débit du solde + log Transaction
            $wallet->decrement('balance', $cost);
            Transaction::create([
                'user_id'        => $user->id,
                'currency_type'  => Currency::TYPE_SHARDS,
                'amount'         => -$cost,
                'balance_after'  => $wallet->balance,
                'reason'         => 'gacha_pull',
                'reference_id'   => $banner->id,
                'reference_type' => Banner::class,
                'description'    => "Tirage {$count}× sur {$banner->name}",
                'ip_address'     => $ipAddress,
            ]);

            return $results;
        });
    }

    /**
     * Un tirage unique. Met à jour pity + retourne le résultat.
     *
     * @return array{pull: GachaPull, operator: Operator, is_new: bool}
     */
    private function singleDraw(User $user, Banner $banner, PityCounter $pity, ?string $sessionId, ?string $ipAddress): array
    {
        $rarity = $this->rollRarity($banner, $pity);
        $wasPityHit = ($rarity === 'legendary' && $pity->legendary_counter + 1 >= $banner->pity_legendary)
                   || ($rarity === 'epic' && $pity->epic_counter + 1 >= $banner->pity_epic);
        $wasSoftPity = $rarity === 'legendary' && $pity->legendary_counter >= $banner->soft_pity_start;

        $pityBefore = $pity->legendary_counter;

        // Sélection de l'opérateur dans la rareté (avec rate-up si applicable)
        [$operator, $wasRateUp] = $this->pickOperator($banner, $rarity);

        // Update pity
        $pity->total_pulls++;
        if ($rarity === 'legendary') {
            $pity->legendary_counter = 0;
            $pity->epic_counter++;     // on incrémente quand même : un legendary inclut un epic
        } elseif ($rarity === 'epic') {
            $pity->epic_counter = 0;
            $pity->legendary_counter++;
        } else {
            $pity->legendary_counter++;
            $pity->epic_counter++;
        }
        $pity->save();

        // Log gacha (audit légal — immuable)
        $pull = GachaPull::create([
            'user_id'           => $user->id,
            'banner_id'         => $banner->id,
            'operator_id'       => $operator->id,
            'rarity'            => $rarity,
            'currency_used'     => Currency::TYPE_SHARDS,
            'cost'              => self::COST_PER_PULL,
            'pity_count_before' => $pityBefore,
            'was_pity_hit'      => $wasPityHit,
            'was_soft_pity'     => $wasSoftPity,
            'was_rate_up'       => $wasRateUp,
            'session_id'        => $sessionId,
            'ip_address'        => $ipAddress,
        ]);

        // Update collection
        $playerOp = PlayerOperator::firstOrCreate(
            ['user_id' => $user->id, 'operator_id' => $operator->id],
            ['duplicate_count' => 0, 'obtained_at' => now()]
        );
        $isNew = $playerOp->wasRecentlyCreated;
        if (! $isNew) {
            $playerOp->increment('duplicate_count');
        }

        return ['pull' => $pull, 'operator' => $operator, 'is_new' => $isNew];
    }

    /**
     * Détermine la rareté de ce tirage en tenant compte du pity.
     */
    private function rollRarity(Banner $banner, PityCounter $pity): string
    {
        // Pity Légendaire garanti
        if ($pity->legendary_counter + 1 >= $banner->pity_legendary) {
            return 'legendary';
        }

        // Pity Épique garanti (mais peut être upgrade en légendaire par hard pity)
        if ($pity->epic_counter + 1 >= $banner->pity_epic) {
            return 'epic';
        }

        // Soft pity : taux légendaire boostés progressivement entre soft_pity_start et pity_legendary
        $rateLegendary = (float) $banner->rate_legendary;
        if ($pity->legendary_counter >= $banner->soft_pity_start) {
            $progress = ($pity->legendary_counter - $banner->soft_pity_start)
                      / max(1, $banner->pity_legendary - $banner->soft_pity_start);
            $rateLegendary = $rateLegendary + ($progress * (1.0 - $rateLegendary));
        }

        $roll = mt_rand(0, 9999) / 10000;   // [0..1)
        $cumLegendary = $rateLegendary;
        $cumEpic      = $cumLegendary + (float) $banner->rate_epic;
        $cumRare      = $cumEpic + (float) $banner->rate_rare;

        if ($roll < $cumLegendary) return 'legendary';
        if ($roll < $cumEpic)      return 'epic';
        if ($roll < $cumRare)      return 'rare';
        return 'common';
    }

    /**
     * Pioche un opérateur de la rareté demandée. Si la bannière a des
     * rate-up et que la rareté est epic ou legendary, 50% chance que
     * ce soit un rate-up.
     *
     * @return array{0: Operator, 1: bool} [operator, wasRateUp]
     */
    private function pickOperator(Banner $banner, string $rarity): array
    {
        $rateUpCodenames = $banner->rate_up_operators ?? [];

        if (in_array($rarity, ['epic', 'legendary'], true) && ! empty($rateUpCodenames) && mt_rand(0, 1) === 1) {
            $rateUpPool = Operator::where('rarity', $rarity)
                ->where('is_available', true)
                ->whereIn('codename', $rateUpCodenames)
                ->get();
            if ($rateUpPool->isNotEmpty()) {
                return [$rateUpPool->random(), true];
            }
        }

        $pool = Operator::where('rarity', $rarity)->where('is_available', true)->get();
        if ($pool->isEmpty()) {
            // Fallback sur common si la rareté n'a aucun opérateur disponible
            $pool = Operator::where('rarity', 'common')->where('is_available', true)->get();
        }
        if ($pool->isEmpty()) {
            throw new RuntimeException("Aucun opérateur disponible pour rareté {$rarity}.");
        }

        return [$pool->random(), false];
    }
}
