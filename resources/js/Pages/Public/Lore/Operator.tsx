import { Link } from '@inertiajs/react';
import SEO from '@/Components/SEO';
import RarityBadge from '@game/RarityBadge';
import FactionBadge from '@game/FactionBadge';

interface Ability {
    name: string;
    type: 'active' | 'passive' | 'ultimate';
    description: string;
}

interface OperatorPublic {
    slug: string;
    name: string;
    codename: string;
    faction: 'ORBIT' | 'FERRO' | 'VEIL';
    role: string;
    rarity: 'common' | 'rare' | 'epic' | 'legendary';
    lore: string | null;
    portrait_url: string | null;
    stat_hp: number;
    stat_damage: number;
    stat_mobility: number;
    weapon_name: string | null;
    weapon_description: string | null;
    abilities: Ability[] | null;
}

interface Props {
    operator: OperatorPublic;
}

export default function PublicOperator({ operator }: Props) {
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://rocketpi.pro';

    const breadcrumb = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: 'Accueil', item: baseUrl },
            { '@type': 'ListItem', position: 2, name: 'Lore', item: `${baseUrl}/lore` },
            { '@type': 'ListItem', position: 3, name: operator.faction, item: `${baseUrl}/lore/factions/${operator.faction}` },
            { '@type': 'ListItem', position: 4, name: operator.name, item: `${baseUrl}/lore/operators/${operator.slug}` },
        ],
    };

    const description = operator.lore
        ? operator.lore.slice(0, 155)
        : `${operator.name} (${operator.codename}) — ${operator.role} de la faction ${operator.faction}. Rareté ${operator.rarity}.`;

    return (
        <>
            <SEO
                title={`${operator.name} — ${operator.faction}`}
                description={description}
                image={operator.portrait_url ?? undefined}
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
                    <Link
                        href={`/lore/factions/${operator.faction}`}
                        className="font-display text-xs uppercase tracking-wide text-text-low hover:text-text-medium"
                    >
                        ← Roster {operator.faction}
                    </Link>

                    <header className="mt-4 grid sm:grid-cols-[1fr_2fr] gap-6 items-start">
                        <div className="aspect-[3/4] rounded-lg bg-bg-elev2 overflow-hidden">
                            {operator.portrait_url ? (
                                <img src={operator.portrait_url} alt={operator.name} className="w-full h-full object-cover" />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center font-display text-5xl text-text-low uppercase">
                                    {operator.name.slice(0, 2)}
                                </div>
                            )}
                        </div>

                        <div>
                            <p className="font-mono text-xs text-text-medium">{operator.codename}</p>
                            <h1 className="font-display font-bold text-3xl sm:text-5xl uppercase tracking-tight mt-1">
                                {operator.name}
                            </h1>
                            <div className="mt-3 flex flex-wrap gap-2">
                                <FactionBadge faction={operator.faction.toLowerCase() as 'orbit' | 'ferro' | 'veil'} />
                                <RarityBadge rarity={operator.rarity} />
                                <span className="font-display text-xs uppercase tracking-wide px-2 py-0.5 rounded bg-bg-elev2 text-text-medium border border-border-default">
                                    {operator.role}
                                </span>
                            </div>

                            <div className="mt-6 grid grid-cols-3 gap-3">
                                <div className="rounded-md bg-bg-elev1 border border-border-default p-3 text-center">
                                    <p className="font-display text-[10px] uppercase tracking-mega text-text-low">HP</p>
                                    <p className="font-mono text-xl text-text-high mt-1">{operator.stat_hp}</p>
                                </div>
                                <div className="rounded-md bg-bg-elev1 border border-border-default p-3 text-center">
                                    <p className="font-display text-[10px] uppercase tracking-mega text-text-low">DMG</p>
                                    <p className="font-mono text-xl text-text-high mt-1">{operator.stat_damage}</p>
                                </div>
                                <div className="rounded-md bg-bg-elev1 border border-border-default p-3 text-center">
                                    <p className="font-display text-[10px] uppercase tracking-mega text-text-low">MOB</p>
                                    <p className="font-mono text-xl text-text-high mt-1">{operator.stat_mobility}</p>
                                </div>
                            </div>
                        </div>
                    </header>

                    {operator.lore && (
                        <section className="mt-8 rounded-lg bg-bg-elev1 border border-border-default p-5 sm:p-6">
                            <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Lore</h2>
                            <p className="text-text-medium leading-relaxed whitespace-pre-wrap">{operator.lore}</p>
                        </section>
                    )}

                    {operator.weapon_name && (
                        <section className="mt-6 rounded-lg bg-bg-elev1 border border-border-default p-5 sm:p-6">
                            <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-1">Arme signature</h2>
                            <p className="font-display font-bold text-base uppercase tracking-wide text-text-high">{operator.weapon_name}</p>
                            {operator.weapon_description && (
                                <p className="text-text-medium text-sm mt-2 leading-relaxed">{operator.weapon_description}</p>
                            )}
                        </section>
                    )}

                    {operator.abilities && operator.abilities.length > 0 && (
                        <section className="mt-6">
                            <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Capacités</h2>
                            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                {operator.abilities.map((ab, i) => (
                                    <article key={i} className="rounded-lg bg-bg-elev1 border border-border-default p-4">
                                        <div className="flex items-center justify-between gap-2 mb-2">
                                            <h3 className="font-display font-semibold text-sm uppercase tracking-wide text-text-high truncate">
                                                {ab.name}
                                            </h3>
                                            <span className={
                                                'font-display text-[10px] uppercase tracking-mega px-2 py-0.5 rounded ' +
                                                (ab.type === 'ultimate' ? 'bg-rarity-legendary/20 text-rarity-legendary'
                                                    : ab.type === 'active' ? 'bg-shard-500/15 text-shard-400'
                                                    : 'bg-bg-elev2 text-text-medium')
                                            }>
                                                {ab.type}
                                            </span>
                                        </div>
                                        <p className="text-text-medium text-sm leading-relaxed">{ab.description}</p>
                                    </article>
                                ))}
                            </div>
                        </section>
                    )}

                    <section className="mt-10 text-center">
                        <p className="font-body text-text-medium mb-3">
                            Recrute {operator.name} via le système gacha après inscription.
                        </p>
                        <Link
                            href="/register"
                            className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-12 px-8 bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 rounded-md hover:brightness-110 transition-all"
                        >
                            Rejoindre le protocole
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
