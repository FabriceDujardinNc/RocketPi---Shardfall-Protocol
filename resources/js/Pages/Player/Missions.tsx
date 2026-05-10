import { Head, router, usePage } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import Alert from '@ui/Alert';
import MissionCard from '@game/MissionCard';

interface Reward { type: string; amount: number }

interface MissionVM {
    id: number;
    title: string;
    description: string | null;
    objective_target: number;
    rewards: Reward[];
    xp_reward: number;
    progress: number;
    completed: boolean;
    reward_claimed: boolean;
}

interface Props {
    daily: MissionVM[];
    weekly: MissionVM[];
}

interface PageProps {
    flash?: { status?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}

export default function Missions({ daily, weekly }: Props) {
    const { props } = usePage<PageProps>();
    const claim = (id: number) => router.post(`/missions/${id}/claim`, {}, { preserveScroll: true });

    const renderList = (list: MissionVM[]) => (
        <ul className="flex flex-col gap-3">
            {list.map(m => (
                <li key={m.id}>
                    <MissionCard
                        title={m.title}
                        description={m.description ?? ''}
                        progress={m.progress}
                        total={m.objective_target}
                        rewardLabel={`+${m.xp_reward} XP`}
                        completed={m.completed}
                        claimed={m.reward_claimed}
                        onClaim={() => claim(m.id)}
                    />
                </li>
            ))}
        </ul>
    );

    return (
        <>
            <Head title="Missions" />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-8">Missions</h1>

            {props.flash?.status && (
                <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>
            )}
            {props.errors.mission && (
                <div className="mb-4"><Alert variant="danger" title="Réclamation impossible">{props.errors.mission}</Alert></div>
            )}

            <section className="grid md:grid-cols-2 gap-6">
                <div>
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">
                        Journalières <span className="text-text-low font-mono text-xs">({daily.length})</span>
                    </h2>
                    {daily.length === 0
                        ? <p className="text-text-medium font-mono text-sm">Aucune mission active.</p>
                        : renderList(daily)}
                </div>
                <div>
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">
                        Hebdomadaires <span className="text-text-low font-mono text-xs">({weekly.length})</span>
                    </h2>
                    {weekly.length === 0
                        ? <p className="text-text-medium font-mono text-sm">Aucune mission active.</p>
                        : renderList(weekly)}
                </div>
            </section>
        </>
    );
}

Missions.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
