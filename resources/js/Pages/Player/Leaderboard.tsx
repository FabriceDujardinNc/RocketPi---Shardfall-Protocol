import { Head, Link } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import LeaderboardRow from '@game/LeaderboardRow';

interface Season {
    id: number;
    name: string;
    type: 'weekly' | 'monthly' | 'seasonal' | 'annual' | 'collection' | 'faction';
    faction: string | null;
    season_number?: number;
    starts_at?: string | null;
    ends_at?: string | null;
}

interface Entry {
    rank: number;
    user_id: number;
    score: number;
    name: string;
    display_name: string | null;
    account_level: number;
    is_current_user?: boolean;
}

interface Props {
    currentSeason: Season | null;
    seasons: Season[];
    topEntries: Entry[];
    participantCount: number;
    userRank: number | null;
    userScore: number;
    neighbors: Entry[];
}

const TYPE_LABEL: Record<Season['type'], string> = {
    weekly:     'Hebdomadaire',
    monthly:    'Mensuel',
    seasonal:   'Saison compétitive',
    annual:     'Annuel',
    collection: 'Collection',
    faction:    'Faction',
};

export default function Leaderboard({ currentSeason, seasons, topEntries, participantCount, userRank, userScore, neighbors }: Props) {
    if (!currentSeason) {
        return (
            <>
                <Head title="Classement" />
                <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-8">Classement</h1>
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                    Aucune saison active.
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={currentSeason.name} />

            <header className="mb-6 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">
                        {TYPE_LABEL[currentSeason.type]}
                        {currentSeason.faction && ` · ${currentSeason.faction}`}
                    </p>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">
                        {currentSeason.name}
                    </h1>
                    <p className="font-mono text-xs text-text-low mt-1">
                        {participantCount} participant{participantCount > 1 ? 's' : ''}
                    </p>
                </div>
                <div className="flex flex-wrap gap-3 items-center">
                    <Link
                        href="/hall-of-fame"
                        className="font-display text-xs uppercase tracking-wide text-rarity-legendary hover:text-rarity-epic"
                    >
                        🏆 Hall of Fame
                    </Link>
                    <Link
                        href="/leaderboard/history"
                        className="font-display text-xs uppercase tracking-wide text-text-medium hover:text-shard-400"
                    >
                        Historique →
                    </Link>
                </div>
            </header>

            {/* Tabs saisons */}
            <nav className="flex flex-wrap gap-1 mb-6 p-1 rounded-md bg-bg-elev1 border border-border-default">
                {seasons.map(s => (
                    <Link
                        key={s.id}
                        href={`/leaderboard/${s.id}`}
                        preserveScroll
                        className={
                            'px-3 py-1.5 rounded font-display text-xs uppercase tracking-wide transition-colors duration-fast ' +
                            (s.id === currentSeason.id
                                ? 'bg-shard-500/15 text-shard-400'
                                : 'text-text-medium hover:text-text-high hover:bg-bg-elev2')
                        }
                    >
                        {TYPE_LABEL[s.type]}{s.faction ? ` ${s.faction}` : ''}
                    </Link>
                ))}
            </nav>

            {/* User rank summary */}
            {userRank !== null ? (
                <div className="rounded-lg bg-bg-elev1 border border-shard-500/40 p-4 mb-6 flex items-center justify-between">
                    <div>
                        <p className="font-display text-xs uppercase tracking-wide text-text-low">Ton rang</p>
                        <p className="font-display text-2xl font-bold text-shard-400 mt-1">#{userRank}</p>
                    </div>
                    <div className="text-right">
                        <p className="font-display text-xs uppercase tracking-wide text-text-low">Score</p>
                        <p className="font-mono text-lg text-text-high mt-1 tabular-nums">
                            {userScore.toLocaleString('fr-FR')}
                        </p>
                    </div>
                </div>
            ) : (
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 text-text-medium font-mono text-sm">
                    Pas encore classé sur cette saison — gagne des points en complétant des missions et en recrutant des opérateurs.
                </div>
            )}

            <div className="grid lg:grid-cols-3 gap-6">
                {/* Top 100 */}
                <section className="lg:col-span-2 rounded-lg bg-bg-elev1 border border-border-default p-4">
                    <h2 className="font-display text-sm uppercase tracking-wide text-text-medium mb-3">
                        Top {Math.min(100, topEntries.length)}
                    </h2>
                    {topEntries.length === 0 ? (
                        <p className="text-text-medium font-mono text-sm py-8 text-center">Aucun joueur classé.</p>
                    ) : (
                        <ul className="flex flex-col gap-1.5">
                            {topEntries.map(e => (
                                <li key={e.user_id}>
                                    <LeaderboardRow
                                        rank={e.rank}
                                        name={e.display_name ?? e.name}
                                        score={e.score}
                                        isCurrentUser={e.is_current_user}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {/* Voisins */}
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-4">
                    <h2 className="font-display text-sm uppercase tracking-wide text-text-medium mb-3">Autour de toi</h2>
                    {neighbors.length === 0 ? (
                        <p className="text-text-medium font-mono text-sm py-4">—</p>
                    ) : (
                        <ul className="flex flex-col gap-1.5">
                            {neighbors.map(n => (
                                <li key={n.user_id}>
                                    <LeaderboardRow
                                        rank={n.rank}
                                        name={n.display_name ?? n.name}
                                        score={n.score}
                                        isCurrentUser={n.is_current_user}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

Leaderboard.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
