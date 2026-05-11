import { Link } from '@inertiajs/react';
import SEO from '@/Components/SEO';

interface Season {
    id: number;
    name: string;
    type: string;
    season_number: number;
    starts_at: string | null;
    ends_at: string | null;
}

interface Entry {
    rank: number;
    display_name: string;
    score: number;
}

interface Props {
    season: Season | null;
    entries: Entry[];
    participantCount: number;
}

function rankClass(rank: number): string {
    if (rank === 1) return 'text-rarity-legendary font-bold';
    if (rank <= 3) return 'text-rarity-epic font-semibold';
    if (rank <= 10) return 'text-rarity-rare';
    return 'text-text-medium';
}

export default function PublicLeaderboard({ season, entries, participantCount }: Props) {
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://rocketpi.pro';

    const breadcrumb = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: 'Accueil', item: baseUrl },
            { '@type': 'ListItem', position: 2, name: 'Top 100', item: `${baseUrl}/top` },
        ],
    };

    const desc = season
        ? `Top 100 du classement hebdomadaire de RocketPi: Shardfall Protocol — ${participantCount} participant${participantCount > 1 ? 's' : ''} en ${season.name}.`
        : 'Classement public RocketPi — aucune saison hebdomadaire active actuellement.';

    return (
        <>
            <SEO
                title="Top 100 — Classement hebdo"
                description={desc}
                type="website"
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

                <section className="mx-auto max-w-4xl px-4 sm:px-6 py-10 sm:py-12">
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">Top 100</p>
                    <h1 className="font-display font-bold text-3xl sm:text-5xl uppercase tracking-tight mt-1">
                        Classement hebdomadaire
                    </h1>
                    {season ? (
                        <p className="font-mono text-sm text-text-medium mt-3">
                            {season.name} — {participantCount} participant{participantCount > 1 ? 's' : ''}
                        </p>
                    ) : (
                        <p className="font-body text-sm text-text-medium mt-3">
                            Aucune saison hebdomadaire active actuellement. Le prochain reset est prévu lundi 00h UTC.
                        </p>
                    )}

                    {entries.length > 0 ? (
                        <div className="mt-8 rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                            <table className="w-full text-sm min-w-[400px]">
                                <thead className="bg-bg-elev2 font-display text-[11px] uppercase tracking-mega text-text-low">
                                    <tr>
                                        <th className="text-left px-4 py-3 w-16">Rang</th>
                                        <th className="text-left px-4 py-3">Opérateur</th>
                                        <th className="text-right px-4 py-3">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {entries.map((e) => (
                                        <tr key={e.rank} className="border-t border-border-default">
                                            <td className={`px-4 py-3 font-display tabular-nums ${rankClass(e.rank)}`}>
                                                #{e.rank}
                                            </td>
                                            <td className="px-4 py-3 font-display text-text-high">
                                                {e.display_name}
                                            </td>
                                            <td className="px-4 py-3 text-right font-mono text-shard-400 tabular-nums">
                                                {e.score.toLocaleString('fr-FR')}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="mt-8 rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium font-mono text-sm">
                            Aucun joueur classé pour le moment.
                        </div>
                    )}

                    <section className="mt-12 text-center">
                        <p className="font-body text-text-medium mb-3">
                            Inscris-toi pour intégrer le classement et gagner des récompenses chaque semaine.
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
