import { Head, Link } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

interface Entry {
    rank: number;
    score: number;
    display_name: string;
    slug: string | null;
    account_level: number;
}

interface Season {
    id: number;
    name: string;
    season_number: number;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
    entries: Entry[];
}

interface Props {
    palmares: Season[];
    totalSeasons: number;
}

function rankClass(rank: number): string {
    if (rank === 1) return 'text-rarity-legendary font-bold';
    if (rank === 2) return 'text-text-high font-semibold';
    if (rank === 3) return 'text-rarity-epic font-semibold';
    if (rank <= 10) return 'text-rarity-rare';
    return 'text-text-medium';
}

function rankIcon(rank: number): string {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return '';
}

export default function HallOfFame({ palmares, totalSeasons }: Props) {
    return (
        <>
            <Head title="Hall of Fame" />

            <header className="mb-8">
                <p className="font-display text-xs uppercase tracking-mega text-rarity-legendary">Palmarès annuel</p>
                <h1 className="font-display font-bold text-3xl sm:text-4xl uppercase tracking-wide mt-1">
                    Hall of Fame
                </h1>
                <p className="font-body text-sm text-text-medium mt-2 max-w-2xl">
                    Les meilleurs Opérateurs de chaque cycle annuel. Saisons closes archivées
                    pour la postérité, saisons en cours en temps réel via Redis.
                </p>
                <div className="mt-3 inline-flex items-center gap-2 font-mono text-xs text-text-low px-3 py-1.5 rounded bg-bg-elev1 border border-border-default">
                    {totalSeasons} saison{totalSeasons > 1 ? 's' : ''} annuelle{totalSeasons > 1 ? 's' : ''}
                </div>
            </header>

            {palmares.length === 0 ? (
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center">
                    <p className="font-body text-sm text-text-medium">
                        Le Hall of Fame s'activera après 6+ mois de jeu actif. Aucune saison annuelle
                        n'est encore configurée. Reviens en {new Date().getFullYear() + 1} !
                    </p>
                </div>
            ) : (
                <div className="flex flex-col gap-8">
                    {palmares.map((season) => (
                        <section
                            key={season.id}
                            className="rounded-lg bg-bg-elev1 border-2 border-rarity-legendary/30 overflow-hidden"
                        >
                            <header className="bg-gradient-to-b from-rarity-legendary/10 to-transparent p-5 sm:p-6 border-b border-border-default">
                                <div className="flex items-start justify-between flex-wrap gap-3">
                                    <div>
                                        <p className="font-display text-xs uppercase tracking-mega text-rarity-legendary">
                                            Saison annuelle #{season.season_number}
                                        </p>
                                        <h2 className="font-display font-bold text-2xl sm:text-3xl uppercase tracking-wide mt-1">
                                            {season.name}
                                        </h2>
                                        {season.starts_at && season.ends_at && (
                                            <p className="font-mono text-xs text-text-low mt-2">
                                                Du {new Date(season.starts_at).toLocaleDateString('fr-FR')} au {new Date(season.ends_at).toLocaleDateString('fr-FR')}
                                            </p>
                                        )}
                                    </div>
                                    <span className={
                                        'font-display text-[10px] uppercase tracking-mega px-3 py-1 rounded ' +
                                        (season.is_active
                                            ? 'bg-shard-500/15 text-shard-400 border border-shard-500/40'
                                            : 'bg-rarity-legendary/15 text-rarity-legendary border border-rarity-legendary/40')
                                    }>
                                        {season.is_active ? 'En cours' : 'Archivée'}
                                    </span>
                                </div>
                            </header>

                            {season.entries.length === 0 ? (
                                <p className="p-8 text-center font-body text-sm text-text-medium">
                                    Aucun joueur classé pour cette saison.
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm min-w-[500px]">
                                        <thead className="bg-bg-elev2 font-display text-[11px] uppercase tracking-mega text-text-low">
                                            <tr>
                                                <th className="text-left px-4 py-3 w-20">Rang</th>
                                                <th className="text-left px-4 py-3">Opérateur</th>
                                                <th className="text-right px-4 py-3 w-24">Niveau</th>
                                                <th className="text-right px-4 py-3 w-32">Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {season.entries.map((e) => (
                                                <tr key={e.rank} className="border-t border-border-default hover:bg-bg-elev2/30">
                                                    <td className={`px-4 py-3 font-display tabular-nums ${rankClass(e.rank)}`}>
                                                        {rankIcon(e.rank)} #{e.rank}
                                                    </td>
                                                    <td className="px-4 py-3 font-display text-text-high">
                                                        {e.slug ? (
                                                            <Link href={`/profile/${e.slug}`} className="hover:text-shard-400">
                                                                {e.display_name}
                                                            </Link>
                                                        ) : e.display_name}
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-mono text-text-medium tabular-nums">
                                                        {e.account_level}
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-mono text-shard-400 tabular-nums">
                                                        {e.score.toLocaleString('fr-FR')}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </section>
                    ))}
                </div>
            )}
        </>
    );
}

HallOfFame.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
