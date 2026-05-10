import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';

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
    participant_count: number;
    is_expired: boolean;
}

interface Props {
    seasons: Season[];
}

interface PageProps {
    flash?: { status?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}

export default function AdminLeaderboardsIndex({ seasons }: Props) {
    const { props } = usePage<PageProps>();

    const reset = (season: Season) => {
        if (!confirm(`Reset la saison « ${season.name} » ? Cette action archive en MySQL et distribue les rewards. Irréversible.`)) return;
        router.post(`/admin/leaderboards/${season.id}/reset`, {}, { preserveScroll: true });
    };

    const active   = seasons.filter(s => s.is_active);
    const archived = seasons.filter(s => !s.is_active);

    return (
        <>
            <Head title="Admin · Classements" />

            <header className="mb-6">
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Classements</h1>
                <p className="font-mono text-xs text-text-low mt-1">
                    Saisons actives en Redis · saisons archivées en MySQL
                </p>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {props.errors.season && <div className="mb-4"><Alert variant="danger">{props.errors.season}</Alert></div>}

            <section className="mb-8">
                <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">
                    Saisons actives <span className="text-text-low font-mono text-xs">({active.length})</span>
                </h2>
                {active.length === 0 ? (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-6 text-center text-text-medium font-mono text-sm">
                        Aucune saison active.
                    </div>
                ) : <SeasonsTable seasons={active} onReset={reset} showActions />}
            </section>

            <section>
                <h2 className="font-display text-sm uppercase tracking-wide text-text-medium mb-3">
                    Archivées <span className="text-text-low font-mono text-xs">({archived.length})</span>
                </h2>
                {archived.length === 0 ? (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-6 text-center text-text-medium font-mono text-sm">
                        Aucune saison archivée.
                    </div>
                ) : <SeasonsTable seasons={archived} onReset={reset} showActions={false} />}
            </section>
        </>
    );
}

function SeasonsTable({
    seasons, onReset, showActions,
}: { seasons: Season[]; onReset: (s: Season) => void; showActions: boolean }) {
    return (
        <div className="rounded-lg bg-bg-elev1 border border-border-default overflow-hidden">
            <table className="w-full text-sm">
                <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                    <tr>
                        <th className="px-3 py-2 text-left">Nom</th>
                        <th className="px-3 py-2 text-left">Type</th>
                        <th className="px-3 py-2 text-left">Période</th>
                        <th className="px-3 py-2 text-left">Participants</th>
                        <th className="px-3 py-2 text-left">État</th>
                        <th className="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {seasons.map(s => (
                        <tr key={s.id} className="border-t border-border-default hover:bg-bg-elev2/50">
                            <td className="px-3 py-2">
                                <Link href={`/admin/leaderboards/${s.id}`} className="text-shard-400 hover:text-shard-300 font-display">
                                    {s.name}
                                </Link>
                            </td>
                            <td className="px-3 py-2 font-mono text-xs text-text-medium">
                                {s.type}{s.faction ? ` · ${s.faction}` : ''}
                            </td>
                            <td className="px-3 py-2 font-mono text-xs text-text-low">
                                {s.starts_at ? new Date(s.starts_at).toLocaleDateString('fr-FR') : '—'}
                                {' → '}
                                {s.ends_at ? new Date(s.ends_at).toLocaleDateString('fr-FR') : '—'}
                            </td>
                            <td className="px-3 py-2 font-mono text-text-high tabular-nums">{s.participant_count}</td>
                            <td className="px-3 py-2">
                                {s.is_active
                                    ? (s.is_expired
                                        ? <span className="font-display text-xs uppercase text-warning">Expirée</span>
                                        : <span className="font-display text-xs uppercase text-success">Active</span>)
                                    : <span className="font-display text-xs uppercase text-text-low">Archivée{s.rewards_distributed ? ' · rewards distribués' : ''}</span>}
                            </td>
                            <td className="px-3 py-2 text-right">
                                {showActions && (
                                    <Button size="sm" variant="danger" onClick={() => onReset(s)}>
                                        Reset
                                    </Button>
                                )}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

AdminLeaderboardsIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
