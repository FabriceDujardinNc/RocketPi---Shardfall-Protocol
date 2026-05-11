import { useForm } from '@inertiajs/react';
import Button from '@ui/Button';

export interface SeasonFormData {
    id?: number;
    name: string;
    season_number: number;
    total_tiers: number;
    premium_price_shards: number;
    starts_at: string;
    ends_at: string;
    is_active: boolean;
}

interface Props {
    initial: SeasonFormData;
    submitLabel: string;
    action: { method: 'post' | 'put'; url: string };
}

const inputCls = 'h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 w-full';

export default function SeasonForm({ initial, submitLabel, action }: Props) {
    const { data, setData, errors, processing, post, put } = useForm({ ...initial });

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        (action.method === 'put' ? put : post)(action.url, { preserveScroll: true });
    };

    return (
        <form onSubmit={onSubmit} className="space-y-6 max-w-3xl">
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Identité</legend>
                <div className="grid md:grid-cols-2 gap-4 mt-2">
                    <Field label="Nom de la saison" error={errors.name} required>
                        <input value={data.name} onChange={(e) => setData('name', e.target.value)} className={inputCls} maxLength={64} placeholder="Saison 1 — Éveil des Shards" />
                    </Field>
                    <Field label="Numéro de saison" error={errors.season_number} required>
                        <input type="number" min={1} value={data.season_number} onChange={(e) => setData('season_number', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                    <Field label="Nombre total de paliers" error={errors.total_tiers} required hint="50 par défaut. Modifier ce nombre crée les paliers manquants automatiquement.">
                        <input type="number" min={1} max={200} value={data.total_tiers} onChange={(e) => setData('total_tiers', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                    <Field label="Prix premium (shards)" error={errors.premium_price_shards} required>
                        <input type="number" min={0} value={data.premium_price_shards} onChange={(e) => setData('premium_price_shards', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                </div>
            </fieldset>

            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Période</legend>
                <p className="text-text-low text-xs font-mono mt-1 mb-3">
                    Deux saisons ne peuvent pas se chevaucher — le serveur rejette les périodes en conflit.
                </p>
                <div className="grid md:grid-cols-3 gap-4">
                    <Field label="Démarre le" error={errors.starts_at} required>
                        <input type="datetime-local" value={data.starts_at?.slice(0, 16) ?? ''} onChange={(e) => setData('starts_at', e.target.value)} className={inputCls} />
                    </Field>
                    <Field label="Termine le" error={errors.ends_at} required>
                        <input type="datetime-local" value={data.ends_at?.slice(0, 16) ?? ''} onChange={(e) => setData('ends_at', e.target.value)} className={inputCls} />
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
