<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Sitemap XML public.
 *
 * Site simplifié : on liste juste les pages publiques (landing, dons, idées).
 * Cache 6h.
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
        $urls = [
            ['loc' => "{$base}/",      'priority' => '1.0', 'changefreq' => 'weekly'],
            // TODO Phase 3 : décommenter dès que les pages existent.
            // ['loc' => "{$base}/dons",  'priority' => '0.8', 'changefreq' => 'monthly'],
            // ['loc' => "{$base}/idees", 'priority' => '0.7', 'changefreq' => 'weekly'],
        ];

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
