import { useForm } from '@inertiajs/react';
import Button from '@ui/Button';

export interface SeasonFormData {
    id?: number;
    slug?: string;
    name: string;
    type: string;
    faction: string | null;
    season_number: number;
    starts_at: string;
    ends_at: string;
    is_active: boolean;
}

interface Props {
    initial: SeasonFormData;
    enums: { types: string[]; factions: string[] };
    submitLabel: string;
    action: { method: 'post' | 'put'; url: string };
}

const inputCls = 'h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 w-full';

export default function SeasonForm({ initial, enums, submitLabel, action }: Props) {
    const { data, setData, errors, processing, post, put } = useForm({ ...initial });

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
                        <input value={data.name} onChange={e => setData('name', e.target.value)} className={inputCls} maxLength={64} placeholder="Semaine 12 — Quasar" />
                    </Field>
                    <Field label="Numéro de saison" error={errors.season_number} required>
                        <input type="number" min={1} value={data.season_number} onChange={e => setData('season_number', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                    <Field label="Type" error={errors.type} required>
                        <select value={data.type} onChange={e => setData('type', e.target.value)} className={inputCls}>
                            <option value="">—</option>
                            {enums.types.map(t => <option key={t} value={t}>{t}</option>)}
                        </select>
                    </Field>
                    <Field label="Faction (si type=faction)" error={errors.faction} hint="Requis seulement pour type=faction">
                        <select value={data.faction ?? ''} onChange={e => setData('faction', e.target.value || null)} className={inputCls}>
                            <option value="">— Aucune —</option>
                            {enums.factions.map(f => <option key={f} value={f}>{f}</option>)}
                        </select>
                    </Field>
                </div>
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
