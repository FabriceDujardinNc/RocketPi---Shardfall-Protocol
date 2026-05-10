import { Head, Link, router, usePage } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import PityCounter from '@game/PityCounter';
import CurrencyDisplay from '@game/CurrencyDisplay';
import RarityBadge from '@game/RarityBadge';
import GachaPullAnimation from '@game/GachaPullAnimation';
import { useState } from 'react';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';

interface Banner {
    id: number;
    name: string;
    tag: string | null;
    subtitle: string | null;
    type: string;
    featured_operator: string | null;
    rate_up_operators: string[] | null;
    banner_image_url: string | null;
    rate_legendary: string;
    rate_epic: string;
    rate_rare: string;
    rate_common: string;
    pity_legendary: number;
    pity_epic: number;
    soft_pity_start: number;
    ends_at: string | null;
}

interface PullResult {
    operator: {
        id: number;
        name: string;
        codename: string;
        rarity: Rarity;
        faction: string;
        portrait_url: string | null;
    };
    is_new: boolean;
    was_pity_hit: boolean;
    was_soft_pity: boolean;
    was_rate_up: boolean;
}

interface Props {
    banner: Banner;
    pity: { legendary_counter: number; epic_counter: number; total_pulls: number };
    shards: number;
    cost: { single: number; ten: number };
    recentPulls: Array<{
        id: number;
        rarity: Rarity;
        was_rate_up: boolean;
        created_at: string;
        operator: { name: string; codename: string; rarity: Rarity; faction: string; portrait_url: string | null };
    }>;
}

interface PageProps {
    flash?: { pullResults?: PullResult[]; status?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}

export default function GachaBanner({ banner, pity, shards, cost, recentPulls }: Props) {
    const { props } = usePage<PageProps>();
    const [animating, setAnimating] = useState<PullResult[] | null>(null);
    const [pulling, setPulling] = useState(false);

    // Récupère les résultats du dernier pull (depuis flash data)
    const flashResults = props.flash?.pullResults;
    if (flashResults && !animating && flashResults.length > 0) {
        setAnimating(flashResults);
    }

    const doPull = (count: 1 | 10) => {
        if (pulling) return;
        setPulling(true);
        router.post(`/gacha/${banner.id}/pull`, { count }, {
            preserveScroll: true,
            onFinish: () => setPulling(false),
        });
    };

    const canSingle = shards >= cost.single;
    const canTen    = shards >= cost.ten;

    return (
        <>
            <Head title={banner.name} />

            <Link href="/gacha" className="font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high">
                ← Toutes les bannières
            </Link>

            <header className="mt-2 mb-6 flex items-end justify-between">
                <div>
                    {banner.tag && (
                        <p className="font-display text-xs uppercase tracking-mega text-shard-400">{banner.tag}</p>
                    )}
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">{banner.name}</h1>
                    {banner.subtitle && (
                        <p className="font-mono text-sm text-text-medium mt-1">{banner.subtitle}</p>
                    )}
                </div>
                <CurrencyDisplay currency="premium" amount={shards} />
            </header>

            {/* Bannière visuel */}
            <div className="rounded-lg overflow-hidden border-2 border-shard-500/40 shadow-glow-shard mb-6 bg-bg-elev1">
                <div className="aspect-[16/6] bg-bg-elev2 flex items-center justify-center">
                    {banner.banner_image_url
                        ? <img src={banner.banner_image_url} alt={banner.name} className="w-full h-full object-cover" />
                        : <span className="font-display text-2xl text-text-low uppercase tracking-mega">{banner.featured_operator ?? banner.name}</span>}
                </div>
            </div>

            {props.errors.gacha && (
                <div className="mb-4">
                    <Alert variant="danger" title="Tirage impossible">{props.errors.gacha}</Alert>
                </div>
            )}

            <div className="grid md:grid-cols-2 gap-6 mb-8">
                {/* Pity & taux */}
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-6 flex flex-col gap-4">
                    <PityCounter
                        current={pity.legendary_counter}
                        threshold={banner.pity_legendary}
                        softPity={banner.soft_pity_start}
                        rarity="legendary"
                    />
                    <PityCounter
                        current={pity.epic_counter}
                        threshold={banner.pity_epic}
                        rarity="epic"
                    />
                    <div className="pt-3 border-t border-border-default">
                        <p className="font-display text-xs uppercase tracking-wide text-text-low mb-2">Taux de base</p>
                        <ul className="font-mono text-xs text-text-medium space-y-1">
                            <li>Légendaire : <span className="text-rarity-legendary">{(parseFloat(banner.rate_legendary) * 100).toFixed(2)}%</span></li>
                            <li>Épique     : <span className="text-rarity-epic">{(parseFloat(banner.rate_epic) * 100).toFixed(2)}%</span></li>
                            <li>Rare       : <span className="text-rarity-rare">{(parseFloat(banner.rate_rare) * 100).toFixed(2)}%</span></li>
                            <li>Commun     : <span className="text-rarity-common">{(parseFloat(banner.rate_common) * 100).toFixed(2)}%</span></li>
                        </ul>
                    </div>
                </section>

                {/* Boutons de tirage */}
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-6 flex flex-col gap-3 justify-center">
                    <Button
                        size="lg"
                        variant="secondary"
                        fullWidth
                        loading={pulling}
                        disabled={!canSingle}
                        onClick={() => doPull(1)}
                    >
                        Tirer ×1 — {cost.single} ✦
                    </Button>
                    <Button
                        size="lg"
                        variant="shard"
                        fullWidth
                        loading={pulling}
                        disabled={!canTen}
                        onClick={() => doPull(10)}
                    >
                        Tirer ×10 — {cost.ten} ✦
                    </Button>
                    {!canSingle && (
                        <p className="font-mono text-xs text-danger text-center">Solde insuffisant — visite la boutique.</p>
                    )}
                    <p className="font-mono text-[10px] text-text-low text-center mt-2">
                        Total tirages bannière : {pity.total_pulls}
                    </p>
                </section>
            </div>

            {/* Historique récent */}
            {recentPulls.length > 0 && (
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                    <h2 className="font-display text-sm uppercase tracking-wide text-text-medium mb-4">
                        Derniers tirages
                    </h2>
                    <ul className="grid grid-cols-2 md:grid-cols-5 gap-2">
                        {recentPulls.map(p => (
                            <li key={p.id} className="rounded-md bg-bg-elev2 border border-border-default p-2 flex flex-col items-center gap-1">
                                <div className="aspect-square w-full bg-bg-elev3 rounded flex items-center justify-center font-display text-xs text-text-low">
                                    {p.operator.portrait_url
                                        ? <img src={p.operator.portrait_url} alt={p.operator.name} className="w-full h-full object-cover rounded" />
                                        : p.operator.codename}
                                </div>
                                <RarityBadge rarity={p.rarity} />
                                <span className="font-display text-[10px] uppercase text-text-medium tracking-wide truncate w-full text-center">
                                    {p.operator.name}
                                </span>
                                {p.was_rate_up && <span className="font-display text-[9px] uppercase tracking-mega text-shard-400">Rate-up</span>}
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {/* Animation de tirage */}
            {animating && (
                <GachaPullAnimation
                    results={animating.map(r => ({
                        operatorId: r.operator.id,
                        name: r.operator.name,
                        rarity: r.operator.rarity,
                        portraitUrl: r.operator.portrait_url ?? undefined,
                        isNew: r.is_new,
                    }))}
                    onComplete={() => setAnimating(null)}
                />
            )}
        </>
    );
}

GachaBanner.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
