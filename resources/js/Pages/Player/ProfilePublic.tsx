import PlayerLayout from '@/Layouts/PlayerLayout';
import SEO from '@/Components/SEO';
import ReportButton from '@game/ReportButton';
import { usePage } from '@inertiajs/react';

interface User {
    id: number;
    slug: string | null;
    name: string;
    display_name: string | null;
    avatar_url: string | null;
    account_level: number;
    account_xp: number;
    member_since: string | null;
    faction: 'ORBIT' | 'FERRO' | 'VEIL' | null;
}

interface Stats {
    operators_owned: number;
    operators_total: number;
    achievements_done: number;
    achievements_total: number;
}

interface Affinity {
    operator_codename: string | null;
    operator_name: string | null;
    faction: 'ORBIT' | 'FERRO' | 'VEIL' | null;
    rarity: 'common' | 'rare' | 'epic' | 'legendary' | null;
    level: number;
}

interface Rank {
    season: string;
    type: string;
    rank: number;
    score: number;
}

interface Props {
    user: User;
    stats: Stats;
    topAffinities: Affinity[];
    ranks: Rank[];
}

const FACTION_COLOR: Record<NonNullable<Affinity['faction']>, string> = {
    ORBIT: 'text-orbit-500',
    FERRO: 'text-ferro-500',
    VEIL:  'text-veil-500',
};

const RARITY_COLOR: Record<NonNullable<Affinity['rarity']>, string> = {
    common:    'border-rarity-common',
    rare:      'border-rarity-rare',
    epic:      'border-rarity-epic',
    legendary: 'border-rarity-legendary',
};

export default function ProfilePublic({ user, stats, topAffinities, ranks }: Props) {
    const { auth } = usePage<{ auth?: { user?: { id?: number } } }>().props;
    const isOwnProfile = auth?.user?.id === user.id;

    const collectionPct  = stats.operators_total > 0
        ? Math.round((stats.operators_owned / stats.operators_total) * 100)
        : 0;
    const honorsPct      = stats.achievements_total > 0
        ? Math.round((stats.achievements_done / stats.achievements_total) * 100)
        : 0;

    const displayName = user.display_name ?? user.name;
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://rocketpi.pro';
    const profileUrl = user.slug ? `${baseUrl}/profile/${user.slug}` : baseUrl;

    const personLd = {
        '@context': 'https://schema.org',
        '@type': 'Person',
        name: displayName,
        url: profileUrl,
        ...(user.avatar_url ? { image: user.avatar_url } : {}),
        identifier: user.slug ?? String(user.id),
        memberOf: user.faction
            ? { '@type': 'Organization', name: user.faction }
            : undefined,
    };

    const breadcrumbLd = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: 'Accueil', item: baseUrl },
            { '@type': 'ListItem', position: 2, name: 'Profils', item: `${baseUrl}/top` },
            { '@type': 'ListItem', position: 3, name: displayName, item: profileUrl },
        ],
    };

    const seoDesc = `Profil de ${displayName} (niveau ${user.account_level}${user.faction ? `, faction ${user.faction}` : ''}) sur RocketPi: Shardfall Protocol. ${stats.operators_owned}/${stats.operators_total} opérateurs recrutés, ${stats.achievements_done}/${stats.achievements_total} honneurs débloqués.`;

    return (
        <>
            <SEO
                title={displayName}
                description={seoDesc}
                type="profile"
                image={user.avatar_url ?? undefined}
                canonical={profileUrl}
                jsonLd={[personLd, breadcrumbLd]}
            />

            {/* ── Header ─────────────────────────────────────────────── */}
            <header className="rounded-lg bg-bg-elev1 border border-border-default p-6 md:p-8 flex flex-wrap items-center gap-6 mb-6">
                {user.avatar_url ? (
                    <img src={user.avatar_url} alt="" className="size-20 md:size-24 rounded-full border-2 border-shard-500" />
                ) : (
                    <div className="size-20 md:size-24 rounded-full bg-bg-elev2 border-2 border-border-default flex items-center justify-center font-display text-2xl text-text-low uppercase">
                        {(user.display_name ?? user.name).slice(0, 2)}
                    </div>
                )}
                <div className="flex-1 min-w-0">
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">Opérateur</p>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1 truncate">
                        {user.display_name ?? user.name}
                    </h1>
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 font-mono text-sm text-text-medium">
                        <span>Niveau <span className="text-text-high">{user.account_level}</span></span>
                        <span>·</span>
                        <span>{user.account_xp.toLocaleString()} XP</span>
                        {user.member_since && (
                            <>
                                <span>·</span>
                                <span>Recruté le <span className="text-text-high">{user.member_since}</span></span>
                            </>
                        )}
                    </div>
                </div>
                {auth?.user && ! isOwnProfile && (
                    <div className="self-start">
                        <ReportButton
                            reportedId={user.id}
                            reportedName={user.display_name ?? user.name}
                            variant="icon"
                        />
                    </div>
                )}
            </header>

            {/* ── Stats grid ─────────────────────────────────────────── */}
            <section className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Stat label="Niveau" value={user.account_level.toString()} sub={`${user.account_xp.toLocaleString()} XP`} />
                <Stat
                    label="Collection"
                    value={`${stats.operators_owned} / ${stats.operators_total}`}
                    sub={`${collectionPct}% du roster`}
                    accent="shard"
                />
                <Stat
                    label="Honneurs"
                    value={`${stats.achievements_done} / ${stats.achievements_total}`}
                    sub={`${honorsPct}% débloqués`}
                    accent="success"
                />
                <Stat
                    label="Classements actifs"
                    value={ranks.length.toString()}
                    sub={ranks.length > 0 ? 'Voir ci-dessous' : 'Aucun pour l’instant'}
                    accent="warning"
                />
            </section>

            {/* ── Top affinities ─────────────────────────────────────── */}
            <section className="mb-6">
                <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">
                    Opérateurs favoris
                </h2>
                {topAffinities.length === 0 ? (
                    <EmptyCard>Aucun opérateur lié pour l’instant.</EmptyCard>
                ) : (
                    <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        {topAffinities.map((a, i) => (
                            <div
                                key={i}
                                className={`rounded-lg bg-bg-elev1 border-2 ${a.rarity ? RARITY_COLOR[a.rarity] : 'border-border-default'} p-4`}
                            >
                                <p className="font-display text-xs uppercase tracking-wide text-text-low">
                                    {a.operator_codename ?? '—'}
                                </p>
                                <p className="font-display text-lg uppercase tracking-wide text-text-high mt-1">
                                    {a.operator_name ?? 'Opérateur inconnu'}
                                </p>
                                <div className="flex items-center justify-between mt-3 font-mono text-xs">
                                    {a.faction && (
                                        <span className={`font-display uppercase tracking-wide ${FACTION_COLOR[a.faction]}`}>
                                            {a.faction}
                                        </span>
                                    )}
                                    <span className="text-text-medium">
                                        Affinité <span className="text-shard-400">{a.level}</span>/10
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </section>

            {/* ── Ranks ──────────────────────────────────────────────── */}
            <section>
                <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">
                    Meilleurs classements
                </h2>
                {ranks.length === 0 ? (
                    <EmptyCard>Pas encore de score sur les classements actifs.</EmptyCard>
                ) : (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default overflow-hidden">
                        <table className="w-full text-sm">
                            <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                                <tr>
                                    <th className="px-3 py-2 text-left">Saison</th>
                                    <th className="px-3 py-2 text-left">Type</th>
                                    <th className="px-3 py-2 text-right">Rang</th>
                                    <th className="px-3 py-2 text-right">Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                {ranks.map((r, i) => (
                                    <tr key={i} className="border-t border-border-default">
                                        <td className="px-3 py-2 text-text-high">{r.season}</td>
                                        <td className="px-3 py-2 font-mono text-xs text-text-low uppercase">{r.type}</td>
                                        <td className="px-3 py-2 font-mono text-right text-shard-400">#{r.rank}</td>
                                        <td className="px-3 py-2 font-mono text-right text-text-high">{r.score.toLocaleString()}</td>
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

function Stat({ label, value, sub, accent = 'high' }: {
    label: string;
    value: string;
    sub?: string;
    accent?: 'high' | 'shard' | 'success' | 'warning';
}) {
    const color =
        accent === 'shard'   ? 'text-shard-400' :
        accent === 'success' ? 'text-success'   :
        accent === 'warning' ? 'text-warning'   :
                               'text-text-high';
    return (
        <div className="rounded-lg bg-bg-elev1 border border-border-default p-4">
            <p className="font-display text-xs uppercase tracking-wide text-text-low">{label}</p>
            <p className={`font-display text-3xl font-bold mt-2 ${color}`}>{value}</p>
            {sub && <p className="font-mono text-xs text-text-medium mt-1">{sub}</p>}
        </div>
    );
}

function EmptyCard({ children }: { children: React.ReactNode }) {
    return (
        <div className="rounded-lg bg-bg-elev1 border border-border-default p-6 text-center text-text-medium font-mono text-sm">
            {children}
        </div>
    );
}

ProfilePublic.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
