import { Head, router, usePage } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import Progress from '@ui/Progress';
import BattlePassNode from '@game/BattlePassNode';
import CurrencyDisplay from '@game/CurrencyDisplay';

interface RewardLine { type: string; amount: number }

interface Tier {
    id: number;
    tier_number: number;
    xp_required: number;
    free_reward: RewardLine[] | null;
    premium_reward: RewardLine[] | null;
    is_milestone: boolean;
}

interface Season {
    id: number;
    name: string;
    season_number: number;
    total_tiers: number;
    starts_at: string;
    ends_at: string;
    premium_price_shards: number;
}

interface ProgressVM {
    is_premium: boolean;
    xp_earned: number;
    current_tier: number;
    claimed_tiers: number[];
}

interface Props {
    season: Season | null;
    tiers: Tier[];
    progress: ProgressVM | null;
    shards: number;
    wallet: Record<string, number>;
}

interface PageProps {
    flash?: { status?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}

export default function BattlePass({ season, tiers, progress, shards, wallet }: Props) {
    const { props } = usePage<PageProps>();

    if (!season || !progress) {
        return (
            <>
                <Head title="Battle Pass" />
                <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-8">Battle Pass</h1>
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                    Aucune saison active.
                </div>
            </>
        );
    }

    const purchase = () => {
        if (!confirm(`Acheter le Battle Pass premium pour ${season.premium_price_shards} shards ?`)) return;
        router.post(`/battlepass/${season.id}/purchase`, {}, { preserveScroll: true });
    };
    const claim = (tier: Tier) => router.post(`/battlepass/tier/${tier.id}/claim`, {}, { preserveScroll: true });

    // XP nécessaire pour le tier suivant
    const nextTier = tiers.find(t => t.tier_number === progress.current_tier + 1);
    const xpInTier = nextTier
        ? progress.xp_earned - (tiers[progress.current_tier - 1]?.xp_required ?? 0)
        : 0;
    const xpToNext = nextTier
        ? nextTier.xp_required - (tiers[progress.current_tier - 1]?.xp_required ?? 0)
        : 1;

    return (
        <>
            <Head title={season.name} />

            <header className="mb-6 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">Saison {season.season_number}</p>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">{season.name}</h1>
                    <p className="font-mono text-xs text-text-low mt-1">
                        {new Date(season.starts_at).toLocaleDateString('fr-FR')} → {new Date(season.ends_at).toLocaleDateString('fr-FR')}
                    </p>
                </div>
                <div className="flex flex-wrap gap-3">
                    <CurrencyDisplay currency="premium" amount={shards} />
                    {Object.entries(wallet ?? {})
                        .filter(([type]) => type !== 'shards')
                        .map(([type, amount]) => (
                            <div
                                key={type}
                                className="px-3 py-1.5 rounded bg-bg-elev1 border border-border-default font-mono text-xs"
                            >
                                <span className="text-text-low uppercase tracking-wide">{type.replace(/_/g, ' ')}</span>
                                <span className="ml-2 text-text-high">{amount.toLocaleString('fr-FR')}</span>
                            </div>
                        ))}
                </div>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {props.errors.battlepass && <div className="mb-4"><Alert variant="danger">{props.errors.battlepass}</Alert></div>}

            {/* Progression actuelle */}
            <section className="rounded-lg bg-bg-elev1 border border-shard-500/30 p-6 mb-6">
                <div className="flex items-center justify-between flex-wrap gap-3 mb-3">
                    <div>
                        <p className="font-display text-xs uppercase tracking-wide text-text-low">Palier actuel</p>
                        <p className="font-display text-3xl font-bold text-shard-400 mt-1">
                            {progress.current_tier} <span className="text-text-low text-base">/ {season.total_tiers}</span>
                        </p>
                    </div>
                    {progress.is_premium ? (
                        <span className="font-display text-xs uppercase tracking-wide px-3 py-1.5 rounded bg-rarity-legendary/15 text-rarity-legendary border border-rarity-legendary/40">
                            Premium actif
                        </span>
                    ) : (
                        <Button onClick={purchase} variant="shard" disabled={shards < season.premium_price_shards}>
                            Activer Premium ({season.premium_price_shards} ✦)
                        </Button>
                    )}
                </div>
                {nextTier && (
                    <Progress
                        value={xpInTier}
                        max={xpToNext}
                        label={`${xpInTier} / ${xpToNext} XP vers palier ${nextTier.tier_number}`}
                    />
                )}
                <p className="font-mono text-xs text-text-low mt-2">
                    {progress.xp_earned.toLocaleString('fr-FR')} XP cumulé
                </p>
            </section>

            {/* Roadmap des paliers */}
            <section>
                <h2 className="font-display text-sm uppercase tracking-wide text-text-medium mb-3">
                    Roadmap des {season.total_tiers} paliers
                </h2>
                <div className="overflow-x-auto pb-4">
                    <div className="flex gap-2 min-w-max">
                        {tiers.map(t => {
                            const unlocked = progress.current_tier >= t.tier_number;
                            const claimed  = progress.claimed_tiers.includes(t.tier_number);
                            return (
                                <BattlePassNode
                                    key={t.id}
                                    tier={t.tier_number}
                                    label={t.is_milestone ? '⭐ Milestone' : `${t.xp_required} XP`}
                                    free={!progress.is_premium}
                                    premium={progress.is_premium}
                                    unlocked={unlocked}
                                    claimed={claimed}
                                    onClick={() => unlocked && !claimed && claim(t)}
                                />
                            );
                        })}
                    </div>
                </div>

                {/* Récapitulatif des rewards par palier */}
                <div className="mt-6 grid md:grid-cols-2 gap-4">
                    {tiers.filter(t => t.is_milestone || t.tier_number === progress.current_tier + 1).slice(0, 4).map(t => (
                        <div key={t.id} className="rounded-md bg-bg-elev1 border border-border-default p-4">
                            <p className="font-display text-xs uppercase tracking-wide text-shard-400">
                                Palier {t.tier_number} {t.is_milestone && '⭐'}
                            </p>
                            <div className="mt-2 grid grid-cols-2 gap-3 text-xs font-mono">
                                <div>
                                    <p className="text-text-low uppercase tracking-wide">Free</p>
                                    {t.free_reward?.map((r, i) => (
                                        <p key={i} className="text-text-medium"><span className="text-shard-400">{r.amount}</span> {r.type}</p>
                                    ))}
                                </div>
                                <div>
                                    <p className="text-text-low uppercase tracking-wide">Premium</p>
                                    {t.premium_reward?.map((r, i) => (
                                        <p key={i} className="text-rarity-legendary"><span className="text-text-high">{r.amount}</span> {r.type}</p>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </section>
        </>
    );
}

BattlePass.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
