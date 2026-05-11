import { useForm } from '@inertiajs/react';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import { Plus, Trash2, Save, Star } from 'lucide-react';
import { useState } from 'react';

export interface RewardLine { type: string; amount: number }

export interface Tier {
    id?: number;
    tier_number: number;
    xp_required: number;
    free_reward: RewardLine[] | null;
    premium_reward: RewardLine[] | null;
    is_milestone: boolean;
}

interface Props {
    battlePassId: number;
    tiers: Tier[];
}

const inputCls = 'h-8 px-2 rounded bg-bg-elev2 border border-border-default text-text-high text-xs focus:outline-none focus:ring-1 focus:ring-shard-500';

const REWARD_TYPE_SUGGESTIONS = [
    'shards', 'credits',
    'tickets_standard', 'tickets_premium',
    'tokens_rare_choice', 'tokens_epic_choice', 'tokens_legendary_choice',
    'cosmetic_title_apex',
];

export default function TiersEditor({ battlePassId, tiers }: Props) {
    const { data, setData, errors, processing, put } = useForm<{ tiers: Tier[] }>({
        tiers: tiers.map(t => ({
            ...t,
            free_reward:    t.free_reward    ?? [],
            premium_reward: t.premium_reward ?? [],
        })),
    });

    const [filter, setFilter] = useState<'all' | 'milestones'>('all');
    const visibleTiers = filter === 'milestones'
        ? data.tiers.filter(t => t.is_milestone)
        : data.tiers;

    const patchTier = (idx: number, patch: Partial<Tier>) => {
        const next = [...data.tiers];
        next[idx] = { ...next[idx], ...patch };
        setData('tiers', next);
    };

    const realIndex = (tier: Tier) => data.tiers.findIndex(t => t.tier_number === tier.tier_number);

    const patchReward = (tier: Tier, kind: 'free_reward' | 'premium_reward', rewardIdx: number, patch: Partial<RewardLine>) => {
        const idx = realIndex(tier);
        const rewards = [...(data.tiers[idx][kind] ?? [])];
        rewards[rewardIdx] = { ...rewards[rewardIdx], ...patch };
        patchTier(idx, { [kind]: rewards } as Partial<Tier>);
    };
    const addReward = (tier: Tier, kind: 'free_reward' | 'premium_reward') => {
        const idx = realIndex(tier);
        const rewards = [...(data.tiers[idx][kind] ?? []), { type: 'shards', amount: 50 }];
        patchTier(idx, { [kind]: rewards } as Partial<Tier>);
    };
    const removeReward = (tier: Tier, kind: 'free_reward' | 'premium_reward', rewardIdx: number) => {
        const idx = realIndex(tier);
        const rewards = (data.tiers[idx][kind] ?? []).filter((_, i) => i !== rewardIdx);
        patchTier(idx, { [kind]: rewards } as Partial<Tier>);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/battle-passes/${battlePassId}/tiers`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="flex items-end justify-between flex-wrap gap-3">
                <div>
                    <h2 className="font-display font-bold text-lg uppercase tracking-wide">Paliers</h2>
                    <p className="font-mono text-xs text-text-low mt-1">{data.tiers.length} palier(s) configuré(s)</p>
                </div>
                <div className="flex gap-2">
                    <Button type="button" size="sm" variant={filter === 'all' ? 'shard' : 'ghost'} onClick={() => setFilter('all')}>Tous</Button>
                    <Button type="button" size="sm" variant={filter === 'milestones' ? 'shard' : 'ghost'} onClick={() => setFilter('milestones')} icon={<Star size={12} />}>Milestones</Button>
                    <Button type="submit" variant="shard" loading={processing} icon={<Save size={14} />}>Enregistrer tous les paliers</Button>
                </div>
            </div>

            {Object.keys(errors).length > 0 && (
                <Alert variant="danger">
                    {Object.entries(errors).slice(0, 3).map(([k, v]) => <p key={k} className="font-mono text-xs">{k} : {String(v)}</p>)}
                </Alert>
            )}

            <div className="space-y-2 overflow-y-auto pr-2" style={{ maxHeight: '70vh' }}>
                {visibleTiers.map(tier => {
                    const idx = realIndex(tier);
                    return (
                        <details key={tier.tier_number} className="rounded-lg bg-bg-elev1 border border-border-default group">
                            <summary className="cursor-pointer p-3 flex items-center gap-4 list-none">
                                <span className="font-display text-shard-400 text-sm w-16">Palier {tier.tier_number}</span>
                                <input
                                    type="number"
                                    value={tier.xp_required}
                                    min={0}
                                    onChange={(e) => patchTier(idx, { xp_required: parseInt(e.target.value || '0') })}
                                    className={inputCls + ' w-28'}
                                    onClick={(e) => e.stopPropagation()}
                                />
                                <span className="font-mono text-xs text-text-low">XP requis</span>
                                <label className="flex items-center gap-2 ml-auto font-mono text-xs text-text-medium" onClick={(e) => e.stopPropagation()}>
                                    <input type="checkbox" checked={tier.is_milestone} onChange={(e) => patchTier(idx, { is_milestone: e.target.checked })} />
                                    Milestone
                                </label>
                                <span className="font-mono text-xs text-text-low">
                                    {(tier.free_reward?.length ?? 0)}f · {(tier.premium_reward?.length ?? 0)}p
                                </span>
                            </summary>
                            <div className="p-4 border-t border-border-default grid md:grid-cols-2 gap-4">
                                <RewardBlock
                                    label="Récompense gratuite"
                                    rewards={tier.free_reward ?? []}
                                    onChange={(rewardIdx, patch) => patchReward(tier, 'free_reward', rewardIdx, patch)}
                                    onAdd={() => addReward(tier, 'free_reward')}
                                    onRemove={(rewardIdx) => removeReward(tier, 'free_reward', rewardIdx)}
                                />
                                <RewardBlock
                                    label="Récompense premium"
                                    rewards={tier.premium_reward ?? []}
                                    onChange={(rewardIdx, patch) => patchReward(tier, 'premium_reward', rewardIdx, patch)}
                                    onAdd={() => addReward(tier, 'premium_reward')}
                                    onRemove={(rewardIdx) => removeReward(tier, 'premium_reward', rewardIdx)}
                                />
                            </div>
                        </details>
                    );
                })}
            </div>
        </form>
    );
}

function RewardBlock({ label, rewards, onChange, onAdd, onRemove }: {
    label: string;
    rewards: RewardLine[];
    onChange: (idx: number, patch: Partial<RewardLine>) => void;
    onAdd: () => void;
    onRemove: (idx: number) => void;
}) {
    return (
        <div>
            <p className="font-display text-xs uppercase tracking-wide text-text-low mb-2">{label}</p>
            <div className="space-y-2">
                {rewards.length === 0 && <p className="font-mono text-xs text-text-low italic">Aucune récompense.</p>}
                {rewards.map((r, i) => (
                    <div key={i} className="flex gap-2 items-center">
                        <input
                            value={r.type}
                            onChange={(e) => onChange(i, { type: e.target.value })}
                            className={inputCls + ' flex-1'}
                            list="reward-types"
                        />
                        <input
                            type="number"
                            min={1}
                            value={r.amount}
                            onChange={(e) => onChange(i, { amount: parseInt(e.target.value || '0') })}
                            className={inputCls + ' w-20'}
                        />
                        <Button type="button" size="sm" variant="danger" icon={<Trash2 size={10} />} onClick={() => onRemove(i)}>—</Button>
                    </div>
                ))}
                <Button type="button" size="sm" variant="ghost" icon={<Plus size={10} />} onClick={onAdd}>Ajouter</Button>
            </div>
            <datalist id="reward-types">
                {REWARD_TYPE_SUGGESTIONS.map(t => <option key={t} value={t} />)}
            </datalist>
        </div>
    );
}
