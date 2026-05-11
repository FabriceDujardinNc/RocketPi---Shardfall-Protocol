import { useForm } from '@inertiajs/react';
import Button from '@ui/Button';
import { X } from 'lucide-react';

export interface BannerFormData {
    id?: number;
    name: string;
    tag: string | null;
    subtitle: string | null;
    type: string;
    featured_operator: string | null;
    rate_up_operators: string[] | null;
    banner_image_url: string | null;
    rate_legendary: number | string;
    rate_epic: number | string;
    rate_rare: number | string;
    rate_common: number | string;
    pity_legendary: number;
    soft_pity_start: number;
    pity_epic: number;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
}

interface OperatorOption {
    codename: string;
    name: string;
    rarity: string;
    faction: string;
}

interface Props {
    initial: BannerFormData;
    enums: { types: string[] };
    operators: OperatorOption[];
    submitLabel: string;
    action: { method: 'post' | 'put'; url: string };
}

const inputCls = 'h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 w-full';

export default function BannerForm({ initial, enums, operators, submitLabel, action }: Props) {
    const { data, setData, errors, processing, post, put } = useForm({
        ...initial,
        rate_up_operators: initial.rate_up_operators ?? [],
    });

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const handler = action.method === 'put' ? put : post;
        handler(action.url, { preserveScroll: true });
    };

    const toggleRateUp = (codename: string) => {
        const current = data.rate_up_operators ?? [];
        const next = current.includes(codename) ? current.filter(c => c !== codename) : [...current, codename];
        setData('rate_up_operators', next);
    };

    const totalRate = (
        parseFloat(String(data.rate_legendary)) +
        parseFloat(String(data.rate_epic)) +
        parseFloat(String(data.rate_rare)) +
        parseFloat(String(data.rate_common))
    ).toFixed(4);

    return (
        <form onSubmit={onSubmit} className="space-y-6 max-w-4xl">
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Identité</legend>
                <div className="grid md:grid-cols-2 gap-4 mt-2">
                    <Field label="Nom" error={errors.name} required>
                        <input value={data.name} onChange={(e) => setData('name', e.target.value)} className={inputCls} maxLength={64} />
                    </Field>
                    <Field label="Type" error={errors.type} required>
                        <select value={data.type} onChange={(e) => setData('type', e.target.value)} className={inputCls}>
                            <option value="">—</option>
                            {enums.types.map(t => <option key={t} value={t}>{t}</option>)}
                        </select>
                    </Field>
                    <Field label="Tag (haut de bannière)" error={errors.tag}>
                        <input value={data.tag ?? ''} onChange={(e) => setData('tag', e.target.value || null)} className={inputCls} maxLength={64} placeholder="SIGNAL SHARD · ÉVÉNEMENT" />
                    </Field>
                    <Field label="Sous-titre" error={errors.subtitle}>
                        <input value={data.subtitle ?? ''} onChange={(e) => setData('subtitle', e.target.value || null)} className={inputCls} maxLength={128} placeholder="VEX RATE-UP ×3" />
                    </Field>
                    <Field label="URL image bannière" error={errors.banner_image_url}>
                        <input value={data.banner_image_url ?? ''} onChange={(e) => setData('banner_image_url', e.target.value || null)} className={inputCls} placeholder="https://..." />
                    </Field>
                    <Field label="Opérateur featured (codename)" error={errors.featured_operator}>
                        <select value={data.featured_operator ?? ''} onChange={(e) => setData('featured_operator', e.target.value || null)} className={inputCls}>
                            <option value="">—</option>
                            {operators.map(o => <option key={o.codename} value={o.codename}>{o.codename} — {o.name} ({o.rarity})</option>)}
                        </select>
                    </Field>
                </div>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Rate-up (max 5)</legend>
                <p className="text-text-low text-xs font-mono mb-3">Les opérateurs cochés bénéficient d'une chance accrue (50% au sein de leur rareté).</p>
                <div className="grid md:grid-cols-3 gap-2">
                    {operators.map(o => {
                        const checked = (data.rate_up_operators ?? []).includes(o.codename);
                        return (
                            <label key={o.codename} className={'flex items-center gap-2 px-3 py-2 rounded-md border cursor-pointer text-sm ' + (checked ? 'bg-shard-500/10 border-shard-500/60' : 'bg-bg-elev2 border-border-default hover:bg-bg-elev3')}>
                                <input type="checkbox" checked={checked} onChange={() => toggleRateUp(o.codename)} />
                                <span className="font-mono text-text-medium">{o.codename}</span>
                                <span className="text-text-low ml-auto text-xs">{o.rarity}</span>
                            </label>
                        );
                    })}
                </div>
                {errors['rate_up_operators'] && <p className="text-danger text-xs font-mono mt-2">{errors['rate_up_operators']}</p>}
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Taux de drop</legend>
                <p className={'text-xs font-mono mb-3 ' + (parseFloat(totalRate) === 1 ? 'text-success' : 'text-warning')}>
                    Somme des taux : {totalRate} (doit faire exactement 1.0000)
                </p>
                <div className="grid md:grid-cols-4 gap-4">
                    <Field label="Légendaire" error={errors.rate_legendary} required>
                        <input type="number" step="0.0001" min="0" max="1" value={data.rate_legendary} onChange={(e) => setData('rate_legendary', e.target.value)} className={inputCls} />
                    </Field>
                    <Field label="Épique" error={errors.rate_epic} required>
                        <input type="number" step="0.0001" min="0" max="1" value={data.rate_epic} onChange={(e) => setData('rate_epic', e.target.value)} className={inputCls} />
                    </Field>
                    <Field label="Rare" error={errors.rate_rare} required>
                        <input type="number" step="0.0001" min="0" max="1" value={data.rate_rare} onChange={(e) => setData('rate_rare', e.target.value)} className={inputCls} />
                    </Field>
                    <Field label="Commun" error={errors.rate_common} required>
                        <input type="number" step="0.0001" min="0" max="1" value={data.rate_common} onChange={(e) => setData('rate_common', e.target.value)} className={inputCls} />
                    </Field>
                </div>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Pity</legend>
                <div className="grid md:grid-cols-3 gap-4 mt-2">
                    <Field label="Hard pity Légendaire" error={errors.pity_legendary} required hint="Garanti après N tirages">
                        <input type="number" min={1} value={data.pity_legendary} onChange={(e) => setData('pity_legendary', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                    <Field label="Soft pity démarrage" error={errors.soft_pity_start} required hint="Taux boosté à partir de N">
                        <input type="number" min={1} value={data.soft_pity_start} onChange={(e) => setData('soft_pity_start', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                    <Field label="Hard pity Épique" error={errors.pity_epic} required hint="Garanti épique après N">
                        <input type="number" min={1} value={data.pity_epic} onChange={(e) => setData('pity_epic', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                </div>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Période & activation</legend>
                <div className="grid md:grid-cols-3 gap-4 mt-2">
                    <Field label="Date de début" error={errors.starts_at} hint="Vide = pas de contrainte">
                        <input type="datetime-local" value={data.starts_at?.slice(0, 16) ?? ''} onChange={(e) => setData('starts_at', e.target.value || null)} className={inputCls} />
                    </Field>
                    <Field label="Date de fin" error={errors.ends_at}>
                        <input type="datetime-local" value={data.ends_at?.slice(0, 16) ?? ''} onChange={(e) => setData('ends_at', e.target.value || null)} className={inputCls} />
                    </Field>
                    <Field label="Active" error={errors.is_active}>
                        <label className="flex items-center gap-2 h-9 mt-1">
                            <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                            <span className="text-sm">Bannière active (visible côté joueur)</span>
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
