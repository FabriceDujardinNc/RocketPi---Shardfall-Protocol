<?php

namespace App\Http\Controllers;

use App\Models\Faction;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Sitemap XML public.
 *
 * Liste les URLs indexables (lore, factions publiques, opérateurs publiés,
 * profils joueurs) au format `<urlset>` standard sitemaps.org.
 *
 * Cache 6h pour éviter de hammer la BDD à chaque crawl Googlebot.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('seo:sitemap:xml', 6 * 3600, fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    private function build(): string
    {
        $base = rtrim(config('app.url'), '/');
        $urls = [];

        // Static pages
        $urls[] = ['loc' => "{$base}/",         'priority' => '1.0', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => "{$base}/lore",     'priority' => '0.9', 'changefreq' => 'monthly'];
        $urls[] = ['loc' => "{$base}/leaderboard", 'priority' => '0.7', 'changefreq' => 'daily'];

        // Factions publiques (3 fixes)
        foreach (Faction::query()->get(['slug', 'updated_at']) as $faction) {
            $urls[] = [
                'loc'      => "{$base}/lore/factions/{$faction->slug}",
                'lastmod'  => optional($faction->updated_at)->toAtomString(),
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        // Opérateurs publiés (vitrines lore + stats sans données joueur)
        Operator::query()
            ->where('is_available', true)
            ->whereNull('deleted_at')
            ->get(['slug', 'updated_at'])
            ->each(function (Operator $op) use (&$urls, $base) {
                $urls[] = [
                    'loc'      => "{$base}/lore/operators/{$op->slug}",
                    'lastmod'  => optional($op->updated_at)->toAtomString(),
                    'priority' => '0.7',
                    'changefreq' => 'monthly',
                ];
            });

        // Profils joueurs publics (limite top 1000 par account_level pour éviter
        // d'exploser le sitemap si beaucoup de comptes — Google ne traitera pas
        // au-delà de 50 000 URLs / fichier de toute façon).
        User::query()
            ->whereNotNull('slug')
            ->where('is_banned', false)
            ->where('account_level', '>=', 5)
            ->orderByDesc('account_level')
            ->limit(1000)
            ->get(['slug', 'last_active_at'])
            ->each(function (User $u) use (&$urls, $base) {
                $urls[] = [
                    'loc'      => "{$base}/profile/{$u->slug}",
                    'lastmod'  => optional($u->last_active_at)->toAtomString(),
                    'priority' => '0.5',
                    'changefreq' => 'weekly',
                ];
            });

        return $this->renderXml($urls);
    }

    /**
     * @param  array<int,array{loc:string,lastmod?:?string,priority:string,changefreq:string}>  $urls
     */
    private function renderXml(array $urls): string
    {
        $entries = [];
        foreach ($urls as $u) {
            $entry = "  <url>\n    <loc>".htmlspecialchars($u['loc'], ENT_XML1)."</loc>\n";
            if (! empty($u['lastmod'])) {
                $entry .= "    <lastmod>".htmlspecialchars($u['lastmod'], ENT_XML1)."</lastmod>\n";
            }
            $entry .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            $entry .= "    <priority>{$u['priority']}</priority>\n  </url>";
            $entries[] = $entry;
        }

        $body = implode("\n", $entries);
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$body}
</urlset>

XML;
    }
}
