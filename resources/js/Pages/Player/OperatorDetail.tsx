import { Head, Link } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import RarityBadge from '@game/RarityBadge';
import FactionBadge from '@game/FactionBadge';
import AffinityMeter from '@game/AffinityMeter';
import Progress from '@ui/Progress';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';

interface Ability { name: string; description: string; type: 'active' | 'passive' | 'ultimate' }

interface Op {
    id: number;
    name: string;
    codename: string;
    faction: 'ORBIT' | 'FERRO' | 'VEIL';
    role: string;
    rarity: Rarity;
    lore: string | null;
    portrait_url: string | null;
    stat_hp: number;
    stat_damage: number;
    stat_mobility: number;
    weapon_name: string | null;
    weapon_description: string | null;
    abilities: Ability[] | null;
}

interface LoreUnlock { level: number; title: string; unlocked: boolean; snippet: string | null }

interface Props {
    operator: Op;
    owned: boolean;
    duplicateCount: number;
    constellation: number;
    affinity: { level: number; xp_current: number; next_xp: number; is_max: boolean };
    loreUnlocks: LoreUnlock[];
    fragmentsBalance: number;
}

export default function OperatorDetail({ operator, owned, duplicateCount, constellation, affinity, loreUnlocks, fragmentsBalance }: Props) {
    return (
        <>
            <Head title={operator.name} />

            <Link href="/collection" className="font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high">
                ← Collection
            </Link>

            <header className="mt-2 mb-6 grid md:grid-cols-[200px_1fr] gap-6 items-start">
                <div className="aspect-[3/4] rounded-lg bg-bg-elev2 border-2 border-border-default overflow-hidden flex items-center justify-center">
                    {operator.portrait_url
                        ? <img src={operator.portrait_url} alt={operator.name} className="w-full h-full object-cover" />
                        : <span className="font-display text-4xl uppercase tracking-mega text-text-low">{operator.codename}</span>}
                </div>
                <div>
                    <p className="font-mono text-xs text-text-low">{operator.codename}</p>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">{operator.name}</h1>
                    <div className="flex gap-2 mt-2">
                        <FactionBadge faction={operator.faction.toLowerCase() as 'orbit' | 'ferro' | 'veil'} />
                        <RarityBadge rarity={operator.rarity} />
                        <span className="font-display text-xs uppercase tracking-wide px-2 py-0.5 rounded bg-bg-elev2 text-text-medium border border-border-default">
                            {operator.role}
                        </span>
                    </div>
                    {!owned && (
                        <p className="font-mono text-xs text-warning mt-3">⚠ Pas encore recruté — disponible via Recrutement.</p>
                    )}
                    {owned && (
                        <div className="mt-4 grid grid-cols-3 gap-3">
                            <div>
                                <p className="font-display text-xs uppercase tracking-wide text-text-low">Doublons</p>
                                <p className="font-mono text-lg text-shard-400 mt-1">+{duplicateCount}</p>
                            </div>
                            <div>
                                <p className="font-display text-xs uppercase tracking-wide text-text-low">Constellation</p>
                                <p className="font-mono text-lg text-text-high mt-1">{constellation}/6</p>
                            </div>
                            <div>
                                <p className="font-display text-xs uppercase tracking-wide text-text-low">Fragments</p>
                                <p className="font-mono text-lg text-rarity-epic mt-1">{fragmentsBalance}</p>
                            </div>
                        </div>
                    )}
                </div>
            </header>

            <div className="grid md:grid-cols-2 gap-6 mb-6">
                {/* Stats */}
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-4">Caractéristiques</h2>
                    <div className="flex flex-col gap-3">
                        <Progress value={operator.stat_hp}       max={200} label={`HP ${operator.stat_hp}`}       variant="success" />
                        <Progress value={operator.stat_damage}   max={200} label={`Dégâts ${operator.stat_damage}`} variant="danger" />
                        <Progress value={operator.stat_mobility} max={200} label={`Mobilité ${operator.stat_mobility}`} variant="shard" />
                    </div>
                    {operator.weapon_name && (
                        <div className="mt-4 pt-4 border-t border-border-default">
                            <p className="font-display text-xs uppercase tracking-wide text-text-low">Arme signature</p>
                            <p className="font-display text-sm text-shard-400 mt-1">{operator.weapon_name}</p>
                            {operator.weapon_description && (
                                <p className="font-body text-xs text-text-medium mt-1">{operator.weapon_description}</p>
                            )}
                        </div>
                    )}
                </section>

                {/* Affinité */}
                <section className="rounded-lg bg-bg-elev1 border border-shard-500/30 p-6">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-4">Affinité</h2>
                    {owned ? (
                        <>
                            <AffinityMeter
                                level={affinity.level}
                                xp={affinity.xp_current}
                                nextLevelXp={affinity.next_xp}
                            />
                            <p className="font-mono text-xs text-text-low mt-4">
                                {affinity.is_max
                                    ? 'Affinité maximale atteinte — toutes les confidences débloquées.'
                                    : 'Tire cet opérateur ou utilise-le pour gagner de l\'affinité.'}
                            </p>
                        </>
                    ) : (
                        <p className="text-text-medium font-mono text-sm">Recrute d'abord cet opérateur.</p>
                    )}
                </section>
            </div>

            {/* Capacités */}
            {operator.abilities && operator.abilities.length > 0 && (
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-6 mb-6">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-4">Capacités</h2>
                    <ul className="grid md:grid-cols-3 gap-4">
                        {operator.abilities.map((ab, i) => (
                            <li key={i} className="rounded-md bg-bg-elev2 border border-border-default p-3">
                                <header className="flex items-center justify-between mb-1">
                                    <span className="font-display text-xs uppercase tracking-wide text-text-high">{ab.name}</span>
                                    <span className={
                                        'font-display text-[10px] uppercase tracking-mega px-1.5 py-0.5 rounded ' +
                                        (ab.type === 'ultimate' ? 'bg-rarity-legendary/15 text-rarity-legendary' :
                                         ab.type === 'active'   ? 'bg-shard-500/15 text-shard-400' :
                                                                  'bg-bg-elev3 text-text-low')
                                    }>
                                        {ab.type}
                                    </span>
                                </header>
                                <p className="font-body text-xs text-text-medium">{ab.description}</p>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {/* Lore débloqué progressivement */}
            <section className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-4">Lore — confidences</h2>
                <ul className="flex flex-col gap-3">
                    {loreUnlocks.map((unlock, i) => (
                        <li key={i} className={
                            'rounded-md p-3 border ' +
                            (unlock.unlocked
                                ? 'bg-bg-elev2 border-shard-500/30'
                                : 'bg-bg-elev2/50 border-border-default opacity-60')
                        }>
                            <header className="flex items-center justify-between mb-1">
                                <span className="font-display text-xs uppercase tracking-wide text-text-high">
                                    {unlock.title}
                                </span>
                                <span className={
                                    'font-display text-[10px] uppercase tracking-mega ' +
                                    (unlock.unlocked ? 'text-shard-400' : 'text-text-low')
                                }>
                                    {unlock.unlocked ? `Niv. ${unlock.level} ✓` : `Niv. ${unlock.level} 🔒`}
                                </span>
                            </header>
                            {unlock.unlocked && unlock.snippet ? (
                                <p className="font-body text-sm text-text-medium leading-relaxed">{unlock.snippet}</p>
                            ) : (
                                <p className="font-mono text-xs text-text-low italic">
                                    Atteindre l'affinité {unlock.level} pour débloquer.
                                </p>
                            )}
                        </li>
                    ))}
                </ul>
            </section>
        </>
    );
}

OperatorDetail.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
