<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Force la régénération du sitemap.xml mis en cache (TTL 6h sinon).
 *
 * Scheduler `routes/console.php` : quotidienne 04:00 UTC pour garantir un
 * sitemap à jour pour les crawlers, même si aucune visite n'a renouvelé
 * le cache dans la journée.
 */
class RebuildSitemapCache extends Command
{
    protected $signature = 'sitemap:rebuild';
    protected $description = 'Régénère le cache du sitemap.xml (factions, opérateurs publiés, profils top).';

    public function handle(): int
    {
        Cache::forget('seo:sitemap:xml');
        // Force la construction immédiate (warm cache)
        app(SitemapController::class)->index();
        $this->info('Sitemap régénéré.');
        return self::SUCCESS;
    }
}
