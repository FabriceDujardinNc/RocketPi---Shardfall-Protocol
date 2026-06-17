import { Head, usePage } from '@inertiajs/react';

interface JsonLd {
    [key: string]: unknown;
}

interface Props {
    /** Titre court de la page (sera suffixé avec le nom du jeu). Mets `null` pour utiliser le titre brut sans suffixe. */
    title?: string | null;
    /** Meta description (155 chars max idéalement) — vital SEO. */
    description?: string;
    /** Liste de mots-clés pour les moteurs (utilité moderne faible mais sans coût). */
    keywords?: string;
    /** Image absolue pour OG / Twitter. Recommandé 1200x630. */
    image?: string;
    /** URL canonique. Si omis, dérive de l'URL actuelle. */
    canonical?: string;
    /** Type Open Graph. Par défaut `website`, `article` pour pages narratives. */
    type?: 'website' | 'article' | 'profile' | 'video.other';
    /** Empêche l'indexation (pages joueur privées, admin…). */
    noindex?: boolean;
    /** JSON-LD additionnel (Organization, VideoGame, Breadcrumb…). Tableau ou objet. */
    jsonLd?: JsonLd | JsonLd[];
}

const SITE_NAME = 'RocketPi: Shardfall Protocol';
const DEFAULT_DESCRIPTION = 'Hero-shooter avec recrutement gacha, classements compétitifs et trois factions : ORBIT, FERRO, VEIL. Recrute des Opérateurs, monte ton affinité, gagne ta place dans le Hall of Fame.';
const DEFAULT_IMAGE = '/og-default.jpg';

/**
 * Composant SEO réutilisable — émet title + meta + Open Graph + Twitter Cards
 * + canonical + JSON-LD via `<Head>` Inertia (inséré dans `<head>` côté SSR).
 *
 * Convention : titre court par page, suffixé automatiquement par le nom du jeu.
 *
 *   <SEO title="Recrutement" description="..." />
 *   → <title>Recrutement — RocketPi: Shardfall Protocol</title>
 *
 * Pour les pages privées (auth required), passe `noindex` pour empêcher Google
 * d'indexer (la page reste accessible mais hors moteur).
 */
export default function SEO({
    title,
    description = DEFAULT_DESCRIPTION,
    keywords,
    image,
    canonical,
    type = 'website',
    noindex = false,
    jsonLd,
}: Props) {
    const { url, props } = usePage<{ app?: { name?: string }; baseUrl?: string }>();

    // Construit le titre final. `null` → titre brut (utile pour Landing).
    const fullTitle = title === null
        ? SITE_NAME
        : title
            ? `${title} — ${SITE_NAME}`
            : SITE_NAME;

    // Base URL : utilise APP_URL injecté ou window.location au runtime.
    const origin = props.baseUrl
        ?? (typeof window !== 'undefined' ? window.location.origin : 'https://rocketpi.pro');
    const fullUrl = canonical ?? `${origin}${url}`;
    const ogImage = image ? (image.startsWith('http') ? image : `${origin}${image}`) : `${origin}${DEFAULT_IMAGE}`;

    const ldEntries = jsonLd ? (Array.isArray(jsonLd) ? jsonLd : [jsonLd]) : [];

    return (
        <Head>
            <title>{fullTitle}</title>
            <meta name="description" content={description} />
            {keywords && <meta name="keywords" content={keywords} />}

            {/* Indexation */}
            {noindex
                ? <meta name="robots" content="noindex,nofollow" />
                : <meta name="robots" content="index,follow,max-image-preview:large" />
            }
            <link rel="canonical" href={fullUrl} />

            {/* Open Graph */}
            <meta property="og:type" content={type} />
            <meta property="og:site_name" content={SITE_NAME} />
            <meta property="og:title" content={fullTitle} />
            <meta property="og:description" content={description} />
            <meta property="og:url" content={fullUrl} />
            <meta property="og:image" content={ogImage} />
            <meta property="og:image:width" content="1200" />
            <meta property="og:image:height" content="630" />
            <meta property="og:locale" content="fr_FR" />

            {/* Twitter Cards */}
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content={fullTitle} />
            <meta name="twitter:description" content={description} />
            <meta name="twitter:image" content={ogImage} />

            {/* JSON-LD */}
            {ldEntries.map((ld, i) => (
                <script
                    key={i}
                    type="application/ld+json"
                    // dangerouslySetInnerHTML : Inertia Head rend le contenu littéral
                    // dans le <head> serveur. Pas de XSS car ld vient de la page elle-même.
                    dangerouslySetInnerHTML={{ __html: JSON.stringify(ld) }}
                />
            ))}
        </Head>
    );
}

/** Helper pour Breadcrumb JSON-LD — utile sur factions/operators détail. */
export function breadcrumbLd(items: { name: string; url: string }[]): JsonLd {
    return {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: items.map((it, i) => ({
            '@type': 'ListItem',
            position: i + 1,
            name: it.name,
            item: it.url,
        })),
    };
}
