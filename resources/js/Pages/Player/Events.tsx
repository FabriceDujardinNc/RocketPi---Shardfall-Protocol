import { Head, Link } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

interface RewardLine { type: string; amount: number }

interface EventItem {
    id: number;
    slug: string;
    name: string;
    type: 'limited_banner' | 'pvp_mode' | 'pve_mode' | 'story' | 'collaboration';
    description: string | null;
    image_url: string | null;
    starts_at: string | null;
    ends_at: string | null;
    rewards_pool: RewardLine[] | null;
    phase: 'scheduled' | 'current' | 'expired';
    banner: { slug: string; name: string } | null;
}

interface Props {
    events: EventItem[];
    counts: { current: number; scheduled: number; expired: number };
}

const TYPE_LABEL: Record<string, string> = {
    limited_banner: 'Bannière limitée',
    pvp_mode: 'Mode PvP',
    pve_mode: 'Mode PvE',
    story: 'Story',
    collaboration: 'Collaboration',
};

const PHASE_COLOR: Record<string, string> = {
    current: 'text-success border-success/40 bg-success/10',
    scheduled: 'text-warning border-warning/40 bg-warning/10',
    expired: 'text-text-low border-border-default bg-bg-elev2',
};

const PHASE_LABEL: Record<string, string> = {
    current: 'En cours',
    scheduled: 'Bientôt',
    expired: 'Terminé',
};

function formatDateRange(start: string | null, end: string | null): string {
    if (!start && !end) return '—';
    const fmt = (s: string) => new Date(s).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
    if (!end) return `Dès le ${fmt(start!)}`;
    if (!start) return `Jusqu'au ${fmt(end)}`;
    return `${fmt(start)} → ${fmt(end)}`;
}

function daysRemaining(end: string | null): number | null {
    if (!end) return null;
    const diff = new Date(end).getTime() - Date.now();
    return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)));
}

export default function Events({ events, counts }: Props) {
    return (
        <>
            <Head title="Événements" />

            <header className="mb-6">
                <p className="font-display text-xs uppercase tracking-mega text-shard-400">Événements limités</p>
                <h1 className="font-display font-bold text-2xl sm:text-3xl uppercase tracking-wide mt-1">
                    Calendrier
                </h1>
                <p className="font-body text-sm text-text-medium mt-2 max-w-2xl">
                    Bannières temporaires, modes spéciaux, collaborations — ne loupe rien.
                </p>
                <div className="flex flex-wrap gap-3 mt-3 font-mono text-xs">
                    <span className="text-success">{counts.current} en cours</span>
                    <span className="text-warning">{counts.scheduled} à venir</span>
                    <span className="text-text-low">{counts.expired} terminé{counts.expired > 1 ? 's' : ''}</span>
                </div>
            </header>

            {events.length === 0 ? (
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center font-body text-sm text-text-medium">
                    Aucun événement actif. Reviens bientôt — le calendrier est régulièrement enrichi.
                </div>
            ) : (
                <section className="grid gap-4 sm:grid-cols-2">
                    {events.map((e) => {
                        const days = daysRemaining(e.ends_at);
                        return (
                            <article
                                key={e.id}
                                className={
                                    'rounded-lg border overflow-hidden flex flex-col ' +
                                    (e.phase === 'current' ? 'border-shard-500/40 shadow-glow-shard'
                                        : 'border-border-default')
                                }
                            >
                                <div className="aspect-[16/7] bg-bg-elev2 relative">
                                    {e.image_url ? (
                                        <img
                                            src={e.image_url}
                                            alt={e.name}
                                            loading="lazy"
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <div className="absolute inset-0 flex items-center justify-center font-display font-bold text-2xl text-text-low uppercase">
                                            {e.name}
                                        </div>
                                    )}
                                    <span className={`absolute top-3 left-3 font-display text-[10px] uppercase tracking-mega px-2 py-1 rounded border ${PHASE_COLOR[e.phase]}`}>
                                        {PHASE_LABEL[e.phase]}
                                        {e.phase === 'current' && days !== null && days <= 7 && (
                                            <> · J-{days}</>
                                        )}
                                    </span>
                                </div>
                                <div className="p-5 bg-bg-elev1 flex-1 flex flex-col gap-2">
                                    <p className="font-mono text-[11px] text-text-medium">{TYPE_LABEL[e.type] ?? e.type}</p>
                                    <h2 className="font-display font-bold text-base uppercase tracking-wide text-text-high">
                                        {e.name}
                                    </h2>
                                    {e.description && (
                                        <p className="font-body text-sm text-text-medium line-clamp-3">
                                            {e.description}
                                        </p>
                                    )}
                                    <p className="font-mono text-xs text-text-low">
                                        {formatDateRange(e.starts_at, e.ends_at)}
                                    </p>

                                    {e.rewards_pool && e.rewards_pool.length > 0 && (
                                        <ul className="flex flex-wrap gap-2 mt-1">
                                            {e.rewards_pool.slice(0, 4).map((r, i) => (
                                                <li
                                                    key={i}
                                                    className="font-mono text-[11px] px-2 py-0.5 rounded bg-bg-elev2 border border-border-default text-text-medium"
                                                >
                                                    <span className="text-shard-400">{r.amount}</span> {r.type}
                                                </li>
                                            ))}
                                            {e.rewards_pool.length > 4 && (
                                                <li className="font-mono text-[11px] text-text-low">
                                                    +{e.rewards_pool.length - 4}
                                                </li>
                                            )}
                                        </ul>
                                    )}

                                    {e.banner && (
                                        <div className="mt-auto pt-3 border-t border-border-default">
                                            <Link
                                                href={`/gacha/${e.banner.slug}`}
                                                className="font-display text-xs uppercase tracking-wide text-shard-400 hover:text-shard-300"
                                            >
                                                Tenter la bannière {e.banner.name} →
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            </article>
                        );
                    })}
                </section>
            )}
        </>
    );
}

Events.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
