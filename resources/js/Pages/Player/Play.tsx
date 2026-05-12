import { Head, router } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import UnityCanvas from '@game/UnityCanvas';

interface Rank {
    points: number;
    tier: string;
    next_tier_threshold: number | null;
}

interface Daily {
    played: number;
    limit: number;
}

interface HistoryEntry {
    id: number;
    mode: 'deathmatch' | 'pve' | 'custom' | 'training' | null;
    rank_type: 'ranked' | 'casual' | null;
    started_at: string | null;
    duration_seconds: number | null;
    score: number;
    kills: number;
    deaths: number;
    assists: number;
    won: boolean;
    is_mvp: boolean;
    rank_points_delta: number;
}

interface UnityConfig {
    api_base_url: string;
    api_token: string;
    user_id: number;
    locale: string;
}

interface Props {
    rank: Rank;
    daily: Daily;
    history: HistoryEntry[];
    photonAppId: string | null;
    unityConfig: UnityConfig | null;
}

const TIER_LABEL: Record<string, string> = {
    bronze: 'Bronze',
    silver: 'Argent',
    gold: 'Or',
    platinum: 'Platine',
    diamond: 'Diamant',
    master: 'Maître',
};

const TIER_COLOR: Record<string, string> = {
    bronze: 'text-rarity-rare',
    silver: 'text-text-medium',
    gold: 'text-warning',
    platinum: 'text-shard-400',
    diamond: 'text-rarity-epic',
    master: 'text-rarity-legendary',
};

const MODE_LABEL: Record<string, string> = {
    deathmatch: 'Deathmatch',
    pve: 'PvE',
    custom: 'Custom',
    training: 'Entraînement',
};

function formatDuration(s: number | null): string {
    if (!s) return '—';
    const m = Math.floor(s / 60);
    const r = s % 60;
    return `${m}:${String(r).padStart(2, '0')}`;
}

export default function Play({ rank, daily, history, photonAppId, unityConfig }: Props) {
    const tierColor = TIER_COLOR[rank.tier] ?? 'text-text-medium';
    const progress = rank.next_tier_threshold
        ? Math.min(100, Math.round((rank.points / rank.next_tier_threshold) * 100))
        : 100;
    const dailyPct = Math.min(100, Math.round((daily.played / daily.limit) * 100));

    return (
        <>
            <Head title="Jouer" />

            <header className="mb-6 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">Champ de bataille</p>
                    <h1 className="font-display font-bold text-2xl sm:text-3xl uppercase tracking-wide mt-1">Jouer</h1>
                </div>
            </header>

            <section className="grid sm:grid-cols-2 gap-4 mb-6">
                <article className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <p className="font-display text-[10px] uppercase tracking-mega text-text-low">Rang compétitif</p>
                    <p className={`font-display font-bold text-3xl uppercase tracking-wide mt-2 ${tierColor}`}>
                        {TIER_LABEL[rank.tier] ?? rank.tier}
                    </p>
                    <p className="font-mono text-sm text-text-medium mt-1">
                        {rank.points} pts
                        {rank.next_tier_threshold && (
                            <span className="text-text-low"> / {rank.next_tier_threshold}</span>
                        )}
                    </p>
                    <div className="mt-3 h-2 rounded-full bg-bg-elev2 overflow-hidden">
                        <div
                            className="h-full bg-gradient-to-r from-shard-400 to-shard-600 transition-all duration-normal"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </article>

                <article className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <p className="font-display text-[10px] uppercase tracking-mega text-text-low">Matchs classés aujourd'hui</p>
                    <p className="font-display font-bold text-3xl text-text-high mt-2 tabular-nums">
                        {daily.played} <span className="text-text-low text-base">/ {daily.limit}</span>
                    </p>
                    <p className="font-mono text-xs text-text-medium mt-1">
                        Reset minuit UTC — anti-burnout
                    </p>
                    <div className="mt-3 h-2 rounded-full bg-bg-elev2 overflow-hidden">
                        <div
                            className={
                                'h-full transition-all duration-normal ' +
                                (dailyPct >= 90 ? 'bg-danger' : dailyPct >= 60 ? 'bg-warning' : 'bg-success')
                            }
                            style={{ width: `${dailyPct}%` }}
                        />
                    </div>
                </article>
            </section>

            <section className="mb-6">
                {unityConfig ? (
                    <UnityCanvas
                        apiBaseUrl={unityConfig.api_base_url}
                        apiToken={unityConfig.api_token}
                        userId={unityConfig.user_id}
                        locale={unityConfig.locale}
                        photonAppId={photonAppId}
                        onMatchFinished={() => router.reload({ only: ['history', 'rank', 'daily'] })}
                        onRequestReload={() => router.reload()}
                    />
                ) : (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                        <p className="font-display text-xs uppercase tracking-mega text-shard-400 mb-2">Unity 6 WebGL</p>
                        <p className="font-body text-sm">Configuration Unity indisponible.</p>
                    </div>
                )}
            </section>

            <section>
                <h2 className="font-display font-semibold text-lg uppercase tracking-wide mb-3">
                    Historique récent
                </h2>
                {history.length === 0 ? (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-8 text-center font-body text-sm text-text-medium">
                        Aucun match joué pour le moment.
                    </div>
                ) : (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                        <table className="w-full text-sm min-w-[700px]">
                            <thead className="bg-bg-elev2 font-display text-[11px] uppercase tracking-mega text-text-low">
                                <tr>
                                    <th className="text-left px-4 py-3">Mode</th>
                                    <th className="text-left px-4 py-3">Résultat</th>
                                    <th className="text-right px-4 py-3">Score</th>
                                    <th className="text-right px-4 py-3">K/D/A</th>
                                    <th className="text-right px-4 py-3">Durée</th>
                                    <th className="text-right px-4 py-3">Δ Pts</th>
                                </tr>
                            </thead>
                            <tbody>
                                {history.map((h) => (
                                    <tr key={h.id} className="border-t border-border-default">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <span className="font-display text-text-high">
                                                    {h.mode ? MODE_LABEL[h.mode] ?? h.mode : '—'}
                                                </span>
                                                {h.rank_type === 'ranked' && (
                                                    <span className="font-display text-[10px] uppercase tracking-mega text-shard-400 bg-shard-500/10 border border-shard-500/30 px-1.5 py-0.5 rounded">
                                                        Classé
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={
                                                'font-display text-xs uppercase tracking-mega ' +
                                                (h.won ? 'text-success' : 'text-danger')
                                            }>
                                                {h.won ? 'Victoire' : 'Défaite'}
                                            </span>
                                            {h.is_mvp && (
                                                <span className="ml-2 font-display text-[10px] uppercase tracking-mega text-rarity-legendary">
                                                    MVP
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right font-mono text-shard-400 tabular-nums">
                                            {h.score.toLocaleString('fr-FR')}
                                        </td>
                                        <td className="px-4 py-3 text-right font-mono text-text-medium tabular-nums">
                                            {h.kills}/{h.deaths}/{h.assists}
                                        </td>
                                        <td className="px-4 py-3 text-right font-mono text-text-low text-xs">
                                            {formatDuration(h.duration_seconds)}
                                        </td>
                                        <td className={
                                            'px-4 py-3 text-right font-mono tabular-nums ' +
                                            (h.rank_points_delta > 0 ? 'text-success'
                                                : h.rank_points_delta < 0 ? 'text-danger'
                                                : 'text-text-low')
                                        }>
                                            {h.rank_points_delta > 0 ? `+${h.rank_points_delta}` : h.rank_points_delta || '—'}
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

Play.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
