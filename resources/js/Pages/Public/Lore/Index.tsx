import { Link } from '@inertiajs/react';
import SEO from '@/Components/SEO';

interface Faction {
    slug: 'ORBIT' | 'FERRO' | 'VEIL';
    name: string;
    tagline: string | null;
    lore: string | null;
    color_hue: number;
}

interface Props {
    factions: Faction[];
    operatorsCount: number;
}

export default function LoreIndex({ factions, operatorsCount }: Props) {
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://rocketpi.pro';

    const breadcrumb = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: 'Accueil', item: baseUrl },
            { '@type': 'ListItem', position: 2, name: 'Lore', item: `${baseUrl}/lore` },
        ],
    };

    return (
        <>
            <SEO
                title="Lore & Univers"
                description={`Découvre l'univers de RocketPi: Shardfall Protocol — 3 factions, ${operatorsCount} opérateurs jouables, lore narratif post-2087.`}
                type="article"
                jsonLd={breadcrumb}
            />

            <main className="min-h-screen bg-bg-base text-text-high">
                <header className="border-b border-border-default">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 h-16 flex items-center justify-between">
                        <Link href="/" className="font-display font-bold uppercase tracking-wide">
                            ROCKETPI<span className="text-shard-500">.</span>
                        </Link>
                        <nav className="font-display text-sm uppercase tracking-wide flex gap-4">
                            <Link href="/login" className="text-text-medium hover:text-text-high">Connexion</Link>
                            <Link href="/register" className="text-shard-400 hover:text-shard-300">Rejoindre</Link>
                        </nav>
                    </div>
                </header>

                <section className="mx-auto max-w-4xl px-4 sm:px-6 py-12 sm:py-16">
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">Univers</p>
                    <h1 className="font-display font-bold text-3xl sm:text-5xl uppercase tracking-tight mt-2">
                        Lore Shardfall
                    </h1>
                    <p className="font-body text-base sm:text-lg text-text-medium mt-4 leading-relaxed">
                        En 2087, la station orbitale RocketPi s'est désintégrée dans des conditions
                        toujours classifiées. Des fragments cristallins — les <em>Shards</em> — ont
                        plu sur Terre, conférant à certains humains des capacités hors normes.
                        Trois factions ont émergé pour les contrôler.
                    </p>
                    <p className="font-body text-base text-text-medium mt-3 leading-relaxed">
                        {operatorsCount} Opérateurs jouables. Trois doctrines. Un seul Hall of Fame.
                    </p>
                </section>

                <section className="mx-auto max-w-6xl px-4 sm:px-6 pb-12">
                    <h2 className="font-display font-bold text-2xl uppercase tracking-wide mb-6">
                        Les trois factions
                    </h2>
                    <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        {factions.map((f) => (
                            <article
                                key={f.slug}
                                className="rounded-lg bg-bg-elev1 border-2 p-5"
                                style={{ borderColor: `oklch(0.55 0.15 ${f.color_hue} / 0.4)` }}
                            >
                                <h3
                                    className="font-display font-bold text-lg uppercase tracking-mega"
                                    style={{ color: `oklch(0.78 0.18 ${f.color_hue})` }}
                                >
                                    {f.name}
                                </h3>
                                {f.tagline && (
                                    <p className="font-mono text-xs text-text-medium mt-1">{f.tagline}</p>
                                )}
                                {f.lore && (
                                    <p className="font-body text-sm text-text-medium mt-3 leading-relaxed line-clamp-5">
                                        {f.lore}
                                    </p>
                                )}
                                <Link
                                    href={`/lore/factions/${f.slug}`}
                                    className="inline-block mt-3 font-display text-xs uppercase tracking-wide text-shard-400 hover:text-shard-300"
                                >
                                    Voir le roster →
                                </Link>
                            </article>
                        ))}
                    </div>
                </section>

                <section className="mx-auto max-w-4xl px-4 sm:px-6 py-12 text-center">
                    <h2 className="font-display font-bold text-2xl uppercase tracking-wide mb-3">
                        Prêt à choisir ton camp ?
                    </h2>
                    <Link
                        href="/register"
                        className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-8 mt-4 bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 rounded-md hover:brightness-110 transition-all"
                    >
                        Rejoindre le protocole
                    </Link>
                </section>

                <footer className="border-t border-border-default py-6 text-center font-mono text-xs text-text-low">
                    RocketPi: Shardfall Protocol · 2087
                </footer>
            </main>
        </>
    );
}
