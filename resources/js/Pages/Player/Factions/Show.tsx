import { Head, Link } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import RarityBadge from '@game/RarityBadge';
import { ArrowLeft, Lock } from 'lucide-react';

interface Faction {
    slug: string;
    name: string;
    tagline: string | null;
    lore: string | null;
    color_hue: number;
    banner_image_url: string | null;
}

interface Operator {
    id: number;
    name: string;
    codename: string;
    role: string;
    rarity: 'common' | 'rare' | 'epic' | 'legendary';
    portrait_url: string | null;
    is_available: boolean;
    owned: boolean;
}

interface Props {
    faction: Faction;
    byRarity: Record<string, Operator[]>;
    stats: { total: number; owned: number };
}

const RARITY_ORDER = ['legendary', 'epic', 'rare', 'common'] as const;
const RARITY_LABEL: Record<string, string> = {
    legendary: 'Légendaire',
    epic:      'Épique',
    rare:      'Rare',
    common:    'Commun',
};

export default function PlayerFactionShow({ faction, byRarity, stats }: Props) {
    return (
        <>
            <Head title={faction.name} />

            <div className="flex items-center gap-4 mb-4 flex-wrap">
                <Link href="/factions" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1">
                    <ArrowLeft size={12} /> Toutes les factions
                </Link>
                <span className="text-text-low">·</span>
                <Link href="/collection" className="font-mono text-xs text-text-low hover:text-shard-400">
                    Ma collection complète →
                </Link>
            </div>

            <header className="mb-6 sm:mb-8 rounded-lg p-5 sm:p-8 border-2 relative overflow-hidden"
                style={{ borderColor: `oklch(0.55 0.15 ${faction.color_hue} / 0.5)` }}>
                {faction.banner_image_url && (
                    <div className="absolute inset-0 opacity-30">
                        <img src={faction.banner_image_url} alt="" className="w-full h-full object-cover" />
                        <div className="absolute inset-0 bg-gradient-to-t from-bg-base via-bg-base/70 to-transparent" />
                    </div>
                )}
                <div className="relative">
                    <p className="font-display text-xs uppercase tracking-mega" style={{ color: `oklch(0.78 0.18 ${faction.color_hue})` }}>Faction</p>
                    <h1 className="font-display font-bold text-3xl sm:text-4xl uppercase tracking-wide mt-1" style={{ color: `oklch(0.85 0.18 ${faction.color_hue})` }}>
                        {faction.name}
                    </h1>
                    {faction.tagline && <p className="text-text-medium text-sm sm:text-base mt-2 max-w-2xl">{faction.tagline}</p>}
                    <div className="mt-4 inline-flex items-center gap-2 font-mono text-xs text-text-low px-3 py-1.5 rounded bg-bg-elev1 border border-border-default">
                        Collection : {stats.owned} / {stats.total} opérateurs débloqués
                    </div>
                </div>
            </header>

            {faction.lore && (
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-6 mb-8">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Lore</h2>
                    <p className="text-text-medium text-sm whitespace-pre-wrap leading-relaxed">{faction.lore}</p>
                </section>
            )}

            <section>
                <h2 className="font-display font-bold text-lg uppercase tracking-wide mb-4">Collection</h2>
                {RARITY_ORDER.map(rarity => {
                    const list = byRarity?.[rarity] ?? [];
                    if (list.length === 0) return null;
                    return (
                        <div key={rarity} className="mb-8">
                            <h3 className="font-display text-xs uppercase tracking-wide text-text-low mb-3">{RARITY_LABEL[rarity]} ({list.length})</h3>
                            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                {list.map(op => (
                                    <Link
                                        key={op.id}
                                        href={op.owned ? `/operators/${op.slug}` : '#'}
                                        onClick={(e) => { if (!op.owned) e.preventDefault(); }}
                                        className={
                                            'group rounded-lg bg-bg-elev1 border-2 overflow-hidden transition-all duration-fast ' +
                                            (op.owned
                                                ? 'border-shard-500/40 hover:scale-[1.03] cursor-pointer'
                                                : 'border-border-default opacity-50 cursor-not-allowed grayscale')
                                        }
                                    >
                                        <div className="aspect-[3/4] bg-bg-elev2 relative">
                                            {op.portrait_url ? (
                                                <img src={op.portrait_url} alt={op.name} className="w-full h-full object-cover" />
                                            ) : (
                                                <div className="absolute inset-0 flex items-center justify-center font-display text-3xl uppercase text-text-low">
                                                    {op.name.slice(0, 2)}
                                                </div>
                                            )}
                                            {!op.owned && (
                                                <div className="absolute inset-0 flex items-center justify-center bg-bg-base/60">
                                                    <Lock size={32} className="text-text-low" />
                                                </div>
                                            )}
                                            <div className="absolute top-2 right-2">
                                                <RarityBadge rarity={op.rarity} />
                                            </div>
                                        </div>
                                        <div className="p-3">
                                            <p className="font-display font-bold text-sm uppercase tracking-wide text-text-high">{op.name}</p>
                                            <p className="font-mono text-xs text-text-low mt-0.5">{op.codename} · {op.role}</p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    );
                })}
            </section>
        </>
    );
}

PlayerFactionShow.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
