<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Applique une récompense (sous forme de tableau de {type, amount})
 * en créditant les currencies et en loguant chaque mouvement dans transactions.
 *
 * Exemples de rewards :
 *   [{type: shards,  amount: 50}]
 *   [{type: credits, amount: 100}, {type: tickets_premium, amount: 1}]
 *   [{type: fragments_VX-01, amount: 10}]
 *
 * Tout est encapsulé dans une DB::transaction par appel.
 */
class RewardService
{
    /**
     * @param array<int, array{type: string, amount: int}> $rewards
     * @param string $reason  Code court (daily_login, mission_reward, gacha_duplicate…)
     * @param Model|null $reference  Source pour audit (Mission, DailyLogin, GachaPull…)
     */
    public function apply(User $user, array $rewards, string $reason, ?Model $reference = null, ?string $ipAddress = null): void
    {
        if (empty($rewards)) {
            return;
        }

        DB::transaction(function () use ($user, $rewards, $reason, $reference, $ipAddress) {
            foreach ($rewards as $reward) {
                $type   = $reward['type'] ?? null;
                $amount = (int) ($reward['amount'] ?? 0);
                if (! $type || $amount <= 0) {
                    continue;
                }

                $currency = Currency::firstOrCreate(
                    ['user_id' => $user->id, 'type' => $type],
                    ['balance' => 0]
                );
                $locked = Currency::where('id', $currency->id)->lockForUpdate()->first();
                $locked->increment('balance', $amount);

                Transaction::create([
                    'user_id'        => $user->id,
                    'currency_type'  => $type,
                    'amount'         => $amount,
                    'balance_after'  => $locked->fresh()->balance,
                    'reason'         => $reason,
                    'reference_id'   => $reference?->getKey(),
                    'reference_type' => $reference ? $reference::class : null,
                    'ip_address'     => $ipAddress,
                ]);
            }
        });
    }
}
