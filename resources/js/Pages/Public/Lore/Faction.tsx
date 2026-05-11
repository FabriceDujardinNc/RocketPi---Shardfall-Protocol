import { Link } from '@inertiajs/react';
import SEO from '@/Components/SEO';
import RarityBadge from '@game/RarityBadge';

interface Faction {
    slug: 'ORBIT' | 'FERRO' | 'VEIL';
    name: string;
    tagline: string | null;
    lore: string | null;
    color_hue: number;
    banner_image_url?: string | null;
}

interface OperatorLite {
    id: number;
    slug: string;
    name: string;
    codename: string;
    role: string;
    rarity: 'common' | 'rare' | 'epic' | 'legendary';
    portrait_url: string | null;
}

interface Props {
    faction: Faction;
    operators: OperatorLite[];
}

export default function PublicFaction({ faction, operators }: Props) {
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://rocketpi.pro';

    const breadcrumb = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: 'Accueil', item: baseUrl },
            { '@type': 'ListItem', position: 2, name: 'Lore', item: `${baseUrl}/lore` },
            { '@type': 'ListItem', position: 3, name: faction.name, item: `${baseUrl}/lore/factions/${faction.slug}` },
        ],
    };

    const desc = faction.tagline
        ? `${faction.name} — ${faction.tagline}. ${operators.length} opérateur${operators.length > 1 ? 's' : ''} dans le roster.`
        : `Faction ${faction.name} dans RocketPi: Shardfall Protocol.`;

    return (
        <>
            <SEO
                title={`Faction ${faction.name}`}
                description={desc}
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
                            <Link href="/lore" className="text-text-medium hover:text-text-high">Lore</Link>
                            <Link href="/login" className="text-text-medium hover:text-text-high">Connexion</Link>
                            <Link href="/register" className="text-shard-400 hover:text-shard-300">Rejoindre</Link>
                        </nav>
                    </div>
                </header>

                <section className="mx-auto max-w-5xl px-4 sm:px-6 py-10 sm:py-12">
                    <Link href="/lore" className="font-display text-xs uppercase tracking-wide text-text-low hover:text-text-medium">
                        ← Toutes les factions
                    </Link>

                    <header
                        className="mt-4 rounded-lg p-5 sm:p-8 border-2"
                        style={{ borderColor: `oklch(0.55 0.15 ${faction.color_hue} / 0.5)` }}
                    >
                        <p
                            className="font-display text-xs uppercase tracking-mega"
                            style={{ color: `oklch(0.78 0.18 ${faction.color_hue})` }}
                        >
                            Faction
                        </p>
                        <h1
                            className="font-display font-bold text-3xl sm:text-5xl uppercase tracking-tight mt-1"
                            style={{ color: `oklch(0.85 0.18 ${faction.color_hue})` }}
                        >
                            {faction.name}
                        </h1>
                        {faction.tagline && (
                            <p className="text-text-medium text-sm sm:text-base mt-2">{faction.tagline}</p>
                        )}
                    </header>

                    {faction.lore && (
                        <section className="mt-6 rounded-lg bg-bg-elev1 border border-border-default p-5 sm:p-6">
                            <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Doctrine</h2>
                            <p className="text-text-medium whitespace-pre-wrap leading-relaxed">{faction.lore}</p>
                        </section>
                    )}

                    <section className="mt-8">
                        <h2 className="font-display font-bold text-xl sm:text-2xl uppercase tracking-wide mb-4">
                            Roster ({operators.length})
                        </h2>
                        {operators.length === 0 ? (
                            <p className="text-text-medium font-body text-sm">Aucun opérateur publié pour cette faction.</p>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                {operators.map((op) => (
                                    <Link
                                        key={op.id}
                                        href={`/lore/operators/${op.slug}`}
                                        className="group rounded-lg bg-bg-elev1 border border-border-default hover:border-shard-500/40 transition-all duration-fast overflow-hidden"
                                    >
                                        <div className="aspect-[3/4] bg-bg-elev2 relative">
                                            {op.portrait_url ? (
                                                <img src={op.portrait_url} alt={op.name} className="w-full h-full object-cover" loading="lazy" />
                                            ) : (
                                                <div className="absolute inset-0 flex items-center justify-center font-display text-2xl text-text-low uppercase">
                                                    {op.name.slice(0, 2)}
                                                </div>
                                            )}
                                        </div>
                                        <div className="p-3">
                                            <p className="font-mono text-[10px] text-text-low">{op.codename}</p>
                                            <h3 className="font-display font-bold text-sm uppercase tracking-wide truncate">{op.name}</h3>
                                            <div className="flex items-center justify-between mt-2">
                                                <span className="font-mono text-[10px] text-text-medium">{op.role}</span>
                                                <RarityBadge rarity={op.rarity} />
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </section>

                    <section className="mt-12 text-center">
                        <p className="font-body text-text-medium mb-3">Rejoins {faction.name} dès l'inscription — ton choix est définitif.</p>
                        <Link
                            href="/register"
                            className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-8 bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 rounded-md hover:brightness-110 transition-all"
                        >
                            Rejoindre {faction.name}
                        </Link>
                    </section>
                </section>

                <footer className="border-t border-border-default py-6 text-center font-mono text-xs text-text-low">
                    RocketPi: Shardfall Protocol · 2087
                </footer>
            </main>
        </>
    );
}
