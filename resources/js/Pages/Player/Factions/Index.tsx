import { Head, Link } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import { Layers } from 'lucide-react';

interface Faction {
    slug: string;
    name: string;
    tagline: string | null;
    color_hue: number;
    banner_image_url: string | null;
    operators_count: number;
    operators_owned: number;
}

export default function PlayerFactionsIndex({ factions }: { factions: Faction[] }) {
    const totalOwned = factions.reduce((s, f) => s + f.operators_owned, 0);
    const totalOps   = factions.reduce((s, f) => s + f.operators_count, 0);

    return (
        <>
            <Head title="Factions" />
            <header className="mb-8 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">Univers RocketPi</p>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">Les trois factions</h1>
                    <p className="text-text-medium text-sm mt-2 max-w-2xl">
                        Après le Shardfall, trois courants se sont structurés autour des survivants exposés aux Shards.
                        Choisis-en un pour voir son lore complet et la collection d'opérateurs qui le compose.
                    </p>
                </div>
                <Link
                    href="/collection"
                    className="rounded-lg bg-bg-elev1 border border-shard-500/40 hover:bg-bg-elev2 hover:border-shard-400 p-4 flex items-center gap-3 transition-all duration-fast group"
                >
                    <Layers size={28} className="text-shard-400 group-hover:text-shard-300" />
                    <div>
                        <p className="font-display text-sm uppercase tracking-wide text-text-high">Ma collection complète</p>
                        <p className="font-mono text-xs text-text-low mt-0.5">{totalOwned} / {totalOps} opérateurs débloqués</p>
                    </div>
                </Link>
            </header>

            <section className="grid md:grid-cols-3 gap-6">
                {factions.map(f => {
                    const pct = f.operators_count > 0 ? Math.round((f.operators_owned / f.operators_count) * 100) : 0;
                    return (
                        <Link
                            key={f.slug}
                            href={`/factions/${f.slug}`}
                            className="group rounded-lg bg-bg-elev1 border-2 p-6 flex flex-col gap-4 transition-all duration-fast hover:scale-[1.02] hover:bg-bg-elev2"
                            style={{ borderColor: `oklch(0.55 0.15 ${f.color_hue} / 0.6)` }}
                        >
                            {f.banner_image_url && (
                                <div className="rounded-md overflow-hidden aspect-video">
                                    <img src={f.banner_image_url} alt={f.name} className="w-full h-full object-cover" />
                                </div>
                            )}
                            <header>
                                <h2 className="font-display font-bold text-2xl uppercase tracking-wide" style={{ color: `oklch(0.78 0.18 ${f.color_hue})` }}>
                                    {f.name}
                                </h2>
                                {f.tagline && <p className="text-text-medium text-sm mt-1">{f.tagline}</p>}
                            </header>
                            <div className="font-mono text-xs text-text-low mt-auto pt-3 border-t border-border-default/50 flex justify-between items-center">
                                <span>Collection {f.operators_owned} / {f.operators_count}</span>
                                <span className="font-display text-text-medium">{pct}%</span>
                            </div>
                        </Link>
                    );
                })}
            </section>
        </>
    );
}

PlayerFactionsIndex.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
