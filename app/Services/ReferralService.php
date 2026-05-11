<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\ReferralReward;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Système de parrainage RocketPi.
 *
 * Flow :
 *  1. À l'inscription avec code → createForNewUser() crée Referral{status=pending}
 *  2. À la vérif email → validateOnEmailVerified() crée le pack starter
 *     pour le filleul, ouvre les paliers du parrain
 *  3. Quand le filleul level-up (via XpService) → checkLevelMilestones()
 *     crée les ReferralReward parrain pour niv 5/15/30
 *  4. Le bénéficiaire clique "Réclamer" → claim() applique les rewards
 *
 * Anti-abuse :
 *  - Max 50 parrainages actifs (validated/rewarded) par parrain
 *  - Auto-flag si même IP que parrain
 *  - Aucune récompense liée au monde réel
 */
class ReferralService
{
    public const MAX_ACTIVE_REFERRALS_PER_USER = 50;

    /**
     * Mapping trigger → liste de rewards à appliquer (currencies via RewardService).
     * Les opérateurs au choix (epic/legendary) sont stockés comme tickets
     * spéciaux à échanger en boutique.
     */
    public const REWARDS_BY_TRIGGER = [
        ReferralReward::TRIGGER_REFEREE_EMAIL_VERIFIED => [
            ['type' => 'shards', 'amount' => 500],
            ['type' => 'tickets_standard', 'amount' => 5],
            ['type' => 'tokens_rare_choice', 'amount' => 1],
        ],
        ReferralReward::TRIGGER_REFEREE_LEVEL_5 => [
            ['type' => 'tickets_premium', 'amount' => 10],
        ],
        ReferralReward::TRIGGER_REFEREE_LEVEL_15 => [
            ['type' => 'shards', 'amount' => 1000],
            ['type' => 'tokens_epic_choice', 'amount' => 1],
        ],
        ReferralReward::TRIGGER_REFEREE_LEVEL_30 => [
            ['type' => 'tokens_legendary_choice', 'amount' => 1],
        ],
    ];

    public function __construct(private readonly RewardService $rewards) {}

    /**
     * Crée la relation parrain ↔ filleul à l'inscription.
     * Auto-flag si IP suspecte.
     */
    public function createForNewUser(User $referrer, User $referee, ?string $ip, ?string $fingerprint = null): Referral
    {
        $activeCount = Referral::where('referrer_id', $referrer->id)
            ->whereIn('status', [Referral::STATUS_VALIDATED, Referral::STATUS_REWARDED])
            ->count();

        if ($activeCount >= self::MAX_ACTIVE_REFERRALS_PER_USER) {
            throw new RuntimeException('Limite de parrainages actifs atteinte pour ce parrain.');
        }

        $sameIp = $ip && Referral::where('referee_ip', $ip)
            ->where('referrer_id', $referrer->id)
            ->exists();

        $referral = Referral::create([
            'referrer_id'         => $referrer->id,
            'referee_id'          => $referee->id,
            'status'              => $sameIp ? Referral::STATUS_FLAGGED : Referral::STATUS_PENDING,
            'referee_ip'          => $ip,
            'referee_fingerprint' => $fingerprint,
            'same_ip_as_referrer' => (bool) $sameIp,
            'flagged_at'          => $sameIp ? now() : null,
            'flag_reason'         => $sameIp ? 'IP déjà utilisée par un autre filleul du même parrain' : null,
        ]);

        return $referral;
    }

    /**
     * Appelé après la vérification email.
     * - Marque validated
     * - Crée les ReferralReward pour le filleul (starter pack)
     */
    public function validateOnEmailVerified(User $referee): ?Referral
    {
        $referral = Referral::where('referee_id', $referee->id)->first();
        if (! $referral || $referral->status !== Referral::STATUS_PENDING) {
            return $referral;
        }

        return DB::transaction(function () use ($referral, $referee) {
            $referral->update([
                'status'       => Referral::STATUS_VALIDATED,
                'validated_at' => now(),
            ]);

            // Reward filleul (starter pack)
            ReferralReward::firstOrCreate(
                ['referral_id' => $referral->id, 'trigger' => ReferralReward::TRIGGER_REFEREE_EMAIL_VERIFIED],
                [
                    'beneficiary_id' => $referee->id,
                    'reward_type'    => 'starter_pack',
                    'reward_amount'  => 500,
                ]
            );

            return $referral;
        });
    }

    /**
     * Appelé sur level-up. Si le filleul atteint 5/15/30, crée la
     * récompense correspondante pour le parrain (en attente de claim).
     */
    public function checkLevelMilestones(User $referee, int $previousLevel, int $newLevel): void
    {
        if ($newLevel <= $previousLevel) {
            return;
        }

        $referral = Referral::where('referee_id', $referee->id)
            ->whereIn('status', [Referral::STATUS_VALIDATED, Referral::STATUS_REWARDED])
            ->first();
        if (! $referral) {
            return;
        }

        $milestones = [
            5  => [ReferralReward::TRIGGER_REFEREE_LEVEL_5,  'tickets_premium', 10],
            15 => [ReferralReward::TRIGGER_REFEREE_LEVEL_15, 'epic_pack',       null],
            30 => [ReferralReward::TRIGGER_REFEREE_LEVEL_30, 'legendary_pack',  null],
        ];

        foreach ($milestones as $level => [$trigger, $type, $amount]) {
            if ($previousLevel < $level && $newLevel >= $level) {
                ReferralReward::firstOrCreate(
                    ['referral_id' => $referral->id, 'trigger' => $trigger],
                    [
                        'beneficiary_id' => $referral->referrer_id,
                        'reward_type'    => $type,
                        'reward_amount'  => $amount,
                    ]
                );
            }
        }
    }

    /**
     * Premier achat payant du filleul → +50% des shards dépensés crédités au
     * parrain (one-shot par filleul). Appelé depuis ShopService::purchasePack.
     */
    public function registerFirstPurchase(User $referee, int $shardsSpent): ?ReferralReward
    {
        if ($shardsSpent <= 0) {
            return null;
        }

        $referral = Referral::where('referee_id', $referee->id)
            ->whereIn('status', [Referral::STATUS_VALIDATED, Referral::STATUS_REWARDED])
            ->first();
        if (! $referral) {
            return null;
        }

        $existing = ReferralReward::where('referral_id', $referral->id)
            ->where('trigger', ReferralReward::TRIGGER_REFEREE_FIRST_PURCHASE)
            ->first();
        if ($existing) {
            return null;
        }

        return ReferralReward::create([
            'referral_id'    => $referral->id,
            'beneficiary_id' => $referral->referrer_id,
            'trigger'        => ReferralReward::TRIGGER_REFEREE_FIRST_PURCHASE,
            'reward_type'    => 'shards',
            'reward_amount'  => (int) floor($shardsSpent * 0.5),
        ]);
    }

    public function claim(User $user, ReferralReward $reward, ?string $ipAddress = null): array
    {
        if ($reward->beneficiary_id !== $user->id) {
            throw new RuntimeException('Cette récompense ne te concerne pas.');
        }
        if ($reward->claimed) {
            throw new RuntimeException('Récompense déjà réclamée.');
        }

        $rewardsList = $this->rewardsListFor($reward);
        if (empty($rewardsList)) {
            throw new RuntimeException("Trigger inconnu ou récompense vide: {$reward->trigger}");
        }

        return DB::transaction(function () use ($user, $reward, $rewardsList, $ipAddress) {
            $this->rewards->apply($user, $rewardsList, "referral:{$reward->trigger}", $reward, $ipAddress);

            $reward->update(['claimed' => true, 'claimed_at' => now()]);

            // Marque le referral comme rewarded si toutes les rewards sont distribuées
            $referral = $reward->referral;
            $allClaimed = $referral->rewards()->where('claimed', false)->doesntExist();
            if ($allClaimed && $referral->status !== Referral::STATUS_REWARDED) {
                $referral->update(['status' => Referral::STATUS_REWARDED]);
            }

            return [
                'reward_id' => $reward->id,
                'rewards'   => $rewardsList,
            ];
        });
    }

    /**
     * Liste des rewards en attente pour un user (parrain ou filleul).
     */
    public function pendingRewardsFor(User $user): array
    {
        return ReferralReward::with(['referral.referee:id,name,email,display_name'])
            ->where('beneficiary_id', $user->id)
            ->where('claimed', false)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'trigger'     => $r->trigger,
                'reward_type' => $r->reward_type,
                'rewards'     => $this->rewardsListFor($r),
                'created_at'  => $r->created_at,
                'referee'     => $r->referral?->referee?->only(['id', 'name', 'display_name']),
            ])
            ->toArray();
    }

    /**
     * Pour les triggers à reward fixe, on lit REWARDS_BY_TRIGGER.
     * Pour le first_purchase (montant dynamique), on lit reward_type/amount sur la ligne.
     */
    private function rewardsListFor(ReferralReward $reward): array
    {
        if ($reward->trigger === ReferralReward::TRIGGER_REFEREE_FIRST_PURCHASE) {
            return [[
                'type'   => $reward->reward_type ?: 'shards',
                'amount' => (int) ($reward->reward_amount ?? 0),
            ]];
        }
        return self::REWARDS_BY_TRIGGER[$reward->trigger] ?? [];
    }
}
