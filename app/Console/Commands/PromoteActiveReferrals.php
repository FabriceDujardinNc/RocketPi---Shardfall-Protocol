<?php

namespace App\Console\Commands;

use App\Services\ReferralService;
use Illuminate\Console\Command;

/**
 * Balaye les parrainages PENDING dont la vérification email date d'au moins
 * `referrals.activity_delay_days` jours (défaut 7) et dont le filleul a
 * effectivement été actif (`last_active_at` non null) — les promeut à
 * `validated`, ce qui débloque les rewards parrain sur paliers de level.
 *
 * Programmée dans `routes/console.php` (daily 03:15 UTC).
 */
class PromoteActiveReferrals extends Command
{
    protected $signature = 'referrals:promote-active';
    protected $description = "Promeut les parrainages dont le filleul a validé sa présence active depuis N jours.";

    public function handle(ReferralService $service): int
    {
        $count = $service->promoteActiveSweep();
        $this->info("Referrals promus : {$count}");
        return self::SUCCESS;
    }
}
