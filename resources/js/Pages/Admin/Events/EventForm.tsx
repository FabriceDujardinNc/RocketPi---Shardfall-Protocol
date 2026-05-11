import { useForm } from '@inertiajs/react';
import Button from '@ui/Button';
import { Plus, Trash2 } from 'lucide-react';

export interface RewardLine { type: string; amount: number }

export interface EventFormData {
    id?: number;
    name: string;
    lore: string | null;
    banner_image_url: string | null;
    type: string;
    banner_id: number | null;
    rewards_pool: RewardLine[] | null;
    starts_at: string;
    ends_at: string;
    is_active: boolean;
}

interface Props {
    initial: EventFormData;
    enums: { types: string[] };
    banners: Array<{ id: number; name: string; type: string }>;
    submitLabel: string;
    action: { method: 'post' | 'put'; url: string };
}

const inputCls = 'h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 w-full';
const REWARD_TYPES = ['shards', 'credits', 'tickets_standard', 'tickets_premium', 'tokens_rare_choice', 'tokens_epic_choice', 'tokens_legendary_choice', 'cosmetic_title_event'];

export default function EventForm({ initial, enums, banners, submitLabel, action }: Props) {
    const { data, setData, errors, processing, post, put } = useForm({
        ...initial,
        rewards_pool: initial.rewards_pool ?? [],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        (action.method === 'put' ? put : post)(action.url, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-6 max-w-3xl">
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Identité</legend>
                <div className="grid md:grid-cols-2 gap-4 mt-2">
                    <Field label="Nom" error={errors.name} required>
                        <input value={data.name} onChange={e => setData('name', e.target.value)} className={inputCls} maxLength={128} />
                    </Field>
                    <Field label="Type" error={errors.type} required>
                        <select value={data.type} onChange={e => setData('type', e.target.value)} className={inputCls}>
                            <option value="">—</option>
                            {enums.types.map(t => <option key={t} value={t}>{t}</option>)}
                        </select>
                    </Field>
                    <Field label="Bannière liée (optionnel)" error={errors.banner_id} hint="Pour les events `limited_banner` notamment.">
                        <select value={data.banner_id ?? ''} onChange={e => setData('banner_id', e.target.value ? parseInt(e.target.value) : null)} className={inputCls}>
                            <option value="">— Aucune —</option>
                            {banners.map(b => <option key={b.id} value={b.id}>{b.name} ({b.type})</option>)}
                        </select>
                    </Field>
                    <Field label="URL image" error={errors.banner_image_url}>
                        <input value={data.banner_image_url ?? ''} onChange={e => setData('banner_image_url', e.target.value || null)} className={inputCls} placeholder="https://..." />
                    </Field>
                </div>
                <Field label="Lore" error={errors.lore}>
                    <textarea value={data.lore ?? ''} onChange={e => setData('lore', e.target.value || null)} maxLength={5000}
                        className={inputCls + ' h-32 mt-2'} placeholder="Description narrative de l'événement..." />
                </Field>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Période</legend>
                <div className="grid md:grid-cols-3 gap-4 mt-2">
                    <Field label="Démarre le" error={errors.starts_at} required>
                        <input type="datetime-local" value={data.starts_at?.slice(0, 16) ?? ''} onChange={e => setData('starts_at', e.target.value)} className={inputCls} />
                    </Field>
                    <Field label="Termine le" error={errors.ends_at} required>
                        <input type="datetime-local" value={data.ends_at?.slice(0, 16) ?? ''} onChange={e => setData('ends_at', e.target.value)} className={inputCls} />
                    </Field>
                    <Field label="Active">
                        <label className="flex items-center gap-2 h-9 mt-1 text-sm">
                            <input type="checkbox" checked={data.is_active} onChange={e => setData('is_active', e.target.checked)} />
                            Visible côté joueur
                        </label>
                    </Field>
                </div>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Récompenses pool</legend>
                <p className="text-text-low text-xs font-mono mt-1 mb-3">Pool de récompenses additionnelles spécifiques à l'événement (max 10).</p>
                <div className="space-y-2">
                    {(data.rewards_pool ?? []).map((r, i) => (
                        <div key={i} className="grid grid-cols-[2fr_1fr_auto] gap-2">
                            <input value={r.type} list="event-reward-types"
                                onChange={e => setData('rewards_pool', (data.rewards_pool ?? []).map((x, j) => j === i ? { ...x, type: e.target.value } : x))}
                                className={inputCls} />
                            <input type="number" min={1} value={r.amount}
                                onChange={e => setData('rewards_pool', (data.rewards_pool ?? []).map((x, j) => j === i ? { ...x, amount: parseInt(e.target.value || '0') } : x))}
                                className={inputCls} />
                            <Button type="button" size="sm" variant="danger" icon={<Trash2 size={12} />}
                                onClick={() => setData('rewards_pool', (data.rewards_pool ?? []).filter((_, j) => j !== i))}>—</Button>
                        </div>
                    ))}
                    <Button type="button" size="sm" variant="secondary" icon={<Plus size={12} />}
                        onClick={() => setData('rewards_pool', [...(data.rewards_pool ?? []), { type: 'shards', amount: 100 }])}>Ajouter</Button>
                    <datalist id="event-reward-types">{REWARD_TYPES.map(t => <option key={t} value={t} />)}</datalist>
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
