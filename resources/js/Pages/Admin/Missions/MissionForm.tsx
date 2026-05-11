import { useForm } from '@inertiajs/react';
import Button from '@ui/Button';
import { Plus, Trash2 } from 'lucide-react';

export interface RewardLine { type: string; amount: number }

export interface MissionFormData {
    id?: number;
    title: string;
    description: string | null;
    type: string;
    objective_type: string;
    objective_target: number;
    rewards: RewardLine[];
    xp_reward: number;
    is_active: boolean;
    available_from: string | null;
    available_until: string | null;
}

interface Props {
    initial: MissionFormData;
    enums: { types: string[]; objective_types: string[]; reward_types: string[] };
    submitLabel: string;
    action: { method: 'post' | 'put'; url: string };
}

const inputCls = 'h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 w-full';

export default function MissionForm({ initial, enums, submitLabel, action }: Props) {
    const { data, setData, errors, processing, post, put } = useForm({
        ...initial,
        rewards: initial.rewards?.length ? initial.rewards : [{ type: 'shards', amount: 50 }],
    });

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        (action.method === 'put' ? put : post)(action.url, { preserveScroll: true });
    };

    const setReward = (idx: number, patch: Partial<RewardLine>) => {
        const next = [...data.rewards];
        next[idx] = { ...next[idx], ...patch };
        setData('rewards', next);
    };

    return (
        <form onSubmit={onSubmit} className="space-y-6 max-w-3xl">
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Identité</legend>
                <div className="grid md:grid-cols-2 gap-4 mt-2">
                    <Field label="Titre" error={errors.title} required>
                        <input value={data.title} onChange={(e) => setData('title', e.target.value)} className={inputCls} maxLength={128} />
                    </Field>
                    <Field label="Type" error={errors.type} required>
                        <select value={data.type} onChange={(e) => setData('type', e.target.value)} className={inputCls}>
                            <option value="">—</option>
                            {enums.types.map(t => <option key={t} value={t}>{t}</option>)}
                        </select>
                    </Field>
                    <Field label="" error={errors.description}>
                        <textarea value={data.description ?? ''} onChange={(e) => setData('description', e.target.value || null)}
                            className={inputCls + ' h-24'} maxLength={2000} placeholder="Description de la mission..." />
                    </Field>
                </div>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Objectif</legend>
                <div className="grid md:grid-cols-2 gap-4 mt-2">
                    <Field label="Type d'objectif" error={errors.objective_type} required>
                        <select value={data.objective_type} onChange={(e) => setData('objective_type', e.target.value)} className={inputCls}>
                            <option value="">—</option>
                            {enums.objective_types.map(t => <option key={t} value={t}>{t}</option>)}
                        </select>
                    </Field>
                    <Field label="Valeur cible" error={errors.objective_target} required>
                        <input type="number" min={1} value={data.objective_target} onChange={(e) => setData('objective_target', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                </div>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Récompenses</legend>
                <div className="space-y-3 mt-2">
                    {data.rewards.map((r, idx) => (
                        <div key={idx} className="grid md:grid-cols-[2fr_1fr_auto] gap-2">
                            <select value={r.type} onChange={(e) => setReward(idx, { type: e.target.value })} className={inputCls}>
                                {enums.reward_types.map(t => <option key={t} value={t}>{t}</option>)}
                            </select>
                            <input type="number" min={1} value={r.amount} onChange={(e) => setReward(idx, { amount: parseInt(e.target.value || '0') })} className={inputCls} />
                            <Button type="button" size="sm" variant="danger" icon={<Trash2 size={12} />} onClick={() => setData('rewards', data.rewards.filter((_, i) => i !== idx))}>—</Button>
                        </div>
                    ))}
                    {errors['rewards'] && <p className="text-danger text-xs font-mono">{errors['rewards']}</p>}
                    <Button type="button" size="sm" variant="secondary" icon={<Plus size={12} />}
                        onClick={() => setData('rewards', [...data.rewards, { type: 'shards', amount: 10 }])}>Ajouter</Button>
                </div>
                <div className="mt-4">
                    <Field label="XP de compte récompensé" error={errors.xp_reward} hint="0–65535">
                        <input type="number" min={0} value={data.xp_reward} onChange={(e) => setData('xp_reward', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                </div>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Disponibilité</legend>
                <div className="grid md:grid-cols-3 gap-4 mt-2">
                    <Field label="Disponible à partir de" error={errors.available_from}>
                        <input type="datetime-local" value={data.available_from?.slice(0, 16) ?? ''} onChange={(e) => setData('available_from', e.target.value || null)} className={inputCls} />
                    </Field>
                    <Field label="Disponible jusqu'à" error={errors.available_until}>
                        <input type="datetime-local" value={data.available_until?.slice(0, 16) ?? ''} onChange={(e) => setData('available_until', e.target.value || null)} className={inputCls} />
                    </Field>
                    <Field label="Active">
                        <label className="flex items-center gap-2 h-9 mt-1">
                            <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                            <span className="text-sm">Visible côté joueur</span>
                        </label>
                    </Field>
                </div>
            </fieldset>

            <div className="flex gap-3 pt-2">
                <Button type="submit" variant="shard" loading={processing}>{submitLabel}</Button>
                <Button type="button" variant="ghost" onClick={() => window.history.back()}>Annuler</Button>
            </div>
        </form>
    );
}

function Field({ label, error, hint, required, children }: { label: string; error?: string; hint?: string; required?: boolean; children: React.ReactNode }) {
    return (
        <label className="flex flex-col gap-1">
            {label && <span className="font-display text-xs uppercase tracking-wide text-text-low">{label}{required && <span className="text-danger ml-1">*</span>}</span>}
            {children}
            {hint && !error && <span className="text-text-low text-xs font-mono">{hint}</span>}
            {error && <span className="text-danger text-xs font-mono">{error}</span>}
        </label>
    );
}
