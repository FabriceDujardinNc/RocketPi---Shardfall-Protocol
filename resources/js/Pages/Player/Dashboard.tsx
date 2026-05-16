import { Head, Link, router, usePage } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import Progress from '@ui/Progress';
import CurrencyDisplay from '@game/CurrencyDisplay';
import MissionCard from '@game/MissionCard';

interface Reward { type: string; amount: number }

interface MissionVM {
    id: number;
    slug: string;
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
    user: { id: number; name: string; display_name: string | null; account_level: number; account_xp: number };
    xpThreshold: number;
    currencies: { shards: number; credits: number };
    operatorsCount: number;
    dailyLogin: { streak_day: number; streak_count: number; reward_claimed: boolean; reward: Reward[] };
    dailyMissions: MissionVM[];
    weeklyMissions: MissionVM[];
}

interface PageProps {
    flash?: { status?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}

export default function Dashboard({
    user, xpThreshold, currencies, operatorsCount,
    dailyLogin, dailyMissions, weeklyMissions,
}: Props) {
    const { props } = usePage<PageProps>();

    const claimDaily = () => router.post('/daily-login/claim', {}, { preserveScroll: true });
    const claimMission = (slug: string) => router.post(`/missions/${slug}/claim`, {}, { preserveScroll: true });

    return (
        <>
            <Head title="Tableau de bord" />

            {props.flash?.status && (
                <div className="mb-4">
                    <Alert variant="success">{props.flash.status}</Alert>
                </div>
            )}

            <header className="mb-8">
                <p className="font-display text-xs uppercase tracking-mega text-shard-400">Cellule active</p>
                <div className="flex items-end justify-between gap-4 mt-1 flex-wrap">
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide">
                        Bienvenue, {user.display_name ?? user.name}
                    </h1>
                    <div className="flex items-center gap-4">
                        <CurrencyDisplay currency="premium" amount={currencies.shards} />
                        <CurrencyDisplay currency="soft" amount={currencies.credits} />
                    </div>
                </div>
            </header>

            <section className="grid md:grid-cols-3 gap-4 mb-8">
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                    <p className="font-display text-xs uppercase tracking-wide text-text-low">Niveau compte</p>
                    <p className="font-display text-3xl font-bold text-shard-400 mt-2">{user.account_level}</p>
                    <div className="mt-3">
                        <Progress
                            value={user.account_xp}
                            max={xpThreshold}
                            label={`XP ${user.account_xp} / ${xpThreshold}`}
                            variant="shard"
                        />
                    </div>
                </div>
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                    <p className="font-display text-xs uppercase tracking-wide text-text-low">Opérateurs recrutés</p>
                    <p className="font-display text-3xl font-bold text-text-high mt-2">{operatorsCount}</p>
                    <Link
                        href="/collection"
                        className="font-display text-xs uppercase tracking-wide text-shard-400 hover:text-shard-300 mt-3 inline-block"
                    >
                        Voir la collection →
                    </Link>
                </div>
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                    <p className="font-display text-xs uppercase tracking-wide text-text-low">Streak quotidienne</p>
                    <p className="font-display text-3xl font-bold text-text-high mt-2">
                        {dailyLogin.streak_count}<span className="text-sm text-text-low ml-2">jour{dailyLogin.streak_count > 1 ? 's' : ''}</span>
                    </p>
                    <p className="font-mono text-xs text-text-low mt-2">Cycle mensuel : jour {dailyLogin.streak_day} / 30</p>
                </div>
            </section>

            {/* Daily login claim */}
            <section className="rounded-lg bg-bg-elev1 border border-shard-500/30 p-6 mb-8">
                <header className="flex items-center justify-between mb-3">
                    <div>
                        <p className="font-display text-xs uppercase tracking-mega text-shard-400">Récompense quotidienne</p>
                        <h2 className="font-display font-bold text-lg uppercase tracking-wide mt-1">Jour {dailyLogin.streak_day}</h2>
                    </div>
                    {dailyLogin.reward_claimed ? (
                        <span className="font-display text-xs uppercase tracking-wide text-success">Réclamée</span>
                    ) : (
                        <Button onClick={claimDaily} variant="shard">Réclamer</Button>
                    )}
                </header>
                <ul className="flex gap-4 flex-wrap">
                    {dailyLogin.reward.map((r, i) => (
                        <li key={i} className="font-mono text-sm text-text-medium">
                            <span className="text-shard-400">{r.amount}</span> {r.type}
                        </li>
                    ))}
                </ul>
                {props.errors.daily && <p className="text-danger text-xs font-mono mt-2">{props.errors.daily}</p>}
            </section>

            {/* Missions */}
            <section className="grid md:grid-cols-2 gap-6">
                <div>
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Journalières</h2>
                    {dailyMissions.length === 0
                        ? <p className="text-text-medium font-mono text-sm">Aucune mission active.</p>
                        : <ul className="flex flex-col gap-3">
                            {dailyMissions.map(m => (
                                <li key={m.id}>
                                    <MissionCard
                                        title={m.title}
                                        description={m.description ?? ''}
                                        progress={m.progress}
                                        total={m.objective_target}
                                        rewardLabel={`+${m.xp_reward} XP`}
                                        completed={m.completed}
                                        claimed={m.reward_claimed}
                                        onClaim={() => claimMission(m.slug)}
                                    />
                                </li>
                            ))}
                        </ul>
                    }
                </div>
                <div>
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Hebdomadaires</h2>
                    {weeklyMissions.length === 0
                        ? <p className="text-text-medium font-mono text-sm">Aucune mission active.</p>
                        : <ul className="flex flex-col gap-3">
                            {weeklyMissions.map(m => (
                                <li key={m.id}>
                                    <MissionCard
                                        title={m.title}
                                        description={m.description ?? ''}
                                        progress={m.progress}
                                        total={m.objective_target}
                                        rewardLabel={`+${m.xp_reward} XP`}
                                        completed={m.completed}
                                        claimed={m.reward_claimed}
                                        onClaim={() => claimMission(m.slug)}
                                    />
                                </li>
                            ))}
                        </ul>
                    }
                </div>
            </section>

            {props.errors.mission && (
                <div className="mt-6">
                    <Alert variant="danger" title="Réclamation impossible">{props.errors.mission}</Alert>
                </div>
            )}
        </>
    );
}

Dashboard.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
