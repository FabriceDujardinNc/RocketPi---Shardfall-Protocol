import { useForm } from '@inertiajs/react';
import Button from '@ui/Button';

export interface CosmeticFormData {
    id?: number;
    slug: string;
    name: string;
    description: string | null;
    type: string;
    rarity: string;
    operator_id: number | null;
    preview_url: string | null;
    asset_url: string | null;
    is_active: boolean;
    metadata: Record<string, unknown> | null;
}

interface Props {
    initial: CosmeticFormData;
    enums: { types: string[]; rarities: string[] };
    operators: Array<{ id: number; name: string; codename: string; faction: string }>;
    submitLabel: string;
    action: { method: 'post' | 'put'; url: string };
}

const inputCls = 'h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 w-full';

export default function CosmeticForm({ initial, enums, operators, submitLabel, action }: Props) {
    const { data, setData, errors, processing, post, put } = useForm({ ...initial });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        (action.method === 'put' ? put : post)(action.url, { preserveScroll: true });
    };

    const showOperatorPicker = data.type === 'skin' || ['voiceline'].includes(data.type);

    return (
        <form onSubmit={submit} className="space-y-6 max-w-3xl">
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Identité</legend>
                <div className="grid md:grid-cols-2 gap-4 mt-2">
                    <Field label="Slug" error={errors.slug} required hint="minuscules + chiffres + _ + -. Stable, ne devrait pas changer.">
                        <input value={data.slug} onChange={e => setData('slug', e.target.value)} className={inputCls} maxLength={96} pattern="[a-z0-9_-]+" placeholder="title_apex_2026" />
                    </Field>
                    <Field label="Nom affiché" error={errors.name} required>
                        <input value={data.name} onChange={e => setData('name', e.target.value)} className={inputCls} maxLength={128} />
                    </Field>
                    <Field label="Type" error={errors.type} required>
                        <select value={data.type} onChange={e => setData('type', e.target.value)} className={inputCls}>
                            <option value="">—</option>
                            {enums.types.map(t => <option key={t} value={t}>{t}</option>)}
                        </select>
                    </Field>
                    <Field label="Rareté" error={errors.rarity} required>
                        <select value={data.rarity} onChange={e => setData('rarity', e.target.value)} className={inputCls}>
                            {enums.rarities.map(r => <option key={r} value={r}>{r}</option>)}
                        </select>
                    </Field>
                    {showOperatorPicker && (
                        <Field label="Opérateur lié" error={errors.operator_id} required={data.type === 'skin'}>
                            <select value={data.operator_id ?? ''} onChange={e => setData('operator_id', e.target.value ? parseInt(e.target.value) : null)} className={inputCls}>
                                <option value="">— Aucun —</option>
                                {operators.map(o => <option key={o.id} value={o.id}>{o.codename} — {o.name} ({o.faction})</option>)}
                            </select>
                        </Field>
                    )}
                    <Field label="Active">
                        <label className="flex items-center gap-2 h-9 mt-1 text-sm">
                            <input type="checkbox" checked={data.is_active} onChange={e => setData('is_active', e.target.checked)} />
                            Affiché et déblocable
                        </label>
                    </Field>
                </div>
                <Field label="Description" error={errors.description}>
                    <textarea value={data.description ?? ''} onChange={e => setData('description', e.target.value || null)}
                        className={inputCls + ' h-20 mt-2'} maxLength={2000} placeholder="Quel effet visuel, contexte narratif..." />
                </Field>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Assets</legend>
                <div className="grid md:grid-cols-2 gap-4 mt-2">
                    <Field label="URL preview (image/audio)" error={errors.preview_url}>
                        <input value={data.preview_url ?? ''} onChange={e => setData('preview_url', e.target.value || null)} className={inputCls} placeholder="https://..." />
                    </Field>
                    <Field label="URL asset (ressource exposée joueur)" error={errors.asset_url}>
                        <input value={data.asset_url ?? ''} onChange={e => setData('asset_url', e.target.value || null)} className={inputCls} placeholder="https://..." />
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
            <span className="font-display text-xs uppercase tracking-wide text-text-low">{label}{required && <span className="text-danger ml-1">*</span>}</span>
            {children}
            {hint && !error && <span className="text-text-low text-xs font-mono">{hint}</span>}
            {error && <span className="text-danger text-xs font-mono">{error}</span>}
        </label>
    );
}
