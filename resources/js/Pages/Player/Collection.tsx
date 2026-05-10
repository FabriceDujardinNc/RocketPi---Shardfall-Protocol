import { Head, Link } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import OperatorCard from '@game/OperatorCard';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';
type Faction = 'orbit' | 'ferro' | 'veil';

interface OwnedOperator {
    id: number;
    duplicate_count: number;
    constellation: number;
    is_favorite: boolean;
    obtained_at: string;
    operator: {
        id: number;
        name: string;
        codename: string;
        faction: 'ORBIT' | 'FERRO' | 'VEIL';
        role: string;
        rarity: Rarity;
        portrait_url: string | null;
        lore: string | null;
    };
    affinity: { level: number; xp_current: number; next_xp: number };
}

interface Props {
    operators: OwnedOperator[];
    totalCount: number;
}

export default function Collection({ operators, totalCount }: Props) {
    return (
        <>
            <Head title="Collection" />
            <header className="mb-8 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide">Collection</h1>
                    <p className="font-mono text-sm text-text-medium mt-1">
                        {totalCount} Opérateur{totalCount > 1 ? 's' : ''} recruté{totalCount > 1 ? 's' : ''}
                    </p>
                </div>
                <Link
                    href="/gacha"
                    className="font-display text-xs uppercase tracking-wide text-shard-400 hover:text-shard-300"
                >
                    → Recruter via Signal Shard
                </Link>
            </header>

            {operators.length === 0 ? (
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center">
                    <p className="text-text-medium font-mono mb-4">
                        Aucun Opérateur — fais ton premier Recrutement par Signal Shard.
                    </p>
                    <Link
                        href="/gacha"
                        className="font-display text-shard-400 hover:text-shard-300 uppercase tracking-wide text-sm"
                    >
                        Aller au Recrutement →
                    </Link>
                </div>
            ) : (
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    {operators.map(po => (
                        <div key={po.id} className="flex flex-col gap-2">
                            <div className="relative">
                                <OperatorCard
                                    name={po.operator.name}
                                    role={po.operator.role}
                                    rarity={po.operator.rarity}
                                    faction={po.operator.faction.toLowerCase() as Faction}
                                    portraitUrl={po.operator.portrait_url ?? undefined}
                                    level={po.constellation}
                                />
                                {po.duplicate_count > 0 && (
                                    <span className="absolute top-2 left-2 z-overlay font-mono text-xs px-1.5 py-0.5 rounded bg-bg-base/90 text-shard-400 border border-shard-500/40">
                                        +{po.duplicate_count}
                                    </span>
                                )}
                            </div>
                            <div className="px-2">
                                <div className="flex justify-between font-display text-xs uppercase tracking-wide">
                                    <span className="text-text-medium">Affinité</span>
                                    <span className="text-shard-400 font-mono">Niv. {po.affinity.level}/10</span>
                                </div>
                                <div className="h-1.5 mt-1 rounded-full bg-bg-elev2 overflow-hidden">
                                    <div
                                        className="h-full bg-gradient-to-r from-shard-400 to-shard-600 transition-all duration-normal"
                                        style={{ width: `${Math.min(100, (po.affinity.xp_current / po.affinity.next_xp) * 100)}%` }}
                                    />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}

Collection.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
