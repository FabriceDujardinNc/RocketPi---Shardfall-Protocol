import { Head, Link } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import FactionBadge from '@game/FactionBadge';

interface HistoryRow {
    season_id: number;
    season_name: string | null;
    season_type: string | null;
    faction: 'ORBIT' | 'FERRO' | 'VEIL' | null;
    starts_at: string | null;
    ends_at: string | null;
    rank: number | null;
    score: number;
    games_played: number;
    wins: number;
}

interface Props {
    history: HistoryRow[];
    best: HistoryRow[];
    totals: { archived_seasons: number; top_1_count: number; top_10_count: number };
}

const TYPE_LABELS: Record<string, string> = {
    weekly: 'Hebdo',
    monthly: 'Mensuel',
    seasonal: 'Saisonnier',
    collection: 'Collection',
    faction: 'Faction',
    annual: 'Annuel',
};

function rankClass(rank: number | null): string {
    if (!rank) return 'text-text-low';
    if (rank === 1) return 'text-rarity-legendary font-bold';
    if (rank <= 10) return 'text-rarity-epic font-semibold';
    if (rank <= 100) return 'text-rarity-rare';
    return 'text-text-medium';
}

export default function LeaderboardHistory({ history, best, totals }: Props) {
    return (
        <>
            <Head title="Historique classements" />

            <header className="mb-6 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">Archives</p>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">
                        Historique des classements
                    </h1>
                    <p className="font-body text-sm text-text-medium mt-2">
                        Toutes tes saisons closes — rang final, score, performance.
                    </p>
                </div>
                <Link
                    href="/leaderboard"
                    className="font-display text-xs uppercase tracking-wide text-text-medium hover:text-shard-400"
                >
                    ← Saisons en cours
                </Link>
            </header>

            <section className="grid sm:grid-cols-3 gap-4 mb-8">
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-4">
                    <p className="font-display text-[10px] uppercase tracking-mega text-text-low">Saisons archivées</p>
                    <p className="font-display font-bold text-3xl text-text-high tabular-nums mt-1">{totals.archived_seasons}</p>
                </div>
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-4">
                    <p className="font-display text-[10px] uppercase tracking-mega text-text-low">Top 10</p>
                    <p className="font-display font-bold text-3xl text-rarity-epic tabular-nums mt-1">{totals.top_10_count}</p>
                </div>
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-4 shadow-glow-legendary/30">
                    <p className="font-display text-[10px] uppercase tracking-mega text-text-low">1ère place</p>
                    <p className="font-display font-bold text-3xl text-rarity-legendary tabular-nums mt-1">{totals.top_1_count}</p>
                </div>
            </section>

            {best.length > 0 && (
                <section className="mb-8">
                    <h2 className="font-display font-semibold text-lg uppercase tracking-wide mb-3">Tes meilleurs résultats</h2>
                    <div className="grid sm:grid-cols-3 gap-4">
                        {best.map(b => (
                            <article
                                key={b.season_id}
                                className="rounded-lg bg-bg-elev1 border border-shard-500/30 p-4 flex flex-col gap-2"
                            >
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="font-mono text-[11px] text-text-medium">
                                            {b.season_type && TYPE_LABELS[b.season_type]}
                                        </p>
                                        <h3 className="font-display font-semibold text-sm uppercase tracking-wide">
                                            {b.season_name}
                                        </h3>
                                    </div>
                                    {b.faction && <FactionBadge faction={b.faction.toLowerCase() as 'orbit' | 'ferro' | 'veil'} />}
                                </div>
                                <p className={'font-display font-bold text-4xl tabular-nums ' + rankClass(b.rank)}>
                                    #{b.rank}
                                </p>
                                <p className="font-mono text-sm text-text-medium">
                                    {b.score.toLocaleString('fr-FR')} pts
                                </p>
                            </article>
                        ))}
                    </div>
                </section>
            )}

            <section>
                <h2 className="font-display font-semibold text-lg uppercase tracking-wide mb-3">Toutes les saisons closes</h2>

                {history.length === 0 ? (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center">
                        <p className="font-body text-sm text-text-medium">
                            Aucune saison archivée pour le moment. L'historique se remplira au fil des resets hebdo / mensuels.
                        </p>
                    </div>
                ) : (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default overflow-hidden">
                        <table className="w-full font-mono text-sm">
                            <thead className="bg-bg-elev2 text-text-medium font-display text-[10px] uppercase tracking-mega">
                                <tr>
                                    <th className="text-left px-4 py-3">Saison</th>
                                    <th className="text-left px-4 py-3">Type</th>
                                    <th className="text-right px-4 py-3">Rang</th>
                                    <th className="text-right px-4 py-3">Score</th>
                                    <th className="text-right px-4 py-3">Période</th>
                                </tr>
                            </thead>
                            <tbody>
                                {history.map(row => (
                                    <tr key={row.season_id} className="border-t border-border-default">
                                        <td className="px-4 py-3 text-text-high">
                                            <div className="flex items-center gap-2">
                                                {row.season_name}
                                                {row.faction && <FactionBadge faction={row.faction.toLowerCase() as 'orbit' | 'ferro' | 'veil'} />}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-text-medium">
                                            {row.season_type && TYPE_LABELS[row.season_type]}
                                        </td>
                                        <td className={'px-4 py-3 text-right tabular-nums ' + rankClass(row.rank)}>
                                            {row.rank ? `#${row.rank}` : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums text-shard-400">
                                            {row.score.toLocaleString('fr-FR')}
                                        </td>
                                        <td className="px-4 py-3 text-right text-text-low text-xs">
                                            {row.ends_at && new Date(row.ends_at).toLocaleDateString('fr-FR')}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        </>
    );
}

LeaderboardHistory.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
