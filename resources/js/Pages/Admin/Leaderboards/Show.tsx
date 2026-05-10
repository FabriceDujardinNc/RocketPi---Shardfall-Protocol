import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import LeaderboardRow from '@game/LeaderboardRow';

interface Season {
    id: number;
    name: string;
    type: string;
    faction: string | null;
    season_number: number;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
    rewards_distributed: boolean;
}

interface Entry {
    rank: number;
    user_id: number;
    score: number;
    name: string;
    display_name: string | null;
    account_level: number;
}

interface Props {
    season: Season;
    entries: Entry[];
    source: 'redis' | 'mysql';
    participantCount: number;
}

export default function AdminLeaderboardShow({ season, entries, source, participantCount }: Props) {
    return (
        <>
            <Head title={`Admin · ${season.name}`} />

            <Link href="/admin/leaderboards" className="font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high">
                ← Toutes les saisons
            </Link>

            <header className="mt-2 mb-6 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">
                        {season.type}{season.faction ? ` · ${season.faction}` : ''}
                    </p>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide mt-1">{season.name}</h1>
                    <p className="font-mono text-xs text-text-low mt-1">
                        Source: {source === 'redis' ? 'Redis (live)' : 'MySQL (archivé)'} · {participantCount} participants
                    </p>
                </div>
                <div className="font-mono text-xs text-text-low text-right">
                    {season.starts_at && new Date(season.starts_at).toLocaleString('fr-FR')}<br/>
                    → {season.ends_at && new Date(season.ends_at).toLocaleString('fr-FR')}
                </div>
            </header>

            <section className="rounded-lg bg-bg-elev1 border border-border-default p-4">
                <h2 className="font-display text-sm uppercase tracking-wide text-text-medium mb-3">
                    Top {Math.min(100, entries.length)}
                </h2>
                {entries.length === 0 ? (
                    <p className="text-text-medium font-mono text-sm py-8 text-center">Aucun joueur classé.</p>
                ) : (
                    <ul className="flex flex-col gap-1.5">
                        {entries.map(e => (
                            <li key={e.user_id}>
                                <LeaderboardRow
                                    rank={e.rank}
                                    name={e.display_name ?? e.name}
                                    score={e.score}
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </>
    );
}

AdminLeaderboardShow.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
