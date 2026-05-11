import { useForm } from '@inertiajs/react';
import Button from '@ui/Button';
import { Plus, Trash2 } from 'lucide-react';

export interface RewardLine { type: string; amount: number }

export interface AchievementFormData {
    id?: number;
    key: string;
    title: string;
    description: string | null;
    icon_url: string | null;
    category: string;
    is_hidden: boolean;
    rewards: RewardLine[] | null;
}

interface Props {
    initial: AchievementFormData;
    enums: { categories: string[] };
    submitLabel: string;
    action: { method: 'post' | 'put'; url: string };
}

const inputCls = 'h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 w-full';
const REWARD_TYPES = ['shards', 'credits', 'tickets_standard', 'tickets_premium', 'tokens_rare_choice', 'tokens_epic_choice', 'tokens_legendary_choice'];

export default function AchievementForm({ initial, enums, submitLabel, action }: Props) {
    const { data, setData, errors, processing, post, put } = useForm({
        ...initial,
        rewards: initial.rewards ?? [],
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
                    <Field label="Clé technique" error={errors.key} required hint="minuscules + chiffres + underscores. ex: first_legendary">
                        <input value={data.key} onChange={e => setData('key', e.target.value)} className={inputCls} maxLength={64} pattern="[a-z0-9_]+" />
                    </Field>
                    <Field label="Catégorie" error={errors.category} required>
                        <select value={data.category} onChange={e => setData('category', e.target.value)} className={inputCls}>
                            <option value="">—</option>
                            {enums.categories.map(c => <option key={c} value={c}>{c}</option>)}
                        </select>
                    </Field>
                    <Field label="Titre affiché" error={errors.title} required>
                        <input value={data.title} onChange={e => setData('title', e.target.value)} className={inputCls} maxLength={128} />
                    </Field>
                    <Field label="URL icône" error={errors.icon_url}>
                        <input value={data.icon_url ?? ''} onChange={e => setData('icon_url', e.target.value || null)} className={inputCls} placeholder="https://..." />
                    </Field>
                </div>
                <Field label="Description" error={errors.description}>
                    <textarea value={data.description ?? ''} onChange={e => setData('description', e.target.value || null)}
                        className={inputCls + ' h-20 mt-2'} maxLength={2000} placeholder="Ce que doit accomplir le joueur pour le débloquer..." />
                </Field>
                <label className="flex items-center gap-2 mt-3 text-sm">
                    <input type="checkbox" checked={data.is_hidden} onChange={e => setData('is_hidden', e.target.checked)} />
                    <span>Caché jusqu'au déblocage (façon "achievement secret")</span>
                </label>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Récompenses</legend>
                <p className="text-text-low text-xs font-mono mt-1 mb-3">Au claim, le joueur reçoit ces currencies via RewardService (max 5 lignes).</p>
                <div className="space-y-2">
                    {(data.rewards ?? []).map((r, i) => (
                        <div key={i} className="grid grid-cols-[2fr_1fr_auto] gap-2">
                            <input value={r.type}
                                onChange={e => setData('rewards', (data.rewards ?? []).map((x, j) => j === i ? { ...x, type: e.target.value } : x))}
                                className={inputCls} list="ach-reward-types" />
                            <input type="number" min={1} value={r.amount}
                                onChange={e => setData('rewards', (data.rewards ?? []).map((x, j) => j === i ? { ...x, amount: parseInt(e.target.value || '0') } : x))}
                                className={inputCls} />
                            <Button type="button" size="sm" variant="danger" icon={<Trash2 size={12} />}
                                onClick={() => setData('rewards', (data.rewards ?? []).filter((_, j) => j !== i))}>—</Button>
                        </div>
                    ))}
                    <Button type="button" size="sm" variant="secondary" icon={<Plus size={12} />}
                        onClick={() => setData('rewards', [...(data.rewards ?? []), { type: 'shards', amount: 100 }])}>Ajouter</Button>
                    <datalist id="ach-reward-types">{REWARD_TYPES.map(t => <option key={t} value={t} />)}</datalist>
                    {errors['rewards'] && <p className="text-danger text-xs font-mono">{errors['rewards']}</p>}
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
