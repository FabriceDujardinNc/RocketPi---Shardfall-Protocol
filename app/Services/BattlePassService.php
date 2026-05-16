<?php

namespace App\Services;

use App\Models\BattlePass;
use App\Models\BattlePassProgress;
use App\Models\BattlePassTier;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Battle Pass saisonnier — 50 paliers, free + premium (~10€ = 1000 shards).
 *
 * Spec :
 *  - 50 paliers, 8 semaines de durée
 *  - Free track + Premium track (achat optionnel)
 *  - Paliers spéciaux : 5, 10, 25, 50 (milestone)
 *  - Progression XP : récupère l'XP gagnée par le joueur (pull, mission)
 *
 * Flow :
 *  - progressXp() : à chaque action, on add XP au BP actif
 *  - purchase() : débite les shards, marque progress.is_premium=true
 *  - claim() : applique la récompense du tier (free + premium si payé)
 */
class BattlePassService
{
    public function __construct(private readonly RewardService $rewards) {}

    public function activeBattlePass(): ?BattlePass
    {
        return BattlePass::with('tiers')
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->first();
    }

    public function progressFor(User $user, BattlePass $bp): BattlePassProgress
    {
        return BattlePassProgress::firstOrCreate(
            ['user_id' => $user->id, 'battle_pass_id' => $bp->id],
            ['xp_earned' => 0, 'current_tier' => 0, 'claimed_tiers' => []]
        );
    }

    /**
     * Ajoute de l'XP au BP actif. Met à jour current_tier en conséquence.
     * Appelé depuis XpService::award() (chaque XP compte aussi pour le BP).
     */
    public function addXp(User $user, int $xp): ?BattlePassProgress
    {
        if ($xp <= 0) return null;

        $bp = $this->activeBattlePass();
        if (! $bp) return null;

        return DB::transaction(function () use ($user, $bp, $xp) {
            $progress = $this->progressFor($user, $bp);
            $progress->xp_earned += $xp;

            // Recalcule current_tier
            $tiers = BattlePassTier::where('battle_pass_id', $bp->id)
                ->where('xp_required', '<=', $progress->xp_earned)
                ->orderByDesc('tier_number')
                ->first();

            if ($tiers) {
                $progress->current_tier = $tiers->tier_number;
            }

            $progress->save();
            return $progress;
        });
    }

    /**
     * Achète l'upgrade premium pour la saison active.
     *
     * @param string $currency Currency::TYPE_SHARDS (cash equivalent) ou
     *                         Currency::TYPE_TICKETS_PREMIUM (parcours F2P).
     */
    public function purchase(
        User $user,
        BattlePass $bp,
        ?string $ipAddress = null,
        string $currency = Currency::TYPE_SHARDS,
    ): BattlePassProgress {
        if (! $bp->is_active) {
            throw new RuntimeException('Battle Pass non actif.');
        }

        if (! in_array($currency, [Currency::TYPE_SHARDS, Currency::TYPE_TICKETS_PREMIUM], true)) {
            throw new RuntimeException("Currency invalide pour l'achat du Battle Pass.");
        }

        $cost = $currency === Currency::TYPE_TICKETS_PREMIUM
            ? $bp->premium_price_tickets
            : $bp->premium_price_shards;
        $unit = $currency === Currency::TYPE_TICKETS_PREMIUM ? 'tickets premium' : 'shards';

        return DB::transaction(function () use ($user, $bp, $ipAddress, $currency, $cost, $unit) {
            $wallet = Currency::where('user_id', $user->id)
                ->where('type', $currency)
                ->lockForUpdate()
                ->first();

            if (! $wallet || $wallet->balance < $cost) {
                throw new RuntimeException("Solde insuffisant. Requis : {$cost} {$unit}.");
            }

            $progress = $this->progressFor($user, $bp);
            if ($progress->is_premium) {
                throw new RuntimeException('Battle Pass premium déjà acheté.');
            }

            $wallet->decrement('balance', $cost);
            Transaction::create([
                'user_id'        => $user->id,
                'currency_type'  => $currency,
                'amount'         => -$cost,
                'balance_after'  => $wallet->balance,
                'reason'         => 'battlepass_purchase',
                'reference_id'   => $bp->id,
                'reference_type' => BattlePass::class,
                'description'    => "Achat Battle Pass premium — {$bp->name} ({$cost} {$unit})",
                'ip_address'     => $ipAddress,
            ]);

            $progress->update([
                'is_premium'   => true,
                'purchased_at' => now(),
            ]);

            return $progress;
        });
    }

    /**
     * Réclame les rewards d'un palier (free + premium si éligible).
     */
    public function claim(User $user, BattlePassTier $tier, ?string $ipAddress = null): array
    {
        $bp = $tier->battlePass;
        $progress = $this->progressFor($user, $bp);

        if ($progress->current_tier < $tier->tier_number) {
            throw new RuntimeException("Palier {$tier->tier_number} pas encore atteint.");
        }

        $claimed = $progress->claimed_tiers ?? [];
        if (in_array($tier->tier_number, $claimed, true)) {
            throw new RuntimeException("Palier {$tier->tier_number} déjà réclamé.");
        }

        return DB::transaction(function () use ($user, $tier, $progress, $claimed, $ipAddress) {
            $appliedFree    = [];
            $appliedPremium = [];

            if ($tier->free_reward) {
                $this->rewards->apply($user, $tier->free_reward, "battlepass_tier_{$tier->tier_number}_free", $tier, $ipAddress);
                $appliedFree = $tier->free_reward;
            }

            if ($progress->is_premium && $tier->premium_reward) {
                $this->rewards->apply($user, $tier->premium_reward, "battlepass_tier_{$tier->tier_number}_premium", $tier, $ipAddress);
                $appliedPremium = $tier->premium_reward;
            }

            $claimed[] = $tier->tier_number;
            $progress->update(['claimed_tiers' => array_values(array_unique($claimed))]);

            return [
                'tier_number' => $tier->tier_number,
                'free'        => $appliedFree,
                'premium'     => $appliedPremium,
            ];
        });
    }
}
