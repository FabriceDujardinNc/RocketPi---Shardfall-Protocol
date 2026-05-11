<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Operator;
use App\Models\PlayerOperator;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Boutique RocketPi.
 *
 * Phase 3 : packs hardcodés (starter, événementiels, fragments → opérateur).
 * Phase 5 : intégration Stripe via Cashier pour les achats réels en €.
 *
 * Pour l'instant, deux modes :
 *  - Pack achetable avec shards in-game (purchaseWithShards)
 *  - Échange fragments → opérateur ciblé (exchangeFragments)
 */
class ShopService
{
    public function __construct(
        private readonly RewardService $rewards,
        private readonly ReferralService $referrals,
    ) {}

    /**
     * Liste statique des packs disponibles. À déplacer en BDD plus tard
     * (modèle ShopPack avec admin CRUD pour packs événementiels).
     */
    public const PACKS = [
        'starter_basic' => [
            'name'        => 'Pack starter — Recrue',
            'description' => 'Pour bien démarrer ton parcours.',
            'price_shards' => 0,    // Gratuit, à un seul achat (à coder)
            'is_one_shot' => true,
            'rewards' => [
                ['type' => 'shards', 'amount' => 200],
                ['type' => 'credits', 'amount' => 1000],
                ['type' => 'tickets_standard', 'amount' => 3],
            ],
        ],
        'shards_pack_small' => [
            'name'        => 'Pack Shards — 100',
            'description' => '100 shards premium pour tes recrutements.',
            'price_shards' => 0,    // Réel : payant via Stripe — pour dev affiché en démo
            'is_one_shot' => false,
            'rewards' => [
                ['type' => 'shards', 'amount' => 100],
            ],
        ],
        'event_apex' => [
            'name'        => 'Pack Apex Commandant',
            'description' => 'Pack événementiel : 1 ticket premium garanti.',
            'price_shards' => 500,
            'is_one_shot' => false,
            'rewards' => [
                ['type' => 'tickets_premium', 'amount' => 1],
                ['type' => 'shards', 'amount' => 50],
            ],
        ],
    ];

    /**
     * Coût en fragments pour échanger contre l'opérateur.
     */
    public const FRAGMENTS_TO_OPERATOR = [
        'common'    => 30,
        'rare'      => 80,
        'epic'      => 200,
        'legendary' => 500,
    ];

    public function listPacks(User $user): array
    {
        $shards = (int) (Currency::where('user_id', $user->id)
            ->where('type', Currency::TYPE_SHARDS)
            ->value('balance') ?? 0);

        return collect(self::PACKS)->map(fn ($pack, $id) => array_merge($pack, [
            'id'          => $id,
            'affordable'  => $shards >= ($pack['price_shards'] ?? 0),
        ]))->values()->toArray();
    }

    public function purchasePack(User $user, string $packId, ?string $ipAddress = null): array
    {
        if (! isset(self::PACKS[$packId])) {
            throw new RuntimeException("Pack inconnu : {$packId}");
        }
        $pack = self::PACKS[$packId];

        return DB::transaction(function () use ($user, $pack, $packId, $ipAddress) {
            $price = (int) ($pack['price_shards'] ?? 0);

            if ($price > 0) {
                $wallet = Currency::where('user_id', $user->id)
                    ->where('type', Currency::TYPE_SHARDS)
                    ->lockForUpdate()
                    ->first();

                if (! $wallet || $wallet->balance < $price) {
                    throw new RuntimeException("Solde insuffisant. Requis : {$price} shards.");
                }

                $wallet->decrement('balance', $price);
                Transaction::create([
                    'user_id'        => $user->id,
                    'currency_type'  => Currency::TYPE_SHARDS,
                    'amount'         => -$price,
                    'balance_after'  => $wallet->balance,
                    'reason'         => 'shop_purchase',
                    'description'    => "Achat pack {$pack['name']}",
                    'ip_address'     => $ipAddress,
                ]);
            }

            $this->rewards->apply($user, $pack['rewards'], "shop:{$packId}", null, $ipAddress);

            // Hook parrainage : premier achat payant du filleul → +50% prem au parrain.
            // registerFirstPurchase est idempotent (no-op si la ligne existe déjà).
            if ($price > 0) {
                $this->referrals->registerFirstPurchase($user, $price);
            }

            return [
                'pack_id' => $packId,
                'rewards' => $pack['rewards'],
            ];
        });
    }

    /**
     * Échange des fragments d'un opérateur contre l'opérateur lui-même
     * (ou un cran de constellation si déjà possédé).
     *
     * Le coût varie selon la rareté (cf. FRAGMENTS_TO_OPERATOR).
     * Le compteur de fragments est stocké dans Currency::type = "fragments_<codename>".
     *
     * Atomique : lockForUpdate sur la row Currency.
     *
     * @return array{operator_id:int, is_new:bool, constellation:int, fragments_used:int, fragments_left:int}
     */
    public function redeemFragments(User $user, Operator $operator, ?string $ipAddress = null): array
    {
        $cost = self::FRAGMENTS_TO_OPERATOR[$operator->rarity] ?? null;
        if ($cost === null) {
            throw new RuntimeException("Rareté inconnue : {$operator->rarity}");
        }

        $currencyType = 'fragments_'.$operator->codename;

        return DB::transaction(function () use ($user, $operator, $cost, $currencyType, $ipAddress) {
            $wallet = Currency::where('user_id', $user->id)
                ->where('type', $currencyType)
                ->lockForUpdate()
                ->first();

            if (! $wallet || $wallet->balance < $cost) {
                $have = $wallet?->balance ?? 0;
                throw new RuntimeException("Fragments insuffisants : {$have}/{$cost}.");
            }

            $wallet->decrement('balance', $cost);

            Transaction::create([
                'user_id'        => $user->id,
                'currency_type'  => $currencyType,
                'amount'         => -$cost,
                'balance_after'  => $wallet->balance,
                'reason'         => 'shop_fragments_redeem',
                'description'    => "Échange fragments → {$operator->name}",
                'ip_address'     => $ipAddress,
            ]);

            $playerOp = PlayerOperator::firstOrCreate(
                ['user_id' => $user->id, 'operator_id' => $operator->id],
                ['duplicate_count' => 0, 'constellation' => 0, 'obtained_at' => now()]
            );
            $isNew = $playerOp->wasRecentlyCreated;
            if (! $isNew) {
                $playerOp->increment('constellation');
            }

            return [
                'operator_id'    => $operator->id,
                'is_new'         => $isNew,
                'constellation'  => $playerOp->fresh()->constellation,
                'fragments_used' => $cost,
                'fragments_left' => $wallet->balance,
            ];
        });
    }
}
