import { Head, router, usePage } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import Progress from '@ui/Progress';

type Category = 'collection' | 'combat' | 'social' | 'progression' | 'special';

interface RewardLine { type: string; amount: number }

interface AchievementVM {
    id: number;
    key: string;
    title: string;
    description: string | null;
    category: Category;
    icon_url: string | null;
    rewards: RewardLine[] | null;
    progress: number;
    completed: boolean;
    reward_claimed: boolean;
    completed_at: string | null;
}

interface Props {
    achievements: AchievementVM[];
    stats: { total: number; completed: number; claimable: number };
}

interface PageProps {
    flash?: { status?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}

const CATEGORY_LABELS: Record<Category, string> = {
    collection:  'Collection',
    combat:      'Combat',
    social:      'Social',
    progression: 'Progression',
    special:     'Spéciaux',
};

const CATEGORY_COLORS: Record<Category, string> = {
    collection:  'text-shard-400',
    combat:      'text-danger',
    social:      'text-info',
    progression: 'text-rarity-legendary',
    special:     'text-warning',
};

export default function Achievements({ achievements, stats }: Props) {
    const { props } = usePage<PageProps>();

    const claim = (id: number) => router.post(`/achievements/${id}/claim`, {}, { preserveScroll: true });

    const categories: Category[] = ['collection', 'progression', 'special', 'social', 'combat'];

    return (
        <>
            <Head title="Achievements" />

            <header className="mb-6">
                <p className="font-display text-xs uppercase tracking-mega text-shard-400">Hall des honneurs</p>
                <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">Achievements</h1>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {props.errors.achievement && <div className="mb-4"><Alert variant="danger">{props.errors.achievement}</Alert></div>}

            <section className="grid md:grid-cols-3 gap-4 mb-8">
                <Stat label="Total" value={stats.total} />
                <Stat label="Complétés" value={stats.completed} accent="success" />
                <Stat label="À réclamer" value={stats.claimable} accent="warning" />
            </section>

            {categories.map(cat => {
                const list = achievements.filter(a => a.category === cat);
                if (list.length === 0) return null;

                return (
                    <section key={cat} className="mb-8">
                        <h2 className={`font-display text-sm uppercase tracking-wide mb-3 ${CATEGORY_COLORS[cat]}`}>
                            {CATEGORY_LABELS[cat]}
                            <span className="text-text-low font-mono text-xs ml-2">
                                ({list.filter(a => a.completed).length}/{list.length})
                            </span>
                        </h2>
                        <ul className="grid md:grid-cols-2 gap-3">
                            {list.map(a => (
                                <li key={a.id}>
                                    <AchievementCard achievement={a} onClaim={() => claim(a.id)} />
                                </li>
                            ))}
                        </ul>
                    </section>
                );
            })}
        </>
    );
}

function AchievementCard({ achievement, onClaim }: { achievement: AchievementVM; onClaim: () => void }) {
    const { id: _id, title, description, rewards, progress, completed, reward_claimed } = achievement;

    return (
        <div className={
            'rounded-lg border p-4 transition-colors duration-fast ' +
            (completed
                ? 'bg-bg-elev1 border-success/40'
                : 'bg-bg-elev1 border-border-default')
        }>
            <header className="flex items-start justify-between gap-3 mb-2">
                <div>
                    <h3 className="font-display font-semibold text-sm uppercase tracking-wide text-text-high">
                        {title}
                    </h3>
                    {description && (
                        <p className="font-body text-xs text-text-medium mt-1">{description}</p>
                    )}
                </div>
                {completed && !reward_claimed && (
                    <Button onClick={onClaim} size="sm" variant="shard">Réclamer</Button>
                )}
                {reward_claimed && (
                    <span className="font-display text-xs uppercase tracking-wide text-success">Réclamé</span>
                )}
            </header>

            {!completed && (
                <Progress
                    value={progress}
                    max={1}
                    label={`${progress > 0 ? 'En cours' : 'Verrouillé'}`}
                    variant="shard"
                />
            )}

            {rewards && rewards.length > 0 && (
                <ul className="mt-3 flex gap-3 flex-wrap font-mono text-xs">
                    {rewards.map((r, i) => (
                        <li key={i} className="text-shard-400">
                            <span className="text-text-high">{r.amount}</span> {r.type}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function Stat({ label, value, accent = 'high' }: { label: string; value: number; accent?: 'high' | 'success' | 'warning' }) {
    const color = accent === 'success' ? 'text-success' : accent === 'warning' ? 'text-warning' : 'text-text-high';
    return (
        <div className="rounded-lg bg-bg-elev1 border border-border-default p-4">
            <p className="font-display text-xs uppercase tracking-wide text-text-low">{label}</p>
            <p className={`font-display text-3xl font-bold mt-2 ${color}`}>{value}</p>
        </div>
    );
}

Achievements.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
